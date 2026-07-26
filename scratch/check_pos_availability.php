<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$stmt = $pdo->query("
    SELECT p.name, pv.variant_name, pv.sku, p.is_active as p_active, pv.is_active as pv_active 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $outlet_id 
    LIMIT 5
");
echo "=== Sample Products ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Stock Quantities ===\n";
$stmt = $pdo->query("
    SELECT rm.name, COALESCE(orm.stock_qty, rm.stock_qty, 0) as stock_qty 
    FROM raw_materials rm
    LEFT JOIN outlet_raw_materials orm ON rm.id = orm.raw_material_id AND orm.outlet_id = $outlet_id
    WHERE rm.outlet_id = 1 OR rm.outlet_id = $outlet_id
    ORDER BY rm.id ASC LIMIT 10
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Outlet POS Setting ===\n";
$stmt = $pdo->query("SELECT * FROM outlets WHERE id = $outlet_id");
$outlet = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Outlet ID 8 Name: " . $outlet['name'] . ", isActive: " . $outlet['is_active'] . "\n";
