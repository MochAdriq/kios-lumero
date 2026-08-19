<?php
define('SECRET_KEY', 'klb-fix-2026');

if (($_GET['key'] ?? '') !== SECRET_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();
    
    // Check payments for today
    $date = date('Y-m-d'); // or hardcode 2026-08-19
    echo "Date: $date\n";
    
    $sql = "SELECT p.id, p.order_id, p.payment_method, p.amount, p.status, o.order_number, o.payment_status as order_pay_status
            FROM payments p 
            JOIN orders o ON o.id = p.order_id 
            WHERE o.business_date = '2026-08-19' AND p.payment_method = 'qris'";

            
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    echo "Found " . count($results) . " QRIS related payments:\n";
    print_r($results);
    
    // Check if ReportModel throws any error
    echo "\n\nTesting ReportModel query:\n";
    require_once __DIR__ . '/../modules/reports/ReportModel.php';
    $rm = new ReportModel();
    // Simulate what ReportModel does for pay
    $outlet = 2; // Assuming KLB is 2
    
    $paySql = "SELECT p.payment_method, COALESCE(SUM(p.amount),0) total 
               FROM payments p 
               JOIN orders o ON o.id=p.order_id 
               WHERE o.outlet_id=? AND o.business_date=? AND p.status='paid' 
               GROUP BY p.payment_method";
    $payStmt = $pdo->prepare($paySql);
    $payStmt->execute([$outlet, '2026-08-19']);
    $payRes = $payStmt->fetchAll();
    echo "Raw payment grouping from DB:\n";
    print_r($payRes);

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
