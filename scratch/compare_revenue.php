<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdoLumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdoOld = new PDO("mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4", "root", "");

// Get total revenue in Lumero (Outlet 8)
$lumeroSales = $pdoLumero->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE outlet_id = 8")->fetchColumn();
$lumeroPaid = $pdoLumero->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE outlet_id = 8 AND payment_status = 'paid'")->fetchColumn();
$lumeroUnpaid = $pdoLumero->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE outlet_id = 8 AND payment_status = 'unpaid'")->fetchColumn();

// Get total revenue in Old DB
$oldSales = $pdoOld->query("SELECT COALESCE(SUM(total), 0) FROM orders")->fetchColumn();
$oldPaid = $pdoOld->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$oldPending = $pdoOld->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'unpaid'")->fetchColumn();

echo "=== PERBANDINGAN OMZET KESELURUHAN ===\n";
echo "LUMERO (Outlet 8):\n";
echo "- Total Semua Status: Rp " . number_format($lumeroSales, 0, ',', '.') . "\n";
echo "- Paid (Lunas): Rp " . number_format($lumeroPaid, 0, ',', '.') . "\n";
echo "- Unpaid (Kasbon): Rp " . number_format($lumeroUnpaid, 0, ',', '.') . "\n\n";

echo "D'CELUP (Old DB):\n";
echo "- Total Semua Status: Rp " . number_format($oldSales, 0, ',', '.') . "\n";
echo "- Paid: Rp " . number_format($oldPaid, 0, ',', '.') . "\n";
echo "- Pending (Kasbon dll): Rp " . number_format($oldPending, 0, ',', '.') . "\n\n";

echo "SELISIH (Lumero Lunas vs D'Celup Paid): Rp " . number_format($lumeroPaid - $oldPaid, 0, ',', '.') . "\n";
echo "SELISIH KESELURUHAN: Rp " . number_format($lumeroSales - $oldSales, 0, ',', '.') . "\n\n";

// Let's break it down by month if there's a difference
if ($lumeroSales != $oldSales) {
    echo "=== BREAKDOWN BY MONTH (ALL STATUS) ===\n";
    $oldByMonth = $pdoOld->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as m, SUM(total) as t FROM orders GROUP BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
    $lumeroByMonth = $pdoLumero->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as m, SUM(grand_total) as t FROM orders WHERE outlet_id = 8 GROUP BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $months = array_unique(array_merge(array_keys($oldByMonth), array_keys($lumeroByMonth)));
    sort($months);
    foreach ($months as $m) {
        $o = $oldByMonth[$m] ?? 0;
        $l = $lumeroByMonth[$m] ?? 0;
        echo "$m : Lumero Rp " . number_format($l,0,',','.') . " | D'Celup Rp " . number_format($o,0,',','.') . " | Selisih: Rp " . number_format($l - $o,0,',','.') . "\n";
    }
}
