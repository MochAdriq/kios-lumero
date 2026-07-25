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

echo "=== EXISTING OUTLETS ===\n";
$stmt = $pdo->query("SELECT id, name, address, latitude, longitude, is_active FROM outlets ORDER BY id");
$outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($outlets as $o) {
    echo "ID {$o['id']} | {$o['name']} | active:{$o['is_active']} | lat:{$o['latitude']} lng:{$o['longitude']}\n";
}

echo "\n=== OUTLET COLUMNS ===\n";
$stmt = $pdo->query("DESCRIBE outlets");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
}

echo "\n=== USERS TABLE COLUMNS ===\n";
$stmt = $pdo->query("DESCRIBE users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
}

echo "\n=== USER ROLES AVAILABLE ===\n";
$stmt = $pdo->query("SELECT DISTINCT role FROM users");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r) {
    echo "  " . $r['role'] . "\n";
}

echo "\n=== OUTLET_USERS COLUMNS ===\n";
$stmt = $pdo->query("DESCRIBE outlet_users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
}

echo "\n=== PURCHASES TABLE COLUMNS ===\n";
$tables = $pdo->query("SHOW TABLES LIKE 'purchase%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    $stmt = $pdo->query("DESCRIBE `$t`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
    }
}
