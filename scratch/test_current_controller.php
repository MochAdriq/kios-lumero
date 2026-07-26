<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$variantId = 150;
$outletId = 8;
$recipe = $pdo->query("SELECT id, name, outlet_id FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($recipe);
