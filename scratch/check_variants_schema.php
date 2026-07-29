<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SHOW COLUMNS FROM product_variants");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
