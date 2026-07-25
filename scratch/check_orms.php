<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$orms = $pdo->query("SELECT rm.name, orm.average_cost, rm.average_cost as default_cost FROM outlet_raw_materials orm JOIN raw_materials rm ON rm.id = orm.raw_material_id WHERE orm.outlet_id = 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($orms);
