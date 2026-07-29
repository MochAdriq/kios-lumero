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

function bp_image_base64(&$arr, $base64) {
    if (empty($base64)) return;
    $obj = new stdClass();
    $obj->type = 3; // Image
    $obj->content = $base64; // RawBT / Thermer will handle the base64
    $obj->align = 1; // Center
    $arr[] = $obj;
}

function bp_image_local(&$arr, $path) {
    if (!file_exists($path)) return;
    // Buka gambar PNG/JPEG
    $info = getimagesize($path);
    if (!$info) return;
    
    $img = null;
    if ($info[2] === IMAGETYPE_PNG) {
        $img = @imagecreatefrompng($path);
    } elseif ($info[2] === IMAGETYPE_JPEG) {
        $img = @imagecreatefromjpeg($path);
    }
    if (!$img) return;
    
    $w = imagesx($img);
    $h = imagesy($img);
    
    // Buat gambar baru dengan background putih (menghindari transparan jadi hitam di printer thermal)
    $newImg = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($newImg, 255, 255, 255);
    imagefill($newImg, 0, 0, $white);
    
    imagecopy($newImg, $img, 0, 0, 0, 0, $w, $h);
    
    ob_start();
    imagejpeg($newImg, null, 100);
    $jpegData = ob_get_clean();
    
    imagedestroy($img);
    imagedestroy($newImg);
    
    bp_image_base64($arr, base64_encode($jpegData));
}

function bp_image_url(&$arr, $url) {
    if (empty($url)) return;
    // Download image from URL (e.g., Quickchart QR)
    $data = @file_get_contents($url);
    if ($data) {
        bp_image_base64($arr, base64_encode($data));
    }
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

    $logoPath = __DIR__ . '/../../public/assets/images/pos-products/apple-touch-icon.jpeg';
    if (file_exists($logoPath)) {
        bp_image_base64($a, base64_encode(file_get_contents($logoPath)));
    }

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
        
        // Menambahkan QR Code sebagai Image Base64 (Server-side rendered)
        if (function_exists('loyalty_member_qr_url')) {
            $qrUrl = loyalty_member_qr_url($claimCode, 150);
            bp_image_url($a, $qrUrl);
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
