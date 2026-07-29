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
    
    $stmt = $pdo->query("SELECT id, outlet_id, name, is_active FROM products WHERE outlet_id = 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Products for outlet 5:\n";
    print_r($products);
    
    $stmt2 = $pdo->query("SELECT id, product_id, outlet_id, variant_name, is_active FROM product_variants WHERE outlet_id = 5");
    $variants = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Variants for outlet 5:\n";
    print_r(array_slice($variants, 0, 5)); // print first 5
    echo "Total variants: " . count($variants) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
