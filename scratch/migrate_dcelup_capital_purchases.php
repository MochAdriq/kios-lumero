<?php
/**
 * MIGRASI MODAL & PEMBELIAN
 * D'Celup Pasekon Sukalarang → Lumero Prod
 * ==========================================
 * 1. Migrasi business_capital_components → business_capitals
 * 2. Migrasi purchase_batches (vendor) → vendors + purchase_orders
 * 3. Migrasi purchase_items (raw materials mapping) → purchase_order_items
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

$NEW_OUTLET_ID = 8; // D'Celup Pasekon Sukalarang

// ============================================================
// STEP 1: Migrasi business_capital_components → business_capitals
// ============================================================
echo "=== STEP 1: MIGRASI MODAL (business_capitals) ===\n";

// Bersihkan data modal yang sudah ada untuk outlet ini
$pdo->exec("DELETE FROM business_capitals WHERE outlet_id = $NEW_OUTLET_ID");
echo "  Cleaned existing business_capitals for outlet $NEW_OUTLET_ID\n";

// Data modal dari D'Celup (business_capital_components), hanya yang is_active = 1
$capitalData = [
    // [capital_date, category, component_name, description, amount, payment_method, supplier, invoice_no, is_active, capital_type]
    ['2026-05-18', 'Modal Awal',         'Merek Dagang',                     'Pembelian Merek Franchise',                    14000000, 'Transfer', 'Dcelup Pusat', '001',  1, 'initial_capital'],
    ['2026-05-18', 'Renovasi/Outlet',    'Kanopi',                           'Atap Kanopi Kanopi Outlet',                     3600000, 'Cash',     'Abuy',         '002',  1, 'initial_capital'],
    ['2026-05-18', 'Renovasi/Outlet',    'Branding',                         'Plang Merek dan Plang Menu',                    2000000, 'Cash',     'Abuy',         '003',  1, 'initial_capital'],
    ['2026-05-18', 'Peralatan',          'Meja lemari dapur dan kios',       'Meja dapur, Lemari Kompor, Meja Pelanggan',     4000000, '',         'Joni',         '',     1, 'initial_capital'],
    ['2026-05-18', 'Renovasi/Outlet',    'Branding Kanopi, Plang & Display', 'Plang jalan dan kanopi + Display',              5250000, 'Cash',     'Aceuh',        '005',  1, 'initial_capital'],
    ['2026-05-18', 'Marketing',          'Spanduk Harga dan backdrop',       '',                                               300000, 'Cash',     'Salman',       '',     1, 'initial_capital'],
    ['2026-05-18', 'Marketing',          'Spanduk Awal',                     '',                                               350000, 'Cash',     'Salman',       '',     1, 'initial_capital'],
    ['2026-05-19', 'Bahan Awal',         'Uji Coba Product',                 'Pembelian bahan-bahan uji coba sebelum launching', 1000000,'Cash',   'Belanja Sendiri','',   1, 'initial_capital'],
    ['2026-05-13', 'Bahan Awal / Persediaan Awal', 'Modal Awal Bahan Baku - Belanja D\'Celup 13 Mei 2026', 'Belanja bahan awal tanggal 13 Mei 2026. Total Rp2.638.600 termasuk ongkir Rp316.000.', 2638600, 'Transfer', "D'Celup Pusat", 'BELANJA-BAHAN-20260513-SUPPLY-ONGKIR', 1, 'initial_capital'],
    ['2026-05-20', 'Renovasi/Outlet',    'Branding Banner',                  '',                                               520000, 'Cash',     'Salman',       '007',  1, 'additional_capital'],
    ['2026-05-11', 'Peralatan',          'Peralatan Dapur',                  'Pembelian alat dapur, rice cooker dll',         2168000, 'trf',      'Berkah',       'xxx',  1, 'initial_capital'],
    ['2026-06-18', 'Peralatan',          'Lampu LED dan Warmer',             '',                                               300000, '',         '',             '',     1, 'additional_capital'],
];

$stmt = $pdo->prepare("INSERT INTO business_capitals 
    (outlet_id, capital_type, amount, description, capital_date, created_at, category, component_name, payment_method, supplier, invoice_no, is_active)
    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");

$totalCapital = 0;
foreach ($capitalData as $c) {
    $stmt->execute([
        $NEW_OUTLET_ID,
        $c[9],   // capital_type
        $c[4],   // amount
        $c[3],   // description
        $c[0],   // capital_date
        $c[1],   // category
        $c[2],   // component_name
        $c[5],   // payment_method
        $c[6],   // supplier
        $c[7],   // invoice_no
        $c[8],   // is_active
    ]);
    $totalCapital += $c[4];
    echo "  ✅ {$c[2]} — Rp " . number_format($c[4]) . " ({$c[0]})\n";
}
echo "\n  Total Modal: Rp " . number_format($totalCapital) . "\n";

// ============================================================
// STEP 2: Buat Vendor + Migrasi purchase_batches → purchase_orders
// ============================================================
echo "\n=== STEP 2: MIGRASI PEMBELIAN (purchase_orders) ===\n";

// Bersihkan PO yang sudah ada untuk outlet ini
$existingPOs = $pdo->query("SELECT id FROM purchase_orders WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingPOs) {
    $idList = implode(',', $existingPOs);
    $pdo->exec("DELETE FROM purchase_order_items WHERE purchase_order_id IN ($idList)");
    $pdo->exec("DELETE FROM purchase_orders WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  Cleaned existing purchase_orders for outlet $NEW_OUTLET_ID\n";
}

// Helper: cari atau buat vendor
function getOrCreateVendor($pdo, $vendorName) {
    if (empty(trim($vendorName))) return null;
    $stmt = $pdo->prepare("SELECT id FROM vendors WHERE name = ? LIMIT 1");
    $stmt->execute([$vendorName]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare("INSERT INTO vendors (name, is_active, created_at, updated_at) VALUES (?, 1, NOW(), NOW())");
    $stmt->execute([$vendorName]);
    return $pdo->lastInsertId();
}

// Data purchase_batches dari D'Celup yang relevan
// Format: [purchase_date, supplier, invoice_no, total_amount, payment_status, notes]
$purchaseBatches = [
    ['id' => 1,  'date' => '2026-05-13', 'supplier' => "D'Celup Pusat", 'invoice' => 'BELANJA-BAHAN-20260513-SUPPLY-ONGKIR', 'total' => 2638600, 'status' => 'paid', 'notes' => 'Belanja bahan tanggal 13 Mei 2026. Subtotal Rp2.322.600; ongkir Rp316.000.'],
    ['id' => 7,  'date' => '2026-05-16', 'supplier' => 'H. Didin',      'invoice' => '',                                     'total' => 267500,  'status' => 'paid', 'notes' => 'Pembelian ayam'],
    ['id' => 8,  'date' => '2026-05-16', 'supplier' => 'H. Didin',      'invoice' => '',                                     'total' => 173000,  'status' => 'paid', 'notes' => 'Pembelian ayam'],
    ['id' => 10, 'date' => '2026-05-20', 'supplier' => "D'Celup Pusat", 'invoice' => 'BELANJA-BAHAN-20260520-SUPPLY-ONGKIR', 'total' => 2178150, 'status' => 'paid', 'notes' => 'Belanja bahan tanggal 20 Mei 2026. Subtotal Rp1.934.150; ongkir Rp244.000.'],
];

// Mapping inventory_item_id (D'Celup) → raw_material_id (Lumero)
// Berdasarkan nama bahan yang cocok
$inventoryToRawMaterial = [
    3  => 3,   // Beras
    4  => 8,   // Bumbu Marinasi Ayam
    5  => 41,  // Tepung Krispy → ID 41
    7  => 5,   // Bubuk Matcha
    9  => 24,  // Kertas Retail → Packaging Retail ID 24
    10 => 18,  // Kertas Nasi
    27 => 14,  // Gas 3kg
    30 => 29,  // Saus Cheese → ID 29
    31 => 28,  // Saus Italian BBQ Spicy → ID 28
    33 => 33,  // Saus Sadis → ID 33
    34 => 34,  // Saus Teriyaki → ID 34
    35 => 31,  // Saus Mentai → ID 31
    37 => 49,  // Saus Garlic → ID 49
    49 => 6,   // Bubuk Taro
    50 => 37,  // Sticky Milky (coklat) → ID 37
];

// Data purchase_items dari D'Celup, mapped ke raw_material_id Lumero
$purchaseItems = [
    // [dcelup_batch_id, inventory_item_id, qty, unit, unit_cost_purchase, total_cost]
    [1,  4,  2000,   'gr',  80,    159729],
    [1,  5,  40,     'kg',  20506, 820231],
    [1,  10, 300,    'gr',  307,   92021],
    [1,  9,  300,    'gr',  441,   132237],
    [1,  30, 2000,   'gr',  60,    120876],
    [1,  31, 2000,   'gr',  69,    138144],
    [1,  33, 2000,   'gr',  70,    140303],
    [1,  35, 2000,   'gr',  76,    151095],
    [1,  37, 2000,   'gr',  69,    138144],
    [1,  34, 2000,   'gr',  65,    129510],
    [7,  3,  10.7,   'kg',  25000, 267500],
    [8,  3,  5.1,    'kg',  33922, 173000],
    [10, 4,  1000,   'gr',  79,    79169],
    [10, 5,  40,     'kg',  20327, 813083],
    [10, 30, 2000,   'gr',  60,    119823],
    [10, 31, 2000,   'gr',  68,    136940],
    [10, 33, 2000,   'gr',  70,    139080],
    [10, 35, 2000,   'gr',  75,    149778],
    [10, 37, 2000,   'gr',  68,    136940],
    [10, 34, 1000,   'gr',  64,    64191],
    [10, 49, 1000,   'gr',  134,   133731],
    [10, 7,  2000,   'gr',  154,   308566],
    [10, 50, 1000,   'gr',  97,    96849],
];

// Ambil atau buat unit_id
$unitGr = $pdo->query("SELECT id FROM units WHERE symbol = 'gr' LIMIT 1")->fetchColumn();
$unitKg = $pdo->query("SELECT id FROM units WHERE symbol = 'kg' LIMIT 1")->fetchColumn();
if (!$unitKg) {
    $pdo->exec("INSERT INTO units (name, symbol) VALUES ('kg', 'kg')");
    $unitKg = $pdo->lastInsertId();
    echo "  ✅ Unit 'kg' dibuat: ID $unitKg\n";
}

$poMap = []; // dcelup_batch_id => lumero_po_id
$totalPOs = 0;
$totalPOItems = 0;

foreach ($purchaseBatches as $batch) {
    $vendorId = getOrCreateVendor($pdo, $batch['supplier']);
    $poNum = 'PO-PSKL-' . str_replace('-', '', $batch['date']) . '-' . $batch['id'];
    
    $stmt = $pdo->prepare("INSERT INTO purchase_orders 
        (outlet_id, vendor_id, po_number, purchase_date, payment_status, subtotal, discount, tax, grand_total, paid_amount, debt_amount, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'paid', ?, 0, 0, ?, ?, 0, ?, NOW(), NOW())");
    $stmt->execute([
        $NEW_OUTLET_ID,
        $vendorId,
        $poNum,
        $batch['date'],
        $batch['total'],
        $batch['total'],
        $batch['total'],
        $batch['notes']
    ]);
    $poId = $pdo->lastInsertId();
    $poMap[$batch['id']] = $poId;
    $totalPOs++;
    echo "  ✅ PO #{$poNum} — Rp " . number_format($batch['total']) . " ({$batch['date']})\n";
}

echo "\n=== STEP 3: MIGRASI ITEM PEMBELIAN (purchase_order_items) ===\n";

foreach ($purchaseItems as $item) {
    [$batchId, $invItemId, $qty, $unit, $unitCost, $totalCost] = $item;
    
    $poId = $poMap[$batchId] ?? null;
    $rmId = $inventoryToRawMaterial[$invItemId] ?? null;
    
    if (!$poId || !$rmId) {
        echo "  [SKIP] batch_id=$batchId inv_id=$invItemId — no mapping\n";
        continue;
    }
    
    $unitId = ($unit === 'kg') ? $unitKg : $unitGr;
    
    $stmt = $pdo->prepare("INSERT INTO purchase_order_items 
        (purchase_order_id, raw_material_id, qty, unit_id, unit_cost, total_cost)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$poId, $rmId, $qty, $unitId, $unitCost, $totalCost]);
    $totalPOItems++;
}

echo "  ✅ Total $totalPOItems item pembelian di-migrasi\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ MIGRASI MODAL & PEMBELIAN SELESAI!\n";
echo str_repeat("=", 50) . "\n";
echo "💰 Modal : " . count($capitalData) . " komponen — Total Rp " . number_format($totalCapital) . "\n";
echo "🛒 PO    : $totalPOs purchase orders, $totalPOItems items\n";
echo "\n⚠️  PENGINGAT: Migrasi Riwayat Penjualan (orders) belum dilakukan!\n";
