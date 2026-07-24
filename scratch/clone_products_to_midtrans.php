<?php
/**
 * Clone Products, Categories, Variants from Outlet Kalibunder (5) -> Outlet Midtrans (7)
 * Then set all variant stocks to 5
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

$SOURCE_OUTLET = 5; // Kalibunder
$TARGET_OUTLET = 7; // Midtrans

echo "=== Cloning Outlet {$SOURCE_OUTLET} -> Outlet {$TARGET_OUTLET} ===\n\n";

// ──── STEP 1: Clone Categories ────
echo "--- STEP 1: Clone Categories ---\n";

// Hapus kategori lama di target (jika ada)
$pdo->prepare("DELETE FROM product_categories WHERE outlet_id = ?")->execute([$TARGET_OUTLET]);

$srcCats = $pdo->prepare("SELECT * FROM product_categories WHERE outlet_id = ? ORDER BY id");
$srcCats->execute([$SOURCE_OUTLET]);
$categories = $srcCats->fetchAll(PDO::FETCH_ASSOC);

$catMap = []; // old_cat_id => new_cat_id

foreach ($categories as $cat) {
    $pdo->prepare("
        INSERT INTO product_categories (outlet_id, name, slug, sort_order, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $TARGET_OUTLET,
        $cat['name'],
        $cat['slug'] . '-mt',
        $cat['sort_order'],
        $cat['is_active'],
    ]);
    $newCatId = (int)$pdo->lastInsertId();
    $catMap[(int)$cat['id']] = $newCatId;
    echo "  Category '{$cat['name']}': {$cat['id']} -> {$newCatId}\n";
}
echo "  Total: " . count($catMap) . " categories cloned.\n\n";

// ──── STEP 2: Clone Products ────
echo "--- STEP 2: Clone Products ---\n";

// Hapus variants lama di target (cascade)
$oldProds = $pdo->query("SELECT id FROM products WHERE outlet_id = {$TARGET_OUTLET}")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($oldProds)) {
    $ids = implode(',', $oldProds);
    $pdo->exec("DELETE FROM product_variants WHERE product_id IN ({$ids})");
    $pdo->exec("DELETE FROM products WHERE outlet_id = {$TARGET_OUTLET}");
    echo "  Cleaned up old products & variants in outlet {$TARGET_OUTLET}\n";
}

$srcProds = $pdo->prepare("SELECT * FROM products WHERE outlet_id = ? ORDER BY id");
$srcProds->execute([$SOURCE_OUTLET]);
$products = $srcProds->fetchAll(PDO::FETCH_ASSOC);

$prodMap = []; // old_product_id => new_product_id

foreach ($products as $p) {
    $newCatId = $catMap[(int)$p['category_id']] ?? (int)$p['category_id'];
    
    $pdo->prepare("
        INSERT INTO products (category_id, outlet_id, sku, name, description, image, product_type, unit_name, base_hpp, base_price, margin_amount, margin_percent, lifetime_qty_sold, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $newCatId,
        $TARGET_OUTLET,
        $p['sku'] . '-MT',
        $p['name'],
        $p['description'],
        $p['image'],  // gambar sama persis
        $p['product_type'],
        $p['unit_name'],
        $p['base_hpp'],
        $p['base_price'],
        $p['margin_amount'],
        $p['margin_percent'],
        0, // reset sold count
        $p['is_active'],
    ]);
    $newProdId = (int)$pdo->lastInsertId();
    $prodMap[(int)$p['id']] = $newProdId;
    echo "  Product '{$p['name']}': {$p['id']} -> {$newProdId}\n";
}
echo "  Total: " . count($prodMap) . " products cloned.\n\n";

// ──── STEP 3: Clone Variants ────
echo "--- STEP 3: Clone Variants ---\n";

$variantCount = 0;
$newVariantIds = [];

foreach ($prodMap as $oldProdId => $newProdId) {
    $srcVars = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id");
    $srcVars->execute([$oldProdId]);
    $variants = $srcVars->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($variants as $v) {
        $pdo->prepare("
            INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, image, hpp, selling_price, margin_amount, margin_percent, is_default, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ")->execute([
            $newProdId,
            $TARGET_OUTLET,
            $v['sku'] . '-MT',
            $v['variant_name'],
            $v['image'],  // gambar sama persis
            $v['hpp'],
            $v['selling_price'],
            $v['margin_amount'],
            $v['margin_percent'],
            $v['is_default'],
            $v['is_active'],
        ]);
        $newVarId = (int)$pdo->lastInsertId();
        $newVariantIds[] = $newVarId;
        $variantCount++;
    }
}
echo "  Total: {$variantCount} variants cloned.\n\n";

// ──── STEP 4: Set Stock = 5 for ALL variants ────
echo "--- STEP 4: Set Stock = 5 for all variants ---\n";

$today = date('Y-m-d');

// Hapus stock lama untuk outlet midtrans hari ini
$pdo->prepare("DELETE FROM daily_product_stocks WHERE outlet_id = ? AND business_date = ?")->execute([$TARGET_OUTLET, $today]);

$stockInserted = 0;
foreach ($newVariantIds as $varId) {
    $pdo->prepare("
        INSERT INTO daily_product_stocks (outlet_id, business_date, product_variant_id, opening_qty, produced_qty, sold_qty, wasted_qty, closing_qty, status, created_at, updated_at)
        VALUES (?, ?, ?, 5, 0, 0, 0, 5, 'available', NOW(), NOW())
    ")->execute([$TARGET_OUTLET, $today, $varId]);
    $stockInserted++;
}
echo "  {$stockInserted} stock records created (qty=5 each) for date {$today}\n\n";

// ──── VERIFIKASI ────
echo "=== VERIFIKASI ===\n";
$catCount = $pdo->query("SELECT COUNT(*) FROM product_categories WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$prodCount = $pdo->query("SELECT COUNT(*) FROM products WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$varCount = $pdo->query("SELECT COUNT(*) FROM product_variants WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$stockCount = $pdo->query("SELECT COUNT(*) FROM daily_product_stocks WHERE outlet_id = {$TARGET_OUTLET} AND business_date = '{$today}'")->fetchColumn();

echo "  Outlet Midtrans (ID: {$TARGET_OUTLET}):\n";
echo "    - Categories: {$catCount}\n";
echo "    - Products:   {$prodCount}\n";
echo "    - Variants:   {$varCount}\n";
echo "    - Stock rows:  {$stockCount} (all qty=5, date={$today})\n";
echo "\nDone!\n";
