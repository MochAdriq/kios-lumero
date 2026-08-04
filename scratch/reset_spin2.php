<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=kios_lumero_db;charset=utf8mb4', 'root', '');
$pdo->exec('TRUNCATE TABLE event_spin_logs;');
echo 'Cleared!';
