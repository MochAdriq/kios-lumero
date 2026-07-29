<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$outlet_id = 5;

$bases = [
    [
        'name' => '[Base] Eskrim Vanilla',
        'powder_id' => 360
    ],
    [
        'name' => '[Base] Eskrim Coklat',
        'powder_id' => 358
    ],
    [
        'name' => '[Base] Eskrim Strawberry',
        'powder_id' => 359
    ]
];

foreach ($bases as $base) {
    // Check if recipe already exists
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE name = ? AND outlet_id = ?");
    $stmt->execute([$base['name'], $outlet_id]);
    if ($stmt->fetch()) {
        echo "Recipe already exists: {$base['name']}\n";
        continue;
    }
    
    // Create Recipe
    $ins = $pdo->prepare("INSERT INTO recipes (outlet_id, recipe_type, name, yield_qty, yield_unit_id, is_active) VALUES (?, 'sub_recipe', ?, 2000, 2, 1)");
    $ins->execute([$outlet_id, $base['name']]);
    $recipe_id = $pdo->lastInsertId();
    
    // Insert Items
    // 1. Listrik (356, Qty 1, Unit 9)
    $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', 356, 1, 9)")->execute([$recipe_id]);
    
    // 2. Air (251, Qty 1000, Unit 2)
    $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', 251, 1000, 2)")->execute([$recipe_id]);
    
    // 3. Powder (Qty 1000, Unit 1)
    $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1000, 1)")->execute([$recipe_id, $base['powder_id']]);
    
    echo "Successfully created {$base['name']}\n";
}
