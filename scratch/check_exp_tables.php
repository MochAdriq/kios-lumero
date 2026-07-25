<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== EXPENSE TABLES ===\n";
print_r($pdo->query("SHOW TABLES LIKE '%expens%'")->fetchAll(PDO::FETCH_COLUMN));

echo "=== PURCHASE TABLES ===\n";
print_r($pdo->query("SHOW TABLES LIKE '%purchas%'")->fetchAll(PDO::FETCH_COLUMN));
