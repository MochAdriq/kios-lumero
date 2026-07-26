<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8; // Pasekon

$stmt = $pdo->query("
    SELECT pv.id as variant_id, pv.variant_name, pv.hpp as variant_hpp, r.total_hpp as recipe_hpp
    FROM product_variants pv
    JOIN recipes r ON r.product_variant_id = pv.id
    WHERE pv.outlet_id = $outlet_id AND ABS(pv.hpp - r.total_hpp) > 0.01
");
$pvAnomalies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- Variant HPP Anomalies (product_variants vs recipes) ---\n";
print_r(array_slice($pvAnomalies, 0, 10)); // print first 10
if(count($pvAnomalies) > 10) echo "... and " . (count($pvAnomalies) - 10) . " more.\n";

