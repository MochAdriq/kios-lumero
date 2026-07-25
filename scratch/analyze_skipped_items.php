<?php
require_once __DIR__ . '/../helpers/functions.php';

$local = new PDO("mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$CUTOFF_DATE = '2026-07-24';

echo "=== ANALISIS ITEM YANG SKIP ===\n\n";

// 1. Semua distinct item_type
echo "--- SEMUA ITEM TYPE DI ORDER_ITEMS ---\n";
$rows = $local->query("
    SELECT oi.item_type, COUNT(*) as total 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    GROUP BY oi.item_type
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  {$r['item_type']}: {$r['total']}\n";

// 2. Chicken dengan sauce_id yang tidak ada di mapping kita
echo "\n--- CHICKEN ITEMS DENGAN SAUCE YANG TIDAK ADA ---\n";
$rows = $local->query("
    SELECT oi.sauce_id, oi.item_name, COUNT(*) as total
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    AND oi.item_type = 'chicken'
    AND oi.sauce_id NOT IN (1,2,3,4,5,6,7,8,9)
    AND oi.sauce_id IS NOT NULL
    GROUP BY oi.sauce_id, oi.item_name
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  sauce_id={$r['sauce_id']}: {$r['item_name']} ({$r['total']} x)\n";

// 3. Semua PROMO items
echo "\n--- PROMO ITEMS ---\n";
$rows = $local->query("
    SELECT oi.item_name, COUNT(*) as total
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    AND oi.item_type = 'promo'
    GROUP BY oi.item_name
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  {$r['item_name']}: {$r['total']} x\n";

// 4. DRINK items yang bukan minuman (yang muncul di unmapped)
echo "\n--- DRINK ITEMS YANG TIDAK ADA DI MAPPING ---\n";
$rows = $local->query("
    SELECT oi.item_name, oi.drink_variant_id, oi.menu_item_id, COUNT(*) as total
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    AND oi.item_type = 'drink'
    AND (oi.drink_variant_id NOT IN (1,2,3,4,5,6,7,8,9,10,11,12,13) OR oi.drink_variant_id IS NULL)
    GROUP BY oi.item_name, oi.drink_variant_id, oi.menu_item_id
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  '{$r['item_name']}' drink_var={$r['drink_variant_id']} menu_item={$r['menu_item_id']}: {$r['total']} x\n";

// 5. Sauces table untuk cek sauce_id garlic
echo "\n--- SAUCES TABLE (D'Celup) ---\n";
$rows = $local->query("SELECT id, name FROM sauces ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  ID {$r['id']}: {$r['name']}\n";

// 6. Total summary
echo "\n--- TOTAL SKIPPED BREAKDOWN ---\n";
$chicken_skip = $local->query("
    SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    AND oi.item_type = 'chicken'
    AND oi.sauce_id NOT IN (1,2,3,4,5,6,7,8,9)
    AND oi.sauce_id IS NOT NULL
")->fetchColumn();

$promo_skip = $local->query("
    SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    AND oi.item_type = 'promo'
")->fetchColumn();

$drink_skip = $local->query("
    SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) <= '$CUTOFF_DATE'
    AND oi.item_type = 'drink'
    AND (oi.drink_variant_id NOT IN (1,2,3,4,5,6,7,8,9,10,11,12,13) OR oi.drink_variant_id IS NULL)
")->fetchColumn();

echo "  Chicken (sauce tidak dikenal) : $chicken_skip items\n";
echo "  Promo items                    : $promo_skip items\n";
echo "  Drink (mapping tidak cocok)    : $drink_skip items\n";
echo "  Total perkiraan                : " . ($chicken_skip + $promo_skip + $drink_skip) . " items\n";
