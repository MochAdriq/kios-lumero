<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$pdo->query("UPDATE products SET image = 'images/pos-products/ayam-klb.png' WHERE image = 'assets/images/pos-products/ayam-klb.png'");
echo 'Fixed paths!';
