<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/loyalty.php';

header('Content-Type: application/json; charset=utf-8');

function bp_text(&$arr, $content, $bold=0, $align=0, $format=0) {
    $obj = new stdClass();
    $obj->type = 0;
    $obj->content = $content;
    $obj->bold = (int)$bold;
    $obj->align = (int)$align;
    $obj->format = (int)$format;
    $arr[] = $obj;
}

function bp_line(&$arr) { 
    bp_text($arr, str_repeat('-', 32), 0, 0, 0); 
}
function bp_html(&$arr, $html) {
    if (empty($html)) return;
    $obj = new stdClass();
    $obj->type = 4;
    $obj->content = $html;
    $arr[] = $obj;
}

function bp_cash_drawer_pulse(&$arr) {
    $obj = new stdClass();
    $obj->type = 0;
    $obj->content = "\x1B\x70\x00\x19\x64"; // pin 0, 25ms, 100ms
    $obj->bold = 0;
    $obj->align = 0;
    $obj->format = 0;
    $arr[] = $obj;
}

try {
    $orderId = (int)($_GET['id'] ?? 0);
    if ($orderId <= 0) { 
        echo json_encode([], JSON_FORCE_OBJECT); 
        exit; 
    }

    $pdo = Database::connection();
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $err = []; bp_text($err, 'Order not found', 1, 1, 0);
        echo json_encode($err, JSON_FORCE_OBJECT); exit;
    }

    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payment details
    $order['payment_method'] = 'cash'; 
    $payStmt = $pdo->prepare("SELECT payment_method, amount as amount_paid FROM payments WHERE order_id = ? LIMIT 1");
    $payStmt->execute([$orderId]);
    $pay = $payStmt->fetch(PDO::FETCH_ASSOC);
    if ($pay) {
        $order['payment_method'] = $pay['payment_method'];
        $order['paid_amount'] = $pay['amount_paid'];
        $order['change_amount'] = max(0, $pay['amount_paid'] - $order['grand_total']);
    }

    $a = [];
    if (strtolower(trim((string)($order['payment_method'] ?? ''))) === 'cash') {
        bp_cash_drawer_pulse($a);
    }

    $logoHtml = '<div style="text-align:center;"><img src="'.asset('images/pos-products/icon-192.png').'" style="width:160px; height:160px; object-fit:contain;" /></div>';
    bp_html($a, $logoHtml);

    bp_text($a, 'LUMERO CHICKEN CRISPY', 1, 1, 3);
    bp_text($a, strtoupper(current_outlet_name() ?: 'PASEKON'), 0, 1, 0);
    bp_text($a, $order['order_number'] ?? '', 1, 1, 2);
    bp_line($a);
    
    bp_text($a, 'Tgl    : ' . date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')), 0, 0, 0);
    bp_text($a, 'Kasir  : ' . ($order['cashier_name'] ?? 'Kasir'), 0, 0, 0);
    bp_text($a, 'Bayar  : ' . strtoupper((string)($order['payment_method'] ?? '-')), 0, 0, 0);
    
    if (!empty($order['customer_phone'])) {
        $phone = $order['customer_phone'];
        $masked = function_exists('loyalty_mask_phone') ? loyalty_mask_phone($phone) : substr($phone, 0, 4) . '****' . substr($phone, -2);
        bp_text($a, 'Member : ' . $masked, 0, 0, 0);
    }
    bp_line($a);

    foreach ($items as $it) {
        $name = strtoupper((string)($it['variant_name_snapshot'] ?: $it['product_name_snapshot']));
        bp_text($a, $name, 1, 0, 0);
        
        $qtyPrice = ((int)($it['quantity'] ?? 1)) . 'x ' . rupiahPlain($it['price'] ?? 0);
        $sub = rupiahPlain($it['subtotal'] ?? 0);
        $sp = max(1, 32 - strlen($qtyPrice) - strlen($sub));
        bp_text($a, $qtyPrice . str_repeat(' ', $sp) . $sub, 0, 0, 0);
    }
    
    bp_line($a);
    bp_text($a, 'Subtotal : ' . rupiahPlain($order['subtotal'] ?? 0), 0, 0, 0);
    if ((float)($order['discount_amount'] ?? 0) > 0) {
        bp_text($a, 'Diskon   : -' . rupiahPlain($order['discount_amount']), 0, 0, 0);
    }
    
    bp_text($a, 'TOTAL    : ' . rupiahPlain($order['grand_total'] ?? 0), 1, 0, 2);
    
    if (strtolower(trim((string)($order['payment_method'] ?? ''))) === 'cash') {
        bp_line($a);
        bp_text($a, 'Dibayar  : ' . rupiahPlain($order['paid_amount'] ?? 0), 0, 0, 0);
        bp_text($a, 'Kembali  : ' . rupiahPlain($order['change_amount'] ?? 0), 0, 0, 0);
    }
    
    $claimCode = trim((string)($order['loyalty_claim_code'] ?? ''));
    $claimPoints = (int)($order['loyalty_claim_points'] ?? max(1, floor(($order['grand_total'] ?? 0) / 1000)));
    if (empty($order['member_id']) && $claimCode !== '') {
        bp_line($a);
        bp_text($a, 'KODE KLAIM POIN', 1, 1, 0);
        bp_text($a, $claimCode, 1, 1, 3);
        bp_text($a, 'Bonus: +' . $claimPoints . ' Poin', 1, 1, 0);
        
        // Menambahkan QR Code HTML untuk Thermer
        if (function_exists('loyalty_member_qr_url')) {
            $qrHtml = '<div style="text-align:center;"><img src="'.htmlspecialchars(loyalty_member_qr_url($claimCode, 150)).'" style="width:150px; height:150px; object-fit:contain;" /></div>';
            bp_html($a, $qrHtml);
        }
        
        bp_text($a, 'Scan QR di atas untuk klaim', 0, 1, 0);
    }
    
    bp_line($a);
    bp_text($a, 'Terima kasih. Selamat menikmati.', 0, 1, 0);
    bp_text($a, ' ', 0, 0, 0);
    bp_text($a, ' ', 0, 0, 0);

    echo json_encode($a, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    $err = []; bp_text($err, 'Gagal memuat struk', 1, 1, 0);
    bp_text($err, $e->getMessage(), 0, 1, 0);
    echo json_encode($err, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
