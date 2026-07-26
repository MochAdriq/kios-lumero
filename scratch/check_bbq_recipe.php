<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Pasekon Raw Materials:\n";
$rm = $pdo->query("SELECT id, name FROM raw_materials WHERE outlet_id = 8")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r($rm);

$bbq = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = 8 AND variant_name LIKE '%Dada BBQ Spicy Tanpa Nasi%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "BBQ Variant: " . print_r($bbq, true) . "\n";

$recipe = $pdo->query("SELECT id, total_hpp FROM recipes WHERE product_variant_id = {$bbq['id']} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Recipe: " . print_r($recipe, true) . "\n";

$items = $pdo->query("
    SELECT ri.item_type, rm.name as raw_name, sr.name as sub_name, ri.qty 
    FROM recipe_items ri 
    LEFT JOIN raw_materials rm ON ri.raw_material_id = rm.id 
    LEFT JOIN recipes sr ON ri.sub_recipe_id = sr.id 
    WHERE ri.recipe_id = {$recipe['id']}
")->fetchAll(PDO::FETCH_ASSOC);
print_r($items);
