<?php
require 'core/Database.php';
require 'helpers/functions.php';
$pdo = Database::connection();
$q = $pdo->query("SELECT p.name as product_name, pv.name as variant_name FROM product_variants pv JOIN products p ON p.id = pv.product_id");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
