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
    
    // IDs needed
    $stmt = $pdo->query("SELECT id, name FROM raw_materials WHERE outlet_id = 5 AND name IN ('Saus Carbonara', 'Saus Mozzarella', 'Kantong Besar', 'Kantong Kecil')");
    $raws = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "Raw Materials:\n"; print_r($raws);
    
    $stmt = $pdo->query("SELECT id, name FROM recipes WHERE outlet_id = 5 AND recipe_type = 'sub_recipe' AND name LIKE '%[Base]%'");
    $bases = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "Base Recipes:\n"; print_r($bases);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
