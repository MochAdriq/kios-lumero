<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

echo "=== CHECK SAUS PRODUCTS & VARIANTS ===\n";

$stmt = $pdo->query("
    SELECT p.id as product_id, p.name as product_name, p.is_active as product_active
    FROM products p
    WHERE p.name LIKE '%Saus%' OR p.name LIKE '%Sauce%' OR p.name LIKE '%BBQ%' OR p.name LIKE '%Keju%' OR p.name LIKE '%Mentai%' OR p.name LIKE '%Teriyaki%'
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    echo "- Product: {$p['product_name']} (Active: {$p['product_active']})\n";
    
    $stmt2 = $pdo->prepare("SELECT id, variant_name, selling_price, is_active FROM product_variants WHERE product_id = ?");
    $stmt2->execute([$p['product_id']]);
    $variants = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($variants as $v) {
        $stmt3 = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id = ? AND recipe_type = 'final'");
        $stmt3->execute([$v['id']]);
        $has_recipe = $stmt3->fetchColumn() ? 'Yes' : 'No';
        
        echo "  -> Variant: {$v['variant_name']} | Price: {$v['selling_price']} | Active: {$v['is_active']} | Has Recipe: $has_recipe\n";
    }
}
