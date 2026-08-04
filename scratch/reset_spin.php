<?php
require_once dirname(__DIR__) . '/config/database.php';
$pdo->exec('TRUNCATE TABLE event_spin_logs;');
echo "Spin logs cleared.";
