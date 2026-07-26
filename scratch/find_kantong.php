<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Finding Kantong Besar and Kantong Kecil:\n";
$stmt = $pdo->query("SELECT id, name, outlet_id FROM raw_materials WHERE name LIKE '%Kantong Besar%' OR name LIKE '%Kantong Kecil%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
