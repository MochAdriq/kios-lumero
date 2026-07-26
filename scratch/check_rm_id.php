<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$stmt = $pdo->query("SELECT id, name FROM raw_materials WHERE name LIKE '%Dada Mentah%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT id, name FROM raw_materials WHERE name LIKE '%Ayam 1 Ekor%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT id, name FROM raw_materials WHERE name LIKE '%Saus Sachet%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
