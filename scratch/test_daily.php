<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$q = "SELECT
    COALESCE(SUM(total_revenue),0) gross_sales,
    COALESCE(SUM(total_hpp),0) hpp,
    COALESCE(SUM(gross_profit),0) gross_profit,
    COALESCE(SUM(total_expense),0) expenses,
    COALESCE(SUM(net_profit),0) net_profit,
    COALESCE(SUM(total_transactions),0) paid_orders
    FROM daily_closing_reports WHERE outlet_id=8 AND business_date BETWEEN '2026-05-01' AND '2026-07-31'";

$res = $pdo->query($q)->fetch(PDO::FETCH_ASSOC);
print_r($res);
