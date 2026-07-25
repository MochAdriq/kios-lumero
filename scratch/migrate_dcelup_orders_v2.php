<?php
/**
 * MIGRASI ORDERS D'CELUP v2 — MENGGUNAKAN TEMP DB
 * =================================================
 * Load SQL dump ke database lokal sementara,
 * lalu query langsung untuk mapping yang akurat.
 */

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

set_time_limit(600);

// === KONEKSI KE PROD (Lumero) ===
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$lumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// === KONEKSI KE LOCAL MYSQL (untuk temp DB) ===
$localHost = '127.0.0.1';
$localUser = 'root';
$localPass = '';
$tempDbName = 'dcelup_temp_migration';

echo "=== STEP 0: SETUP TEMP DATABASE ===\n";
$local = new PDO("mysql:host={$localHost};charset=utf8mb4", $localUser, $localPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$local->exec("CREATE DATABASE IF NOT EXISTS `{$tempDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$local->exec("USE `{$tempDbName}`");
echo "  ✅ Temp DB '$tempDbName' ready\n";

// Load SQL dump — hanya tabel yang kita butuhkan
// Cek apakah sudah ada datanya
$tableExists = $local->query("SHOW TABLES LIKE 'orders'")->fetchColumn();
if (!$tableExists) {
    echo "  Loading SQL dump ke temp DB (ini mungkin butuh 1-2 menit)...\n";
    $sqlFile = 'C:/Users/HYPE R Series/Downloads/u643003184_newcelup (5).sql';
    // Execute via mysql CLI untuk performa
    $cmd = "mysql -h {$localHost} -u {$localUser} {$tempDbName} < \"{$sqlFile}\" 2>&1";
    $output = shell_exec($cmd);
    echo "  Selesai. Output: " . ($output ?? 'ok') . "\n";
} else {
    echo "  ✅ Data sudah ada di temp DB, skip loading\n";
}

// Reconnect dengan database
$local = new PDO("mysql:host={$localHost};dbname={$tempDbName};charset=utf8mb4", $localUser, $localPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$CUTOFF_DATE   = '2026-07-24';
$NEW_OUTLET_ID = 8;
$CASHIER_ID    = 6;

// ============================================================
// STEP 1: Ambil data orders dari temp DB
// ============================================================
echo "\n=== STEP 1: QUERY ORDERS FROM TEMP DB ===\n";

$totalDCelupOrders = $local->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) <= '$CUTOFF_DATE'")->fetchColumn();
echo "  Total D'Celup orders: $totalDCelupOrders\n";

$totalDCelupItems = $local->query("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) <= '$CUTOFF_DATE'")->fetchColumn();
echo "  Total D'Celup order items: $totalDCelupItems\n";

// ============================================================
// STEP 2: Build variant mapping dari Lumero
// ============================================================
echo "\n=== STEP 2: BUILD VARIANT MAPPING ===\n";

$priceRuleMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-AYM-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-AYM-(\d+)-PSKL/', $r['sku'], $m)) $priceRuleMap[(int)$m[1]] = $r['id'];
}

$matchaMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-MCH-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-MCH-(\d+)-PSKL/', $r['sku'], $m)) $matchaMap[(int)$m[1]] = $r['id'];
}

$kentangMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-KTG-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-KTG-(\d+)-PSKL/', $r['sku'], $m)) $kentangMap[(int)$m[1]] = $r['id'];
}

$kopiMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-KOP-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-KOP-(\d+)-PSKL/', $r['sku'], $m)) $kopiMap[(int)$m[1]] = $r['id'];
}

$menuMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-MNU-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-MNU-(\d+)-PSKL/', $r['sku'], $m)) $menuMap[(int)$m[1]] = $r['id'];
}

echo "  Ayam: " . count($priceRuleMap) . ", Kentang: " . count($kentangMap) . ", Matcha: " . count($matchaMap) . ", Kopi: " . count($kopiMap) . ", Menu: " . count($menuMap) . "\n";

