<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT id, variant_name, sku, selling_price, product_id FROM product_variants WHERE variant_name LIKE '%Sundae%' OR variant_name LIKE '%Topping%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
