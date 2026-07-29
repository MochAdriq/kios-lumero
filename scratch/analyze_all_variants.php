<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

$stmt = $pdo->query("
    SELECT pv.id, pv.variant_name, r.id as recipe_id, 
           (SELECT COUNT(*) FROM recipe_items ri WHERE ri.recipe_id = r.id) as item_count
    FROM product_variants pv
    JOIN products p ON p.id = pv.product_id
    LEFT JOIN recipes r ON r.product_variant_id = pv.id
    WHERE pv.is_active = 1 AND p.outlet_id = 5
");
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$noRecipe = [];
$emptyRecipe = [];
$hasRecipe = [];

foreach ($variants as $v) {
    if (empty($v['recipe_id'])) {
        $noRecipe[] = $v;
    } else if ($v['item_count'] == 0) {
        $emptyRecipe[] = $v;
    } else {
        $hasRecipe[] = $v;
    }
}

echo "Tanpa Baris Resep: " . count($noRecipe) . "\n";
foreach(array_slice($noRecipe, 0, 5) as $v) echo "- " . $v['variant_name'] . "\n";

echo "\nAda Baris Resep Tapi Kosong (0 item): " . count($emptyRecipe) . "\n";
foreach(array_slice($emptyRecipe, 0, 5) as $v) echo "- " . $v['variant_name'] . "\n";

echo "\nAda Resep (Berisi Item): " . count($hasRecipe) . "\n";

