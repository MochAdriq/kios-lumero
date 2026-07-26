<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Gas Stock in Outlet 8:\n";
print_r($pdo->query("SELECT * FROM outlet_raw_materials WHERE raw_material_id = 178 AND outlet_id = 8")->fetchAll(PDO::FETCH_ASSOC));
