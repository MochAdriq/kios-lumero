<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/receipt_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Basic Auth Check
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    $expectedToken = app_env('PRINT_AGENT_TOKEN', 'LUMERO_PRINT_SECRET_2026');
    if ($token !== $expectedToken && $expectedToken !== '') {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $pdo = Database::connection();
    
    // Begin transaction to safely pick one order
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_status = 'paid' AND order_status <> 'cancelled' AND print_status = 'waiting' ORDER BY id ASC LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->commit();
        echo json_encode(['success' => true, 'has_order' => false, 'message' => 'Tidak ada order menunggu print.']);
        exit;
    }

    // Mark as printing
    $updateStmt = $pdo->prepare("UPDATE orders SET print_status = 'printing', print_attempt = print_attempt + 1, print_error = NULL WHERE id = ?");
    $updateStmt->execute([$order['id']]);

    // Get order items
    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
    $itemsStmt->execute([$order['id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    // Get payment details
    $order['payment_method'] = 'cash'; // TODO: fetch from payments table if available, for now assume cash or default
    // We should actually fetch from order_payments if possible
    $payStmt = $pdo->prepare("SELECT payment_method, amount_paid FROM order_payments WHERE order_id = ? LIMIT 1");
    $payStmt->execute([$order['id']]);
    $pay = $payStmt->fetch(PDO::FETCH_ASSOC);
    if ($pay) {
        $order['payment_method'] = $pay['payment_method'];
        $order['paid_amount'] = $pay['amount_paid'];
        $order['change_amount'] = max(0, $pay['amount_paid'] - $order['grand_total']);
    }

    // Use helper to build receipt
    $receiptLines = build_receipt_text($order, $items, 32, current_outlet_name());

    echo json_encode([
        'success' => true,
        'has_order' => true,
        'order' => $order,
        'items' => $items,
        'server_time' => date('Y-m-d H:i:s'),
        'receipt_width' => 32,
        'receipt_text' => implode("\n", $receiptLines),
        'receipt_lines' => $receiptLines,
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
