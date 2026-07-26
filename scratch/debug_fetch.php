<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$finalRecipes = $pdo->query("SELECT id FROM recipes WHERE recipe_type = 'final' AND outlet_id = 8")->fetchAll();
echo "Count of final recipes for 8: " . count($finalRecipes) . "\n";
