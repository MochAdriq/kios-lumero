<?php
$db = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

function getRecipe($db, $id) {
    $recipe = $db->query("SELECT * FROM recipes WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
    if (!$recipe) return null;
    $recipe['items'] = $db->query("SELECT ri.* FROM recipe_items ri WHERE ri.recipe_id = $id")->fetchAll(PDO::FETCH_ASSOC);
    return $recipe;
}

function explodeBOM($db, $recipeId, $qtyMultiplier = 1.0, &$visited = []) {
    if (in_array($recipeId, $visited)) return [];
    $visited[] = $recipeId;
    $recipe = getRecipe($db, $recipeId);
    if (!$recipe) { array_pop($visited); return []; }
    $ratio = $qtyMultiplier / (float)($recipe['yield_qty'] > 0 ? $recipe['yield_qty'] : 1.0);
    $bom = [];
    foreach ($recipe['items'] as $item) {
        $requiredQty = (float)$item['qty'] * $ratio;
        if ($item['item_type'] === 'raw_material') {
            $rmId = (int)$item['raw_material_id'];
            if (!isset($bom[$rmId])) $bom[$rmId] = 0.0;
            $bom[$rmId] += $requiredQty;
        } elseif ($item['item_type'] === 'sub_recipe') {
            $subBom = explodeBOM($db, (int)$item['sub_recipe_id'], $requiredQty, $visited);
            foreach ($subBom as $rmId => $subQty) {
                if (!isset($bom[$rmId])) $bom[$rmId] = 0.0;
                $bom[$rmId] += $subQty;
            }
        }
    }
    array_pop($visited);
    return $bom;
}

$variantId = 1291;
$outletId = 5;
$recipe = $db->query("SELECT id, name FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId AND outlet_id IN ($outletId, 1) ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$recipe) { echo "Recipe not found\n"; exit; }
$bom = explodeBOM($db, $recipe['id'], 1.0);
if (!$bom) { echo "BOM is empty\n"; exit; }

$rmIds = array_keys($bom);
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

echo "Final Raw Materials:\n";
print_r($rawMaterials);
