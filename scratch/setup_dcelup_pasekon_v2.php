<?php
/**
 * SETUP OUTLET: D'Celup Pasekon Sukalarang
 * =========================================
 * Outlet ID baru: 8 (sudah dibuat di run sebelumnya)
 * Step 1: Clone Product Categories
 * Step 2: Clone Products + Variants
 * Step 3: Clone Recipes + Recipe Items
 * Step 4: Buat User Admin + Kasir
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

$SOURCE_OUTLET_ID = 5;   // Kalibunder
$NEW_OUTLET_ID    = 8;   // D'Celup Pasekon Sukalarang

echo "Outlet ID baru: $NEW_OUTLET_ID\n\n";

// ============================================================
// STEP 1: Clone Product Categories
// ============================================================
echo "=== STEP 1: CLONE PRODUCT CATEGORIES ===\n";
$catMap = [];

// Bersihkan kategori yang mungkin terduplikat dari run sebelumnya
$pdo->exec("DELETE FROM product_categories WHERE outlet_id = $NEW_OUTLET_ID");
echo "  Cleaned existing categories for outlet $NEW_OUTLET_ID\n";

$srcCats = $pdo->prepare("SELECT * FROM product_categories WHERE outlet_id = ? ORDER BY sort_order");
$srcCats->execute([$SOURCE_OUTLET_ID]);
$cats = $srcCats->fetchAll(PDO::FETCH_ASSOC);

foreach ($cats as $cat) {
    $insStmt = $pdo->prepare("INSERT INTO product_categories 
        (outlet_id, name, slug, sort_order, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $insStmt->execute([$NEW_OUTLET_ID, $cat['name'], $cat['slug'], $cat['sort_order'], $cat['is_active']]);
    $newCatId = $pdo->lastInsertId();
    $catMap[$cat['id']] = $newCatId;
    echo "  ✅ '{$cat['name']}' → ID: $newCatId\n";
}

// ============================================================
// STEP 2: Clone Products + Variants
// ============================================================
echo "\n=== STEP 2: CLONE PRODUCTS + VARIANTS ===\n";
$productMap = [];
$variantMap = [];

// Bersihkan produk yang sudah ada dari run sebelumnya
$existingProdIds = $pdo->query("SELECT id FROM products WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingProdIds) {
    $idList = implode(',', $existingProdIds);
    $pdo->exec("DELETE FROM product_variants WHERE product_id IN ($idList)");
    $pdo->exec("DELETE FROM products WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  Cleaned existing products/variants for outlet $NEW_OUTLET_ID\n";
}

$srcProducts = $pdo->prepare("SELECT * FROM products WHERE outlet_id = ? ORDER BY id");
$srcProducts->execute([$SOURCE_OUTLET_ID]);
$products = $srcProducts->fetchAll(PDO::FETCH_ASSOC);

$totalProducts = 0;
$totalVariants = 0;

foreach ($products as $prod) {
    $newCatId = $catMap[$prod['category_id']] ?? null;
    $newSku = $prod['sku'] . '-PSKL';
    
    $insStmt = $pdo->prepare("INSERT INTO products 
        (category_id, outlet_id, sku, name, description, image, product_type, unit_name,
         base_hpp, base_price, margin_amount, margin_percent, lifetime_qty_sold, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())");
    $insStmt->execute([
        $newCatId,
        $NEW_OUTLET_ID,
        $newSku,
        $prod['name'],
        $prod['description'],
        $prod['image'],
        $prod['product_type'],
        $prod['unit_name'],
        $prod['base_hpp'],
        $prod['base_price'],
        $prod['margin_amount'],
        $prod['margin_percent'],
        $prod['is_active']
    ]);
    $newProdId = $pdo->lastInsertId();
    $productMap[$prod['id']] = $newProdId;
    $totalProducts++;
    
    // Clone variants
    $srcVariants = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $srcVariants->execute([$prod['id']]);
    $variants = $srcVariants->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($variants as $v) {
        $newVarSku = $v['sku'] . '-PSKL';
        $insVar = $pdo->prepare("INSERT INTO product_variants 
            (product_id, outlet_id, sku, variant_name, image, hpp, selling_price,
             margin_amount, margin_percent, is_default, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $insVar->execute([
            $newProdId,
            $NEW_OUTLET_ID,
            $newVarSku,
            $v['variant_name'],
            $v['image'],
            $v['hpp'],
            $v['selling_price'],
            $v['margin_amount'],
            $v['margin_percent'],
            $v['is_default'],
            $v['is_active']
        ]);
        $newVarId = $pdo->lastInsertId();
        $variantMap[$v['id']] = $newVarId;
        $totalVariants++;
    }
    echo "  ✅ '{$prod['name']}' → ID: $newProdId (" . count($variants) . " varian)\n";
}

echo "\n  Total: $totalProducts produk, $totalVariants varian\n";

// ============================================================
// STEP 3: Clone Recipes + Recipe Items
// ============================================================
echo "\n=== STEP 3: CLONE RECIPES ===\n";
$recipeMap = [];
$totalRecipes = 0;
$totalRecipeItems = 0;

// Bersihkan resep yang sudah ada dari run sebelumnya
$existingRecIds = $pdo->query("SELECT id FROM recipes WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingRecIds) {
    $idList = implode(',', $existingRecIds);
    $pdo->exec("DELETE FROM recipe_items WHERE recipe_id IN ($idList)");
    $pdo->exec("DELETE FROM recipes WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  Cleaned existing recipes for outlet $NEW_OUTLET_ID\n";
}

$srcRecipes = $pdo->prepare("SELECT * FROM recipes WHERE outlet_id = ? ORDER BY id");
$srcRecipes->execute([$SOURCE_OUTLET_ID]);
$recipes = $srcRecipes->fetchAll(PDO::FETCH_ASSOC);

// First pass: buat semua resep dulu (untuk mapping sub_recipe referensi)
foreach ($recipes as $rec) {
    // Cari mapped variant ID jika ada
    $newVariantId = null;
    if ($rec['product_variant_id']) {
        $newVariantId = $variantMap[$rec['product_variant_id']] ?? null;
    }
    
    $insRec = $pdo->prepare("INSERT INTO recipes 
        (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, yield_unit_label,
         version, total_hpp, is_active, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $insRec->execute([
        $NEW_OUTLET_ID,
        $newVariantId,
        $rec['name'],
        $rec['recipe_type'],
        $rec['yield_qty'],
        $rec['yield_unit_id'],
        $rec['yield_unit_label'],
        $rec['version'] ?? 1,
        $rec['total_hpp'],
        $rec['is_active'],
        $rec['notes']
    ]);
    $newRecipeId = $pdo->lastInsertId();
    $recipeMap[$rec['id']] = $newRecipeId;
    $totalRecipes++;
}

// Second pass: clone recipe items (setelah semua recipe_id baru sudah ada untuk sub_recipe mapping)
foreach ($recipes as $rec) {
    $newRecipeId = $recipeMap[$rec['id']];
    
    $srcItems = $pdo->prepare("SELECT * FROM recipe_items WHERE recipe_id = ?");
    $srcItems->execute([$rec['id']]);
    $items = $srcItems->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        // Jika item_type adalah sub_recipe, map ke sub_recipe baru
        $newSubRecipeId = null;
        if ($item['item_type'] === 'sub_recipe' && $item['sub_recipe_id']) {
            $newSubRecipeId = $recipeMap[$item['sub_recipe_id']] ?? $item['sub_recipe_id'];
        }
        
        $insItem = $pdo->prepare("INSERT INTO recipe_items 
            (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insItem->execute([
            $newRecipeId,
            $item['item_type'],
            $item['raw_material_id'],
            $newSubRecipeId,
            $item['qty'],
            $item['unit_id'],
            $item['cost_per_unit'],
            $item['total_cost']
        ]);
        $totalRecipeItems++;
    }
}

echo "  ✅ Total $totalRecipes resep + $totalRecipeItems recipe items di-clone\n";

// ============================================================
// STEP 4: Buat User Admin & Kasir
// ============================================================
echo "\n=== STEP 4: BUAT USER ADMIN & KASIR ===\n";

$defaultPass = password_hash('dcelup2026', PASSWORD_DEFAULT);

$usersToCreate = [
    [
        'name'     => "Admin D'Celup Pasekon",
        'username' => 'admin_pasekon',
        'email'    => 'admin.pasekon@lokapedia.id',
        'role_id'  => 2, // Administrator
    ],
    [
        'name'     => "Kasir D'Celup Pasekon",
        'username' => 'kasir_pasekon',
        'email'    => 'kasir.pasekon@lokapedia.id',
        'role_id'  => 3, // Cashier
    ],
];

foreach ($usersToCreate as $u) {
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkUser->execute([$u['username']]);
    $existingUser = $checkUser->fetchColumn();
    
    if ($existingUser) {
        // Update outlet_id jika user sudah ada
        $pdo->prepare("UPDATE users SET outlet_id = ? WHERE id = ?")->execute([$NEW_OUTLET_ID, $existingUser]);
        echo "  [UPDATE] User '{$u['username']}' outlet_id → $NEW_OUTLET_ID\n";
        continue;
    }
    
    $insUser = $pdo->prepare("INSERT INTO users 
        (outlet_id, role_id, name, username, email, password, daily_salary, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())");
    $insUser->execute([$NEW_OUTLET_ID, $u['role_id'], $u['name'], $u['username'], $u['email'], $defaultPass]);
    $newUserId = $pdo->lastInsertId();
    echo "  ✅ '{$u['username']}' → ID: $newUserId (role_id: {$u['role_id']})\n";
}

// ============================================================
// RINGKASAN
// ============================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ SETUP OUTLET SELESAI!\n";
echo str_repeat("=", 50) . "\n";
echo "🏪 Outlet ID     : $NEW_OUTLET_ID\n";
echo "🏪 Nama          : D'Celup Pasekon Sukalarang\n";
echo "📂 Kategori      : " . count($catMap) . "\n";
echo "🍗 Produk        : $totalProducts produk, $totalVariants varian\n";
echo "📋 Resep         : $totalRecipes resep, $totalRecipeItems items\n";
echo "👤 Admin         : admin_pasekon / dcelup2026\n";
echo "👤 Kasir         : kasir_pasekon / dcelup2026\n";
echo "\n🔔 LANGKAH BERIKUTNYA: Migrasikan Modal & Pembelian dari SQL D'Celup\n";
