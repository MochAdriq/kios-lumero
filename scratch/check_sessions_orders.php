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

echo "=== DAILY_STORE_SESSIONS TABLE ===\n";
$cols = $pdo->query("DESCRIBE daily_store_sessions")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== TOTAL ORDERS IN LUMERO ===\n";
echo "Total: " . $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() . "\n";

echo "\n=== SAMPLE ORDER IN LUMERO ===\n";
$rows = $pdo->query("SELECT id, outlet_id, order_number, order_source, business_date, grand_total, order_status, daily_store_session_id FROM orders LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo "\n=== HOW MANY ORDERS IN DCELUP SQL (rough count) ===\n";
// Count orders dari SQL dump: cari berapa baris data order di antara baris 11632 dan seterusnya
