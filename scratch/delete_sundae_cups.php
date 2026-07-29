<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

$stmt = $pdo->prepare("DELETE FROM raw_materials WHERE name IN ('Cup Sundae', 'Tutup Cup Sundae') AND outlet_id = 5");
$stmt->execute();
echo "Deleted " . $stmt->rowCount() . " rows.\n";
