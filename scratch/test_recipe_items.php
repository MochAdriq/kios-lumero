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

echo "Recipe items count for recipe 394: " . $pdo->query("SELECT count(*) FROM recipe_items WHERE recipe_id = 394")->fetchColumn() . "\n";
$items = $pdo->query("SELECT * FROM recipe_items WHERE recipe_id = 394")->fetchAll(PDO::FETCH_ASSOC);
print_r($items);
