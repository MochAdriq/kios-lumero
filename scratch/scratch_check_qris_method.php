<?php
require 'helpers/functions.php';
$config = require 'config/database.php';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$stmt = $pdo->query("SELECT outlet_id, setting_value FROM system_settings WHERE setting_key = 'qris_payment_method'");
print_r($stmt->fetchAll());
