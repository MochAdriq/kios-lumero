<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT id, name, unit_id FROM raw_materials WHERE name IN ('Air', '[Operational] Listrik', 'Bubuk Es Krim Vanilla', 'Bubuk Es Krim Coklat', 'Bubuk Es Krim Strawberry')"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
