<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdoLumero = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdoOld = new PDO("mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4", "root", "");

// Find cancelled orders in old DB
$cancelledOld = $pdoOld->query("SELECT order_no FROM orders WHERE payment_status = 'cancelled'")->fetchAll(PDO::FETCH_COLUMN);

if ($cancelledOld) {
    foreach ($cancelledOld as $oldNo) {
        $newNo = 'HIST-' . $oldNo;
        // Fix in Lumero
        $pdoLumero->exec("UPDATE orders SET payment_status = 'cancelled', order_status = 'cancelled' WHERE order_number = '$newNo'");
    }
    echo "Fixed " . count($cancelledOld) . " cancelled orders in Kios Lumero.\n";
} else {
    echo "No cancelled orders found.\n";
}
