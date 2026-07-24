<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
$stmt=$pdo->query("SELECT pv.id, pv.variant_name, p.name as p_name, p.is_active as p_active, pv.is_active as v_active, rs.ready_stock FROM product_variants pv JOIN products p ON p.id=pv.product_id LEFT JOIN product_ready_stocks rs ON rs.variant_id=pv.id WHERE p.name LIKE '%dada%' OR pv.variant_name LIKE '%dada%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
