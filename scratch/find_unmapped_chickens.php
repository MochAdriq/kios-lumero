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

// 1. Build maps
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

// Query all order items
$items = $local->query("
    SELECT oi.item_type, oi.chicken_part_id, oi.chicken_style, oi.sauce_id, oi.with_rice,
           oi.matcha_variant_id, oi.kentang_variant_id, oi.drink_variant_id, oi.menu_item_id,
           oi.item_name, oi.price, COUNT(*) as count, SUM(oi.line_total) as total_value
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    GROUP BY oi.item_type, oi.chicken_part_id, oi.chicken_style, oi.sauce_id, oi.with_rice, oi.matcha_variant_id, oi.kentang_variant_id, oi.drink_variant_id, oi.menu_item_id, oi.item_name, oi.price
")->fetchAll(PDO::FETCH_ASSOC);

$skippedGroups = [];
foreach ($items as $item) {
    $variantId = null;
    $reason = '';
    
    switch ($item['item_type']) {
        case 'chicken':
            $sauceId  = (int)($item['sauce_id'] ?? 0);
            $partId   = (int)($item['chicken_part_id'] ?? 0);
            $style    = $item['chicken_style'] ?? 'original';
            $withRice = (int)($item['with_rice'] ?? 0);
            $key      = "{$partId}-{$style}-{$sauceId}-{$withRice}";
            $ruleId   = $chickenComboToRuleId[$key] ?? null;
            if (!$ruleId) {
                $reason = "Combo not in dictionary: $key";
            } else {
                $variantId = $priceRuleMap[$ruleId] ?? null;
                if (!$variantId) {
                    $reason = "Rule ID $ruleId not found in priceRuleMap (SKU DC-AYM-{$ruleId}-PSKL missing)";
                }
            }
            break;
        case 'matcha':
            $variantId = $item['matcha_variant_id'] ? ($matchaMap[(int)$item['matcha_variant_id']] ?? null) : null;
            if (!$variantId) $reason = "Matcha variant {$item['matcha_variant_id']} not mapped";
            break;
        case 'kentang':
            $variantId = $item['kentang_variant_id'] ? ($kentangMap[(int)$item['kentang_variant_id']] ?? null) : null;
            if (!$variantId) $reason = "Kentang variant {$item['kentang_variant_id']} not mapped";
            break;
        case 'drink':
            $variantId = $item['drink_variant_id'] ? ($kopiMap[(int)$item['drink_variant_id']] ?? null) : null;
            if (!$variantId) $reason = "Drink variant {$item['drink_variant_id']} not mapped";
            break;
        default: // menu
            $variantId = $item['menu_item_id'] ? ($menuMap[(int)$item['menu_item_id']] ?? null) : null;
            if (!$variantId) $reason = "Menu item {$item['menu_item_id']} not mapped";
    }

    if (!$variantId) {
        $skippedGroups[] = [
            'item' => $item,
            'reason' => $reason
        ];
    }
}

echo "Found " . count($skippedGroups) . " unique unmapped variations.\n\n";

foreach ($skippedGroups as $group) {
    echo "- Name: " . $group['item']['item_name'] . "\n";
    echo "  Reason: " . $group['reason'] . "\n";
    echo "  Count: " . $group['item']['count'] . " (Total Value: " . $group['item']['total_value'] . ")\n\n";
}
