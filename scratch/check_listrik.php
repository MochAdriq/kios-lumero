<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT rm.id, rm.name, rm.sku, u.name as unit_name, u.symbol as unit_symbol, rm.average_cost FROM raw_materials rm LEFT JOIN units u ON rm.unit_id = u.id WHERE rm.name LIKE '%Listrik%'"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
