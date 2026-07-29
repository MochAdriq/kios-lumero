<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt=$pdo->query("SELECT id, product_variant_id, outlet_id FROM recipes WHERE id = 1257"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
