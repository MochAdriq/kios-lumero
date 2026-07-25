<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== CHECKING BUBUK COKLAT ===\n";
$rm = $pdo->query("SELECT * FROM raw_materials WHERE name LIKE '%Coklat%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($rm);

echo "=== CHECKING RECIPE ITEMS WITH BUBUK COKLAT ===\n";
// Find recipes using Bubuk Coklat
if (count($rm) > 0) {
    $rmIds = implode(',', array_column($rm, 'id'));
    $items = $pdo->query("SELECT ri.id, ri.recipe_id, ri.qty, ri.unit_id, ri.cost_per_unit, ri.total_cost, r.outlet_id, r.name as recipe_name FROM recipe_items ri JOIN recipes r ON ri.recipe_id = r.id WHERE ri.raw_material_id IN ($rmIds)")->fetchAll(PDO::FETCH_ASSOC);
    print_r($items);
}
