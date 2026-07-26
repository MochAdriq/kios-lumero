<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$stmt = $pdo->query("SELECT orm.*, rm.name FROM outlet_raw_materials orm JOIN raw_materials rm ON orm.raw_material_id = rm.id WHERE orm.outlet_id = $outlet_id AND rm.name IN ('Dada Mentah', 'Ayam 1 Ekor', 'Minyak Goreng')");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
