<?php
/**
 * MIGRASI ORDERS D'CELUP → LUMERO OUTLET 8
 * ==========================================
 * Strategi:
 * 1. Parse orders dari SQL dump D'Celup
 * 2. Buat daily_store_session per hari unik
 * 3. Insert orders ke Lumero dengan mapping:
 *    - item_type='chicken' → price_rule mapping → product_variant_id
 *    - item_type='matcha'  → matcha_variant_id → product_variant_id
 *    - item_type='kentang' → kentang_variant_id → product_variant_id
 *    - item_type='drink'   → kopi_variant_id → product_variant_id
 *    - item_type='menu'    → menu_item_id → product_variant_id
 *
 * Hanya orders sampai 2026-07-24
 */

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

set_time_limit(300); // 5 menit untuk proses besar

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$lumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$NEW_OUTLET_ID = 8;
$CASHIER_ID    = 6; // kasir_pasekon
$CUTOFF_DATE   = '2026-07-24';

// ============================================================
// STEP 0: Ambil mapping product_variant_id dari Lumero outlet 8
// ============================================================
echo "=== STEP 0: BUILD VARIANT MAPPING ===\n";

// Mapping: price_rule_id → product_variant_id
// Kita ambil berdasarkan SKU yang sudah kita buat: DC-AYM-{ruleId}-PSKL
$priceRuleMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-AYM-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-AYM-(\d+)-PSKL/', $r['sku'], $m)) {
        $priceRuleMap[(int)$m[1]] = $r['id'];
    }
}
echo "  Price rule mappings: " . count($priceRuleMap) . "\n";

// Mapping: matcha_variant_id → product_variant_id (DC-MCH-{id}-PSKL)
$matchaMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-MCH-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-MCH-(\d+)-PSKL/', $r['sku'], $m)) {
        $matchaMap[(int)$m[1]] = $r['id'];
    }
}
echo "  Matcha mappings: " . count($matchaMap) . "\n";

// Mapping: kentang_variant_id → product_variant_id (DC-KTG-{id}-PSKL)
$kentangMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-KTG-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-KTG-(\d+)-PSKL/', $r['sku'], $m)) {
        $kentangMap[(int)$m[1]] = $r['id'];
    }
}
echo "  Kentang mappings: " . count($kentangMap) . "\n";

// Mapping: kopi_variant_id → product_variant_id (DC-KOP-{id}-PSKL)
$kopiMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-KOP-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-KOP-(\d+)-PSKL/', $r['sku'], $m)) {
        $kopiMap[(int)$m[1]] = $r['id'];
    }
}
echo "  Kopi mappings: " . count($kopiMap) . "\n";

// Mapping: menu_item_id → product_variant_id (DC-MNU-{id}-PSKL-VAR)
$menuMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-MNU-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-MNU-(\d+)-PSKL/', $r['sku'], $m)) {
        $menuMap[(int)$m[1]] = $r['id'];
    }
}
echo "  Menu item mappings: " . count($menuMap) . "\n";

