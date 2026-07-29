<?php
require 'helpers/functions.php'; 
require 'core/Database.php'; 
$pdo = Database::connection();
$stmt = $pdo->query("SELECT p.outlet_id, pv.id, pv.variant_name, p.name FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE (pv.variant_name LIKE '%italian%' OR pv.variant_name LIKE '%barbeque%')");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
