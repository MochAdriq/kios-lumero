<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$products = $pdo->query("SELECT id, sku, name FROM products WHERE outlet_id = $outlet_id AND (name LIKE '%Potato%' OR name LIKE '%Kentang%')")->fetchAll(PDO::FETCH_ASSOC);
print_r($products);
