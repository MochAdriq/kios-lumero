<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Searching for '[Bumbu] Racik Untuk 1 potong ayam' in Sub-Recipes:\n";
$stmt = $pdo->query("SELECT * FROM recipes WHERE name LIKE '%[Bumbu] Racik Untuk 1 potong ayam%' AND recipe_type = 'sub_recipe'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nSearching for '[Bumbu] Racik Untuk 1 potong ayam' in Raw Materials:\n";
$stmt = $pdo->query("SELECT * FROM raw_materials WHERE name LIKE '%[Bumbu] Racik Untuk 1 potong ayam%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
