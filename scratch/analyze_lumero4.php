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

echo "=== PURCHASES TABLES ===\n";
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
$stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE outlet_id = 5");
echo "Categories (Kalibunder): " . $stmt->fetchColumn() . "\n";

echo "\n=== COMPANY_ID ===\n";
$stmt = $pdo->query("SELECT id, company_id, name FROM outlets ORDER BY id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  outlet:{$r['id']} company:{$r['company_id']} | {$r['name']}\n";
}

echo "\n=== SAMPLE USER ===\n";
$stmt = $pdo->query("SELECT id, outlet_id, role_id, name, username FROM users LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== OUTLET_STOCKS TABLE ===\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'outlet%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($stmt as $t) {
    echo "  $t\n";
}
