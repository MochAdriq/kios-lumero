<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// Get all variants for product ID 19 (Ayam Crispy)
$variants = $pdo->query("SELECT id, variant_name, sku FROM product_variants WHERE product_id = 19")->fetchAll(PDO::FETCH_ASSOC);

echo "Ayam Crispy Variants:\n";
foreach ($variants as $v) {
    // check recipe in outlet 8
    $r8 = $pdo->query("SELECT id, name FROM recipes WHERE product_variant_id = {$v['id']} AND outlet_id = 8")->fetch(PDO::FETCH_ASSOC);
    if ($r8) {
        echo " - {$v['variant_name']} (ID: {$v['id']}): HAS RECIPE in 8 ({$r8['id']}: {$r8['name']})\n";
    } else {
        echo " - {$v['variant_name']} (ID: {$v['id']}): NO RECIPE in 8\n";
    }
}
