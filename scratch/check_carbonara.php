<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

$stmt = $pdo->query("
    SELECT pv.id, pv.variant_name, p.outlet_id, pv.is_active, r.id as recipe_id, 
           (SELECT COUNT(*) FROM recipe_items ri WHERE ri.recipe_id = r.id) as item_count
    FROM product_variants pv
    JOIN products p ON p.id = pv.product_id
    LEFT JOIN recipes r ON r.product_variant_id = pv.id
    WHERE pv.variant_name LIKE '%Carbonara%' AND p.outlet_id = 5
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
