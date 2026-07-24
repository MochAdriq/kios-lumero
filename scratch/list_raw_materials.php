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

echo "--- SEMUA RAW MATERIALS ---\n";
$stmt = $pdo->query("SELECT id, name FROM raw_materials ORDER BY name ASC");
$rms = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rms as $rm) {
    echo "- ID {$rm['id']} | {$rm['name']}\n";
}
