<?php
/**
 * MIGRASI PRODUK D'CELUP → LUMERO OUTLET 8
 * ==========================================
 * Hapus produk clone Kalibunder & resep dari outlet 8,
 * lalu buat ulang dari data SQL dump D'Celup:
 *   1. Hapus resep, produk, kategori outlet 8
 *   2. Buat product_categories
 *   3. Buat products + product_variants dari:
 *      - menu_items (Nasi, Saus, Burger, Kebab, Paket, dll)
 *      - price_rules (Ayam Crispy semua varian)
 *      - kentang_variants (Kentang Kriwil)
 *      - matcha_variants (Matcha Series)
 *      - kopi_variants (Kopi & Minuman Sachet)
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

$NEW_OUTLET_ID = 8;

// ============================================================
// STEP 1: Hapus resep, produk, kategori lama outlet 8
// ============================================================
echo "=== STEP 1: BERSIHKAN DATA LAMA OUTLET 8 ===\n";

// Hapus recipe_items & recipes
$existingRecIds = $pdo->query("SELECT id FROM recipes WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingRecIds) {
    $idList = implode(',', $existingRecIds);
    $pdo->exec("DELETE FROM recipe_items WHERE recipe_id IN ($idList)");
    $pdo->exec("DELETE FROM recipes WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  ✅ " . count($existingRecIds) . " resep dihapus\n";
}

// Hapus product_variants & products
$existingProdIds = $pdo->query("SELECT id FROM products WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingProdIds) {
    $idList = implode(',', $existingProdIds);
    $pdo->exec("DELETE FROM product_variants WHERE product_id IN ($idList)");
    $pdo->exec("DELETE FROM products WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  ✅ " . count($existingProdIds) . " produk + variannya dihapus\n";
}

// Hapus product_categories
$pdo->exec("DELETE FROM product_categories WHERE outlet_id = $NEW_OUTLET_ID");
echo "  ✅ Kategori produk dihapus\n";

// ============================================================
// STEP 2: Buat Product Categories
// ============================================================
echo "\n=== STEP 2: BUAT PRODUCT CATEGORIES ===\n";

$categories = [
    ['name' => 'Ayam Crispy',         'sort' => 1],
    ['name' => 'Kentang',             'sort' => 2],
    ['name' => 'Matcha',              'sort' => 3],
    ['name' => 'Kopi & Minuman',      'sort' => 4],
    ['name' => 'Menu Tambahan',       'sort' => 5],
    ['name' => 'Paket Lumer',         'sort' => 6],
    ['name' => 'Paket 12.500',        'sort' => 7],
    ['name' => 'Burger & Kebab',      'sort' => 8],
];

$catIds = [];
foreach ($categories as $cat) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cat['name']));
    $stmt = $pdo->prepare("INSERT INTO product_categories (outlet_id, name, slug, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt->execute([$NEW_OUTLET_ID, $cat['name'], $slug, $cat['sort']]);
    $catIds[$cat['name']] = $pdo->lastInsertId();
    echo "  ✅ '{$cat['name']}' → ID: {$catIds[$cat['name']]}\n";
}

// Helper: buat produk & kembalikan ID-nya
function createProduct($pdo, $outletId, $catId, $sku, $name, $productType, $baseHpp, $basePrice, $image = null) {
    $stmt = $pdo->prepare("INSERT INTO products 
        (category_id, outlet_id, sku, name, description, image, product_type, unit_name,
         base_hpp, base_price, margin_amount, margin_percent, lifetime_qty_sold, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, NULL, ?, 'variant_parent', 'Porsi', ?, ?, ?, ?, 0, 1, NOW(), NOW())");
    $margin = $basePrice > 0 ? $basePrice - $baseHpp : 0;
    $marginPct = $basePrice > 0 ? round(($margin / $basePrice) * 100, 2) : 0;
    $stmt->execute([$catId, $outletId, $sku, $name, $image, $baseHpp, $basePrice, $margin, $marginPct]);
    return $pdo->lastInsertId();
}

// Helper: buat variant
function createVariant($pdo, $productId, $outletId, $sku, $variantName, $hpp, $price, $isDefault = 0, $isActive = 1, $image = null) {
    $margin = $price > 0 ? $price - $hpp : 0;
    $marginPct = $price > 0 ? round(($margin / $price) * 100, 2) : 0;
    $stmt = $pdo->prepare("INSERT INTO product_variants 
        (product_id, outlet_id, sku, variant_name, image, hpp, selling_price,
         margin_amount, margin_percent, is_default, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$productId, $outletId, $sku, $variantName, $image, $hpp, $price, $margin, $marginPct, $isDefault, $isActive]);
    return $pdo->lastInsertId();
}

$totalProducts = 0;
$totalVariants = 0;

// ============================================================
// STEP 3: Ayam Crispy — dari price_rules
// ============================================================
echo "\n=== STEP 3: AYAM CRISPY (dari price_rules) ===\n";
$catId = $catIds['Ayam Crispy'];

$prodId = createProduct($pdo, $NEW_OUTLET_ID, $catId, 'DC-AYAM-PSKL', 'Ayam Crispy', 'variant_parent', 5864, 12000);
$totalProducts++;

$priceRules = [
    // Ayam Original - Tanpa Nasi
    [1,  'Dada Original Tanpa Nasi',              12000, 5864,  0],
    [2,  'Paha Atas Original Tanpa Nasi',         12000, 5864,  0],
    [3,  'Paha Bawah Original Tanpa Nasi',        10000, 5864,  0],
    [4,  'Sayap Original Tanpa Nasi',              8000, 5864,  0],
    // Ayam Original - dengan Nasi
    [8,  'Dada Original + Nasi',                  15000, 7891,  0],
    [9,  'Paha Atas Original + Nasi',             15000, 7891,  0],
    [10, 'Paha Bawah Original + Nasi',            13000, 7891,  0],
    [11, 'Sayap Original + Nasi',                 11000, 7891,  0],
    // BBQ Spicy Tanpa Nasi
    [15, 'Dada BBQ Spicy Tanpa Nasi',             15000, 7631,  1],
    [16, 'Paha Atas BBQ Spicy Tanpa Nasi',        15000, 7631,  1],
    [17, 'Paha Bawah BBQ Spicy Tanpa Nasi',       13000, 7631,  1],
    [18, 'Sayap BBQ Spicy Tanpa Nasi',            11000, 7631,  1],
    // Keju Tanpa Nasi
    [19, 'Dada Keju Tanpa Nasi',                  15000, 7588,  1],
    [20, 'Paha Atas Keju Tanpa Nasi',             15000, 7588,  1],
    [21, 'Paha Bawah Keju Tanpa Nasi',            13000, 7588,  1],
    [22, 'Sayap Keju Tanpa Nasi',                 11000, 7588,  1],
    // Lada Hitam Tanpa Nasi
    [23, 'Dada Lada Hitam Tanpa Nasi',            15000, 7573,  1],
    [24, 'Paha Atas Lada Hitam Tanpa Nasi',       15000, 7573,  1],
    [25, 'Paha Bawah Lada Hitam Tanpa Nasi',      13000, 7573,  1],
    [26, 'Sayap Lada Hitam Tanpa Nasi',           11000, 7573,  1],
    // Sadis Tanpa Nasi
    [27, 'Dada Sadis Tanpa Nasi',                 15000, 7660,  1],
    [28, 'Paha Atas Sadis Tanpa Nasi',            15000, 7660,  1],
    [29, 'Paha Bawah Sadis Tanpa Nasi',           13000, 7660,  1],
    [30, 'Sayap Sadis Tanpa Nasi',                11000, 7660,  1],
    // Sambal Geprek Tanpa Nasi
    [31, 'Dada Sambal Geprek Tanpa Nasi',         15000, 7986,  1],
    [32, 'Paha Atas Sambal Geprek Tanpa Nasi',    15000, 7986,  1],
    [33, 'Paha Bawah Sambal Geprek Tanpa Nasi',   13000, 7986,  1],
    [34, 'Sayap Sambal Geprek Tanpa Nasi',        11000, 7986,  1],
    // Mentai Tanpa Nasi
    [35, 'Dada Mentai Tanpa Nasi',                15000, 8446,  1],
    [36, 'Paha Atas Mentai Tanpa Nasi',           15000, 8446,  1],
    [37, 'Paha Bawah Mentai Tanpa Nasi',          13000, 8446,  1],
    [38, 'Sayap Mentai Tanpa Nasi',               11000, 8446,  1],
    // Teriyaki Tanpa Nasi
    [39, 'Dada Teriyaki Tanpa Nasi',              15000, 7516,  1],
    [40, 'Paha Atas Teriyaki Tanpa Nasi',         15000, 7516,  1],
    [41, 'Paha Bawah Teriyaki Tanpa Nasi',        13000, 7516,  1],
    [42, 'Sayap Teriyaki Tanpa Nasi',             11000, 7516,  1],
    // Geprek Extra Mozzarella Tanpa Nasi
    [43, 'Dada Geprek Extra Mozzarella Tanpa Nasi',     15000, 14549, 0],
    [44, 'Paha Atas Geprek Extra Mozzarella Tanpa Nasi',15000, 14549, 0],
    [45, 'Paha Bawah Geprek Extra Mozzarella Tanpa Nasi',13000,14549, 0],
    [46, 'Sayap Geprek Extra Mozzarella Tanpa Nasi',    11000, 14549, 0],
    // Geprek Extra Mentai Tanpa Nasi
    [47, 'Dada Geprek Extra Mentai Tanpa Nasi',         15000, 12481, 0],
    [48, 'Paha Atas Geprek Extra Mentai Tanpa Nasi',    15000, 12481, 0],
    [49, 'Paha Bawah Geprek Extra Mentai Tanpa Nasi',   13000, 12481, 0],
    [50, 'Sayap Geprek Extra Mentai Tanpa Nasi',        11000, 12481, 0],
    // BBQ Spicy + Nasi
    [78, 'Dada BBQ Spicy + Nasi',                 18000, 9236,  1],
    [79, 'Paha Atas BBQ Spicy + Nasi',            18000, 9236,  1],
    [80, 'Paha Bawah BBQ Spicy + Nasi',           16000, 9236,  1],
    [81, 'Sayap BBQ Spicy + Nasi',                13000, 9236,  1],
    // Keju + Nasi
    [82, 'Dada Keju + Nasi',                      18000, 9193,  1],
    [83, 'Paha Atas Keju + Nasi',                 18000, 9193,  1],
    [84, 'Paha Bawah Keju + Nasi',                16000, 9193,  1],
    [85, 'Sayap Keju + Nasi',                     13000, 9193,  1],
    // Lada Hitam + Nasi
    [86, 'Dada Lada Hitam + Nasi',                18000, 9178,  1],
    [87, 'Paha Atas Lada Hitam + Nasi',           18000, 9178,  1],
    [88, 'Paha Bawah Lada Hitam + Nasi',          16000, 9178,  1],
    [89, 'Sayap Lada Hitam + Nasi',               13000, 9178,  1],
    // Sadis + Nasi
    [90, 'Dada Sadis + Nasi',                     18000, 9265,  1],
    [91, 'Paha Atas Sadis + Nasi',                18000, 9265,  1],
    [92, 'Paha Bawah Sadis + Nasi',               16000, 9265,  1],
    [93, 'Sayap Sadis + Nasi',                    13000, 9265,  1],
    // Sambal Geprek + Nasi
    [94, 'Dada Sambal Geprek + Nasi',             18000, 9591,  1],
    [95, 'Paha Atas Sambal Geprek + Nasi',        18000, 9591,  1],
    [96, 'Paha Bawah Sambal Geprek + Nasi',       16000, 9591,  1],
    [97, 'Sayap Sambal Geprek + Nasi',            13000, 9591,  1],
    // Mentai + Nasi
    [98, 'Dada Mentai + Nasi',                    18000, 10051, 1],
    [99, 'Paha Atas Mentai + Nasi',               18000, 10051, 1],
    [100,'Paha Bawah Mentai + Nasi',              16000, 10051, 1],
    [101,'Sayap Mentai + Nasi',                   13000, 10051, 1],
    // Teriyaki + Nasi
    [102,'Dada Teriyaki + Nasi',                  18000, 9121,  1],
    [103,'Paha Atas Teriyaki + Nasi',             18000, 9121,  1],
    [104,'Paha Bawah Teriyaki + Nasi',            16000, 9121,  1],
    [105,'Sayap Teriyaki + Nasi',                 13000, 9121,  1],
    // Geprek Extra Mozzarella + Nasi
    [106,'Dada Geprek Extra Mozzarella + Nasi',   18000, 16154, 0],
    [107,'Paha Atas Geprek Extra Mozzarella + Nasi',18000,16154,0],
    [108,'Paha Bawah Geprek Extra Mozzarella + Nasi',16000,16154,0],
    [109,'Sayap Geprek Extra Mozzarella + Nasi',  14000, 16154, 0],
    // Geprek Extra Mentai + Nasi
    [110,'Dada Geprek Extra Mentai + Nasi',       18000, 17879, 0],
    [111,'Paha Atas Geprek Extra Mentai + Nasi',  18000, 17879, 0],
    [112,'Paha Bawah Geprek Extra Mentai + Nasi', 16000, 17879, 0],
    [113,'Sayap Geprek Extra Mentai + Nasi',      14000, 17879, 0],
];

foreach ($priceRules as $idx => [$ruleId, $varName, $price, $hpp, $isActive]) {
    $sku = 'DC-AYM-' . $ruleId . '-PSKL';
    $isDefault = ($idx === 0) ? 1 : 0;
    createVariant($pdo, $prodId, $NEW_OUTLET_ID, $sku, $varName, $hpp, $price, $isDefault, $isActive);
    $totalVariants++;
}
echo "  ✅ Ayam Crispy: " . count($priceRules) . " varian\n";

// ============================================================
// STEP 4: Kentang Kriwil — dari kentang_variants
// ============================================================
echo "\n=== STEP 4: KENTANG KRIWIL (dari kentang_variants) ===\n";
$catId = $catIds['Kentang'];
$prodId = createProduct($pdo, $NEW_OUTLET_ID, $catId, 'DC-KTG-PSKL', 'Kentang Kriwil', 'variant_parent', 4651, 8000);
$totalProducts++;

$kentangVars = [
    [1,  'Kentang Kriwil Original',              8000,  4651, 1],
    [2,  'Kentang Kriwil Saus Sadis',            10000, 5322, 1],
    [3,  'Kentang Kriwil Saus Barbeque Spicy',   10000, 5304, 1],
    [4,  'Kentang Kriwil Saus Teriyaki',         10000, 5233, 1],
    [5,  'Kentang Kriwil Saus Lada Hitam',       10000, 5269, 1],
    [6,  'Kentang Kriwil Saus Keju',             10000, 5052, 1],
    [7,  'Kentang Kriwil Saus Mentai',           10000, 5814, 1],
    [8,  'Kentang Kriwil Sambal Master',         10000, 5068, 1],
    [9,  'Kentang Kriwil Smocky Saus Mentai',    16000, 9079, 0],
    [10, 'Kentang Kriwil Smocky Keju Mozzarella',16000, 9854, 0],
    [11, 'Kentang Kriwil Saus Garlic',           10000, 5052, 1],
];

foreach ($kentangVars as $idx => [$id, $varName, $price, $hpp, $isActive]) {
    $sku = 'DC-KTG-' . $id . '-PSKL';
    createVariant($pdo, $prodId, $NEW_OUTLET_ID, $sku, $varName, $hpp, $price, ($idx === 0 ? 1 : 0), $isActive);
    $totalVariants++;
}
echo "  ✅ Kentang Kriwil: " . count($kentangVars) . " varian\n";

// ============================================================
// STEP 5: Matcha Series — dari matcha_variants
// ============================================================
echo "\n=== STEP 5: MATCHA SERIES (dari matcha_variants) ===\n";
$catId = $catIds['Matcha'];
$prodId = createProduct($pdo, $NEW_OUTLET_ID, $catId, 'DC-MCH-PSKL', 'Matcha Series', 'variant_parent', 6511, 13000);
$totalProducts++;

$matchaVars = [
    [1, 'Matcha Latte',         13000, 6511, 1],
    [2, 'Matcha Coklat',        15000, 7966, 1],
    [3, 'Matcha Taro',          15000, 8521, 1],
    [4, 'Alpukat Kocok Ori',    13000, 7000, 1],
];

foreach ($matchaVars as $idx => [$id, $varName, $price, $hpp, $isActive]) {
    $sku = 'DC-MCH-' . $id . '-PSKL';
    createVariant($pdo, $prodId, $NEW_OUTLET_ID, $sku, $varName, $hpp, $price, ($idx === 0 ? 1 : 0), $isActive);
    $totalVariants++;
}
echo "  ✅ Matcha Series: " . count($matchaVars) . " varian\n";

// ============================================================
// STEP 6: Kopi & Minuman Sachet — dari kopi_variants
// ============================================================
echo "\n=== STEP 6: KOPI & MINUMAN SACHET (dari kopi_variants) ===\n";
$catId = $catIds['Kopi & Minuman'];
$prodId = createProduct($pdo, $NEW_OUTLET_ID, $catId, 'DC-KOP-PSKL', 'Minuman Seduh', 'variant_parent', 2750, 5000);
$totalProducts++;

$kopiVars = [
    [1,  'Kapal Api',            2750, 5000,  1],
    [2,  'Good Day Mocacino',    2750, 5000,  1],
    [3,  'Good Day Capucino',    2750, 6000,  1],
    [4,  'ABC Susu',             2750, 5000,  1],
    [5,  'Torabika Creamy Late', 2750, 6000,  1],
    [6,  'Wdank',                2750, 6000,  1],
    [7,  'Mix Tea Ice',          2750, 7000,  1],
    [8,  'Luwak White Koffe',    2750, 5000,  1],
    [9,  'Nescafe Klasik',       2750, 5000,  1],
    [10, 'Genus Water',          3000, 4000,  1],
    [11, 'Mix Tea Hot',          2500, 6000,  1],
    [12, 'Iced Palm Latte',      5400, 10000, 1],
    [13, 'Iced Latte',           5400, 10000, 1],
];

foreach ($kopiVars as $idx => [$id, $varName, $hpp, $price, $isActive]) {
    $sku = 'DC-KOP-' . $id . '-PSKL';
    createVariant($pdo, $prodId, $NEW_OUTLET_ID, $sku, $varName, $hpp, $price, ($idx === 0 ? 1 : 0), $isActive);
    $totalVariants++;
}
echo "  ✅ Kopi & Minuman: " . count($kopiVars) . " varian\n";

// ============================================================
// STEP 7: Menu Sederhana — dari menu_items
// ============================================================
echo "\n=== STEP 7: MENU SEDERHANA (dari menu_items) ===\n";

// [menu_item_id, nama produk, harga, hpp, kategori, image_url, is_active]
$menuItems = [
    // Menu Tambahan (cat menu_tambahan)
    [1,  'Nasi',                3000,  1200,  'Menu Tambahan', 'https://www.lokapedia.id/dc/assets/img/nasi.png', 1],
    [2,  'Saus',                3000,  100,   'Menu Tambahan', 'https://www.lokapedia.id/dc/assets/img/saus.png', 1],
    [3,  '1 ekor ayam original',66000, 40000, 'Menu Tambahan', null,  1],
    [4,  '1 ekor ayam + saus',  76000, 45000, 'Menu Tambahan', null,  1],
    [13, 'Nasi 5rb',            5000,  2000,  'Menu Tambahan', null,  1],
    [18, 'Saus 2000',           2000,  750,   'Menu Tambahan', null,  1],
    // Minuman Sachet (Thai Tea, Korean, dll sudah di menu_items)
    [5,  'Thai Tea Lumut',      13000, 6500,  'Kopi & Minuman', null, 1],
    [6,  'Korean Strawberry',   13000, 6600,  'Kopi & Minuman', null, 1],
    [7,  'Coffee Latte Ice',    13000, 6600,  'Kopi & Minuman', null, 1],
    [8,  'Taro Ice',            13000, 6600,  'Kopi & Minuman', null, 1],
    [9,  'Cokelat Ice',         13000, 6600,  'Kopi & Minuman', null, 1],
    // Paket Ayam
    [10, 'Paket 20rb',          20000, 12500, 'Menu Tambahan', null,  1],
    [11, 'Paket 22rb',          22000, 12500, 'Menu Tambahan', null,  1],
    [12, 'Tanpa Nasi 15rb',     15000, 10000, 'Menu Tambahan', null,  1],
    // Paket Lumer
    [14, 'Lumer 25RB',          25000, 15000, 'Paket Lumer',   null,  1],
    [15, 'Lumer 30rb',          30000, 18000, 'Paket Lumer',   null,  1],
    // Paket 12.500
    [16, 'Paket 12.500',        12500, 8000,  'Paket 12.500',  null,  1],
    // Burger & Kebab
    [19, 'Cheese Burger',       15000, 8000,  'Burger & Kebab', null, 1],
    [20, 'Kebab Telor',         10000, 5000,  'Burger & Kebab', null, 1],
    [21, 'Kebab Ayam',          13000, 7000,  'Burger & Kebab', null, 1],
    [22, 'Kebab Beef',          15000, 8500,  'Burger & Kebab', null, 1],
];

// Simpan mapping menu_item_id → product_variant_id (untuk migrasi orders nanti)
$menuItemVariantMap = [];

foreach ($menuItems as [$menuId, $name, $price, $hpp, $catName, $image, $isActive]) {
    $catId = $catIds[$catName] ?? $catIds['Menu Tambahan'];
    $sku = 'DC-MNU-' . $menuId . '-PSKL';
    $prodId = createProduct($pdo, $NEW_OUTLET_ID, $catId, $sku, $name, 'single', $hpp, $price, $image);
    $totalProducts++;
    // Buat 1 varian (Default)
    $varSku = $sku . '-VAR';
    $varId = createVariant($pdo, $prodId, $NEW_OUTLET_ID, $varSku, 'Default', $hpp, $price, 1, $isActive, $image);
    $totalVariants++;
    $menuItemVariantMap[$menuId] = $varId;
    echo "  ✅ '{$name}' → produk ID: $prodId, varian ID: $varId\n";
}

// ============================================================
// RINGKASAN
// ============================================================
echo "\n" . str_repeat("=", 55) . "\n";
echo "✅ MIGRASI PRODUK D'CELUP → LUMERO SELESAI!\n";
echo str_repeat("=", 55) . "\n";
echo "📂 Kategori : " . count($categories) . "\n";
echo "🍗 Produk   : $totalProducts\n";
echo "📋 Varian   : $totalVariants\n";
echo "\nMapping menu_item_id → product_variant_id (untuk orders):\n";
foreach ($menuItemVariantMap as $mId => $vId) {
    echo "  menu_item[$mId] → variant[$vId]\n";
}
echo "\n🔔 LANGKAH BERIKUTNYA: Migrasi orders dari SQL D'Celup\n";
