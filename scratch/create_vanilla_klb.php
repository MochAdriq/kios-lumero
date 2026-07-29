<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$sku = 'RM-' . strtoupper(substr(md5(uniqid()), 0, 8));
$name = 'Bubuk Es Krim Vanilla';
$insert = $pdo->prepare("INSERT INTO raw_materials (outlet_id, category_id, sku, name, unit_id, average_cost, is_active) VALUES (5, 110009, ?, ?, 1, 0, 1)");
$insert->execute([$sku, $name]);
echo "Created: $name (SKU: $sku) for Outlet 5\n";
