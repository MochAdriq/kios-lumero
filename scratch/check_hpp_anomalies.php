<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8; // Pasekon

// Check anomalies in recipe_items cost_per_unit vs current average_cost
$anomalies = [];
$stmt = $pdo->query("
    SELECT 
        ri.id as item_id, 
        r.name as recipe_name, 
        rm.name as rm_name, 
        ri.cost_per_unit as item_cost, 
        COALESCE(orm.average_cost, rm.average_cost, 0) as current_rm_cost
    FROM recipe_items ri
    JOIN recipes r ON ri.recipe_id = r.id
    JOIN raw_materials rm ON ri.raw_material_id = rm.id
    LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = r.outlet_id
    WHERE r.outlet_id = $outlet_id AND ri.item_type = 'raw_material'
      AND ABS(ri.cost_per_unit - COALESCE(orm.average_cost, rm.average_cost, 0)) > 0.01
");
$rmAnomalies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- RM Cost Anomalies (recipe_items vs raw_materials) ---\n";
print_r(array_slice($rmAnomalies, 0, 10)); // print first 10
if(count($rmAnomalies) > 10) echo "... and " . (count($rmAnomalies) - 10) . " more.\n";

// Check if any "Bahan Jadi" in raw_materials has different cost than its sub_recipe
$stmt = $pdo->query("
    SELECT rm.id, rm.name as rm_name, rm.average_cost, sr.total_hpp, sr.yield_qty
    FROM raw_materials rm
    JOIN recipes sr ON rm.name LIKE CONCAT('%', sr.name, '%') AND sr.recipe_type = 'sub_recipe'
    WHERE rm.name LIKE 'Bahan Jadi%' AND sr.outlet_id = $outlet_id
");
$bahanJadiAnomalies = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $expectedCost = $row['yield_qty'] > 0 ? $row['total_hpp'] / $row['yield_qty'] : $row['total_hpp'];
    if (abs($row['average_cost'] - $expectedCost) > 0.01) {
        $row['expected_cost'] = $expectedCost;
        $bahanJadiAnomalies[] = $row;
    }
}
echo "\n--- Bahan Jadi Anomalies (raw_materials vs recipes) ---\n";
print_r($bahanJadiAnomalies);
