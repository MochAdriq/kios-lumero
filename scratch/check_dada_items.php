<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$dada = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = $outlet_id AND variant_name = 'Dada Original + Nasi' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$recipe = $pdo->query("SELECT id, total_hpp FROM recipes WHERE product_variant_id = {$dada['id']} LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$items = $pdo->query("
    SELECT ri.item_type, rm.name as raw_name, sr.name as sub_name, ri.qty, ri.cost_per_unit, ri.total_cost
    FROM recipe_items ri 
    LEFT JOIN raw_materials rm ON ri.raw_material_id = rm.id 
    LEFT JOIN recipes sr ON ri.sub_recipe_id = sr.id 
    WHERE ri.recipe_id = {$recipe['id']}
")->fetchAll(PDO::FETCH_ASSOC);

print_r($items);
