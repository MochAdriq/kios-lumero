<?php
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$freeOrders = $pdoOld->query("SELECT DATE(created_at), COUNT(*) FROM free_orders GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_ASSOC);
echo "Free Orders:\n";
print_r($freeOrders);

$otherOrders = $pdoOld->query("SELECT DATE(created_at), COUNT(*) FROM backup_orders_before_update_20260518 GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_ASSOC);
echo "Backup Orders:\n";
print_r($otherOrders);
