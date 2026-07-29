<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$outletId = 5; // KLB

// Get all Ayam Crispy variants
$stmt = $pdo->query("
    SELECT pv.id, pv.variant_name, r.id as recipe_id, 
           (SELECT COUNT(*) FROM recipe_items ri WHERE ri.recipe_id = r.id) as item_count
    FROM product_variants pv
    JOIN products p ON p.id = pv.product_id
    LEFT JOIN recipes r ON r.product_variant_id = pv.id
    WHERE p.name LIKE '%Ayam Crispy%' AND pv.is_active = 1 AND p.outlet_id = 5
");
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$noRecipe = [];
$hasRecipe = [];

foreach ($variants as $v) {
    if (empty($v['recipe_id']) || $v['item_count'] == 0) {
        $noRecipe[] = $v['variant_name'];
    } else {
        $hasRecipe[] = $v['recipe_id'];
    }
}

echo "=== VARIANT AYAM TANPA RESEP (" . count($noRecipe) . ") ===\n";
foreach (array_slice($noRecipe, 0, 10) as $name) {
    echo "- $name\n";
}
if (count($noRecipe) > 10) echo "... dan " . (count($noRecipe) - 10) . " lainnya\n\n";

if (count($hasRecipe) > 0) {
    echo "\n=== POLA RESEP YANG SUDAH ADA ===\n";
    $sampleRecipeId = $hasRecipe[0];
    $items = $pdo->query("SELECT ri.item_type, ri.qty, u.name as unit, 
                          COALESCE(rm.name, subr.name) as name
                          FROM recipe_items ri
                          LEFT JOIN raw_materials rm ON ri.raw_material_id = rm.id AND ri.item_type = 'raw_material'
                          LEFT JOIN recipes subr ON ri.sub_recipe_id = subr.id AND ri.item_type = 'sub_recipe'
                          LEFT JOIN units u ON u.id = ri.unit_id
                          WHERE ri.recipe_id = $sampleRecipeId")->fetchAll(PDO::FETCH_ASSOC);
    $varName = $pdo->query("SELECT variant_name FROM product_variants pv JOIN recipes r ON r.product_variant_id = pv.id WHERE r.id = $sampleRecipeId")->fetchColumn();
    
    echo "Contoh Resep: $varName\n";
    foreach ($items as $item) {
        echo "- " . $item['name'] . " (" . $item['qty'] . " " . $item['unit'] . ")\n";
    }
} else {
    echo "\n=== POLA RESEP YANG SUDAH ADA ===\nBelum ada satupun varian ayam di KLB yang punya resep.";
}
