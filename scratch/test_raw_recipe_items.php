<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$id = 394;
$sql = "SELECT ri.*, 
                   u.symbol unit_symbol,
                   CASE 
                       WHEN ri.item_type = 'raw_material' THEN rm.name 
                       ELSE sr.name 
                   END as material_name,
                   CASE 
                       WHEN ri.item_type = 'raw_material' THEN COALESCE(NULLIF(orm.average_cost, 0), rm.average_cost, 0)
                       ELSE (sr.total_hpp / sr.yield_qty) 
                   END as current_unit_cost
            FROM recipe_items ri
            JOIN recipes r ON r.id = ri.recipe_id
            LEFT JOIN raw_materials rm ON rm.id = ri.raw_material_id
            LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND (orm.outlet_id = r.outlet_id OR (r.outlet_id IS NULL AND orm.outlet_id = 1))
            LEFT JOIN recipes sr ON sr.id = ri.sub_recipe_id
            LEFT JOIN units u ON u.id = ri.unit_id
            WHERE ri.recipe_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Items found: " . count($items) . "\n";
print_r($items);
