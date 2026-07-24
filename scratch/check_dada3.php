<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
$stmt=$pdo->query("SELECT pv.id, pv.variant_name, p.name as p_name FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE p.name LIKE '%dada%' OR pv.variant_name LIKE '%dada%'");
$dadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require 'modules/recipe/RecipeModel.php';
$rm = new \Modules\Recipe\RecipeModel($pdo);
$outletId = 1;

foreach($dadas as &$d) {
    $d['ready_stock'] = $rm->calculateMaxYield((int)$d['id'], $outletId);
}
print_r($dadas);
