<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$kaliId = 2;
$pasId = 8;

echo "Kalibunder Items:\n";
$kaliItems = $pdo->query("SELECT id, name FROM raw_materials WHERE outlet_id = $kaliId")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r(array_slice($kaliItems, 0, 5, true));

echo "Pasekon Items:\n";
$pasItems = $pdo->query("SELECT id, name FROM raw_materials WHERE outlet_id = $pasId")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r(array_slice($pasItems, 0, 5, true));

$ayamPas = $pdo->query("SELECT id, name FROM products WHERE outlet_id = $pasId AND name LIKE '%Ayam Crispy%'")->fetch(PDO::FETCH_ASSOC);
echo "Ayam Crispy in Pasekon: " . print_r($ayamPas, true) . "\n";

$potatoPas = $pdo->query("SELECT id, name FROM products WHERE outlet_id = $pasId AND name LIKE '%Potato Crispy%'")->fetch(PDO::FETCH_ASSOC);
echo "Potato Crispy in Pasekon: " . print_r($potatoPas, true) . "\n";
