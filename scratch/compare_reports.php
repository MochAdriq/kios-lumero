<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$out = 8;
$from = '2026-05-01';
$to = '2026-07-25';

echo "=== 1. EXECUTIVE DASHBOARD (daily_closing_reports) ===\n";
$exec = $pdo->query("SELECT 
    COALESCE(SUM(total_revenue),0) as gross_sales,
    COALESCE(SUM(total_hpp),0) as hpp,
    COALESCE(SUM(total_expense),0) as expenses,
    COALESCE(SUM(net_profit),0) as net_profit
    FROM daily_closing_reports WHERE outlet_id = $out AND business_date BETWEEN '$from' AND '$to'")->fetch(PDO::FETCH_ASSOC);
print_r($exec);

echo "\n=== 2. FINANCIAL REPORTS (ReportModel) ===\n";
// Let's see what ReportModel does. It probably queries `orders` directly.
$reports = $pdo->query("SELECT 
    COALESCE(SUM(grand_total),0) as gross_sales,
    COALESCE(SUM(total_hpp),0) as hpp
    FROM orders WHERE outlet_id = $out AND DATE(created_at) BETWEEN '$from' AND '$to' AND payment_status = 'paid'")->fetch(PDO::FETCH_ASSOC);
print_r($reports);

echo "\n=== 3. ORDERS LIST ===\n";
// Orders list might just be summing all orders regardless of payment status?
$allOrders = $pdo->query("SELECT 
    COALESCE(SUM(grand_total),0) as gross_sales,
    COALESCE(SUM(total_hpp),0) as hpp
    FROM orders WHERE outlet_id = $out AND DATE(created_at) BETWEEN '$from' AND '$to'")->fetch(PDO::FETCH_ASSOC);
print_r($allOrders);

// Check if there are unpaid/cancelled orders
$statusCount = $pdo->query("SELECT payment_status, order_status, COUNT(*), SUM(grand_total) FROM orders WHERE outlet_id = $out AND DATE(created_at) BETWEEN '$from' AND '$to' GROUP BY payment_status, order_status")->fetchAll(PDO::FETCH_ASSOC);
echo "\nOrder statuses:\n";
print_r($statusCount);

// Check expenses table
$expenses = $pdo->query("SELECT SUM(amount) as exp FROM expenses WHERE outlet_id = $out AND expense_date BETWEEN '$from' AND '$to'")->fetch(PDO::FETCH_ASSOC);
echo "\nExpenses table total: " . $expenses['exp'] . "\n";
