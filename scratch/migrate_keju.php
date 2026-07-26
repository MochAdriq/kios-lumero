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

$waterId = 169;
$unitPorsi = 7;
$unitGr = 1;
$unitMl = 2;

$rmName = 'Saus Keju';
$shortName = 'Cheese';

echo "Processing $shortName...\n";

// Find the raw material ID used in Outlet 8
$stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? AND outlet_id = 8 LIMIT 1");
$stmt->execute([$rmName]);
$rawMat = $stmt->fetch();

$rmId = (int)$rawMat['id'];
echo "  -> Found RM ID: $rmId\n";

$subRecipeName = "[Saus] $shortName";

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
    $rm->addItem($subRecipeId, 'raw_material', $rmId, 250, $unitGr);
    // Water 125ml for cheese
    $rm->addItem($subRecipeId, 'raw_material', $waterId, 125, $unitMl);
    
    $rm->recalculate($subRecipeId, 1);
    echo "  -> Ingredients added to Sub-Recipe.\n";
}

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
    $rm->removeItem((int)$usage['item_id'], 1);
    $rm->addItem((int)$usage['recipe_id'], 'sub_recipe', $subRecipeId, 1.0, $unitPorsi);
}
echo "Cheese processed successfully.\n";
