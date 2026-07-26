<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// Find what raw materials recipe 134 requires
$items = $pdo->query("SELECT ri.raw_material_id, rm.name, rm.outlet_id FROM recipe_items ri JOIN raw_materials rm ON rm.id = ri.raw_material_id WHERE ri.recipe_id = 134")->fetchAll(PDO::FETCH_ASSOC);
echo "Raw Materials for Recipe 134:\n";
print_r($items);

$subItems = $pdo->query("SELECT ri.sub_recipe_id, sr.name, sr.outlet_id FROM recipe_items ri JOIN recipes sr ON sr.id = ri.sub_recipe_id WHERE ri.recipe_id = 134")->fetchAll(PDO::FETCH_ASSOC);
echo "Sub Recipes for Recipe 134:\n";
print_r($subItems);

// For those sub-recipes, what raw materials do they need?
foreach ($subItems as $sr) {
    $srItems = $pdo->query("SELECT ri.raw_material_id, rm.name, rm.outlet_id FROM recipe_items ri JOIN raw_materials rm ON rm.id = ri.raw_material_id WHERE ri.recipe_id = {$sr['sub_recipe_id']}")->fetchAll(PDO::FETCH_ASSOC);
    echo "Raw Materials for Sub-Recipe {$sr['sub_recipe_id']} ({$sr['name']}):\n";
    print_r($srItems);
}
