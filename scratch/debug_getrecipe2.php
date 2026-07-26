<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
require_once __DIR__ . '/../core/Database.php';

$rm = new RecipeModel();
try {
    $fullRecipe = $rm->getRecipe(935);
    echo "Full Recipe:\n";
    print_r($fullRecipe);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
