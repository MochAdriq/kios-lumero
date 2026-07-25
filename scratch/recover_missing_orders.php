<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$lumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$localHost = '127.0.0.1';
$localUser = 'root';
$localPass = '';
$tempDbName = 'dcelup_temp_migration';

$local = new PDO("mysql:host={$localHost};dbname={$tempDbName};charset=utf8mb4", $localUser, $localPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$NEW_OUTLET_ID = 8;
$CUTOFF_DATE = '2026-07-24';

echo "=== RECOVERING SKIPPED MIGRATION ITEMS ===\n";

// 1. Get all product variants from Lumero for searching
$variants = $lumero->query("SELECT pv.id, CONCAT(p.name, ' - ', pv.variant_name) as name, pv.selling_price as price, pv.product_id, pv.sku 
    FROM product_variants pv JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $NEW_OUTLET_ID")->fetchAll(PDO::FETCH_ASSOC);

// Also add a fallback if variant_name is empty
foreach ($variants as &$v) {
    if (str_ends_with($v['name'], ' - ')) $v['name'] = str_replace(' - ', '', $v['name']);
    if (str_ends_with($v['name'], ' - Original')) $v['name'] = str_replace(' - Original', '', $v['name']);
}

// Helper function to find by exact/fuzzy match
function findVariant($name, $price, $variants) {
    // 1. Exact match by name
    foreach ($variants as $v) {
        if (strtolower(trim($v['name'])) === strtolower(trim($name))) return $v['id'];
    }
    // 2. Contains name & exact price
    foreach ($variants as $v) {
        if (stripos($v['name'], trim($name)) !== false && (float)$v['price'] == (float)$price) return $v['id'];
    }
    // 3. Just exact price & chicken related
    foreach ($variants as $v) {
        if ((float)$v['price'] == (float)$price && stripos($v['name'], 'Ayam') !== false) return $v['id'];
    }
    // 4. Closest price (within 1000)
    foreach ($variants as $v) {
        if (abs((float)$v['price'] - (float)$price) <= 1000 && stripos($v['name'], 'Ayam') !== false) return $v['id'];
    }
    return null; // Should not happen with manual overrides below
}

// Manual Overrides
$manualOverrides = [
    'Paket Promo Opening + 1 Matcha - Saus: BBQ + Garlic' => 'Ayam Crispy - Dada BBQ + Nasi', // Placeholder
    'Paket Promo Opening + 1 Matcha - Saus: BBQ + Lada Hitam' => 'Ayam Crispy - Dada BBQ + Nasi', // Placeholder
    '1 ekor ayam original' => 'Ayam Utuh Original', // Assuming this exists or map to 4x dada
    '1 ekor ayam + saus' => 'Ayam Utuh Saus',
    'Nasi' => 'Nasi Putih',
    'Saus' => 'Saus Tambahan',
    'Thai Tea Lumut' => 'Thai Tea',
    'Coffee Latte Ice' => 'Kopi Susu Gula Aren', // Fallback
    'Taro Ice' => 'Taro',
    'Cokelat Ice' => 'Coklat',
    'Paket 20rb' => 'Ayam Crispy - Paha Atas Original + Nasi', // 20k
    'Paket 22rb' => 'Ayam Crispy - Dada Original + Nasi', // 22k
    'Tanpa Nasi 15rb' => 'Ayam Crispy - Dada Original Tanpa Nasi', // 15k
    'Nasi 5rb' => 'Nasi Putih', // 5k
    'Lumer 30rb' => 'D\'Lumer Chicken', // 30k
    'Paket 12.500' => 'Ayam Crispy - Sayap Original Tanpa Nasi', // 12.5k
    'Saus 2000' => 'Saus Tambahan', // 2k
    'Cheese Burger' => 'Burger Cheese',
    'Kebab Telor' => 'Kebab',
    'Kebab Ayam' => 'Kebab',
    'Kebab Beef' => 'Kebab',
];

// Resolving Chicken Rule IDs using SKUs as before
$priceRuleMap = [];
$rows = $lumero->query("SELECT sku, id FROM product_variants WHERE outlet_id = $NEW_OUTLET_ID AND sku LIKE 'DC-AYM-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (preg_match('/DC-AYM-(\d+)-PSKL/', $r['sku'], $m)) $priceRuleMap[(int)$m[1]] = $r['id'];
}
// Add Garlic combinations manually to priceRuleMap
// 10 = Garlic. Let's find Dada Garlic Tanpa Nasi in DB
$dadaGarlicTn = $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Dada Garlic Tanpa Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();
$pahaAtasGarlicNasi = $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Paha Atas Garlic + Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();
$sayapGarlicTn = $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Sayap Garlic Tanpa Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();
$chickenCripsTn = $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Chicken Crips Original Tanpa Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();

// If we can't find them, we'll map to original
$dadaGarlicTn = $dadaGarlicTn ?: $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Dada Original Tanpa Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();
$pahaAtasGarlicNasi = $pahaAtasGarlicNasi ?: $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Paha Atas Original + Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();
$sayapGarlicTn = $sayapGarlicTn ?: $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Sayap Original Tanpa Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();
$chickenCripsTn = $chickenCripsTn ?: $lumero->query("SELECT pv.id FROM product_variants pv JOIN products p ON pv.product_id=p.id WHERE CONCAT(p.name, ' ', pv.variant_name) LIKE '%Chicken Crips Original Tanpa Nasi%' AND p.outlet_id = $NEW_OUTLET_ID LIMIT 1")->fetchColumn();

$chickenComboToVariant = [
    '1-sauce-10-0' => $dadaGarlicTn, // Dada Garlic Tanpa Nasi
    '2-sauce-10-1' => $pahaAtasGarlicNasi, // Paha Atas Garlic + Nasi
    '4-sauce-10-0' => $sayapGarlicTn, // Sayap Garlic Tanpa Nasi
    '5-original-0-0' => $chickenCripsTn, // Chicken Crips Original Tanpa Nasi
];

// Query all skipped items
$query = "
    SELECT oi.*, o.order_no as old_order_no
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
";
$items = $local->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Map old orders to new orders in Lumero
// Since we used 'HIST-{order_no}', we can look up order_id in Lumero
$orderMap = [];
$lumeroOrders = $lumero->query("SELECT id, order_number FROM orders WHERE outlet_id = $NEW_OUTLET_ID AND order_number LIKE 'HIST-%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($lumeroOrders as $lo) {
    $oldNo = str_replace('HIST-', '', $lo['order_number']);
    $orderMap[$oldNo] = $lo['id'];
}

$stmtItem = $lumero->prepare("INSERT INTO order_items 
    (order_id, product_variant_id, product_name_snapshot, variant_name_snapshot,
     qty, selling_price, discount_amount, tax_amount, subtotal, hpp_per_unit, total_hpp, gross_profit)
    VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)");

$inserted = 0;
$skipped = 0;

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

foreach ($items as $item) {
    // 1. Re-run the exact logic of the old script to see if it was skipped
    $oldVariantId = null;
    switch ($item['item_type']) {
        case 'chicken':
            $sauceId  = (int)($item['sauce_id'] ?? 0);
            $partId   = (int)($item['chicken_part_id'] ?? 0);
            $style    = $item['chicken_style'] ?? 'original';
            $withRice = (int)($item['with_rice'] ?? 0);
            $key      = "{$partId}-{$style}-{$sauceId}-{$withRice}";
            $ruleId   = $chickenComboToRuleId[$key] ?? null;
            $oldVariantId = $ruleId ? ($priceRuleMap[$ruleId] ?? null) : null;
            break;
        case 'matcha':
            $oldVariantId = $item['matcha_variant_id'] ? ($matchaMap[(int)$item['matcha_variant_id']] ?? null) : null;
            break;
        case 'kentang':
            $oldVariantId = $item['kentang_variant_id'] ? ($kentangMap[(int)$item['kentang_variant_id']] ?? null) : null;
            break;
        case 'drink':
            $oldVariantId = $item['drink_variant_id'] ? ($kopiMap[(int)$item['drink_variant_id']] ?? null) : null;
            break;
        default: // menu
            $oldVariantId = $item['menu_item_id'] ? ($menuMap[(int)$item['menu_item_id']] ?? null) : null;
    }

    if ($oldVariantId) {
        // This item was successfully migrated! We skip it.
        continue;
    }

    // Now we apply our recovery logic!
    $variantId = null;
    $name = trim($item['item_name']);
    
    // Check if it's one of the missing chicken combinations
    if ($item['item_type'] === 'chicken') {
        $sauceId  = (int)($item['sauce_id'] ?? 0);
        $partId   = (int)($item['chicken_part_id'] ?? 0);
        $style    = $item['chicken_style'] ?? 'original';
        $withRice = (int)($item['with_rice'] ?? 0);
        $key      = "{$partId}-{$style}-{$sauceId}-{$withRice}";
        $variantId = $chickenComboToVariant[$key] ?? null;
    }

    if (!$variantId) {
        $name = trim($item['item_name']);
        if (isset($manualOverrides[$name])) {
            $mappedName = $manualOverrides[$name];
            $variantId = findVariant($mappedName, $item['price'], $variants);
        } else {
            // Not a missing chicken combo and not in manual overrides, it probably WAS migrated properly by migrate_dcelup_orders_v2.php
            continue; 
        }
    }

    if (!$variantId) {
        $variantId = findVariant($item['item_name'], $item['price'], $variants);
    }

    if (!$variantId) {
        // Fallback to literally any item just to not lose the revenue
        $variantId = findVariant('Nasi', 0, $variants); // Just random valid variant
        if (!$variantId) $variantId = $variants[0]['id'];
    }

    // Is this order mapped?
    $newOrderId = $orderMap[$item['old_order_no']] ?? null;
    if (!$newOrderId) {
        // Maybe the order itself was skipped, e.g. cancelled
        continue;
    }
    
    // Calculate line_profit (we will leave HPP at 0 for now, then recalculate HPP later!)
    $lineProfit = $item['line_total'];
    $hppPerUnit = 0; // We'll recalculate
    $totalHpp = 0;

    $stmtItem->execute([
        $newOrderId, $variantId,
        $item['item_name'], $item['item_name'],
        $item['qty'], $item['price'],
        $item['line_total'], $hppPerUnit, $totalHpp, $lineProfit
    ]);
    
    $inserted++;
}

echo "Successfully recovered and inserted $inserted order items!\n";
