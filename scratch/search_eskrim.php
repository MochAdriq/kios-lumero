<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT id, name, sku, unit_id, average_cost FROM raw_materials WHERE name LIKE '%es%' OR name LIKE '%krim%' OR name LIKE '%ice%' OR name LIKE '%cream%'"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
