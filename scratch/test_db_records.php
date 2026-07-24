<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$TARGET_OUTLET = 7;

echo "=== CATEGORIES ===\n";
$cats = $pdo->query("SELECT id, name, is_active FROM product_categories WHERE outlet_id = {$TARGET_OUTLET}")->fetchAll(PDO::FETCH_ASSOC);
print_r($cats);

echo "=== PRODUCTS ===\n";
$prods = $pdo->query("SELECT id, name, category_id, is_active FROM products WHERE outlet_id = {$TARGET_OUTLET} LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($prods);

echo "=== VARIANTS ===\n";
$vars = $pdo->query("SELECT id, variant_name, product_id, is_active FROM product_variants WHERE outlet_id = {$TARGET_OUTLET} LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($vars);
