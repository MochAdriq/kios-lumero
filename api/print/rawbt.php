<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/receipt_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $orderId = (int)($_GET['id'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    $pdo = Database::connection();
    
    $stmt = $pdo->prepare("SELECT o.*, outl.name as outlet_name FROM orders o LEFT JOIN outlets outl ON o.outlet_id = outl.id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
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

    // Use helper to build rawbt base64 string
    $b64 = build_rawbt_base64($order, $items, 32, $order['outlet_name'] ?? 'Lumero');
    $rawbtUrl = 'rawbt:base64,' . $b64;

    // Generate my.bluetoothprint.scheme URL for Thermer / Bluetooth Print App
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $jsonUrl = $scheme . '://' . $host . $dir . '/btapp-json.php?id=' . $orderId . '&t=' . time();
    $btappUrl = 'my.bluetoothprint.scheme://' . $jsonUrl;

    echo json_encode([
        'success' => true,
        'rawbt_url' => $rawbtUrl,
        'btapp_url' => $btappUrl
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
