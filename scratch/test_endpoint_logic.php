<?php
// Simulate the endpoint logic perfectly
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
$rm = new RecipeModel();

$variantId = 150;
$outletId = 8; // Pasekon

// 1. Fetch recipe
$recipe = $pdo->query("SELECT id, name FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId AND outlet_id IN ($outletId, 1) ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    echo "ERROR: Resep final tidak ditemukan untuk produk ini.\n";
    exit;
}
echo "Found Recipe ID: {$recipe['id']} - {$recipe['name']}\n";

// 2. explodeBOM
$bom = $rm->explodeBOM($recipe['id'], 1.0);
if (!$bom) {
    echo "ERROR: BOM kosong atau tidak valid.\n";
    exit;
}
echo "BOM:\n";
print_r($bom);
