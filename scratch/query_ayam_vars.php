<?php
require 'helpers/functions.php'; 
require 'core/Database.php'; 
$pdo = Database::connection();
$stmt = $pdo->query("SELECT DISTINCT variant_name FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE p.outlet_id=5 AND p.name LIKE 'Ayam Crispy'");
$vars = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($vars as $v) echo $v['variant_name']."\n";
