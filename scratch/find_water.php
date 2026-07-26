<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$stmt = $pdo->query("SELECT id, name, unit_id FROM raw_materials WHERE name LIKE '%Air%' AND outlet_id = 8");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
