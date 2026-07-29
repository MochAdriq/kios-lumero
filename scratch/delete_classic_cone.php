<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

$sku = 'ICE-CRM-CONE';

$stmt = $pdo->prepare("SELECT id, product_id FROM product_variants WHERE sku = ?");
$stmt->execute([$sku]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($variants as $v) {
    // Delete recipe items
    $stmt2 = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id = ?");
    $stmt2->execute([$v['id']]);
    $recipes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recipes as $r) {
        $pdo->prepare("DELETE FROM recipe_items WHERE recipe_id = ?")->execute([$r['id']]);
        $pdo->prepare("DELETE FROM recipes WHERE id = ?")->execute([$r['id']]);
        echo "Deleted recipe and items for variant {$v['id']}\n";
    }
    
    // Delete variant
    $pdo->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$v['id']]);
    echo "Deleted variant {$v['id']} (SKU: $sku)\n";
    
    // Check if product has other variants
    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
    $stmt3->execute([$v['product_id']]);
    if ($stmt3->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$v['product_id']]);
        echo "Deleted parent product {$v['product_id']}\n";
    }
}

if (count($variants) == 0) {
    echo "Variant not found.\n";
}
