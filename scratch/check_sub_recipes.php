<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Sub-Recipe 920 Items ([Base] Dada + Nasi):\n";
print_r($pdo->query("
    SELECT ri.raw_material_id, rm.name, rm.outlet_id 
    FROM recipe_items ri 
    JOIN raw_materials rm ON rm.id = ri.raw_material_id 
    WHERE ri.recipe_id = 920
")->fetchAll(PDO::FETCH_ASSOC));

echo "Sub-Recipe 921 Items ([Base] Dada Tanpa Nasi):\n";
print_r($pdo->query("
    SELECT ri.raw_material_id, rm.name, rm.outlet_id 
    FROM recipe_items ri 
    JOIN raw_materials rm ON rm.id = ri.raw_material_id 
    WHERE ri.recipe_id = 921
")->fetchAll(PDO::FETCH_ASSOC));
