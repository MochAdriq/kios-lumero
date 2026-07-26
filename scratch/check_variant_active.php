<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$stmt = $pdo->query("SELECT id, variant_name, is_active FROM product_variants WHERE outlet_id = $outlet_id AND variant_name LIKE '%Original%' LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
