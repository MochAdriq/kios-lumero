<?php
$file = 'C:/Users/HYPE R Series/Downloads/u643003184_newcelup (5).sql';
$fixedFile = 'C:/Users/HYPE R Series/Downloads/dcelup_fixed.sql';

// Read and replace collations
$content = file_get_contents($file);
$content = str_replace('utf8mb4_uca1400_ai_ci', 'utf8mb4_unicode_ci', $content);
$content = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', $content);
file_put_contents($fixedFile, $content);

echo "Fixed collations. Loading into dcelup_test_dump...\n";

$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
$pdo->exec("DROP DATABASE IF EXISTS dcelup_test_dump");
$pdo->exec("CREATE DATABASE dcelup_test_dump");

// Execute via absolute path to mysql
$cmd = "C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -u root dcelup_test_dump < \"$fixedFile\" 2>&1";
$output = shell_exec($cmd);
echo "Output: $output\n";

$paid = $pdo->query("SELECT COUNT(*), SUM(total) FROM dcelup_test_dump.orders WHERE payment_status = 'paid'")->fetch(PDO::FETCH_NUM);
$all = $pdo->query("SELECT COUNT(*), SUM(total) FROM dcelup_test_dump.orders")->fetch(PDO::FETCH_NUM);

echo "Paid Orders in Fixed Dump: {$paid[0]} | Sum: {$paid[1]}\n";
echo "All Orders in Fixed Dump: {$all[0]} | Sum: {$all[1]}\n";

// Also check how many days
$days = $pdo->query("SELECT COUNT(DISTINCT DATE(created_at)) FROM dcelup_test_dump.orders WHERE payment_status = 'paid'")->fetchColumn();
echo "Active Days: $days\n";

// And operational expenses
$exp = $pdo->query("SELECT SUM(amount) FROM dcelup_test_dump.operational_expenses")->fetchColumn();
echo "Total Expenses: $exp\n";