// Mapping price_rules yang butuh ruleId → chicken_part_id + chicken_style + sauce_id + with_rice
// Ini kebalikan — kita perlu buat lookup dari kombinasi → ruleId
// Berdasarkan data price_rules yang sudah kita masukkan ke script sebelumnya
$chickenComboToRuleId = [
    // [part_id, style, sauce_id, with_rice] → rule_id
    '1-original-0-0' =>  1, '2-original-0-0' =>  2, '3-original-0-0' =>  3, '4-original-0-0' =>  4,
    '1-original-0-1' =>  8, '2-original-0-1' =>  9, '3-original-0-1' => 10, '4-original-0-1' => 11,
    '1-sauce-1-0'    => 15, '2-sauce-1-0'    => 16, '3-sauce-1-0'    => 17, '4-sauce-1-0'    => 18,
    '1-sauce-2-0'    => 19, '2-sauce-2-0'    => 20, '3-sauce-2-0'    => 21, '4-sauce-2-0'    => 22,
    '1-sauce-3-0'    => 23, '2-sauce-3-0'    => 24, '3-sauce-3-0'    => 25, '4-sauce-3-0'    => 26,
    '1-sauce-4-0'    => 27, '2-sauce-4-0'    => 28, '3-sauce-4-0'    => 29, '4-sauce-4-0'    => 30,
    '1-sauce-5-0'    => 31, '2-sauce-5-0'    => 32, '3-sauce-5-0'    => 33, '4-sauce-5-0'    => 34,
    '1-sauce-6-0'    => 35, '2-sauce-6-0'    => 36, '3-sauce-6-0'    => 37, '4-sauce-6-0'    => 38,
    '1-sauce-7-0'    => 39, '2-sauce-7-0'    => 40, '3-sauce-7-0'    => 41, '4-sauce-7-0'    => 42,
    '1-sauce-8-0'    => 43, '2-sauce-8-0'    => 44, '3-sauce-8-0'    => 45, '4-sauce-8-0'    => 46,
    '1-sauce-9-0'    => 47, '2-sauce-9-0'    => 48, '3-sauce-9-0'    => 49, '4-sauce-9-0'    => 50,
    '1-sauce-1-1'    => 78, '2-sauce-1-1'    => 79, '3-sauce-1-1'    => 80, '4-sauce-1-1'    => 81,
    '1-sauce-2-1'    => 82, '2-sauce-2-1'    => 83, '3-sauce-2-1'    => 84, '4-sauce-2-1'    => 85,
    '1-sauce-3-1'    => 86, '2-sauce-3-1'    => 87, '3-sauce-3-1'    => 88, '4-sauce-3-1'    => 89,
    '1-sauce-4-1'    => 90, '2-sauce-4-1'    => 91, '3-sauce-4-1'    => 92, '4-sauce-4-1'    => 93,
    '1-sauce-5-1'    => 94, '2-sauce-5-1'    => 95, '3-sauce-5-1'    => 96, '4-sauce-5-1'    => 97,
    '1-sauce-6-1'    => 98, '2-sauce-6-1'    => 99, '3-sauce-6-1'    =>100, '4-sauce-6-1'    =>101,
    '1-sauce-7-1'    =>102, '2-sauce-7-1'    =>103, '3-sauce-7-1'    =>104, '4-sauce-7-1'    =>105,
    '1-sauce-8-1'    =>106, '2-sauce-8-1'    =>107, '3-sauce-8-1'    =>108, '4-sauce-8-1'    =>109,
    '1-sauce-9-1'    =>110, '2-sauce-9-1'    =>111, '3-sauce-9-1'    =>112, '4-sauce-9-1'    =>113,
];

// ============================================================
// STEP 1: Parse SQL dump untuk order data
// ============================================================
echo "\n=== STEP 1: PARSE SQL DUMP (orders) ===\n";

$sqlFile = 'C:/Users/HYPE R Series/Downloads/u643003184_newcelup (5).sql';
$handle = fopen($sqlFile, 'r');

$ordersRaw   = []; // id => [...]
$orderItemsRaw = []; // order_id => [[...], ...]

$inOrders     = false;
$inOrderItems = false;
$buffer       = '';

