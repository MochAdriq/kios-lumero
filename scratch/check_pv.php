<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$st=$pdo->query("SELECT pv.id, pv.outlet_id as pv_outlet, p.outlet_id as p_outlet, p.name, pv.variant_name FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE p.outlet_id = 5 LIMIT 10");
print_r($st->fetchAll(PDO::FETCH_ASSOC));
