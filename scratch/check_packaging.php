<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("SELECT id, name, outlet_id, is_active FROM raw_materials WHERE name LIKE '%cup%' OR name LIKE '%tutup%' OR name LIKE '%sendok%'"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