while (!feof($handle)) {
    $line = fgets($handle);

    if (strpos($line, 'INSERT INTO `orders`') !== false) {
        $inOrders = true;
        $inOrderItems = false;
        $buffer = $line;
        continue;
    }
    if (strpos($line, 'INSERT INTO `order_items`') !== false) {
        $inOrderItems = true;
        $inOrders = false;
        $buffer = $line;
        continue;
    }

    // Detect end of insert block (blank line atau CREATE TABLE baru)
    if ($inOrders || $inOrderItems) {
        if (trim($line) === '' || strpos($line, 'CREATE TABLE') !== false || strpos($line, '-- -----') !== false) {
            // Parse buffer
            if ($inOrders) {
                // Extract values menggunakan regex
                preg_match_all('/\((\d+),\s*\d+,\s*\d+,\s*\'([^\']+)\',\s*\'([^\']+)\',\s*\'([^\']+)\',\s*(?:NULL|\'[^\']*\'),\s*(?:NULL|\'[^\']*\'),\s*(?:NULL|\d+),\s*\'([^\']+)\',\s*\'([^\']+)\',\s*(\d+),\s*\d+,\s*\d+,\s*(?:NULL|\'[^\']*\'),\s*(\d+),\s*(\d+),\s*\d+,\s*\d+,/', $buffer, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $orderId   = (int)$m[1];
                    $orderNo   = $m[2];
                    $channel   = $m[3];  // kasir/online
                    $source    = $m[4];
                    $payMethod = $m[5];
                    $payStatus = $m[6];
                    $subtotal  = (int)$m[7];
                    $total     = (int)$m[8];
                    $totalHpp  = (int)$m[9];
                    // Extract created_at
                    preg_match('/\'' . preg_quote($m[2], '/') . '\'.*?\'(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\'/s', $buffer, $dtm);
                    $createdAt = $dtm[1] ?? '2026-05-17 00:00:00';

                    $date = substr($createdAt, 0, 10);
                    if ($date <= $CUTOFF_DATE) {
                        $ordersRaw[$orderId] = [
                            'order_no'   => $orderNo,
                            'channel'    => $channel,
                            'pay_method' => $payMethod,
                            'pay_status' => $payStatus,
                            'subtotal'   => $subtotal,
                            'total'      => $total,
                            'total_hpp'  => $totalHpp,
                            'created_at' => $createdAt,
                            'date'       => $date,
                        ];
                    }
                }
            }
            if ($inOrderItems) {
                // Parse order_items
                preg_match_all('/\((\d+),\s*\d+,\s*\d+,\s*(\d+),\s*\'(\w+)\',\s*(?:NULL|(\d+)),\s*(?:NULL|\'(\w+)\'),\s*(?:NULL|(\d+)),\s*(\d+),\s*(?:NULL|(\d+)),\s*(?:NULL|(\d+)),\s*(?:NULL|(\d+)),\s*(?:NULL|\d+),\s*(?:NULL|(\d+)),\s*\'([^\']+)\',\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),/', $buffer, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $itemId     = (int)$m[1];
                    $orderId    = (int)$m[2];
                    $itemType   = $m[3];
                    $partId     = !empty($m[4]) ? (int)$m[4] : null;
                    $style      = !empty($m[5]) ? $m[5] : null;
                    $sauceId    = !empty($m[6]) ? (int)$m[6] : null;
                    $withRice   = (int)$m[7];
                    $matchaVarId  = !empty($m[8]) ? (int)$m[8] : null;
                    $kentangVarId = !empty($m[9]) ? (int)$m[9] : null;
                    $drinkVarId   = !empty($m[10]) ? (int)$m[10] : null;
                    $menuItemId   = !empty($m[11]) ? (int)$m[11] : null;
                    $itemName   = $m[12];
                    $qty        = (int)$m[13];
                    $price      = (int)$m[14];
                    $hpp        = (int)$m[15];
                    $lineTotal  = (int)$m[16];
                    $lineHpp    = (int)$m[17];

                    if (isset($ordersRaw[$orderId])) {
                        $orderItemsRaw[$orderId][] = compact(
                            'itemType', 'partId', 'style', 'sauceId', 'withRice',
                            'matchaVarId', 'kentangVarId', 'drinkVarId', 'menuItemId',
                            'itemName', 'qty', 'price', 'hpp', 'lineTotal', 'lineHpp'
                        );
                    }
                }
            }
            $inOrders = false;
            $inOrderItems = false;
            $buffer = '';
            continue;
        }
        $buffer .= $line;
    }
}
fclose($handle);

echo "  Orders parsed: " . count($ordersRaw) . "\n";
$totalItems = array_sum(array_map('count', $orderItemsRaw));
echo "  Order items parsed: $totalItems\n";

// ============================================================
// STEP 2: Buat daily_store_sessions per hari unik
// ============================================================
echo "\n=== STEP 2: BUAT DAILY STORE SESSIONS ===\n";

