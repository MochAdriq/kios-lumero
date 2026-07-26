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

// Find all recipes in Outlet 8 that use Kantong Besar (232)
$stmt = $pdo->query("
    SELECT r.id as recipe_id, r.name as recipe_name, ri.id as item_id
    FROM recipe_items ri
    JOIN recipes r ON r.id = ri.recipe_id
    WHERE r.outlet_id = 8 AND ri.raw_material_id = 232
");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updatedCount = 0;
foreach ($recipes as $r) {
    // If name DOES NOT contain "+ Nasi" (case-insensitive)
    if (stripos($r['recipe_name'], '+ Nasi') === false) {
        echo "- Updating Recipe {$r['recipe_id']}: {$r['recipe_name']} -> Kantong Kecil\n";
        
        // Update raw_material_id to 237
        $update = $pdo->prepare("UPDATE recipe_items SET raw_material_id = 237 WHERE id = ?");
        $update->execute([$r['item_id']]);
        
        // Recalculate HPP
        $rm->recalculate((int)$r['recipe_id'], 1);
        
        $updatedCount++;
    } else {
        echo "- Kept Recipe {$r['recipe_id']}: {$r['recipe_name']} (has + Nasi)\n";
    }
}

echo "Total updated: $updatedCount\n";