// price_rule lookup dari kombinasi chicken
$chickenComboToRuleId = [
    '1-original-0-0'=>1,  '2-original-0-0'=>2,  '3-original-0-0'=>3,  '4-original-0-0'=>4,
    '1-original-0-1'=>8,  '2-original-0-1'=>9,  '3-original-0-1'=>10, '4-original-0-1'=>11,
    '1-sauce-1-0'=>15,  '2-sauce-1-0'=>16,  '3-sauce-1-0'=>17,  '4-sauce-1-0'=>18,
    '1-sauce-2-0'=>19,  '2-sauce-2-0'=>20,  '3-sauce-2-0'=>21,  '4-sauce-2-0'=>22,
    '1-sauce-3-0'=>23,  '2-sauce-3-0'=>24,  '3-sauce-3-0'=>25,  '4-sauce-3-0'=>26,
    '1-sauce-4-0'=>27,  '2-sauce-4-0'=>28,  '3-sauce-4-0'=>29,  '4-sauce-4-0'=>30,
    '1-sauce-5-0'=>31,  '2-sauce-5-0'=>32,  '3-sauce-5-0'=>33,  '4-sauce-5-0'=>34,
    '1-sauce-6-0'=>35,  '2-sauce-6-0'=>36,  '3-sauce-6-0'=>37,  '4-sauce-6-0'=>38,
    '1-sauce-7-0'=>39,  '2-sauce-7-0'=>40,  '3-sauce-7-0'=>41,  '4-sauce-7-0'=>42,
    '1-sauce-8-0'=>43,  '2-sauce-8-0'=>44,  '3-sauce-8-0'=>45,  '4-sauce-8-0'=>46,
    '1-sauce-9-0'=>47,  '2-sauce-9-0'=>48,  '3-sauce-9-0'=>49,  '4-sauce-9-0'=>50,
    '1-sauce-1-1'=>78,  '2-sauce-1-1'=>79,  '3-sauce-1-1'=>80,  '4-sauce-1-1'=>81,
    '1-sauce-2-1'=>82,  '2-sauce-2-1'=>83,  '3-sauce-2-1'=>84,  '4-sauce-2-1'=>85,
    '1-sauce-3-1'=>86,  '2-sauce-3-1'=>87,  '3-sauce-3-1'=>88,  '4-sauce-3-1'=>89,
    '1-sauce-4-1'=>90,  '2-sauce-4-1'=>91,  '3-sauce-4-1'=>92,  '4-sauce-4-1'=>93,
    '1-sauce-5-1'=>94,  '2-sauce-5-1'=>95,  '3-sauce-5-1'=>96,  '4-sauce-5-1'=>97,
    '1-sauce-6-1'=>98,  '2-sauce-6-1'=>99,  '3-sauce-6-1'=>100, '4-sauce-6-1'=>101,
    '1-sauce-7-1'=>102, '2-sauce-7-1'=>103, '3-sauce-7-1'=>104, '4-sauce-7-1'=>105,
    '1-sauce-8-1'=>106, '2-sauce-8-1'=>107, '3-sauce-8-1'=>108, '4-sauce-8-1'=>109,
    '1-sauce-9-1'=>110, '2-sauce-9-1'=>111, '3-sauce-9-1'=>112, '4-sauce-9-1'=>113,
];

// ============================================================
// STEP 3: Buat daily_store_sessions
// ============================================================
echo "\n=== STEP 3: DAILY STORE SESSIONS ===\n";

$uniqueDates = $local->query(
    "SELECT DISTINCT DATE(created_at) as d FROM orders WHERE DATE(created_at) <= '$CUTOFF_DATE' ORDER BY d"
)->fetchAll(PDO::FETCH_COLUMN);

