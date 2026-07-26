<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

// Fix empty recipe_type
$pdo->query("UPDATE recipes SET recipe_type = 'final' WHERE outlet_id = $outlet_id AND (recipe_type IS NULL OR recipe_type = '') AND product_variant_id IS NOT NULL");
echo "Fixed empty recipe_type for final recipes.\n";

// Now add Kantong Kresek to those that missed it
$kresekId = $pdo->query("SELECT id FROM raw_materials WHERE outlet_id = $outlet_id AND name = 'Kantong Kresek' ORDER BY id DESC LIMIT 1")->fetchColumn();
$finals = $pdo->query("SELECT id FROM recipes WHERE recipe_type = 'final' AND outlet_id = $outlet_id")->fetchAll(PDO::FETCH_ASSOC);

$added = 0;
foreach ($finals as $f) {
    $rId = $f['id'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM recipe_items WHERE recipe_id = ? AND raw_material_id = ?");
    $check->execute([$rId, $kresekId]);
    if ($check->fetchColumn() == 0) {
        $pdo->query("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id, cost_per_unit, total_cost) VALUES ($rId, 'raw_material', $kresekId, 1, 4, 200, 200)");
        $added++;
    }
}
echo "Added Kantong Kresek to $added recipes.\n";

// What is variant 1125?
$var = $pdo->query("SELECT pv.variant_name, p.name FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE pv.id = 1125")->fetch(PDO::FETCH_ASSOC);
print_r($var);
