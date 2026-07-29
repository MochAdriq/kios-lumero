<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt=$pdo->query("SELECT COUNT(*) as cnt FROM product_variants pv JOIN products p ON p.id = pv.product_id LEFT JOIN recipes r ON r.product_variant_id = pv.id WHERE pv.is_active = 1 AND p.outlet_id = 8 AND r.id IS NULL"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
