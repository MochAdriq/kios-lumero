<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Composition of Sub-Recipe 1071:\n";
$stmt = $pdo->query("
    SELECT ri.*, rm.name as raw_material_name, u.symbol as unit_symbol
    FROM recipe_items ri
    LEFT JOIN raw_materials rm ON rm.id = ri.raw_material_id
    LEFT JOIN units u ON u.id = ri.unit_id
    WHERE ri.recipe_id = 1071
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
