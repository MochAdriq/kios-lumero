<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$recipes = $pdo->query("SELECT * FROM recipes WHERE product_variant_id = 1137")->fetchAll(PDO::FETCH_ASSOC);
print_r($recipes);

$kresek_items = $pdo->query("SELECT COUNT(*) FROM recipe_items WHERE raw_material_id = (SELECT id FROM raw_materials WHERE outlet_id = 8 AND name = 'Kantong Kresek' LIMIT 1)")->fetchColumn();
echo "Kresek items count: $kresek_items\n";
