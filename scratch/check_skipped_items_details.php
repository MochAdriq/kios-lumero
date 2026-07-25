<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$query = "
    SELECT o.id, o.order_number, o.grand_total as total, COALESCE(SUM(oi.subtotal), 0) as items_total, o.grand_total - COALESCE(SUM(oi.subtotal), 0) as diff
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.outlet_id = 8
    GROUP BY o.id
    HAVING diff > 0
";

$discrepancies = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

print_r($discrepancies);
