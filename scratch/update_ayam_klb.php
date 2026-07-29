<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// find klb outlet
$st = $pdo->query("SELECT id, name FROM outlets WHERE slug = 'klb' OR name LIKE '%kalibunder%'");
$outlet = $st->fetch(PDO::FETCH_ASSOC);
echo "Outlet: " . json_encode($outlet) . "\n";
$outletId = $outlet['id'] ?? 2;

$st = $pdo->prepare("SELECT p.id, p.name, p.image 
                     FROM products p
                     LEFT JOIN product_categories c ON p.category_id = c.id
                     WHERE p.outlet_id = ? AND (c.name LIKE '%ayam%' OR p.name LIKE '%ayam%' OR c.name LIKE '%chicken%')");
$st->execute([$outletId]);
$products = $st->fetchAll(PDO::FETCH_ASSOC);
echo "Products to update: " . json_encode($products, JSON_PRETTY_PRINT) . "\n";

// Update them
$update = $pdo->prepare("UPDATE products SET image = 'assets/images/pos-products/ayam-klb.png' WHERE id = ?");
foreach ($products as $p) {
    $update->execute([$p['id']]);
    echo "Updated product ID {$p['id']} - {$p['name']}\n";
}
echo "Done!\n";
