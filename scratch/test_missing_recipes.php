<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Total recipes in outlet 1 (HQ): " . $pdo->query('SELECT count(*) FROM recipes WHERE outlet_id = 1')->fetchColumn() . "\n";
echo "Total recipes in outlet 8: " . $pdo->query('SELECT count(*) FROM recipes WHERE outlet_id = 8')->fetchColumn() . "\n";

// Find all variants that have a recipe in HQ but not in Outlet 8
$missing = $pdo->query("
    SELECT pv.id, p.name as product_name, pv.variant_name 
    FROM product_variants pv
    JOIN products p ON p.id = pv.product_id
    WHERE EXISTS (SELECT 1 FROM recipes WHERE product_variant_id = pv.id AND outlet_id = 1)
      AND NOT EXISTS (SELECT 1 FROM recipes WHERE product_variant_id = pv.id AND outlet_id = 8)
")->fetchAll(PDO::FETCH_ASSOC);

echo "Variants with recipe in HQ but missing in Outlet 8: " . count($missing) . "\n";
foreach ($missing as $m) {
    echo "- {$m['product_name']} - {$m['variant_name']} (ID: {$m['id']})\n";
}
