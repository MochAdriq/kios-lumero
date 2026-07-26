<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

echo "--- Kantong Kresek ---\n";
$kresek = $pdo->query("SELECT id, average_cost FROM raw_materials WHERE outlet_id = $outlet_id AND name = 'Kantong Kresek' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($kresek);

echo "\n--- Dada Original + Nasi ---\n";
$dada = $pdo->query("SELECT id, hpp, selling_price FROM product_variants WHERE outlet_id = $outlet_id AND variant_name = 'Dada Original + Nasi' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($dada);

echo "\n--- Potato Crispy Reguler ---\n";
$potato = $pdo->query("SELECT id, hpp, selling_price FROM product_variants WHERE outlet_id = $outlet_id AND variant_name = 'Original Reguler' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($potato);

echo "\n--- BBQ Spicy (to see if Kresek was added) ---\n";
$bbq = $pdo->query("SELECT id, hpp FROM product_variants WHERE outlet_id = $outlet_id AND variant_name = 'Dada BBQ Spicy Tanpa Nasi' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($bbq);
