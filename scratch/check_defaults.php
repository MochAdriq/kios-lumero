<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8; // Pasekon

$variants = $pdo->query("SELECT id, product_id, variant_name FROM product_variants WHERE outlet_id = $outlet_id AND variant_name = 'Default'")->fetchAll(PDO::FETCH_ASSOC);
echo "Total 'Default' variants: " . count($variants) . "\n";
print_r(array_slice($variants, 0, 5));
