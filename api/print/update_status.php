<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $token = $_POST['token'] ?? '';
    $expectedToken = app_env('PRINT_AGENT_TOKEN', 'LUMERO_PRINT_SECRET_2026');
    if ($token !== $expectedToken && $expectedToken !== '') {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? ''; // 'printed' or 'failed'
    $error = $_POST['error'] ?? null;

    if ($orderId <= 0 || !in_array($status, ['printed', 'failed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $pdo = Database::connection();
    
    $stmt = $pdo->prepare("UPDATE orders SET print_status = ?, print_error = ? WHERE id = ?");
    $stmt->execute([$status, $error, $orderId]);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