// Hapus sessions & orders outlet 8 yang dari migrasi sebelumnya
$existOrdIds = $lumero->query("SELECT id FROM orders WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existOrdIds) {
    $idList = implode(',', $existOrdIds);
    $lumero->exec("DELETE FROM order_items WHERE order_id IN ($idList)");
    $lumero->exec("DELETE FROM orders WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  Cleaned " . count($existOrdIds) . " old migrated orders\n";
}
$lumero->exec("DELETE FROM daily_store_sessions WHERE outlet_id = $NEW_OUTLET_ID");

$sessionMap = [];
$stmtSess = $lumero->prepare("INSERT INTO daily_store_sessions 
    (outlet_id, business_date, status, opened_by, opened_at, closed_by, closed_at, opening_cash, closing_cash_system, closing_cash_physical, cash_difference, created_at, updated_at)
    VALUES (?, ?, 'closed', ?, ?, ?, ?, 0, 0, 0, 0, NOW(), NOW())");

foreach ($uniqueDates as $date) {
    $stmtSess->execute([$NEW_OUTLET_ID, $date, $CASHIER_ID, $date.' 07:00:00', $CASHIER_ID, $date.' 21:00:00']);
    $sessionMap[$date] = $lumero->lastInsertId();
}
echo "  ✅ " . count($sessionMap) . " sessions dibuat\n";

// ============================================================
// STEP 4: Insert orders + items
// ============================================================
echo "\n=== STEP 4: INSERT ORDERS & ITEMS ===\n";

$orders = $local->query(
    "SELECT id, order_no, channel, order_source, payment_method, payment_status,
            subtotal, total, total_hpp, created_at
     FROM orders 
     WHERE DATE(created_at) <= '$CUTOFF_DATE'
     ORDER BY created_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$stmtOrder = $lumero->prepare("INSERT INTO orders 
    (outlet_id, daily_store_session_id, order_number, order_source, order_type, business_date,
     subtotal, discount_amount, tax_amount, service_amount, grand_total, total_hpp, gross_profit,
     loyalty_points_earned, loyalty_points_redeemed, loyalty_point_value, loyalty_redeem_amount,
     nominal_point, loyalty_claim_code, loyalty_claim_points, loyalty_claim_status,
     payment_status, order_status, cashier_id, created_at, updated_at)
    VALUES (?, ?, ?, 'cashier', 'dine_in', ?, ?, 0, 0, 0, ?, ?, ?, 0, 0, 0, 0, 0, NULL, 0, 'none', ?, 'completed', ?, ?, ?)");

$stmtItem = $lumero->prepare("INSERT INTO order_items 
    (order_id, product_variant_id, product_name_snapshot, variant_name_snapshot,
     qty, selling_price, discount_amount, tax_amount, subtotal, hpp_per_unit, total_hpp, gross_profit)
    VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)");

$totalOrders = 0;
$totalItems  = 0;
$skipped     = 0;
$unmapped    = [];

$payStatusMap = ['paid'=>'paid','pending'=>'unpaid','unpaid'=>'unpaid'];

foreach ($orders as $order) {
    $date      = substr($order['created_at'], 0, 10);
    $sessionId = $sessionMap[$date] ?? null;
    if (!$sessionId) continue;

    $payStatus   = $payStatusMap[$order['payment_status']] ?? 'paid';
    $grossProfit = $order['total'] - $order['total_hpp'];
    $orderNo     = 'HIST-' . $order['order_no'];

    $stmtOrder->execute([
        $NEW_OUTLET_ID, $sessionId, $orderNo, $date,
        $order['subtotal'], $order['total'], $order['total_hpp'], $grossProfit,
        $payStatus, $CASHIER_ID,
        $order['created_at'], $order['created_at']
    ]);
    $newOrderId = $lumero->lastInsertId();
    $totalOrders++;

    // Ambil items untuk order ini
    $items = $local->prepare(
        "SELECT item_type, chicken_part_id, chicken_style, sauce_id, with_rice,
                matcha_variant_id, kentang_variant_id, drink_variant_id, menu_item_id,
                item_name, qty, price, hpp, line_total, line_hpp
         FROM order_items WHERE order_id = ?"
    );
    $items->execute([$order['id']]);
    $itemRows = $items->fetchAll(PDO::FETCH_ASSOC);

    foreach ($itemRows as $item) {
        $variantId = null;
        switch ($item['item_type']) {
            case 'chicken':
                $sauceId  = (int)($item['sauce_id'] ?? 0);
                $partId   = (int)($item['chicken_part_id'] ?? 0);
                $style    = $item['chicken_style'] ?? 'original';
                $withRice = (int)($item['with_rice'] ?? 0);
                $key      = "{$partId}-{$style}-{$sauceId}-{$withRice}";
                $ruleId   = $chickenComboToRuleId[$key] ?? null;
                $variantId = $ruleId ? ($priceRuleMap[$ruleId] ?? null) : null;
                break;
            case 'matcha':
                $variantId = $item['matcha_variant_id'] ? ($matchaMap[(int)$item['matcha_variant_id']] ?? null) : null;
                break;
            case 'kentang':
                $variantId = $item['kentang_variant_id'] ? ($kentangMap[(int)$item['kentang_variant_id']] ?? null) : null;
                break;
            case 'drink':
                $variantId = $item['drink_variant_id'] ? ($kopiMap[(int)$item['drink_variant_id']] ?? null) : null;
                break;
            default: // menu
                $variantId = $item['menu_item_id'] ? ($menuMap[(int)$item['menu_item_id']] ?? null) : null;
        }

        if (!$variantId) {
            $skipped++;
            $unmapped[] = "{$order['order_no']}: {$item['item_type']} - {$item['item_name']}";
            continue;
        }

        $lineProfit = (int)$item['line_total'] - (int)$item['line_hpp'];
        $stmtItem->execute([
            $newOrderId, $variantId,
            $item['item_name'], $item['item_name'],
            $item['qty'], $item['price'],
            $item['line_total'], $item['hpp'], $item['line_hpp'], $lineProfit
        ]);
        $totalItems++;
    }

    if ($totalOrders % 100 === 0) echo "  ... $totalOrders orders diproses\n";
}

echo "\n" . str_repeat("=", 55) . "\n";
echo "✅ MIGRASI ORDERS SELESAI!\n";
echo str_repeat("=", 55) . "\n";
echo "📅 Tanggal : hingga $CUTOFF_DATE\n";
echo "🧾 Orders  : $totalOrders / " . count($orders) . "\n";
echo "📦 Items   : $totalItems\n";
echo "⚠️  Skipped : $skipped items\n";

if (!empty($unmapped)) {
    $unique = array_unique($unmapped);
    echo "\nUnmapped sample (max 20):\n";
    foreach (array_slice($unique, 0, 20) as $u) echo "  - $u\n";
}
