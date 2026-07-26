<?php
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$tables = ['orders', 'backup_orders_before_update_20260518', 'backup_orders_before_restore_hpp_20260519'];

$totalOrders = 0;
$totalRev = 0;

foreach ($tables as $t) {
    try {
        $res = $pdoOld->query("SELECT COUNT(*) as c, SUM(total) as rev FROM $t WHERE DATE(created_at) = '2026-05-18' AND payment_status = 'paid'")->fetch(PDO::FETCH_ASSOC);
        echo "$t on 18 May: {$res['c']} orders, {$res['rev']} rev\n";
    } catch(Exception $e) {}
}

// Let's just find ALL orders anywhere on May 18!
