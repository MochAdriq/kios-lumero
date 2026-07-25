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

echo "=== ROLES TABLE ===\n";
$stmt = $pdo->query("SELECT id, name, slug FROM roles ORDER BY id");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r) {
    echo "  ID {$r['id']} | {$r['name']} | slug:{$r['slug']}\n";
}

echo "\n=== SAMPLE USER ===\n";
$stmt = $pdo->query("SELECT id, outlet_id, role_id, name, username FROM users LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "\n=== OUTLET_USERS COLUMNS ===\n";
$stmt = $pdo->query("DESCRIBE outlet_users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
}

echo "\n=== PURCHASES TABLE ===\n";
$tables = $pdo->query("SHOW TABLES LIKE 'purchase%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    $stmt = $pdo->query("DESCRIBE `$t`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
    }
}

echo "\n=== COMPANY_ID in use ===\n";
$stmt = $pdo->query("SELECT id, company_id, name FROM outlets ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  outlet_id:{$r['id']} company_id:{$r['company_id']} | {$r['name']}\n";
}

echo "\n=== KALIBUNDER PRODUCTS COUNT ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM products WHERE outlet_id = 5");
echo "Products: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE p.outlet_id = 5");
echo "Variants: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM recipes WHERE outlet_id = 5");
echo "Recipes: " . $stmt->fetchColumn() . "\n";
