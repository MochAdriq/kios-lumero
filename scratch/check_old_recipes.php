<?php
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');

// Get Kalibunder variants and recipes
$kaliId = 2; // Kalibunder outlet id in old dump

echo "Kalibunder Original Variants & Recipes:\n";
$variants = $pdoOld->query("
    SELECT pv.id as variant_id, p.name as product_name, pv.name as variant_name, pv.price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $kaliId AND pv.name LIKE '%Original%'
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($variants as $v) {
    echo "- {$v['product_name']} | {$v['variant_name']} | Rp {$v['price']}\n";
    $recipes = $pdoOld->query("
        SELECT ii.name as item_name, pr.quantity, pr.unit 
        FROM product_recipes pr 
        JOIN inventory_items ii ON pr.inventory_item_id = ii.id 
        WHERE pr.product_variant_id = {$v['variant_id']}
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recipes as $r) {
        echo "   -> {$r['quantity']} {$r['unit']} {$r['item_name']}\n";
    }
}

echo "\nKalibunder Potato Crispy Original:\n";
$potato = $pdoOld->query("
    SELECT pv.id as variant_id, p.name as product_name, pv.name as variant_name, pv.price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $kaliId AND p.name LIKE '%Potato Crispy%' AND pv.name LIKE '%Original%'
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($potato as $v) {
    echo "- {$v['product_name']} | {$v['variant_name']} | Rp {$v['price']}\n";
    $recipes = $pdoOld->query("
        SELECT ii.name as item_name, pr.quantity, pr.unit 
        FROM product_recipes pr 
        JOIN inventory_items ii ON pr.inventory_item_id = ii.id 
        WHERE pr.product_variant_id = {$v['variant_id']}
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recipes as $r) {
        echo "   -> {$r['quantity']} {$r['unit']} {$r['item_name']}\n";
    }
}
