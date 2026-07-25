<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$localHost = '127.0.0.1';
$localUser = 'root';
$localPass = '';
$tempDbName = 'dcelup_temp_migration';

$local = new PDO("mysql:host={$localHost};dbname={$tempDbName};charset=utf8mb4", $localUser, $localPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Check expenses in old db
echo "=== EXPENSE TABLES IN OLD DB ===\n";
$tables = $local->query("SHOW TABLES LIKE '%expens%'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

if (in_array('expenses', $tables) || in_array('operational_expenses', $tables)) {
    $tbl = in_array('expenses', $tables) ? 'expenses' : 'operational_expenses';
    $expenses = $local->query("SELECT SUM(amount) FROM $tbl")->fetchColumn();
    echo "Total expenses in $tbl: $expenses\n";
    $sample = $local->query("SELECT * FROM $tbl LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($sample);
}

// Check other tables for salary
$tablesAll = $local->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$salaryRelated = [];
foreach ($tablesAll as $t) {
    if (strpos($t, 'salary') !== false || strpos($t, 'payroll') !== false || strpos($t, 'wage') !== false) {
        $salaryRelated[] = $t;
    }
}
echo "\n=== SALARY TABLES IN OLD DB ===\n";
print_r($salaryRelated);

// Compare Omzet (Revenue)
echo "\n=== TOTAL REVENUE IN OLD DB ===\n";
$oldRevenue = $local->query("SELECT SUM(total), SUM(subtotal), SUM(discount), SUM(tax) FROM orders")->fetch(PDO::FETCH_ASSOC);
print_r($oldRevenue);

$oldItemsRevenue = $local->query("SELECT SUM(line_total) FROM order_items")->fetchColumn();
echo "Total line_total in old order_items: $oldItemsRevenue\n";

// Get Kios Lumero Revenue for Pasekon
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$lumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo "\n=== TOTAL REVENUE IN KIOS LUMERO (PASEKON) ===\n";
$newRevenue = $lumero->query("SELECT SUM(grand_total), SUM(subtotal), SUM(discount_amount) FROM orders WHERE outlet_id = 8")->fetch(PDO::FETCH_ASSOC);
print_r($newRevenue);
