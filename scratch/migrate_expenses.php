<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$lumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$localHost = '127.0.0.1';
$localUser = 'root';
$localPass = '';
$tempDbName = 'dcelup_temp_migration';

$local = new PDO("mysql:host={$localHost};dbname={$tempDbName};charset=utf8mb4", $localUser, $localPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$OUTLET_ID = 8;

echo "=== MIGRATING EXPENSES ===\n";

// 1. Check if expense category exists, if not create one
$catName = "Migrasi D\\'Celup Lama";
$catId = $lumero->query("SELECT id FROM expense_categories WHERE name = '$catName'")->fetchColumn();

if (!$catId) {
    $lumero->exec("INSERT INTO expense_categories (name, type) VALUES ('$catName', 'operational')");
    $catId = $lumero->lastInsertId();
    echo "Created new category: $catName (ID: $catId)\n";
} else {
    echo "Found existing category: $catName (ID: $catId)\n";
}

// 2. Fetch all expenses from old db
$oldExpenses = $local->query("SELECT * FROM expenses")->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($oldExpenses) . " expenses in old DB.\n";

// 3. Insert into Lumero operational_expenses
$stmt = $lumero->prepare("INSERT INTO operational_expenses 
    (outlet_id, business_date, category_id, amount, payment_method, description, created_by, created_at)
    VALUES (?, ?, ?, ?, 'cash', ?, 1, ?)
");

$inserted = 0;
foreach ($oldExpenses as $e) {
    // Check if already exists (by description, date, and amount)
    $desc = "[$e[category]] $e[description]";
    
    $exists = $lumero->query("SELECT id FROM operational_expenses WHERE outlet_id = $OUTLET_ID AND business_date = '$e[expense_date]' AND description = " . $lumero->quote($desc) . " AND amount = $e[amount]")->fetchColumn();
    
    if (!$exists) {
        $stmt->execute([
            $OUTLET_ID,
            $e['expense_date'],
            $catId,
            $e['amount'],
            $desc,
            $e['created_at']
        ]);
        $inserted++;
    }
}

echo "Successfully migrated $inserted expenses.\n";
