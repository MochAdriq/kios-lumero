<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');
$subtotal = $pdo->query("SELECT SUM(subtotal) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
echo "Subtotal: " . $subtotal . "\n";
$total = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
echo "Total: " . $total . "\n";
