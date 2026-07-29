<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$pdo->query("UPDATE product_variants pv 
             JOIN products p ON p.id = pv.product_id 
             SET pv.image = p.image 
             WHERE p.outlet_id = 2");
echo "Variants updated to match parent product images for KLB!";
