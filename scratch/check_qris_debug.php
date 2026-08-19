<?php
define('SECRET_KEY', 'klb-fix-2026');

if (($_GET['key'] ?? '') !== SECRET_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Database::connection();
    
    // Check payments status for qris
    $sql = "SELECT p.id, p.status as payment_status, o.payment_status as order_pay_status, o.order_number 
            FROM payments p 
            JOIN orders o ON o.id = p.order_id 
            WHERE o.business_date = '2026-08-19' AND p.payment_method = 'qris'";
            
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $results]);
    
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
