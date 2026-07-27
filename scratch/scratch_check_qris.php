<?php
require 'helpers/functions.php';
$config = require 'config/database.php';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
try {
    $stmt = $pdo->query("SELECT setting_value FROM outlet_settings WHERE setting_key = 'payment_qris_image'");
    echo "From outlet_settings:\n";
    print_r($stmt->fetchAll());
} catch(Exception $e) {}
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'payment_qris_image'");
    echo "From system_settings:\n";
    print_r($stmt->fetchAll());
} catch(Exception $e) {}
