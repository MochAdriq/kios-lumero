<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Parents of Recipe 920:\n";
$stmt = $pdo->query("SELECT recipe_id FROM recipe_items WHERE item_type = 'sub_recipe' AND sub_recipe_id = 920");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
