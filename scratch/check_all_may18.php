<?php
$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
$res = $pdoOld->query("SELECT COUNT(*) as c, SUM(total) as rev FROM orders WHERE DATE(created_at) = '2026-05-18'")->fetch(PDO::FETCH_ASSOC);
echo "ALL statuses on 18 May: {$res['c']} orders, {$res['rev']} rev\n";
