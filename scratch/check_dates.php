<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
echo "Max date: " . $pdo->query('SELECT MAX(created_at) FROM orders')->fetchColumn() . "\n";
echo "Min date: " . $pdo->query('SELECT MIN(created_at) FROM orders')->fetchColumn() . "\n";
