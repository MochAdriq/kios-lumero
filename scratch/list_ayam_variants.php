<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$variants = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = 8 AND product_id = (SELECT id FROM products WHERE name LIKE '%Ayam Crispy%' AND outlet_id = 8 LIMIT 1)")->fetchAll(PDO::FETCH_ASSOC);
print_r($variants);
