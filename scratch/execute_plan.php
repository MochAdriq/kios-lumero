<?php
session_start();
$_SESSION['user'] = ['id' => 1];
$_SESSION['outlet_id'] = 8;

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $outlet_id = 8;
    
    $pdo->beginTransaction();

    // 1. Add Kantong Kresek
    echo "Adding Kantong Kresek...\n";
    $kresekId = $pdo->query("SELECT id FROM raw_materials WHERE outlet_id = $outlet_id AND name = 'Kantong Kresek' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$kresekId) {
        $rmCatId = $pdo->query("SELECT id FROM raw_material_categories WHERE outlet_id = $outlet_id LIMIT 1")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO raw_materials (outlet_id, category_id, name, unit_id, average_cost, is_active, updated_at, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $stmt->execute([$outlet_id, $rmCatId, 'Kantong Kresek', 4, 200]);
        $kresekId = $pdo->lastInsertId();
    }
    echo "Kresek ID: $kresekId\n";

    // 2. Find Category for Kentang
    $catStmt = $pdo->prepare("SELECT id FROM product_categories WHERE outlet_id = ? AND name LIKE '%Kentang%' LIMIT 1");
    $catStmt->execute([$outlet_id]);
    $catId = $catStmt->fetchColumn();
    if (!$catId) {
        $pdo->prepare("INSERT INTO product_categories (outlet_id, name, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())")->execute([$outlet_id, 'Kentang']);
        $catId = $pdo->lastInsertId();
    }
    
    // 3. Find Ayam Crispy product
    $ayamProdId = $pdo->prepare("SELECT id FROM products WHERE outlet_id = ? AND name = 'Ayam Crispy' LIMIT 1");
    $ayamProdId->execute([$outlet_id]);
    $ayamProdId = $ayamProdId->fetchColumn();

    // Ayam Crispy Variants
    $ayamVars = [
        ['Dada Original Tanpa Nasi', 12000, '[Base] Dada Tanpa Nasi'],
        ['Paha Atas Original Tanpa Nasi', 12000, '[Base] Paha Atas Tanpa Nasi'],
        ['Paha Bawah Original Tanpa Nasi', 10000, '[Base] Paha Bawah Tanpa Nasi'],
        ['Sayap Original Tanpa Nasi', 8000, '[Base] Sayap Tanpa Nasi'],
        ['Dada Original + Nasi', 15000, '[Base] Dada + Nasi'],
        ['Paha Atas Original + Nasi', 15000, '[Base] Paha Atas + Nasi'],
        ['Paha Bawah Original + Nasi', 13000, '[Base] Paha Bawah + Nasi'],
        ['Sayap Original + Nasi', 11000, '[Base] Sayap + Nasi'],
    ];

    foreach ($ayamVars as $v) {
        $check = $pdo->prepare("SELECT id FROM product_variants WHERE outlet_id = ? AND variant_name = ? LIMIT 1");
        $check->execute([$outlet_id, $v[0]]);
        if ($check->fetchColumn()) continue; // already exists

        // Find Base recipe ID
        $baseId = $pdo->prepare("SELECT id, yield_unit_id, total_hpp FROM recipes WHERE outlet_id = ? AND name = ? AND recipe_type = 'sub_recipe' LIMIT 1");
        $baseId->execute([$outlet_id, $v[2]]);
        $baseData = $baseId->fetch(PDO::FETCH_ASSOC);
        if (!$baseData) continue;
        
        // Insert Variant
        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, hpp, selling_price, is_default, is_active) VALUES (?, ?, ?, ?, ?, ?, 0, 1)");
        $sku = 'DC-AYM-' . str_replace(' ', '', strtoupper(substr($v[0], 0, 8))) . rand(100,999) . '-PSKL';
        $stmt->execute([$ayamProdId, $outlet_id, $sku, $v[0], $baseData['total_hpp'] + 200, $v[1]]);
        $varId = $pdo->lastInsertId();

        // Create Recipe for Variant
        $stmt = $pdo->prepare("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, version, total_hpp, is_active, created_at, updated_at) VALUES (?, ?, ?, 'variant', 1, 4, 1, ?, 1, NOW(), NOW())");
        $stmt->execute([$outlet_id, $varId, 'Resep ' . $v[0], $baseData['total_hpp'] + 200]);
        $recipeId = $pdo->lastInsertId();

        // Add base to recipe items
        $stmt = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost) VALUES (?, 'sub_recipe', ?, 1, ?, ?, ?)");
        $stmt->execute([$recipeId, $baseData['id'], $baseData['yield_unit_id'], $baseData['total_hpp'], $baseData['total_hpp']]);
    }
    echo "Ayam variants added.\n";

    // 4. Create Potato Crispy Product (Look by SKU)
    $potatoProdId = $pdo->prepare("SELECT id FROM products WHERE outlet_id = ? AND sku = 'DC-KTG-PSKL' LIMIT 1");
    $potatoProdId->execute([$outlet_id]);
    $potatoProdId = $potatoProdId->fetchColumn();
    if (!$potatoProdId) {
        $stmt = $pdo->prepare("INSERT INTO products (outlet_id, category_id, sku, name, product_type, is_active) VALUES (?, ?, ?, ?, 'variant_parent', 1)");
        $stmt->execute([$outlet_id, $catId, 'DC-KTG-PSKL', 'Potato Crispy']);
        $potatoProdId = $pdo->lastInsertId();
    }

    $potatoVars = [
        ['Original Reguler', 8000, '[Base] Potato Crispy Reguler'],
        ['Original Large', 9000, '[Base] Potato Crispy Large'],
    ];

    foreach ($potatoVars as $v) {
        $check = $pdo->prepare("SELECT id FROM product_variants WHERE outlet_id = ? AND variant_name = ? LIMIT 1");
        $check->execute([$outlet_id, $v[0]]);
        if ($check->fetchColumn()) continue; // already exists

        $baseId = $pdo->prepare("SELECT id, yield_unit_id, total_hpp FROM recipes WHERE outlet_id = ? AND name = ? AND recipe_type = 'sub_recipe' LIMIT 1");
        $baseId->execute([$outlet_id, $v[2]]);
        $baseData = $baseId->fetch(PDO::FETCH_ASSOC);
        if (!$baseData) continue;

        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, hpp, selling_price, is_default, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $sku = 'DC-KTG-' . str_replace(' ', '', strtoupper(substr($v[0], 0, 8))) . rand(100,999) . '-PSKL';
        $isDef = ($v[0] == 'Original Reguler') ? 1 : 0;
        $stmt->execute([$potatoProdId, $outlet_id, $sku, $v[0], $baseData['total_hpp'] + 200, $v[1], $isDef]);
        $varId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, version, total_hpp, is_active, created_at, updated_at) VALUES (?, ?, ?, 'variant', 1, 4, 1, ?, 1, NOW(), NOW())");
        $stmt->execute([$outlet_id, $varId, 'Resep ' . $v[0], $baseData['total_hpp'] + 200]);
        $recipeId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost) VALUES (?, 'sub_recipe', ?, 1, ?, ?, ?)");
        $stmt->execute([$recipeId, $baseData['id'], $baseData['yield_unit_id'], $baseData['total_hpp'], $baseData['total_hpp']]);
    }
    echo "Potato variants added.\n";

    // 5. Add Kresek to ALL active variant recipes
    echo "Injecting Kresek to all recipes...\n";
    $recipes = $pdo->prepare("SELECT id FROM recipes WHERE outlet_id = ? AND recipe_type = 'variant' AND is_active = 1");
    $recipes->execute([$outlet_id]);
    $recipes = $recipes->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id, cost_per_unit, total_cost) VALUES (?, 'raw_material', ?, 1, 4, 200, 200)");
    $kresekAdded = 0;
    foreach ($recipes as $rId) {
        // prevent duplicate
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

    // 6. Recalculate HPP
    require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
    $rm = new RecipeModel($pdo);
    $rm->recalculateAll(8);
    echo "Recalculation complete.\n";
    
} catch (Exception $e) {
    if(isset($pdo)) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage();
}
