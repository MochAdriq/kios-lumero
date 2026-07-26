<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8; // Pasekon

$recipes = $pdo->query("SELECT id, name, recipe_type, total_hpp FROM recipes WHERE product_variant_id = 1125")->fetchAll(PDO::FETCH_ASSOC);
print_r($recipes);
