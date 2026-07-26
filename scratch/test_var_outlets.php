<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$variants = $pdo->query("SELECT pv.id, p.id as product_id, pv.variant_name, pv.sku, p.outlet_id as p_outlet, pv.outlet_id as pv_outlet FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.sku = 'VAR-273954-4986'")->fetchAll(PDO::FETCH_ASSOC);
print_r($variants);
