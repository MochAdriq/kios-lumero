<?php
$h='srv1864.hstgr.io';$d='u643003184_kios_lumero';$u='u643003184_kios_lumero';$p='Lawmotion1!@#';
try {
    $pdo = new PDO("mysql:host=$h;dbname=$d", $u, $p);
    $r=$pdo->query('DESCRIBE order_items')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($r,JSON_PRETTY_PRINT);
} catch (Exception $e) { echo $e->getMessage(); }
