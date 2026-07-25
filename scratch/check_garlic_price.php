<?php
$local = new PDO("mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Ambil semua price_rules untuk sauce_id=10 (Garlic)
echo "=== PRICE RULES GARLIC (sauce_id=10) ===\n";
$rows = $local->query("SELECT id, item_name, chicken_part_id, chicken_style, with_rice, price, hpp, is_active FROM price_rules WHERE sauce_id = 10 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  ID {$r['id']}: {$r['item_name']} | harga={$r['price']} hpp={$r['hpp']} active={$r['is_active']}\n";

// Cek juga dari order items — harga aktual yang dijual
echo "\n=== HARGA GARLIC DARI ORDER ITEMS AKTUAL ===\n";
$rows = $local->query("
    SELECT item_name, price, hpp, COUNT(*) as cnt
    FROM order_items WHERE sauce_id = 10
    GROUP BY item_name, price, hpp
    ORDER BY item_name
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  {$r['item_name']}: harga={$r['price']} hpp={$r['hpp']} ({$r['cnt']}x)\n";
