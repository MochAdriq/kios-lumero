<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$outletId = 8;
$count = $pdo->query("SELECT COUNT(*) FROM outlet_raw_materials WHERE outlet_id = $outletId")->fetchColumn();
echo "Outlet Raw Materials count for Pasekon: $count\n";

$sample = $pdo->query("SELECT * FROM outlet_raw_materials WHERE outlet_id = $outletId LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
print_r($sample);
