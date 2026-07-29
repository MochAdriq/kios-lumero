<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$outlet_id = 5;
$category_id = 110012; // Kemasan
$unit_id = 3; // pcs

$items = [
    'Cup Sundae',
    'Tutup Cup Sundae',
    'Sendok Es Krim'
];

foreach ($items as $name) {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? AND outlet_id = ?");
    $stmt->execute([$name, $outlet_id]);
    if ($stmt->fetch()) {
        echo "Already exists: $name\n";
        continue;
    }
    
    // Generate SKU
    $sku = 'RM-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    $insert = $pdo->prepare("INSERT INTO raw_materials (outlet_id, category_id, sku, name, unit_id, average_cost, is_active) VALUES (?, ?, ?, ?, ?, 0, 1)");
    $insert->execute([$outlet_id, $category_id, $sku, $name, $unit_id]);
    echo "Created: $name (SKU: $sku) for Outlet 5\n";
}
