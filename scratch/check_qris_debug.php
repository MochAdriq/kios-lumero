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
    
    $date = date('Y-m-d');
    
    // Check what is in the payments table for 'paid' orders today
    $sql = "SELECT p.payment_method, p.status as payment_status, COUNT(*) as count
            FROM payments p 
            JOIN orders o ON o.id = p.order_id 
            WHERE o.business_date = '2026-08-19' AND o.payment_status = 'paid'
            GROUP BY p.payment_method, p.status";
            
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $results]);
    
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
