<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$outletId = 7;

// Ambil salah satu variant id dari outlet 7
$stmt = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = 7 LIMIT 1");
$var = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Variant ID: {$var['id']} - Name: {$var['variant_name']}\n";

$recipe = $pdo->query("SELECT id FROM recipes WHERE product_variant_id = {$var['id']} LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    echo "NO RECIPE FOUND FOR VARIANT ID {$var['id']}!\n";
} else {
    echo "Recipe ID: {$recipe['id']}\n";
    require_once __DIR__ . '/../core/Model.php';
    require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
    $dbObj = new Database();
    $mRecipe = new RecipeModel($dbObj);
    
    $bom = $mRecipe->explodeBOM((int)$recipe['id'], 1.0);
    echo "BOM:\n";
    print_r($bom);
    
    $yield = $mRecipe->calculateMaxYield((int)$var['id'], $outletId);
    echo "Yield: {$yield}\n";
}
