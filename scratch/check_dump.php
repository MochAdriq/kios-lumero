<?php
$file = 'C:/Users/HYPE R Series/Downloads/u643003184_newcelup (5).sql';
$content = file_get_contents($file);

preg_match_all("/INSERT INTO `orders` \([^)]+\) VALUES/i", $content, $matches);
$insertStatements = count($matches[0]);

// Wait, standard mysqldump uses extended inserts (one INSERT for many rows).
// We should count the number of '),(', or just parse the SQL.
// Better: load the dump into a brand NEW database to check!
$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
$pdo->exec("DROP DATABASE IF EXISTS dcelup_test_dump");
$pdo->exec("CREATE DATABASE dcelup_test_dump");
$pdo->exec("USE dcelup_test_dump");

// Execute via CLI
$cmd = "mysql -h 127.0.0.1 -u root dcelup_test_dump < \"$file\" 2>&1";
echo "Loading dump...\n";
shell_exec($cmd);

$paid = $pdo->query("SELECT COUNT(*), SUM(total) FROM dcelup_test_dump.orders WHERE payment_status = 'paid'")->fetch(PDO::FETCH_NUM);
$all = $pdo->query("SELECT COUNT(*), SUM(total) FROM dcelup_test_dump.orders")->fetch(PDO::FETCH_NUM);

echo "Paid Orders in Dump (5).sql: {$paid[0]} | Sum: {$paid[1]}\n";
echo "All Orders in Dump (5).sql: {$all[0]} | Sum: {$all[1]}\n";
