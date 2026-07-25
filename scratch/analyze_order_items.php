<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 1. Total order items for Outlet 8
$total = $pdo->query("SELECT count(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.outlet_id = 8")->fetchColumn();
echo "Total order_items: $total\n";

// 2. Count items with product_variant_id = 0 or NULL
$missingVariant = $pdo->query("SELECT count(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.outlet_id = 8 AND (oi.product_variant_id IS NULL OR oi.product_variant_id = 0)")->fetchColumn();
echo "Order items with missing product_variant_id: $missingVariant\n";

// 3. Count items with 0 HPP
$zeroHpp = $pdo->query("SELECT count(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.outlet_id = 8 AND (oi.hpp_per_unit = 0 OR oi.hpp_per_unit IS NULL)")->fetchColumn();
echo "Order items with 0 HPP: $zeroHpp\n";

// 4. Sample the distinct product_name_snapshot for missing variant or 0 HPP
$samples = $pdo->query("
    SELECT oi.product_name_snapshot, oi.variant_name_snapshot, count(*) as count, SUM(oi.subtotal) as total_sales, SUM(oi.qty) as total_qty 
    FROM order_items oi JOIN orders o ON o.id = oi.order_id 
    WHERE o.outlet_id = 8 AND (oi.product_variant_id IS NULL OR oi.product_variant_id = 0 OR oi.hpp_per_unit = 0)
    GROUP BY oi.product_name_snapshot, oi.variant_name_snapshot
    ORDER BY count DESC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

echo "\nTop Items with Missing Variant or 0 HPP:\n";
print_r($samples);

// 5. Total sales value affected
$affectedSales = $pdo->query("
    SELECT SUM(oi.subtotal) 
    FROM order_items oi JOIN orders o ON o.id = oi.order_id 
    WHERE o.outlet_id = 8 AND (oi.product_variant_id IS NULL OR oi.product_variant_id = 0 OR oi.hpp_per_unit = 0)
")->fetchColumn();
echo "\nTotal Sales Value Affected: Rp " . number_format($affectedSales ?? 0, 0, ',', '.') . "\n";
