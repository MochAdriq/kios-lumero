<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$OUTLET_ID = 8;

echo "=== GENERATING DAILY CLOSING REPORTS UNTUK OUTLET 8 ===\n";

$dates = $pdo->query("SELECT DISTINCT DATE(created_at) as d FROM orders WHERE outlet_id = $OUTLET_ID ORDER BY d")->fetchAll(PDO::FETCH_COLUMN);

if (empty($dates)) {
    echo "Tidak ada data order.\n";
    exit;
}

$stmtIns = $pdo->prepare("
    INSERT INTO daily_closing_reports 
    (outlet_id, daily_store_session_id, business_date, total_revenue, total_hpp, gross_profit, operational_expense, total_expense, net_profit, total_transactions, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE 
    total_revenue=VALUES(total_revenue), total_hpp=VALUES(total_hpp), gross_profit=VALUES(gross_profit), 
    operational_expense=VALUES(operational_expense), total_expense=VALUES(total_expense), net_profit=VALUES(net_profit), total_transactions=VALUES(total_transactions)
");

$generated = 0;
foreach ($dates as $date) {
    // Get sales and hpp
    $sales = $pdo->query("SELECT COALESCE(SUM(grand_total),0) as rev, COALESCE(SUM(total_hpp),0) as hpp, COUNT(*) as trx FROM orders WHERE outlet_id = $OUTLET_ID AND DATE(created_at) = '$date' AND payment_status = 'paid'")->fetch(PDO::FETCH_ASSOC);
    
    // Get expenses
    $exp = 0;
    try {
        $exp = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM operational_expenses WHERE business_date = '$date' AND outlet_id = $OUTLET_ID")->fetchColumn();
    } catch (Exception $e) {}
    
    $rev = $sales['rev'];
    $hpp = $sales['hpp'];
    $trx = $sales['trx'];
    $exp = (float)$exp;
    
    $gross = $rev - $hpp;
    $net = $gross - $exp;
    
    $sessionId = $pdo->query("SELECT id FROM daily_store_sessions WHERE outlet_id = $OUTLET_ID AND DATE(business_date) = '$date' ORDER BY id DESC LIMIT 1")->fetchColumn();
    
    if (!$sessionId) {
        echo "Skip $date (no session)\n";
        continue;
    }

    $stmtIns->execute([$OUTLET_ID, $sessionId, $date, $rev, $hpp, $gross, $exp, $exp, $net, $trx]);
    $generated++;
}

echo "Berhasil meng-generate $generated laporan harian (daily_closing_reports)!\n";
