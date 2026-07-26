<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$recipe = $pdo->query("SELECT id, name, total_hpp FROM recipes WHERE product_variant_id = 1137 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($recipe);

$items = $pdo->query("SELECT ri.*, rm.name, rm.outlet_id as rm_outlet FROM recipe_items ri LEFT JOIN raw_materials rm ON ri.raw_material_id = rm.id WHERE ri.recipe_id = {$recipe['id']}")->fetchAll(PDO::FETCH_ASSOC);
print_r($items);
