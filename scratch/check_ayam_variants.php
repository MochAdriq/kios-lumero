<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

echo "=== CHECK AYAM VARIANTS FOR OUTLET 5 ===\n";

$stmt = $pdo->query("
    SELECT p.name as product_name, pv.variant_name, pv.selling_price
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    WHERE pv.outlet_id = 5 
      AND (p.name LIKE '%Ayam%' OR p.name LIKE '%Chicken%' OR p.name LIKE '%Sayap%' OR p.name LIKE '%Dada%' OR p.name LIKE '%Paha%')
");
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($variants as $v) {
    echo "- {$v['product_name']} | {$v['variant_name']} (Rp {$v['selling_price']})\n";
}
