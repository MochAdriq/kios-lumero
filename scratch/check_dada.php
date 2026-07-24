<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
$q = $pdo->query("SELECT pv.id, pv.variant_name as v_name, p.name as p_name, pv.stock FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE (p.name LIKE '%dada%' OR pv.variant_name LIKE '%dada%') AND p.is_active=1");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
