<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// 1. Find variant ID for VAR-997620-858f
$variant = $pdo->query("SELECT id, product_id, variant_name, sku FROM product_variants WHERE sku = 'VAR-997620-858f' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$variant) {
    echo "Variant not found.\n";
    exit;
}
echo "Variant Found:\n";
print_r($variant);

// 2. Find recipe for this variant in outlet 8
$recipe = $pdo->query("SELECT id, name, outlet_id, product_variant_id FROM recipes WHERE product_variant_id = {$variant['id']} AND outlet_id = 8")->fetchAll(PDO::FETCH_ASSOC);
echo "Recipes for variant in outlet 8:\n";
print_r($recipe);

// 3. Find ANY recipe for this variant across all outlets
$anyRecipe = $pdo->query("SELECT id, name, outlet_id, product_variant_id FROM recipes WHERE product_variant_id = {$variant['id']}")->fetchAll(PDO::FETCH_ASSOC);
echo "Recipes for variant in ALL outlets:\n";
print_r($anyRecipe);

// 4. Find ANY recipe that matches the name
$nameRecipes = $pdo->query("SELECT id, name, outlet_id, product_variant_id FROM recipes WHERE name LIKE '%Chicken Crips Cheese%' AND outlet_id = 8")->fetchAll(PDO::FETCH_ASSOC);
echo "Recipes matching name in outlet 8:\n";
print_r($nameRecipes);
