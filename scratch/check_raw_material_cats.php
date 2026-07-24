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

// Cek kategori bahan baku
$stmtCat = $pdo->query("SELECT id, name FROM raw_material_categories LIMIT 5");
$cats = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

echo "Categories:\n";
print_r($cats);
