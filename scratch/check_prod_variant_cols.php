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

echo "=== PRODUCTS TABLE COLUMNS ===\n";
$cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";
}

echo "\n=== PRODUCT_VARIANTS TABLE COLUMNS ===\n";
$cols = $pdo->query("DESCRIBE product_variants")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";
}

echo "\n=== RECIPES TABLE COLUMNS ===\n";
$cols = $pdo->query("DESCRIBE recipes")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";
}
