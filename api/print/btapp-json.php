<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';

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

    $isCash = strtolower(trim((string)($order['payment_method'] ?? ''))) === 'cash';

    $a = [];
    if ($isCash) {
        bp_cash_drawer_pulse($a);
    }

    bp_text($a, strtoupper(current_outlet_name()), 1, 1, 3);
    bp_text($a, 'POS KASIR', 0, 1, 0);
    bp_line($a);
    bp_text($a, 'Order    : ' . ($order['order_number'] ?? '-'), 0, 0, 0);
    bp_text($a, 'Tanggal  : ' . date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')), 0, 0, 0);
    bp_text($a, 'Bayar    : ' . strtoupper((string)($order['payment_method'] ?? '-')), 0, 0, 0);
    
    if (!empty($order['customer_name']) || !empty($order['customer_phone'])) {
        $cName = mb_substr($order['customer_name'] ?? $order['customer_phone'], 0, 15);
        bp_text($a, 'Plgn     : ' . $cName, 0, 0, 0);
    }
    bp_line($a);

    foreach ($items as $it) {
        $name = strtoupper((string)($it['item_name'] ?? 'ITEM'));
        bp_text($a, $name, 1, 0, 0);
        $qtyPrice = ((int)($it['quantity'] ?? 1)) . ' x ' . rupiahPlain($it['price'] ?? 0);
        
        // Pad left and right manually for qty x price = subtotal
        $sub = rupiahPlain($it['subtotal'] ?? 0);
        $sp = max(1, 32 - strlen($qtyPrice) - strlen($sub));
        bp_text($a, $qtyPrice . str_repeat(' ', $sp) . $sub, 0, 0, 0);
    }
    
    bp_line($a);
    bp_text($a, 'Subtotal : ' . rupiahPlain($order['subtotal'] ?? 0), 0, 0, 0);
    if ((float)($order['discount_amount'] ?? 0) > 0) {
        bp_text($a, 'Diskon   : ' . rupiahPlain($order['discount_amount']), 0, 0, 0);
    }
    
    bp_text($a, 'TOTAL    : ' . rupiahPlain($order['grand_total'] ?? 0), 1, 0, 2);
    bp_line($a);
    
    if ($order['payment_method'] === 'cash') {
        bp_text($a, 'Dibayar  : ' . rupiahPlain($order['paid_amount'] ?? 0), 0, 0, 0);
        bp_text($a, 'Kembali  : ' . rupiahPlain($order['change_amount'] ?? 0), 0, 0, 0);
    }
    
    bp_line($a);
    bp_text($a, 'Terima kasih', 1, 1, 0);
    bp_text($a, 'Selamat menikmati', 0, 1, 0);
    bp_text($a, ' ', 0, 0, 0);
    bp_text($a, ' ', 0, 0, 0);

    echo json_encode($a, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    $err = []; bp_text($err, 'Gagal memuat struk', 1, 1, 0);
    bp_text($err, $e->getMessage(), 0, 1, 0);
    echo json_encode($err, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
