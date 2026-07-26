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
require_once __DIR__ . '/../modules/inventory/InventoryModel.php';
$rm = new RecipeModel();

// Water ID
$waterId = 169;
// Unit ID for 'Porsi'
$unitPorsi = 7;
// Unit ID for 'gr'
$unitGr = 1;
// Unit ID for 'ml'
$unitMl = 2;

// The 7 target sauces and their Raw Material names (based on standard naming in Lumero)
$targetSauces = [
    'BBQ Spicy' => 'Saus Italian Barbeque Spicy',
    'Cheese' => 'Saus Cheese',
    'Garlic' => 'Saus Garlic',
    'Lada Hitam' => 'Saus Lada Hitam',
    'Mentai' => 'Saus Mentai',
    'Sadis' => 'Saus Sadis',
    'Teriyaki' => 'Saus Teriyaki'
];

foreach ($targetSauces as $shortName => $rmName) {
    echo "Processing $shortName...\n";
    
    // Find the raw material ID used in Outlet 8
    $stmt = $pdo->prepare("
        SELECT id FROM raw_materials 
        WHERE name = ? AND outlet_id = 8 LIMIT 1
    ");
    $stmt->execute([$rmName]);
    $rawMat = $stmt->fetch();
    
    if (!$rawMat) {
        // Fallback to global if not found in outlet 8, or check recipes
        $stmt = $pdo->prepare("
            SELECT rm.id 
            FROM recipe_items ri
            JOIN recipes r ON r.id = ri.recipe_id
            JOIN raw_materials rm ON rm.id = ri.raw_material_id
            WHERE r.outlet_id = 8 AND rm.name = ?
            LIMIT 1
        ");
        $stmt->execute([$rmName]);
        $rawMat = $stmt->fetch();
    }
    
    if (!$rawMat) {
        echo "  -> Skip: Raw material '$rmName' not found or used in Outlet 8.\n";
        continue;
    }
    
    $rmId = (int)$rawMat['id'];
    echo "  -> Found RM ID: $rmId\n";
    
    // Create Sub-Recipe
    $subRecipeName = "[Saus] $shortName";
    
    // Check if sub-recipe already exists to avoid duplicates
    $checkSr = $pdo->prepare("SELECT id FROM recipes WHERE name = ? AND outlet_id = 8 LIMIT 1");
    $checkSr->execute([$subRecipeName]);
    $existingSr = $checkSr->fetch();
    
    if ($existingSr) {
        $subRecipeId = (int)$existingSr['id'];
        echo "  -> Sub-Recipe already exists (ID $subRecipeId). Reusing.\n";
    } else {
        $pdo->prepare("
            INSERT INTO recipes (outlet_id, name, recipe_type, yield_qty, yield_unit_id, is_active, version, created_at, updated_at)
            VALUES (8, ?, 'sub_recipe', 1.0, ?, 1, 1, NOW(), NOW())
        ")->execute([$subRecipeName, $unitPorsi]);
        $subRecipeId = (int)$pdo->lastInsertId();
        echo "  -> Created Sub-Recipe (ID $subRecipeId)\n";
        
        // Add Ingredients to Sub-Recipe
        // 1. Sauce powder 250gr
        $rm->addItem($subRecipeId, 'raw_material', $rmId, 250, $unitGr);
        
        // 2. Water 75ml (or 125ml for cheese)
        $waterQty = ($shortName === 'Cheese') ? 125 : 75;
        $rm->addItem($subRecipeId, 'raw_material', $waterId, $waterQty, $unitMl);
        
        // Recalculate Sub-Recipe HPP
        $rm->recalculate($subRecipeId, 1);
        echo "  -> Ingredients added to Sub-Recipe.\n";
    }
    
    // Find all recipes using the raw material
    $findUsage = $pdo->prepare("
        SELECT r.id as recipe_id, ri.id as item_id, r.name as recipe_name
        FROM recipe_items ri
        JOIN recipes r ON r.id = ri.recipe_id
        WHERE r.outlet_id = 8 AND ri.item_type = 'raw_material' AND ri.raw_material_id = ?
          AND r.id != ?
    ");
    $findUsage->execute([$rmId, $subRecipeId]);
    $usages = $findUsage->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  -> Found " . count($usages) . " recipes using raw $rmName.\n";
    
    foreach ($usages as $usage) {
        echo "     - Updating Recipe {$usage['recipe_id']}: {$usage['recipe_name']}\n";
        
        // Remove raw material
        $rm->removeItem((int)$usage['item_id'], 1);
        
        // Add Sub-Recipe (Qty: 1 Porsi)
        $rm->addItem((int)$usage['recipe_id'], 'sub_recipe', $subRecipeId, 1.0, $unitPorsi);
    }
}
echo "All sauces processed successfully.\n";
