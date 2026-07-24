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

echo "=== OUTLETS COLUMNS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM outlets")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | Null: {$c['Null']} | Default: " . ($c['Default'] ?? 'NULL') . "\n";
}

echo "\n=== COMPANIES ===\n";
$companies = $pdo->query("SELECT * FROM companies")->fetchAll(PDO::FETCH_ASSOC);
print_r($companies);

echo "\n=== OUTLET 1 FULL ROW ===\n";
$o1 = $pdo->query("SELECT * FROM outlets WHERE id=1")->fetch(PDO::FETCH_ASSOC);
print_r($o1);
