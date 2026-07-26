<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8; // Pasekon

$recipes = $pdo->query("SELECT id, name, product_variant_id FROM recipes WHERE recipe_type = 'final' AND outlet_id = $outlet_id")->fetchAll(PDO::FETCH_ASSOC);

foreach ($recipes as $r) {
    // try to match the product variant id based on the recipe name
    // e.g. "Resep - Coffee Latte Ice (Default)" -> product name = "Coffee Latte Ice", variant name = "Default"
    // e.g. "1 ekor ayam + saus - Default" -> product name = "1 ekor ayam + saus", variant name = "Default"
    // The easiest way is to look for a matching variant ID.
    // If the recipe name is "Resep - [Product Name] ([Variant Name])"
    if (preg_match('/Resep - (.*?) \((.*?)\)/', $r['name'], $m)) {
        $prodName = $m[1];
        $varName = $m[2];
    } else if (preg_match('/(.*?) - (.*)/', $r['name'], $m)) {
        $prodName = $m[1];
        $varName = $m[2];
    } else {
        continue;
    }
    
    $prodName = trim($prodName);
    $varName = trim($varName);

    // Find product variant
    $stmt = $pdo->prepare("
        SELECT pv.id 
        FROM product_variants pv 
        JOIN products p ON pv.product_id = p.id 
        WHERE pv.outlet_id = $outlet_id AND p.name = ? AND pv.variant_name = ?
    ");
    $stmt->execute([$prodName, $varName]);
    $correct_pv = $stmt->fetchColumn();

    if ($correct_pv && $correct_pv != $r['product_variant_id']) {
        echo "Recipe {$r['id']} ({$r['name']}) -> correct PV is $correct_pv, but currently {$r['product_variant_id']}\n";
        $pdo->query("UPDATE recipes SET product_variant_id = $correct_pv WHERE id = {$r['id']}");
    }
}
