<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$subs = $pdo->query("SELECT id, name FROM recipes WHERE outlet_id = 8 AND recipe_type = 'sub_recipe'")->fetchAll(PDO::FETCH_ASSOC);
print_r($subs);
