<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$db = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$variantId = 1291;
$outletId = 5;

$rm = new RecipeModel();
$rm->db = $db; // Override DB connection for testing

$recipe = $db->query("SELECT id, name FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId AND outlet_id IN ($outletId, 1) ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$recipe) { echo "Recipe not found"; exit; }
echo "Recipe: " . $recipe['name'] . "\n";

$bom = $rm->explodeBOM($recipe['id'], 1.0);
echo "BOM array:\n";
print_r($bom);

$rmIds = array_keys($bom);
if (empty($rmIds)) { echo "BOM is empty"; exit; }

$placeholders = implode(',', array_fill(0, count($rmIds), '?'));
        
$stmt = $db->prepare("
    SELECT rm.id, rm.name, u.symbol as unit, COALESCE(orm.stock_qty, 0) as available_stock
    FROM raw_materials rm
    LEFT JOIN units u ON rm.unit_id = u.id
    LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
    WHERE rm.id IN ($placeholders)
");
$params = array_merge([$outletId], $rmIds);
$stmt->execute($params);
$rawMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Raw Materials Query Result:\n";
print_r($rawMaterials);
