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

echo "=== ROLES TABLE STRUCTURE ===\n";
$stmt = $pdo->query("DESCRIBE roles");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']}\n";
}

echo "\n=== ROLES DATA ===\n";
$stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);

echo "\n=== OUTLET_USERS TABLE ===\n";
$stmt = $pdo->query("DESCRIBE outlet_users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";
}

echo "\n=== PURCHASES TABLES ===\n";
$tables = $pdo->query("SHOW TABLES LIKE 'purchase%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    $stmt = $pdo->query("DESCRIBE `$t`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
    }
}

echo "\n=== KALIBUNDER STATS ===\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE outlet_id = 5");
echo "Products (Kalibunder): " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE p.outlet_id = 5");
echo "Variants (Kalibunder): " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM recipes WHERE outlet_id = 5");
echo "Recipes (Kalibunder): " . $stmt->fetchColumn() . "\n";

echo "\n=== COMPANY_ID ===\n";
$stmt = $pdo->query("SELECT id, company_id, name FROM outlets ORDER BY id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  outlet:{$r['id']} company:{$r['company_id']} | {$r['name']}\n";
}
