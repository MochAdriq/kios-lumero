<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get any variant from outlet 8 that has a final recipe
$variantId = $pdo->query("SELECT product_variant_id FROM recipes WHERE recipe_type = 'final' AND outlet_id = 8 LIMIT 1")->fetchColumn();
echo "Testing with variant ID: $variantId\n";

$recipe = $pdo->query("SELECT id, name FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId AND outlet_id = 8 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    echo "Recipe not found\n";
    exit;
}
echo "Recipe found: {$recipe['name']}\n";

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
// Need to set up Database connection for RecipeModel
require_once __DIR__ . '/../core/Database.php';

$rm = new RecipeModel();
try {
    $bom = $rm->explodeBOM($recipe['id'], 1.0);
    echo "BOM:\n";
    print_r($bom);
    
    $rmIds = array_keys($bom);
    $placeholders = implode(',', array_fill(0, count($rmIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT rm.id, rm.name, u.label as unit, COALESCE(orm.stock_qty, rm.stock_qty, 0) as available_stock
        FROM raw_materials rm
        LEFT JOIN units u ON rm.unit_id = u.id
        LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
        WHERE rm.id IN ($placeholders)
    ");
    $params = array_merge([8], $rmIds);
    $stmt->execute($params);
    $rawMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Raw Materials:\n";
    print_r($rawMaterials);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
