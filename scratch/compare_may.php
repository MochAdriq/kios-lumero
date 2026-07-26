<?php
require_once __DIR__ . '/../helpers/functions.php';

// Old Dump
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$oldSales = $pdoOld->query("
    SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue, SUM(total_hpp) as hpp 
    FROM orders 
    WHERE payment_status = 'paid' AND MONTH(created_at) = 5 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

// New Lumero
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdoNew = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$newSales = $pdoNew->query("
    SELECT business_date as date, total_transactions as orders, total_revenue as revenue, total_hpp as hpp 
    FROM daily_closing_reports 
    WHERE outlet_id = 8 AND MONTH(business_date) = 5
")->fetchAll(PDO::FETCH_ASSOC);

$oldMap = [];
foreach ($oldSales as $row) { $oldMap[$row['date']] = $row; }

$newMap = [];
foreach ($newSales as $row) { $newMap[$row['date']] = $row; }

$allDates = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
sort($allDates);

echo "Perbandingan Penjualan Bulan Mei 2026 (Old Dump vs Lumero):\n";
echo str_pad("Tanggal", 12) . " | " . str_pad("Old Orders", 10) . " | " . str_pad("New Orders", 10) . " | " . str_pad("Old Omzet", 12) . " | " . str_pad("New Omzet", 12) . "\n";
echo str_repeat("-", 70) . "\n";

foreach ($allDates as $date) {
    $oOrd = isset($oldMap[$date]) ? $oldMap[$date]['orders'] : 0;
    $nOrd = isset($newMap[$date]) ? $newMap[$date]['orders'] : 0;
    
    $oRev = isset($oldMap[$date]) ? $oldMap[$date]['revenue'] : 0;
    $nRev = isset($newMap[$date]) ? $newMap[$date]['revenue'] : 0;
    
    if ($oOrd != $nOrd || $oRev != $nRev) {
        echo str_pad($date, 12) . " | " . str_pad($oOrd, 10) . " | " . str_pad($nOrd, 10) . " | " . str_pad($oRev, 12) . " | " . str_pad($nRev, 12) . " <-- BEDA\n";
    } else {
        echo str_pad($date, 12) . " | " . str_pad($oOrd, 10) . " | " . str_pad($nOrd, 10) . " | " . str_pad($oRev, 12) . " | " . str_pad($nRev, 12) . "\n";
    }
}

// Totals
$totalOOrd = array_sum(array_column($oldSales, 'orders'));
$totalNOrd = array_sum(array_column($newSales, 'orders'));
$totalORev = array_sum(array_column($oldSales, 'revenue'));
$totalNRev = array_sum(array_column($newSales, 'revenue'));
echo str_repeat("-", 70) . "\n";
echo str_pad("TOTAL", 12) . " | " . str_pad($totalOOrd, 10) . " | " . str_pad($totalNOrd, 10) . " | " . str_pad($totalORev, 12) . " | " . str_pad($totalNRev, 12) . "\n";

