<?php
/**
 * SETUP OUTLET: D'Celup Pasekon Sukalarang
 * =========================================
 * Step 1: Buat Outlet Baru
 * Step 2: Clone Product Categories dari Kalibunder (ID: 5)
 * Step 3: Clone Products + Variants dari Kalibunder
 * Step 4: Clone Recipes dari Kalibunder
 * Step 5: Buat User Admin + Kasir untuk outlet baru
 *
 * NOTE: Modal & Pembelian akan di-handle di script terpisah
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

$SOURCE_OUTLET_ID = 5; // Kalibunder

// ============================================================
// STEP 1: Buat Outlet Baru
// ============================================================
echo "=== STEP 1: BUAT OUTLET BARU ===\n";

// Cek apakah sudah ada
$existing = $pdo->prepare("SELECT id FROM outlets WHERE name LIKE ?");
$existing->execute(["D'Celup Pasekon Sukalarang%"]);
$existingId = $existing->fetchColumn();

if ($existingId) {
    echo "Outlet sudah ada dengan ID: $existingId — skip\n";
    $NEW_OUTLET_ID = $existingId;
} else {
    // Ambil company_id dari outlet Kalibunder
    $company = $pdo->query("SELECT company_id FROM outlets WHERE id = $SOURCE_OUTLET_ID")->fetchColumn();
    
    // Buat outlet kode unik
    $outletCode = 'DCL-PSK-' . date('Ymd');
    $slug = 'dcelup-pasekon-sukalarang';
    
    $lat = -6.88865967; // Gunakan koordinat D'Celup Pasekon (induk)
    $lng = 107.00026268;
    
    $stmt = $pdo->prepare("INSERT INTO outlets 
        (company_id, outlet_code, slug, is_hq, name, type, address, phone, is_active, closing_hour, latitude, longitude, created_at, updated_at)
        VALUES (?, ?, ?, 0, ?, 'franchise', 'Pasekon, Sukalarang, Sukabumi', NULL, 1, '21:00:00', ?, ?, NOW(), NOW())");
    $stmt->execute([$company, $outletCode, $slug, "D'Celup Pasekon Sukalarang", $lat, $lng]);
    $NEW_OUTLET_ID = $pdo->lastInsertId();
    echo "✅ Outlet baru dibuat: ID = $NEW_OUTLET_ID (D'Celup Pasekon Sukalarang)\n";
}

echo "\n=== STEP 2: CLONE PRODUCT CATEGORIES (dari Kalibunder → Outlet Baru) ===\n";
$catMap = []; // old_cat_id => new_cat_id

$srcCats = $pdo->prepare("SELECT * FROM product_categories WHERE outlet_id = ?");
$srcCats->execute([$SOURCE_OUTLET_ID]);
$cats = $srcCats->fetchAll(PDO::FETCH_ASSOC);

foreach ($cats as $cat) {
    // Cek apakah sudah pernah di-clone ke outlet baru
    $checkStmt = $pdo->prepare("SELECT id FROM product_categories WHERE outlet_id = ? AND name = ?");
    $checkStmt->execute([$NEW_OUTLET_ID, $cat['name']]);
    $existingCat = $checkStmt->fetchColumn();
    
    if ($existingCat) {
        $catMap[$cat['id']] = $existingCat;
        echo "  [SKIP] Kategori '{$cat['name']}' sudah ada (ID: $existingCat)\n";
    } else {
        $insStmt = $pdo->prepare("INSERT INTO product_categories 
            (outlet_id, name, slug, sort_order, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $insStmt->execute([$NEW_OUTLET_ID, $cat['name'], $cat['slug'], $cat['sort_order'], $cat['is_active']]);
        $newCatId = $pdo->lastInsertId();
        $catMap[$cat['id']] = $newCatId;
        echo "  ✅ Kategori '{$cat['name']}' → ID: $newCatId\n";
    }
}

echo "\n=== STEP 3: CLONE PRODUCTS + VARIANTS (dari Kalibunder → Outlet Baru) ===\n";
$productMap = []; // old_product_id => new_product_id

$srcProducts = $pdo->prepare("SELECT * FROM products WHERE outlet_id = ?");
$srcProducts->execute([$SOURCE_OUTLET_ID]);
$products = $srcProducts->fetchAll(PDO::FETCH_ASSOC);

$totalProducts = 0;
$totalVariants = 0;

foreach ($products as $prod) {
    // Cek apakah sudah ada di outlet baru
    $checkStmt = $pdo->prepare("SELECT id FROM products WHERE outlet_id = ? AND name = ?");
    $checkStmt->execute([$NEW_OUTLET_ID, $prod['name']]);
    $existingProd = $checkStmt->fetchColumn();
    
    if ($existingProd) {
        $productMap[$prod['id']] = $existingProd;
        echo "  [SKIP] Produk '{$prod['name']}' sudah ada\n";
        continue;
    }
    
    // Map kategori ke kategori baru
    $newCatId = $catMap[$prod['category_id']] ?? null;
    
    $insStmt = $pdo->prepare("INSERT INTO products 
        (outlet_id, category_id, name, slug, description, image, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $insStmt->execute([
        $NEW_OUTLET_ID,
        $newCatId,
        $prod['name'],
        $prod['slug'],
        $prod['description'],
        $prod['image'],
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
        $insVar = $pdo->prepare("INSERT INTO product_variants 
            (product_id, name, sku, selling_price, hpp_price, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $insVar->execute([
            $newProdId,
            $v['name'],
            $v['sku'] . '-PSKL',
            $v['selling_price'],
            $v['hpp_price'],
            $v['is_active']
        ]);
        $totalVariants++;
    }
    echo "  ✅ '{$prod['name']}' → ID: $newProdId (" . count($variants) . " varian)\n";
}

echo "\n  Total: $totalProducts produk, $totalVariants varian di-clone\n";

echo "\n=== STEP 4: CLONE RECIPES (dari Kalibunder → Outlet Baru) ===\n";
$recipeMap = []; // old_recipe_id => new_recipe_id
$totalRecipes = 0;

$srcRecipes = $pdo->prepare("SELECT * FROM recipes WHERE outlet_id = ?");
$srcRecipes->execute([$SOURCE_OUTLET_ID]);
$recipes = $srcRecipes->fetchAll(PDO::FETCH_ASSOC);

foreach ($recipes as $rec) {
    // Cek apakah sudah ada
    $checkStmt = $pdo->prepare("SELECT id FROM recipes WHERE outlet_id = ? AND name = ?");
    $checkStmt->execute([$NEW_OUTLET_ID, $rec['name']]);
    $existingRec = $checkStmt->fetchColumn();
    
    if ($existingRec) {
        $recipeMap[$rec['id']] = $existingRec;
        continue;
    }
    
    // Cari product_variant_id baru jika ada mapping
    $newVariantId = null;
    if ($rec['product_variant_id']) {
        // Cari dari product yang sudah di-clone
        $newVariantId = $rec['product_variant_id']; // default sama, nanti di-remap
        foreach ($productMap as $oldProdId => $newProdId) {
            $checkVar = $pdo->prepare("SELECT pv_new.id FROM product_variants pv_new 
                JOIN product_variants pv_old ON pv_old.name = pv_new.name
                WHERE pv_old.id = ? AND pv_new.product_id = ?");
            $checkVar->execute([$rec['product_variant_id'], $newProdId]);
            $mappedVariantId = $checkVar->fetchColumn();
            if ($mappedVariantId) {
                $newVariantId = $mappedVariantId;
                break;
            }
        }
    }
    
    $insRec = $pdo->prepare("INSERT INTO recipes 
        (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, notes, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $insRec->execute([
        $NEW_OUTLET_ID,
        $newVariantId,
        $rec['name'],
        $rec['recipe_type'],
        $rec['yield_qty'],
        $rec['yield_unit_id'],
        $rec['notes'] ?? null,
        $rec['is_active']
    ]);
    $newRecipeId = $pdo->lastInsertId();
    $recipeMap[$rec['id']] = $newRecipeId;
    
    // Clone recipe items
    $srcItems = $pdo->prepare("SELECT * FROM recipe_items WHERE recipe_id = ?");
    $srcItems->execute([$rec['id']]);
    $items = $srcItems->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $insItem = $pdo->prepare("INSERT INTO recipe_items 
            (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insItem->execute([
            $newRecipeId,
            $item['item_type'],
            $item['raw_material_id'],
            $item['sub_recipe_id'],
            $item['qty'],
            $item['unit_id'],
            $item['cost_per_unit'],
            $item['total_cost']
        ]);
    }
    $totalRecipes++;
}

echo "  ✅ Total " . $totalRecipes . " resep di-clone ke Outlet $NEW_OUTLET_ID\n";

echo "\n=== STEP 5: BUAT USER ADMIN & KASIR ===\n";

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
        echo "  [SKIP] User '{$u['username']}' sudah ada (ID: $existingUser)\n";
        continue;
    }
    
    $insUser = $pdo->prepare("INSERT INTO users 
        (outlet_id, role_id, name, username, email, password, daily_salary, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())");
    $insUser->execute([$NEW_OUTLET_ID, $u['role_id'], $u['name'], $u['username'], $u['email'], $defaultPass]);
    $newUserId = $pdo->lastInsertId();
    echo "  ✅ User '{$u['username']}' dibuat → ID: $newUserId (role_id: {$u['role_id']})\n";
}

echo "\n=== RINGKASAN ===\n";
echo "🏪 Outlet ID Baru : $NEW_OUTLET_ID\n";
echo "🏪 Nama Outlet    : D'Celup Pasekon Sukalarang\n";
echo "📦 Kategori       : " . count($catMap) . " di-clone dari Kalibunder\n";
echo "🍗 Produk         : $totalProducts produk + $totalVariants varian\n";
echo "📋 Resep          : $totalRecipes resep\n";
echo "👤 Akun Admin     : admin_pasekon / dcelup2026\n";
echo "👤 Akun Kasir     : kasir_pasekon / dcelup2026\n";
echo "\n✅ SELESAI! Step berikutnya: Migrasi Modal & Pembelian\n";
