<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt=$pdo->query("SELECT ri.item_type, ri.raw_material_id, ri.sub_recipe_id, COALESCE(rm.name, subr.name) as name FROM recipe_items ri LEFT JOIN raw_materials rm ON ri.raw_material_id = rm.id LEFT JOIN recipes subr ON ri.sub_recipe_id = subr.id WHERE ri.recipe_id = 1257"); 
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
