<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Total recipes in Lumero for Kalibunder: " . $pdo->query("SELECT COUNT(*) FROM recipes WHERE outlet_id = 2")->fetchColumn() . "\n";
echo "Total raw_materials in Lumero for Kalibunder: " . $pdo->query("SELECT COUNT(*) FROM raw_materials WHERE outlet_id = 2")->fetchColumn() . "\n";