$uniqueDates = [];
foreach ($ordersRaw as $o) {
    $uniqueDates[$o['date']] = true;
}
ksort($uniqueDates);

// Hapus sessions lama outlet 8 yang terkait migrasi
$existingSessions = $lumero->query("SELECT id FROM daily_store_sessions WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingSessions) {
    echo "  Existing sessions found: " . count($existingSessions) . " — akan tetap dipertahankan\n";
}

// Buat session baru per tanggal yang belum ada
$sessionMap = []; // date => session_id
foreach ($existingSessions as $sid) {
    $row = $lumero->query("SELECT business_date FROM daily_store_sessions WHERE id = $sid")->fetch(PDO::FETCH_ASSOC);
    if ($row) $sessionMap[$row['business_date']] = $sid;
}

$newSessions = 0;
$stmt = $lumero->prepare("INSERT INTO daily_store_sessions 
    (outlet_id, business_date, status, opened_by, opened_at, closed_by, closed_at, opening_cash, closing_cash_system, closing_cash_physical, cash_difference, created_at, updated_at)
    VALUES (?, ?, 'closed', ?, ?, ?, ?, 0, 0, 0, 0, NOW(), NOW())");

foreach (array_keys($uniqueDates) as $date) {
    if (isset($sessionMap[$date])) continue;
    $openedAt = $date . ' 07:00:00';
    $closedAt = $date . ' 21:00:00';
    $stmt->execute([$NEW_OUTLET_ID, $date, $CASHIER_ID, $openedAt, $CASHIER_ID, $closedAt]);
    $sessionMap[$date] = $lumero->lastInsertId();
    $newSessions++;
}
echo "  New sessions created: $newSessions\n";
echo "  Total sessions: " . count($sessionMap) . " hari operasional\n";

// ============================================================
// STEP 3: Helper mapping order item → product_variant_id
// ============================================================
function resolveVariantId($item, $priceRuleMap, $chickenComboToRuleId, $matchaMap, $kentangMap, $kopiMap, $menuMap) {
    switch ($item['itemType']) {
        case 'chicken':
            $partId  = $item['partId'] ?? 0;
            $style   = $item['style'] ?? 'original';
            $sauceId = $item['sauceId'] ?? 0;
            $withRice = $item['withRice'] ?? 0;
            $comboKey = "{$partId}-{$style}-{$sauceId}-{$withRice}";
            $ruleId = $chickenComboToRuleId[$comboKey] ?? null;
            return $ruleId ? ($priceRuleMap[$ruleId] ?? null) : null;

        case 'matcha':
            return $item['matchaVarId'] ? ($matchaMap[$item['matchaVarId']] ?? null) : null;

        case 'kentang':
            return $item['kentangVarId'] ? ($kentangMap[$item['kentangVarId']] ?? null) : null;

        case 'drink':
            return $item['drinkVarId'] ? ($kopiMap[$item['drinkVarId']] ?? null) : null;

        case 'menu':
        default:
            return $item['menuItemId'] ? ($menuMap[$item['menuItemId']] ?? null) : null;
    }
}

// ============================================================
// STEP 4: Insert Orders + Order Items ke Lumero
// ============================================================
echo "\n=== STEP 4: INSERT ORDERS & ITEMS ===\n";

// Bersihkan orders lama outlet 8 dari migrasi sebelumnya
$existingOrderIds = $lumero->query("SELECT id FROM orders WHERE outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_COLUMN);
if ($existingOrderIds) {
    $idList = implode(',', $existingOrderIds);
    $lumero->exec("DELETE FROM order_items WHERE order_id IN ($idList)");
    $lumero->exec("DELETE FROM orders WHERE outlet_id = $NEW_OUTLET_ID");
    echo "  Cleaned " . count($existingOrderIds) . " existing migrated orders\n";
}

$stmtOrder = $lumero->prepare("INSERT INTO orders 
    (outlet_id, daily_store_session_id, order_number, order_source, order_type, business_date,
     subtotal, discount_amount, tax_amount, service_amount, grand_total, total_hpp, gross_profit,
     loyalty_points_earned, loyalty_points_redeemed, loyalty_point_value, loyalty_redeem_amount,
     nominal_point, loyalty_claim_code, loyalty_claim_points, loyalty_claim_status,
     payment_status, order_status, cashier_id, created_at, updated_at)
    VALUES (?, ?, ?, ?, 'dine_in', ?, ?, 0, 0, 0, ?, ?, ?, 0, 0, 0, 0, 0, NULL, 0, 'none', ?, 'completed', ?, ?, NOW())");

$stmtItem = $lumero->prepare("INSERT INTO order_items 
    (order_id, product_variant_id, product_name_snapshot, variant_name_snapshot,
     qty, selling_price, discount_amount, tax_amount, subtotal, hpp_per_unit, total_hpp, gross_profit)
    VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)");

$totalOrders     = 0;
$totalOrderItems = 0;
$skippedItems    = 0;
$unmappedLog     = [];

// Mapping payment_status D'Celup → Lumero
$payStatusMap = [
    'paid'    => 'paid',
    'pending' => 'unpaid',
    'unpaid'  => 'unpaid',
    'refund'  => 'refunded',
];

// Mapping order_source D'Celup → Lumero enum
$orderSourceMap = [
    'kasir'   => 'cashier',
    'wic'     => 'cashier',
    'online'  => 'self_order',
    'qris'    => 'cashier',
];

foreach ($ordersRaw as $oldOrderId => $order) {
    $sessionId  = $sessionMap[$order['date']] ?? null;
    if (!$sessionId) continue;

    $orderSource = $orderSourceMap[$order['channel']] ?? 'cashier';
    $payStatus   = $payStatusMap[$order['pay_status']] ?? 'paid';
    $grossProfit = $order['total'] - $order['total_hpp'];

    $stmtOrder->execute([
        $NEW_OUTLET_ID,
        $sessionId,
        'HIST-' . $order['order_no'],
        $orderSource,
        $order['date'],
        $order['subtotal'],
        $order['total'],
        $order['total_hpp'],
        $grossProfit,
        $payStatus,
        $CASHIER_ID,
        $order['created_at'],
    ]);
    $newOrderId = $lumero->lastInsertId();
    $totalOrders++;

    // Insert order items
    $items = $orderItemsRaw[$oldOrderId] ?? [];
    foreach ($items as $item) {
        $variantId = resolveVariantId($item, $priceRuleMap, $chickenComboToRuleId, $matchaMap, $kentangMap, $kopiMap, $menuMap);

        if (!$variantId) {
            $skippedItems++;
            $unmappedLog[] = "order {$order['order_no']}: {$item['itemType']} - {$item['itemName']}";
            continue;
        }

        $lineProfit = $item['lineTotal'] - $item['lineHpp'];
        $stmtItem->execute([
            $newOrderId,
            $variantId,
            $item['itemName'],     // product_name_snapshot
            $item['itemName'],     // variant_name_snapshot
            $item['qty'],
            $item['price'],
            $item['lineTotal'],
            $item['hpp'],
            $item['lineHpp'],
            $lineProfit,
        ]);
        $totalOrderItems++;
    }
}

echo "  ✅ Orders   : $totalOrders\n";
echo "  ✅ Items    : $totalOrderItems\n";
echo "  ⚠️  Skipped  : $skippedItems items (tidak ada mapping)\n";

if (!empty($unmappedLog)) {
    echo "\n  Unmapped items sample (max 20):\n";
    foreach (array_slice($unmappedLog, 0, 20) as $log) {
        echo "    - $log\n";
    }
}

echo "\n" . str_repeat("=", 55) . "\n";
echo "✅ MIGRASI ORDERS SELESAI!\n";
echo str_repeat("=", 55) . "\n";
echo "📅 Tanggal: hingga $CUTOFF_DATE\n";
echo "🧾 Orders : $totalOrders\n";
echo "📦 Items  : $totalOrderItems\n";
echo "⚠️  Skipped: $skippedItems items\n";
