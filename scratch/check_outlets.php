<?php
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$outlets = $pdoOld->query("SELECT outlet_id, COUNT(*) FROM orders GROUP BY outlet_id")->fetchAll(PDO::FETCH_ASSOC);
echo "Orders by Outlet in Dump:\n";
print_r($outlets);

$orders18 = $pdoOld->query("SELECT * FROM orders WHERE DATE(created_at) = '2026-05-18'")->fetchAll(PDO::FETCH_ASSOC);
echo "Total rows on 18 May: " . count($orders18) . "\n";
