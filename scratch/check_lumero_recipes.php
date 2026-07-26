<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$kaliId = 2;

echo "Kalibunder Original Variants & Recipes:\n";
$variants = $pdo->query("
    SELECT pv.id as variant_id, p.name as product_name, pv.variant_name, pv.selling_price 
    FROM product_variants pv 
    JOIN products p ON pv.product_id = p.id 
    WHERE p.outlet_id = $kaliId AND pv.variant_name LIKE '%Original%'
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($variants as $v) {
    echo "- {$v['product_name']} | {$v['variant_name']} | Rp {$v['selling_price']}\n";
    $recipes = $pdo->query("
        SELECT rm.name as item_name, ri.quantity, rm.unit 
        FROM recipe_items ri 
        JOIN raw_materials rm ON ri.raw_material_id = rm.id 
        WHERE ri.product_variant_id = {$v['variant_id']}
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recipes as $r) {
        echo "   -> {$r['quantity']} {$r['unit']} {$r['item_name']}\n";
    }
}
