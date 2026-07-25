<?php
require_once __DIR__ . '/../helpers/functions.php';

$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdoNew = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// 1. Find missing active day
$oldDates = $pdoOld->query("SELECT DISTINCT DATE(created_at) FROM orders WHERE payment_status = 'paid'")->fetchAll(PDO::FETCH_COLUMN);
$newDates = $pdoNew->query("SELECT DISTINCT business_date FROM daily_closing_reports WHERE outlet_id = 8")->fetchAll(PDO::FETCH_COLUMN);

$missingDates = array_diff($oldDates, $newDates);
echo "Missing Dates in Lumero:\n";
print_r($missingDates);

// 2. Count paid orders
$oldOrdersCount = $pdoOld->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$newOrdersCount = $pdoNew->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND outlet_id = 8")->fetchColumn();
echo "\nPaid Orders - Old: $oldOrdersCount | New: $newOrdersCount | Diff: " . ($oldOrdersCount - $newOrdersCount) . "\n";

// 3. Compare Sum Total Paid Orders
$oldSum = $pdoOld->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$newSum = $pdoNew->query("SELECT SUM(grand_total) FROM orders WHERE payment_status = 'paid' AND outlet_id = 8")->fetchColumn();
echo "\nSum of Paid Orders - Old: $oldSum | New: $newSum | Diff: " . ($oldSum - $newSum) . "\n";

// 4. Check if the user's '63.871.500' is the sum of specific dates or status combinations
// Let's get the sum of ALL orders in old DB.
$oldAllSum = $pdoOld->query("SELECT SUM(total) FROM orders")->fetchColumn();
$oldAllCount = $pdoOld->query("SELECT COUNT(*) FROM orders")->fetchColumn();
echo "\nALL Old Orders - Count: $oldAllCount | Total: $oldAllSum\n";

// 5. Find missing expenses
try {
    $oldExpSum = $pdoOld->query("SELECT SUM(amount) FROM operational_expenses")->fetchColumn();
    $newExpSum = $pdoNew->query("SELECT SUM(amount) FROM operational_expenses WHERE outlet_id = 8")->fetchColumn();
    echo "\nExpenses - Old: $oldExpSum | New: $newExpSum | Diff: " . ($oldExpSum - $newExpSum) . "\n";
} catch (Exception $e) {
    echo "\nCould not read operational_expenses from old DB: " . $e->getMessage() . "\n";
}

// 6. Find exactly the 93 missing orders
$oldOrderIds = $pdoOld->query("SELECT order_no FROM orders WHERE payment_status = 'paid'")->fetchAll(PDO::FETCH_COLUMN);
$newOrderIdsRaw = $pdoNew->query("SELECT order_number FROM orders WHERE payment_status = 'paid' AND outlet_id = 8")->fetchAll(PDO::FETCH_COLUMN);
$newOrderIds = array_map(function($o) { return str_replace('HIST-', '', $o); }, $newOrderIdsRaw);

$missingOrderIds = array_diff($oldOrderIds, $newOrderIds);
echo "\nTotal Missing Order IDs from old DB that are not in Kios Lumero: " . count($missingOrderIds) . "\n";

if (count($missingOrderIds) > 0) {
    $placeholders = str_repeat('?,', count($missingOrderIds) - 1) . '?';
    $stmt = $pdoOld->prepare("SELECT SUM(total) FROM orders WHERE order_no IN ($placeholders)");
    $stmt->execute(array_values($missingOrderIds));
    $missingSum = $stmt->fetchColumn();
    echo "Value of these missing orders: $missingSum\n";
}

