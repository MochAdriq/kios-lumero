<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$SRC_OUTLET = 5;
$DST_OUTLET = 8;

echo "=== CLONING INVENTORY MASTER DATA ===\n";

// 1. Clone Categories
echo "Cloning raw_material_categories...\n";
$oldCats = $pdo->query("SELECT * FROM raw_material_categories WHERE outlet_id = $SRC_OUTLET")->fetchAll(PDO::FETCH_ASSOC);

$stmtCat = $pdo->prepare("INSERT INTO raw_material_categories (outlet_id, name, sort_order) VALUES (?, ?, ?)");
$catMap = []; // old_id => new_id

foreach ($oldCats as $c) {
    // Check if already exists
    $exists = $pdo->query("SELECT id FROM raw_material_categories WHERE outlet_id = $DST_OUTLET AND name = " . $pdo->quote($c['name']))->fetchColumn();
    if ($exists) {
        $catMap[$c['id']] = $exists;
    } else {
        $stmtCat->execute([$DST_OUTLET, $c['name'], $c['sort_order']]);
        $catMap[$c['id']] = $pdo->lastInsertId();
    }
}
echo "Cloned/mapped " . count($catMap) . " categories.\n";

// 2. Clone Raw Materials
echo "Cloning raw_materials...\n";
$oldRMs = $pdo->query("SELECT * FROM raw_materials WHERE outlet_id = $SRC_OUTLET")->fetchAll(PDO::FETCH_ASSOC);

$stmtRM = $pdo->prepare("INSERT INTO raw_materials 
    (outlet_id, category_id, unit_id, sku, name, stock_qty, average_cost, min_stock_qty, lead_time_days, is_long_lead_time, is_active, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, NOW(), NOW())");

$rmMap = []; // old_id => new_id

foreach ($oldRMs as $rm) {
    // Generate new sku? Usually SKUs are unique per outlet, or just append '-PSKL'
    $newSku = str_replace('-KLBD', '-PSKL', $rm['sku']);
    
    // Check if exists
    $exists = $pdo->query("SELECT id FROM raw_materials WHERE outlet_id = $DST_OUTLET AND sku = " . $pdo->quote($newSku))->fetchColumn();
    if ($exists) {
        $rmMap[$rm['id']] = $exists;
    } else {
        $newCatId = $catMap[$rm['category_id']] ?? 0;
        $stmtRM->execute([
            $DST_OUTLET,
            $newCatId,
            $rm['unit_id'],
            $newSku,
            $rm['name'],
            $rm['average_cost'],
            $rm['min_stock_qty'],
            $rm['lead_time_days'],
            $rm['is_long_lead_time'],
            $rm['is_active']
        ]);
        $rmMap[$rm['id']] = $pdo->lastInsertId();
    }
}
echo "Cloned/mapped " . count($rmMap) . " raw materials.\n";

// 3. Rewire outlet_raw_materials
echo "Rewiring outlet_raw_materials...\n";
$orms = $pdo->query("SELECT id, raw_material_id FROM outlet_raw_materials WHERE outlet_id = $DST_OUTLET")->fetchAll(PDO::FETCH_ASSOC);
$updatedOrm = 0;
foreach ($orms as $orm) {
    if (isset($rmMap[$orm['raw_material_id']])) {
        $newId = $rmMap[$orm['raw_material_id']];
        // Ensure no duplicate key
        $exists = $pdo->query("SELECT id FROM outlet_raw_materials WHERE outlet_id = $DST_OUTLET AND raw_material_id = $newId")->fetchColumn();
        if ($exists && $exists != $orm['id']) {
            $pdo->exec("DELETE FROM outlet_raw_materials WHERE id = " . $orm['id']);
        } else {
            $pdo->exec("UPDATE outlet_raw_materials SET raw_material_id = $newId WHERE id = " . $orm['id']);
            $updatedOrm++;
        }
    }
}
echo "Rewired $updatedOrm outlet_raw_materials.\n";

// 4. Rewire recipe_items
echo "Rewiring recipe_items...\n";
$recipes = $pdo->query("SELECT id FROM recipes WHERE outlet_id = $DST_OUTLET")->fetchAll(PDO::FETCH_COLUMN);
if ($recipes) {
    $recipeIds = implode(',', $recipes);
    $ritems = $pdo->query("SELECT id, raw_material_id FROM recipe_items WHERE item_type = 'raw_material' AND recipe_id IN ($recipeIds)")->fetchAll(PDO::FETCH_ASSOC);
    $updatedRi = 0;
    foreach ($ritems as $ri) {
        if (isset($rmMap[$ri['raw_material_id']])) {
            $newId = $rmMap[$ri['raw_material_id']];
            $pdo->exec("UPDATE recipe_items SET raw_material_id = $newId WHERE id = " . $ri['id']);
            $updatedRi++;
        }
    }
    echo "Rewired $updatedRi recipe_items.\n";
} else {
    echo "No recipes found for outlet $DST_OUTLET.\n";
}

echo "=== SUCCESS ===\n";
