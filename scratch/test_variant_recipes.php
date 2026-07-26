<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$variant = $pdo->query("SELECT id, product_id, variant_name, sku FROM product_variants WHERE variant_name = 'Chicken Crips Garlic + Nasi'")->fetchAll(PDO::FETCH_ASSOC);
print_r($variant);

foreach ($variant as $v) {
    $r = $pdo->query("SELECT id, name, outlet_id FROM recipes WHERE recipe_type = 'final' AND product_variant_id = {$v['id']}")->fetchAll(PDO::FETCH_ASSOC);
    echo "Recipes for Variant {$v['id']}:\n";
    print_r($r);
}
