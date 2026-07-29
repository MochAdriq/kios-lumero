<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt=$pdo->query("SELECT ri.item_type, ri.qty, u.name as unit, COALESCE(rm.name, subr.name) as name FROM recipe_items ri LEFT JOIN raw_materials rm ON ri.raw_material_id = rm.id AND ri.item_type = 'raw_material' LEFT JOIN recipes subr ON ri.sub_recipe_id = subr.id AND ri.item_type = 'sub_recipe' LEFT JOIN units u ON u.id = ri.unit_id WHERE ri.recipe_id = 1248"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
