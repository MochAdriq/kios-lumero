<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$kalibunder = 2; // from previous output

$variants = $pdo->query("
    SELECT pv.id, p.name as product_name, pv.variant_name, pv.selling_price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $kalibunder AND pv.variant_name LIKE '%Original%'
")->fetchAll(PDO::FETCH_ASSOC);
echo "Kalibunder Original Variants:\n";
print_r($variants);

$potato = $pdo->query("
    SELECT pv.id, p.name as product_name, pv.variant_name, pv.selling_price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $kalibunder AND p.name LIKE '%Potato%'
")->fetchAll(PDO::FETCH_ASSOC);
echo "Kalibunder Potato Crispy:\n";
print_r($potato);
