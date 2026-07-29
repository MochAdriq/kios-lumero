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
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
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
    $b64 = build_rawbt_base64($order, $items, 32, current_outlet_name());
    $rawbtUrl = 'rawbt:base64,' . $b64;

    echo json_encode([
        'success' => true,
        'rawbt_url' => $rawbtUrl
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
