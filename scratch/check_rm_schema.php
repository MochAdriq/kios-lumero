<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT id, name FROM raw_material_categories"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SHOW COLUMNS FROM raw_materials"); 
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
