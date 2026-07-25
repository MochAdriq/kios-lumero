<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$counts = $pdo->query("SELECT outlet_id, COUNT(*) as c FROM raw_materials GROUP BY outlet_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($counts);

$cats = $pdo->query("SELECT outlet_id, COUNT(*) as c FROM raw_material_categories GROUP BY outlet_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($cats);
