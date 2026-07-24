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

$stmt = $pdo->query("SELECT id, name, outlet_id FROM recipes WHERE recipe_type = 'sub_recipe' AND name LIKE '%Geprek%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sub-recipes Geprek:\n";
print_r($rows);
