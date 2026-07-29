<?php
$host = 'srv1864.hstgr.io';
$db = 'u643003184_kios_lumero';
$user = 'u643003184_kios_lumero';
$pass = 'Lawmotion1!@#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $path = __DIR__ . '/../public/assets/images/pos-products/black-white-logo.jpg';
    print_r(getimagesize($path));
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
