<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$outletId = 8; // Pasekon

// Let's find "Dada BBQ Spicy + Nasi" and "Dada Garlic Tanpa Nasi" variants for outlet 8
$variants = $pdo->query("SELECT id, variant_name, sku FROM product_variants WHERE variant_name LIKE '%Dada BBQ Spicy + Nasi%' OR variant_name LIKE '%Dada Garlic Tanpa Nasi%'")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
$rm = new RecipeModel();

foreach ($variants as $v) {
    echo "Variant: {$v['variant_name']} (SKU: {$v['sku']}) - ID: {$v['id']}\n";
    
    $recipe = $pdo->query("SELECT id, name FROM recipes WHERE recipe_type = 'final' AND product_variant_id = {$v['id']} AND outlet_id IN ($outletId, 1) ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$recipe) {
        echo "  - No final recipe found.\n";
        continue;
    }
    
    echo "  - Recipe ID: {$recipe['id']} ({$recipe['name']})\n";
    
    $bom = $rm->explodeBOM($recipe['id'], 1.0);
    foreach ($bom as $rmId => $qty) {
        // fetch raw material name and outlet
        $mat = $pdo->query("SELECT name, outlet_id FROM raw_materials WHERE id = $rmId")->fetch(PDO::FETCH_ASSOC);
        if (stripos($mat['name'], 'dada mentah') !== false) {
            echo "    -> RM ID: $rmId | Name: {$mat['name']} | RM Outlet: {$mat['outlet_id']}\n";
            // Check stock in outlet 8
            $orm = $pdo->query("SELECT stock_qty FROM outlet_raw_materials WHERE raw_material_id = $rmId AND outlet_id = $outletId")->fetchColumn();
            echo "    -> Stock in Outlet 8 (orm): " . ($orm !== false ? $orm : 'NULL') . "\n";
        }
    }
    echo "\n";
}
