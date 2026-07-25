<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$expBefore = $pdo->query("SELECT SUM(amount) FROM operational_expenses WHERE created_at < '2026-05-17'")->fetchColumn();
echo "Expenses before May 17: $expBefore\n";
