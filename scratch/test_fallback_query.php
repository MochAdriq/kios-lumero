<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$variantId = 138;
$outletId = 8;

// See ALL recipes for variant 138
$recipes = $pdo->query("SELECT id, name, outlet_id FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId")->fetchAll(PDO::FETCH_ASSOC);
echo "All Recipes for Variant 138:\n";
print_r($recipes);

// See what my old query returned
$oldQuery = $pdo->query("SELECT id, name, outlet_id FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Old Query Returned:\n";
print_r($oldQuery);

// See what the new strict query returns
$newQuery = $pdo->query("SELECT id, name, outlet_id FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId AND outlet_id IN ($outletId, 1) ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "New Query Returns:\n";
print_r($newQuery);
