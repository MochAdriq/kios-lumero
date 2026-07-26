<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $outlet_id = 8;
    
    $pdo->beginTransaction();

    // 1. Fix recipe types
    $pdo->exec("UPDATE recipes SET recipe_type = 'final' WHERE outlet_id = $outlet_id AND recipe_type = 'variant'");
    
    // 2. Get Kantong Kresek ID
    $kresekId = $pdo->query("SELECT id FROM raw_materials WHERE outlet_id = $outlet_id AND name = 'Kantong Kresek' ORDER BY id DESC LIMIT 1")->fetchColumn();
    
    // 3. Add Kresek to all final recipes
    $recipes = $pdo->prepare("SELECT id FROM recipes WHERE outlet_id = ? AND recipe_type = 'final' AND is_active = 1");
    $recipes->execute([$outlet_id]);
    $recipes = $recipes->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id, cost_per_unit, total_cost) VALUES (?, 'raw_material', ?, 1, 4, 200, 200)");
    $kresekAdded = 0;
    foreach ($recipes as $rId) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM recipe_items WHERE recipe_id = ? AND raw_material_id = ?");
        $check->execute([$rId, $kresekId]);
        if ($check->fetchColumn() == 0) {
            $stmt->execute([$rId, $kresekId]);
            $kresekAdded++;
        }
    }
    echo "Injected kresek to " . $kresekAdded . " recipes.\n";

    $pdo->commit();
    echo "DB commit success.\n";

} catch (Exception $e) {
    if(isset($pdo)) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage();
}

$dbConn = Database::connection();
$rm = new RecipeModel();
$dbConn->exec('SET FOREIGN_KEY_CHECKS=0');
$rm->recalculateAll(8);
$dbConn->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Recalculation complete.\n";
