<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// Find all recipes in Outlet 8 that use Tepung Krispy (Raw Material ID 167)
$stmt = $pdo->query("
    SELECT r.id, r.name, ri.id as item_id, ri.qty, ri.unit_id 
    FROM recipes r
    JOIN recipe_items ri ON ri.recipe_id = r.id
    WHERE r.outlet_id = 8 
      AND ri.item_type = 'raw_material' 
      AND ri.raw_material_id = 167
");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($recipes) . " recipes using Tepung Krispy in Outlet 8.\n";
foreach ($recipes as $r) {
    echo "- Recipe {$r['id']}: {$r['name']} (uses {$r['qty']} of Tepung Krispy)\n";
}
