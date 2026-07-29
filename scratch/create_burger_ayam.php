<?php
$host = 'srv1864.hstgr.io';
$db = 'u643003184_kios_lumero';
$user = 'u643003184_kios_lumero';
$pass = 'Lawmotion1!@#';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$outletId = 5; // Kalibunder

try {
    $pdo->beginTransaction();

    // 1. Create Raw Material "Daging Patty Ayam" if not exists
    $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = 'Daging Patty Ayam' AND (outlet_id = ? OR outlet_id = 0 OR outlet_id IS NULL) LIMIT 1");
    $stmt->execute([$outletId]);
    $ayamId = $stmt->fetchColumn();

    if (!$ayamId) {
        $stmt = $pdo->prepare("INSERT INTO raw_materials (outlet_id, category_id, unit_id, name, sku) VALUES (?, 110009, 1, 'Daging Patty Ayam', 'RM-PATTY-AYAM')");
        $stmt->execute([$outletId]);
        $ayamId = $pdo->lastInsertId();
    }

    // 2. Clone the Burger Beef product as a base for Burger Ayam
    // Get Burger Beef ID 166 (Outlet 5)
    $stmt = $pdo->query("SELECT * FROM products WHERE id = 166");
    $beefProd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get Variant
    $stmt = $pdo->query("SELECT * FROM product_variants WHERE product_id = 166 LIMIT 1");
    $beefVar = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get Recipe
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE product_variant_id = ? LIMIT 1");
    $stmt->execute([$beefVar['id']]);
    $beefRecipe = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get Recipe Items
    $stmt = $pdo->prepare("SELECT * FROM recipe_items WHERE recipe_id = ?");
    $stmt->execute([$beefRecipe['id']]);
    $beefItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Insert Product
    $stmt = $pdo->prepare("INSERT INTO products (category_id, outlet_id, sku, name, description, image, product_type, unit_name, base_hpp, base_price, margin_amount, margin_percent, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt->execute([
        $beefProd['category_id'],
        $outletId,
        $beefProd['sku'] . '-AYAM', // Make SKU unique
        'Burger Ayam',
        'Burger dengan Daging Patty Ayam',
        $beefProd['image'],
        $beefProd['product_type'],
        $beefProd['unit_name'],
        $beefProd['base_hpp'],
        $beefProd['base_price'],
        $beefProd['margin_amount'],
        $beefProd['margin_percent']
    ]);
    $newProdId = $pdo->lastInsertId();

    // 4. Insert Variant
    $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, image, hpp, selling_price, margin_amount, margin_percent, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt->execute([
        $newProdId,
        $outletId,
        $beefVar['sku'] . '-AYAM',
        $beefVar['variant_name'],
        $beefVar['image'],
        $beefVar['hpp'],
        $beefVar['selling_price'],
        $beefVar['margin_amount'],
        $beefVar['margin_percent']
    ]);
    $newVarId = $pdo->lastInsertId();

    // 5. Insert Recipe
    $stmt = $pdo->prepare("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at) VALUES (?, ?, ?, 'final', 1, 4, 1, NOW(), NOW())");
    $stmt->execute([
        $outletId,
        $newVarId,
        'Burger Ayam - ' . ($beefVar['variant_name'] ?: 'Default')
    ]);
    $newRecipeId = $pdo->lastInsertId();

    // 6. Insert Recipe Items
    foreach ($beefItems as $item) {
        // Swap Patty Sapi (346) with Patty Ayam ($ayamId)
        $rawMaterialId = $item['raw_material_id'];
        if ($rawMaterialId == 346) {
            $rawMaterialId = $ayamId;
        }

        $stmt = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $newRecipeId,
            $item['item_type'],
            $rawMaterialId,
            $item['sub_recipe_id'],
            $item['qty'],
            $item['unit_id']
        ]);
    }

    $pdo->commit();
    echo "Burger Ayam successfully created!";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed: " . $e->getMessage();
}
