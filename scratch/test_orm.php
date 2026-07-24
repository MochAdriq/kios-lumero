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

$outletId = 7;
$rmId = 142;

$stmt = $pdo->query("SELECT * FROM outlet_raw_materials WHERE raw_material_id = 142 AND outlet_id = 7");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT * FROM outlet_raw_materials WHERE raw_material_id = 142");
echo "All records for rmId 142:\n";
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
