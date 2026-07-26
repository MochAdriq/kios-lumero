<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(stock_qty) as total_stock FROM outlet_raw_materials WHERE outlet_id = $outlet_id");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM outlet_raw_materials WHERE outlet_id = $outlet_id LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
