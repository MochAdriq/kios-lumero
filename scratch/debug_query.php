<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$id = 1052;
$recipe = $pdo->query("
    SELECT r.*, 
           pv.variant_name, pv.sku, pv.selling_price, pv.hpp variant_hpp,
           p.name product_name,
           u.name as yield_unit_label
    FROM recipes r
    LEFT JOIN product_variants pv ON pv.id = r.product_variant_id
    LEFT JOIN products p ON p.id = pv.product_id
    LEFT JOIN units u ON u.id = r.yield_unit_id
    WHERE r.id = $id
")->fetch(PDO::FETCH_ASSOC);
print_r($recipe);
