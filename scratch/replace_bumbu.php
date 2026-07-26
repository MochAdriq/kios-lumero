<?php
$_COOKIE['lumero_db_mode'] = 'production';
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Database.php';

$ref = new ReflectionClass('Database');
$prop = $ref->getProperty('pdo');
$prop->setAccessible(true);
$prop->setValue(null, $pdo);

class Auth {
    public static function id() { return 1; }
    public static function user() { return ['id' => 1, 'outlet_id' => 8]; }
}

require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
$rm = new RecipeModel();

// Find all recipes in Outlet 8 that use Tepung Krispy (Raw Material ID 167) except recipe 1071 itself
$stmt = $pdo->query("
    SELECT r.id as recipe_id, r.name, ri.id as item_id, ri.qty, ri.unit_id 
    FROM recipes r
    JOIN recipe_items ri ON ri.recipe_id = r.id
    WHERE r.outlet_id = 8 
      AND ri.item_type = 'raw_material' 
      AND ri.raw_material_id = 167
      AND r.id != 1071
");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Replacing Tepung Krispy with [Bumbu] Racik in " . count($recipes) . " recipes...\n";
foreach ($recipes as $r) {
    echo "- Updating Recipe {$r['recipe_id']}: {$r['name']}...\n";
    
    // 1. Remove Tepung Krispy
    $rm->removeItem((int)$r['item_id'], 1);
    
    // 2. Add [Bumbu] Racik Untuk 1 potong ayam (Sub-Recipe 1071)
    // 1 Porsi (unit_id = 7)
    $rm->addItem((int)$r['recipe_id'], 'sub_recipe', 1071, 1.0, 7);
    
    echo "  -> Done.\n";
}
echo "All recipes updated successfully.\n";
