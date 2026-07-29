<?php
require_once __DIR__ . '/../../core/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order_id']);
        exit;
    }

    $pdo = Database::connection();
    
    // Set status back to waiting so print agent picks it up
    $stmt = $pdo->prepare("UPDATE orders SET print_status = 'waiting', print_error = NULL WHERE id = ?");
    $stmt->execute([$orderId]);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
