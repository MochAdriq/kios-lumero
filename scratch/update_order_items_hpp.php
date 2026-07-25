<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "Updating order_items HPP...\n";
$stmt = $pdo->query("
    UPDATE order_items oi
    JOIN product_variants pv ON oi.product_variant_id = pv.id
    SET oi.hpp_per_unit = pv.hpp,
        oi.total_hpp = pv.hpp * oi.qty,
        oi.gross_profit = oi.subtotal - (pv.hpp * oi.qty)
    WHERE oi.hpp_per_unit = 0
");

echo "Rows affected: " . $stmt->rowCount() . "\n";

echo "Updating orders total_hpp...\n";
$stmt2 = $pdo->query("
    UPDATE orders o
    JOIN (
        SELECT order_id, SUM(total_hpp) as sum_hpp, SUM(gross_profit) as sum_gp 
        FROM order_items 
        GROUP BY order_id
    ) oi ON oi.order_id = o.id
    SET o.total_hpp = oi.sum_hpp,
        o.gross_profit = oi.sum_gp
    WHERE o.outlet_id = 8
");

echo "Orders affected: " . $stmt2->rowCount() . "\n";
