<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$hppPaid = $pdo->query("SELECT SUM(total_hpp) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
echo "Old Dump Total HPP (Paid): $hppPaid\n";
