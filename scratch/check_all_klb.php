<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");

$st = $pdo->query("
    SELECT p.id, p.sku, p.name as p_name, c.name as c_name, p.image 
    FROM products p 
    LEFT JOIN product_categories c ON p.category_id = c.id 
    WHERE p.outlet_id = 2 
    ORDER BY p.id ASC
");
$all = $st->fetchAll(PDO::FETCH_ASSOC);
echo "KLB Products:\n";
print_r($all);
