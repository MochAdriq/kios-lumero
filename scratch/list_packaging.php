<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// Find all recipes that use Kantong Besar (232) or Kantong Kecil (237) in Outlet 8
$stmt = $pdo->query("
    SELECT r.id as recipe_id, r.name as recipe_name, rm.id as raw_material_id, rm.name as material_name
    FROM recipe_items ri
    JOIN recipes r ON r.id = ri.recipe_id
    JOIN raw_materials rm ON rm.id = ri.raw_material_id
    WHERE r.outlet_id = 8 AND ri.raw_material_id IN (232, 237)
    ORDER BY r.name
");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recipes as $r) {
    echo "- Recipe {$r['recipe_id']}: {$r['recipe_name']} (uses {$r['material_name']})\n";
}
