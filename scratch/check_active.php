<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$stmt = $pdo->query("SELECT is_active, COUNT(*) as cnt FROM product_variants WHERE outlet_id = $outlet_id GROUP BY is_active");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT is_active, COUNT(*) as cnt FROM products WHERE outlet_id = $outlet_id GROUP BY is_active");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
