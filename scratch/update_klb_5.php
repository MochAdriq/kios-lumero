<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$outletId = 5;

// Update parent products
$st = $pdo->prepare("SELECT p.id, p.name 
                     FROM products p
                     LEFT JOIN product_categories c ON p.category_id = c.id
                     WHERE p.outlet_id = ? AND (c.name LIKE '%ayam%' OR p.name LIKE '%ayam%' OR c.name LIKE '%chicken%')");
$st->execute([$outletId]);
$products = $st->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("UPDATE products SET image = 'images/pos-products/ayam-klb.png' WHERE id = ?");
foreach ($products as $p) {
    $update->execute([$p['id']]);
    echo "Updated parent product ID {$p['id']} - {$p['name']}\n";
}

// Update variants
$pdo->query("UPDATE product_variants pv 
             JOIN products p ON p.id = pv.product_id 
             SET pv.image = p.image 
             WHERE p.outlet_id = 5");
echo "Variants synced for outlet 5!";
