<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$totalSales = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE outlet_id = 8")->fetchColumn();
$totalItemsSales = $pdo->query("SELECT COALESCE(SUM(subtotal), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.outlet_id = 8")->fetchColumn();

echo "Total recorded order sales: Rp " . number_format($totalSales, 0, ',', '.') . "\n";
echo "Total recorded items sales: Rp " . number_format($totalItemsSales, 0, ',', '.') . "\n";
