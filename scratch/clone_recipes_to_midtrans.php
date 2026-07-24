<?php
/**
 * Clone Raw Materials and Recipes from Outlet 5 to Outlet 7
 */
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$SOURCE_OUTLET = 5;
$TARGET_OUTLET = 7;

echo "=== STEP 1: Clone outlet_raw_materials ===\n";
$pdo->prepare("DELETE FROM outlet_raw_materials WHERE outlet_id = ?")->execute([$TARGET_OUTLET]);

$srcRm = $pdo->prepare("SELECT * FROM outlet_raw_materials WHERE outlet_id = ?");
$srcRm->execute([$SOURCE_OUTLET]);
$rawMaterials = $srcRm->fetchAll(PDO::FETCH_ASSOC);

$rmCount = 0;
foreach ($rawMaterials as $rm) {
    $pdo->prepare("
        INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, stock_qty, min_stock_qty, average_cost, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $TARGET_OUTLET,
        $rm['raw_material_id'],
        1000.00, // Beri stock 1000 agar tidak pernah habis (Bahan Habis)
        $rm['min_stock_qty'],
        $rm['average_cost']
    ]);
    $rmCount++;
}
echo "  {$rmCount} raw materials cloned to outlet {$TARGET_OUTLET} with qty = 1000.\n\n";

echo "=== STEP 2: Map Variants ===\n";
// Create a mapping from old variant_id -> new variant_id
// We know old variants have sku = 'XXX', new variants have sku = 'XXX-MT'
$oldVars = $pdo->query("SELECT id, sku FROM product_variants WHERE outlet_id = {$SOURCE_OUTLET}")->fetchAll(PDO::FETCH_ASSOC);
$newVars = $pdo->query("SELECT id, sku FROM product_variants WHERE outlet_id = {$TARGET_OUTLET}")->fetchAll(PDO::FETCH_ASSOC);

$oldSkuMap = []; // sku => id
foreach ($oldVars as $v) $oldSkuMap[$v['sku']] = $v['id'];

$newSkuMap = []; // sku_without_-MT => id
foreach ($newVars as $v) {
    $baseSku = preg_replace('/-MT$/', '', $v['sku']);
    $newSkuMap[$baseSku] = $v['id'];
}

$variantMapping = []; // old_id => new_id
foreach ($oldSkuMap as $sku => $oldId) {
    if (isset($newSkuMap[$sku])) {
        $variantMapping[$oldId] = $newSkuMap[$sku];
    }
}
echo "  Mapped " . count($variantMapping) . " product variants.\n\n";


echo "=== STEP 3: Clone Recipes ===\n";
// Clean up old recipes for target outlet
$pdo->prepare("DELETE FROM recipe_items WHERE recipe_id IN (SELECT id FROM recipes WHERE outlet_id = ?)")->execute([$TARGET_OUTLET]);
$pdo->prepare("DELETE FROM recipes WHERE outlet_id = ?")->execute([$TARGET_OUTLET]);

$srcRecipes = $pdo->prepare("SELECT * FROM recipes WHERE outlet_id = ?");
$srcRecipes->execute([$SOURCE_OUTLET]);
$recipes = $srcRecipes->fetchAll(PDO::FETCH_ASSOC);

$recipeMap = []; // old_recipe_id => new_recipe_id
$recipeCount = 0;
$recipeItemCount = 0;

foreach ($recipes as $r) {
    $oldVarId = $r['product_variant_id'];
    $newVarId = $variantMapping[$oldVarId] ?? null; // Can be null if it's a sub-recipe?
    
    // Some recipes might be sub-recipes without a product_variant_id
    if ($oldVarId !== null && !isset($variantMapping[$oldVarId])) {
        echo "  Skipping recipe {$r['id']} (no mapped variant).\n";
        continue;
    }

    $pdo->prepare("
        INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, yield_unit_label, version, total_hpp, is_active, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $TARGET_OUTLET,
        $newVarId,
        $r['name'],
        $r['recipe_type'],
        $r['yield_qty'],
        $r['yield_unit_id'],
        $r['yield_unit_label'],
        $r['version'],
        $r['total_hpp'],
        $r['is_active'],
        $r['notes']
    ]);
    
    $newRecipeId = (int)$pdo->lastInsertId();
    $recipeMap[$r['id']] = $newRecipeId;
    $recipeCount++;
    
    // Clone recipe items
    $srcItems = $pdo->prepare("SELECT * FROM recipe_items WHERE recipe_id = ?");
    $srcItems->execute([$r['id']]);
    $items = $srcItems->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $pdo->prepare("
            INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $newRecipeId,
            $item['item_type'],
            $item['raw_material_id'],
            null, // sub_recipe_id dihandle nanti di step 4 (update reference)
            $item['qty'],
            $item['unit_id'],
            $item['cost_per_unit'],
            $item['total_cost'],
            $item['notes']
        ]);
        $recipeItemCount++;
    }
}

echo "  {$recipeCount} recipes cloned.\n";
echo "  {$recipeItemCount} recipe items cloned.\n\n";

echo "=== STEP 4: Link Sub-Recipes ===\n";
// Update sub_recipe_id di recipe_items untuk target outlet agar merujuk ke new_recipe_id
foreach ($recipeMap as $oldId => $newId) {
    // Cari items di target outlet yg sub_recipe_id nya oldId
    // Eh tunggu, saat insert di step 3, kita set null dulu. Sebaiknya ambil dari data aslinya.
}
// Mari perbaiki: update ulang recipe_items di target outlet yg merujuk ke sub_recipe.
// Untuk memudahkan, kita update saja semua recipe_items di newRecipeId yg item_type = 'sub_recipe'
$newRecipeIdsList = implode(',', array_values($recipeMap));
if (!empty($newRecipeIdsList)) {
    // Hapus dulu yang null
    $pdo->prepare("DELETE FROM recipe_items WHERE recipe_id IN ($newRecipeIdsList) AND item_type = 'sub_recipe'")->execute();
    
    // Insert ulang khusus sub_recipe
    foreach ($recipes as $r) {
        if (!isset($recipeMap[$r['id']])) continue;
        $newRecipeId = $recipeMap[$r['id']];
        
        $srcItems = $pdo->prepare("SELECT * FROM recipe_items WHERE recipe_id = ? AND item_type = 'sub_recipe'");
        $srcItems->execute([$r['id']]);
        $items = $srcItems->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $newSubId = $recipeMap[$item['sub_recipe_id']] ?? null;
            if (!$newSubId) continue;
            
            $pdo->prepare("
                INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $newRecipeId,
                $item['item_type'],
                null,
                $newSubId,
                $item['qty'],
                $item['unit_id'],
                $item['cost_per_unit'],
                $item['total_cost'],
                $item['notes']
            ]);
        }
    }
}
echo "  Sub-recipes relinked.\n\n";

echo "=== VERIFICATION ===\n";
$rmCount = $pdo->query("SELECT COUNT(*) FROM outlet_raw_materials WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$recCount = $pdo->query("SELECT COUNT(*) FROM recipes WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$ritemCount = $pdo->query("SELECT COUNT(*) FROM recipe_items WHERE recipe_id IN (SELECT id FROM recipes WHERE outlet_id = {$TARGET_OUTLET})")->fetchColumn();

echo "Outlet {$TARGET_OUTLET}:\n";
echo "  Raw Materials: {$rmCount}\n";
echo "  Recipes: {$recCount}\n";
echo "  Recipe Items: {$ritemCount}\n";
echo "Done!\n";
