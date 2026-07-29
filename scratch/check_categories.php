<?php
require 'helpers/functions.php';
require 'core/Database.php';

$host = 'srv1864.hstgr.io';
$db = 'u643003184_kios_lumero';
$user = 'u643003184_kios_lumero';
$pass = 'Lawmotion1!@#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check product categories
    $stmt = $pdo->query("DESCRIBE product_categories");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Check categories for outlet 8
    $stmt = $pdo->query("SELECT * FROM product_categories WHERE outlet_id = 8");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Check raw_material_categories
    $stmt = $pdo->query("SHOW TABLES LIKE '%categories%'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
