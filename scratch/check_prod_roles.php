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

echo "=== ROLES ===\n";
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r) {
    echo "  ID: {$r['id']} | Code: {$r['code']} | Name: {$r['name']}\n";
}

echo "\n=== OUTLETS ===\n";
$outlets = $pdo->query("SELECT id, name, outlet_code, is_hq, is_active FROM outlets ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($outlets as $o) {
    echo "  ID: {$o['id']} | Code: {$o['outlet_code']} | Name: {$o['name']} | HQ: {$o['is_hq']} | Active: {$o['is_active']}\n";
}

echo "\n=== CURRENT midtrans_tester ===\n";
$stmt = $pdo->prepare("SELECT u.*, r.code AS role_code, r.name AS role_name, o.name AS outlet_name FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN outlets o ON o.id=u.outlet_id WHERE u.username='midtrans_tester'");
$stmt->execute();
$mt = $stmt->fetch(PDO::FETCH_ASSOC);
if ($mt) {
    echo "  ID: {$mt['id']} | Role: {$mt['role_name']} ({$mt['role_code']}) | Outlet: {$mt['outlet_name']}\n";
} else {
    echo "  Belum ada\n";
}
