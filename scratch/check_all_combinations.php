<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');
$res = $pdo->query("SELECT payment_status, SUM(total) as t FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
