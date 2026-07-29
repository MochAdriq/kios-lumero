<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT id, variant_name, sku FROM product_variants WHERE product_id = (SELECT id FROM products WHERE name = 'Es Krim Cup 14 oz' AND outlet_id = 5)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
