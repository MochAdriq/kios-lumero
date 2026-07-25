<?php
/**
 * FIX: Tambah Varian Ayam Garlic + Patch Order Items yang Skip
 */
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$lumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$local = new PDO("mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$NEW_OUTLET_ID = 8;
$CUTOFF_DATE   = '2026-07-24';

// ============================================================
// STEP 1: Tambah varian Garlic ke produk Ayam Crispy
// ============================================================
echo "=== STEP 1: TAMBAH VARIAN AYAM GARLIC ===\n";

// Ambil product_id Ayam Crispy outlet 8
$ayamProdId = $lumero->query("SELECT id FROM products WHERE outlet_id = $NEW_OUTLET_ID AND sku = 'DC-AYAM-PSKL'")->fetchColumn();
if (!$ayamProdId) {
    die("  ERROR: Produk Ayam Crispy tidak ditemukan!\n");
}
echo "  Ayam Crispy product_id: $ayamProdId\n";

// Varian Garlic yang perlu ditambah (dari price_rules D'Celup)
// Format: [rule_id, nama, price, hpp, is_active]
$garlicVariants = [
    [482, 'Dada Garlic Tanpa Nasi',       15000, 7588, 1],
    [483, 'Paha Atas Garlic Tanpa Nasi',  15000, 7588, 1],
    [484, 'Paha Bawah Garlic Tanpa Nasi', 13000, 7588, 1],
    [485, 'Sayap Garlic Tanpa Nasi',      11000, 7588, 1],
    [486, 'Chicken Crips Garlic Tanpa Nasi', 13000, 7588, 1],
    [489, 'Dada Garlic + Nasi',           18000, 9193, 1],
    [490, 'Paha Atas Garlic + Nasi',      18000, 9193, 1],
    [491, 'Paha Bawah Garlic + Nasi',     16000, 9193, 1],
    [492, 'Sayap Garlic + Nasi',          13000, 9193, 1],
    [493, 'Chicken Crips Garlic + Nasi',  16000, 9615, 1],
];

$garlicVariantMap = []; // rule_id → variant_id (baru)

$stmt = $lumero->prepare("INSERT INTO product_variants 
    (product_id, outlet_id, sku, variant_name, hpp, selling_price,
     margin_amount, margin_percent, is_default, is_active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())");

foreach ($garlicVariants as [$ruleId, $varName, $price, $hpp, $isActive]) {
    $sku    = 'DC-AYM-' . $ruleId . '-PSKL';
    $margin = $price - $hpp;
    $marginPct = round(($margin / $price) * 100, 2);

    // Cek apakah sudah ada
    $exists = $lumero->prepare("SELECT id FROM product_variants WHERE sku = ?");
    $exists->execute([$sku]);
    $existId = $exists->fetchColumn();
    if ($existId) {
        $garlicVariantMap[$ruleId] = $existId;
        echo "  [SKIP - sudah ada] {$varName} (ID: $existId)\n";
        continue;
    }

    $stmt->execute([$ayamProdId, $NEW_OUTLET_ID, $sku, $varName, $hpp, $price, $margin, $marginPct, $isActive]);
    $varId = $lumero->lastInsertId();
    $garlicVariantMap[$ruleId] = $varId;
    echo "  ✅ '{$varName}' → ID: $varId (Rp " . number_format($price) . ")\n";
}

// ============================================================
// STEP 2: Build chicken combo map (termasuk Garlic / sauce_id=10)
// ============================================================
// Mapping price_rule dari D'Celup untuk sauce_id=10 (Garlic)
// Berdasarkan chicken_part_id: 1=Dada, 2=Paha Atas, 3=Paha Bawah, 4=Sayap
$garlicComboToRuleId = [
    '1-sauce-10-0' => 482,
    '2-sauce-10-0' => 483,
    '3-sauce-10-0' => 484,
    '4-sauce-10-0' => 485,
    '1-sauce-10-1' => 489,
    '2-sauce-10-1' => 490,
    '3-sauce-10-1' => 491,
    '4-sauce-10-1' => 492,
];

// ============================================================
// STEP 3: Patch order items yang skip (chicken Garlic)
// ============================================================
echo "\n=== STEP 2: PATCH ORDER ITEMS GARLIC YANG SKIP ===\n";

// Ambil semua order items chicken dengan sauce_id=10 dari temp DB
$skippedItems = $local->query("
    SELECT oi.id, oi.order_id, oi.chicken_part_id, oi.chicken_style, oi.with_rice,
           oi.item_name, oi.qty, oi.price, oi.hpp, oi.line_total, oi.line_hpp,
           o.order_no
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.item_type = 'chicken'
    AND oi.sauce_id = 10
    AND DATE(o.created_at) <= '$CUTOFF_DATE'
")->fetchAll(PDO::FETCH_ASSOC);

echo "  Total item Garlic yang perlu di-patch: " . count($skippedItems) . "\n\n";

$stmtInsert = $lumero->prepare("INSERT INTO order_items 
    (order_id, product_variant_id, product_name_snapshot, variant_name_snapshot,
     qty, selling_price, discount_amount, tax_amount, subtotal, hpp_per_unit, total_hpp, gross_profit)
    VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)");

// Kita butuh mapping: order_no D'Celup → lumero order_id
$orderNoToLumeroId = [];
$existingOrders = $lumero->query("
    SELECT id, order_number FROM orders WHERE outlet_id = $NEW_OUTLET_ID
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($existingOrders as $o) {
    // order_number format: HIST-DCK2000
    $originalNo = str_replace('HIST-', '', $o['order_number']);
    $orderNoToLumeroId[$originalNo] = $o['id'];
}

$patched = 0;
$failed  = 0;

foreach ($skippedItems as $item) {
    $partId   = (int)($item['chicken_part_id'] ?? 0);
    $style    = $item['chicken_style'] ?? 'sauce';
    $withRice = (int)($item['with_rice'] ?? 0);
    $comboKey = "{$partId}-{$style}-10-{$withRice}";

    $ruleId    = $garlicComboToRuleId[$comboKey] ?? null;
    $variantId = $ruleId ? ($garlicVariantMap[$ruleId] ?? null) : null;

    $lumeroOrderId = $orderNoToLumeroId[$item['order_no']] ?? null;

    if (!$variantId || !$lumeroOrderId) {
        echo "  [FAIL] {$item['order_no']}: {$item['item_name']} (combo=$comboKey, variantId=$variantId, orderId=$lumeroOrderId)\n";
        $failed++;
        continue;
    }

    $lineProfit = (int)$item['line_total'] - (int)$item['line_hpp'];
    $stmtInsert->execute([
        $lumeroOrderId,
        $variantId,
        'Ayam Crispy',
        $item['item_name'],
        $item['qty'],
        $item['price'],
        $item['line_total'],
        $item['hpp'],
        $item['line_hpp'],
        $lineProfit
    ]);
    $patched++;
    echo "  ✅ {$item['order_no']}: {$item['item_name']} → variant $variantId\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ PATCH GARLIC SELESAI!\n";
echo str_repeat("=", 50) . "\n";
echo "🍗 Varian Garlic baru : " . count($garlicVariants) . "\n";
echo "🔧 Items di-patch     : $patched\n";
echo "❌ Items gagal        : $failed\n";
