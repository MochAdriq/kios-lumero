<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

// Get packaging IDs for Outlet 5 (or Outlet 1 if not found)
$packaging = ['Cup 14 oz', 'Tutup Cup', 'Sendok Es Krim'];
foreach ($packaging as $p) {
    $stmt = $pdo->prepare("SELECT id, name, outlet_id FROM raw_materials WHERE name = ? AND (outlet_id = 5 OR outlet_id = 1) ORDER BY outlet_id DESC");
    $stmt->execute([$p]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
}

// Get Product Categories
$stmt = $pdo->query("SELECT id, name FROM product_categories");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

