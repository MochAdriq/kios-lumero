<?php
require_once __DIR__ . '/../../core/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $orderId = (int)($_GET['order_id'] ?? 0);

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order_id']);
        exit;
    }

    $pdo = Database::connection();
    
    $stmt = $pdo->prepare("SELECT id, order_number, print_status, print_error, print_attempt FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    echo json_encode(['success' => true, 'order' => $order]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
