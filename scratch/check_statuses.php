<?php
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$orders18 = $pdoOld->query("SELECT payment_status, COUNT(*) as c, SUM(total) as rev FROM orders WHERE DATE(created_at) = '2026-05-18' GROUP BY payment_status")->fetchAll(PDO::FETCH_ASSOC);
echo "18 May in Dump:\n";
print_r($orders18);

$orders19 = $pdoOld->query("SELECT payment_status, COUNT(*) as c, SUM(total) as rev FROM orders WHERE DATE(created_at) = '2026-05-19' GROUP BY payment_status")->fetchAll(PDO::FETCH_ASSOC);
echo "19 May in Dump:\n";
print_r($orders19);
