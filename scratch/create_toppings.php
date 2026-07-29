<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$outlet_id = 5;
$category_id = 110009; // Bahan Makanan

$items = [
    // Powders (Unit: gr = 1)
    ['name' => 'Serbuk Topping Strawberry', 'unit_id' => 1],
    ['name' => 'Serbuk Topping Coklat', 'unit_id' => 1],
    ['name' => 'Serbuk Topping Matcha', 'unit_id' => 1],
    // Syrups (Unit: ml = 2)
    ['name' => 'Sirup Es Krim Strawberry', 'unit_id' => 2],
    ['name' => 'Sirup Es Krim Coklat', 'unit_id' => 2],
    ['name' => 'Sirup Es Krim Matcha', 'unit_id' => 2],
];

foreach ($items as $item) {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? AND outlet_id = ?");
    $stmt->execute([$item['name'], $outlet_id]);
    if ($stmt->fetch()) {
        echo "Already exists: {$item['name']}\n";
        continue;
    }
    
    // Generate SKU
    $sku = 'RM-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    $insert = $pdo->prepare("INSERT INTO raw_materials (outlet_id, category_id, sku, name, unit_id, average_cost, is_active) VALUES (?, ?, ?, ?, ?, 0, 1)");
    $insert->execute([$outlet_id, $category_id, $sku, $item['name'], $item['unit_id']]);
    echo "Created: {$item['name']} (SKU: $sku) for Outlet 5\n";
}
