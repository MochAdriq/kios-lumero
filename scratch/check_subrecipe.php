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
    
    // Check [Saus] Cheese sub recipe
    $stmt = $pdo->query("SELECT * FROM recipes WHERE outlet_id = 5 AND name LIKE '%[Saus] Cheese%'");
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($recipes);
    
    foreach ($recipes as $r) {
        $items = $pdo->query("SELECT * FROM recipe_items WHERE recipe_id = {$r['id']}")->fetchAll(PDO::FETCH_ASSOC);
        print_r($items);
    }
    
    // Also find the parent product "Ayam Crispy" and "Potato Crispy" to know their IDs
    $p = $pdo->query("SELECT id, name, category_id, outlet_id FROM products WHERE outlet_id = 5 AND name IN ('Ayam Crispy', 'Potato Crispy')")->fetchAll(PDO::FETCH_ASSOC);
    print_r($p);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
