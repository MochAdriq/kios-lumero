<?php
require 'core/Database.php';

$host = 'srv1864.hstgr.io';
$db = 'u643003184_kios_lumero';
$user = 'u643003184_kios_lumero';
$pass = 'Lawmotion1!@#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_variants WHERE outlet_id = 5 AND variant_name LIKE '%Teriyaki%'");
    echo "Teriyaki variants: " . $stmt->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
