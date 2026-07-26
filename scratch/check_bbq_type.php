<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$bbq = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = $outlet_id AND variant_name = 'Dada BBQ Spicy Tanpa Nasi' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$recipe = $pdo->query("SELECT id, total_hpp, recipe_type FROM recipes WHERE product_variant_id = {$bbq['id']} LIMIT 1")->fetch(PDO::FETCH_ASSOC);

print_r($recipe);
