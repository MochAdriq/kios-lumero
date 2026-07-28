<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

$st = $pdo->query("SELECT p.outlet_id, count(pv.id) FROM product_variants pv JOIN products p ON p.id = pv.product_id GROUP BY p.outlet_id");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

$st2 = $pdo->query("SELECT pv.id, pv.variant_name, p.name FROM product_variants pv JOIN products p ON p.id = pv.product_id LEFT JOIN recipes r ON r.product_variant_id = pv.id WHERE r.id IS NULL");
echo "Varian tanpa resep:\n";
print_r($st2->fetchAll(PDO::FETCH_ASSOC));
