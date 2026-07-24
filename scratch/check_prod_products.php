<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 1. Cek struktur products
echo "=== PRODUCTS COLUMNS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']} | Default:" . ($c['Default'] ?? 'NULL') . "\n";

// 2. Cek product_variants
echo "\n=== PRODUCT_VARIANTS COLUMNS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM product_variants")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']} | Default:" . ($c['Default'] ?? 'NULL') . "\n";

// 3. Cek apakah ada tabel outlet_products atau product_outlets (junction table)
echo "\n=== TABLES CONTAINING 'product' or 'stock' ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    if (stripos($t, 'product') !== false || stripos($t, 'stock') !== false || stripos($t, 'outlet') !== false) {
        echo "  {$t}\n";
    }
}

// 4. Cek apakah products punya outlet_id
echo "\n=== SAMPLE PRODUCTS (Kalibunder outlet_id=5, first 5) ===\n";
$stmt = $pdo->query("SELECT id, name, category_id, outlet_id, is_active, image FROM products WHERE outlet_id = 5 LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  ID:{$r['id']} | {$r['name']} | Cat:{$r['category_id']} | Outlet:{$r['outlet_id']} | Active:{$r['is_active']} | Img:" . substr($r['image'] ?? '', 0, 50) . "\n";
}

// 5. Count products per outlet
echo "\n=== PRODUCT COUNT PER OUTLET ===\n";
$stmt = $pdo->query("SELECT outlet_id, COUNT(*) as cnt FROM products GROUP BY outlet_id ORDER BY outlet_id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) echo "  Outlet {$r['outlet_id']}: {$r['cnt']} products\n";

// 6. Cek daily_stocks atau stock table
echo "\n=== DAILY STOCK TABLE COLUMNS ===\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM daily_stocks")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']}\n";
} catch (Exception $e) {
    echo "  Table daily_stocks not found. Trying ready_stock...\n";
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM ready_stock")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']}\n";
    } catch (Exception $e2) {
        echo "  ready_stock not found either.\n";
    }
}

// 7. Cek product_categories
echo "\n=== PRODUCT_CATEGORIES COLUMNS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM product_categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']}\n";

echo "\n=== CATEGORIES PER OUTLET ===\n";
$stmt = $pdo->query("SELECT outlet_id, COUNT(*) as cnt FROM product_categories GROUP BY outlet_id ORDER BY outlet_id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) echo "  Outlet {$r['outlet_id']}: {$r['cnt']} categories\n";

// 8. Variants per outlet
echo "\n=== VARIANT COUNT (via product.outlet_id) ===\n";
$stmt = $pdo->query("SELECT p.outlet_id, COUNT(pv.id) as cnt FROM product_variants pv JOIN products p ON p.id = pv.product_id GROUP BY p.outlet_id ORDER BY p.outlet_id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) echo "  Outlet {$r['outlet_id']}: {$r['cnt']} variants\n";
