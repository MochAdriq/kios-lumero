<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$q = "SELECT count(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.outlet_id = 8";
echo "Total order_items for Outlet 8: " . $pdo->query($q)->fetchColumn() . "\n";
