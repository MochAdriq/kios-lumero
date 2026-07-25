<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$count = $pdo->query("SELECT count(*) FROM orders WHERE outlet_id = 8")->fetchColumn();
echo "Total orders for Outlet 8: $count\n";

if ($count > 0) {
    $dates = $pdo->query("SELECT min(created_at), max(created_at) FROM orders WHERE outlet_id = 8")->fetch(PDO::FETCH_ASSOC);
    print_r($dates);
}
