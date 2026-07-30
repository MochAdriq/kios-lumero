<?php
$h='srv1864.hstgr.io';$d='u643003184_kios_lumero';$u='u643003184_kios_lumero';$p='Lawmotion1!@#';
$pdo = new PDO("mysql:host=$h;dbname=$d", $u, $p);
$res = $pdo->query('DESCRIBE orders')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
