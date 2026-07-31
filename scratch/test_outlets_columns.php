<?php
$_SERVER['HTTP_HOST'] = 'lokapedia.id';
putenv("APP_ENV=production");
require 'helpers/functions.php';
require 'core/Database.php';

try {
    $pdo = Database::connection();
    $stmt = $pdo->query('SHOW COLUMNS FROM outlets');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
