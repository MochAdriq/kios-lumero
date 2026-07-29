<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$outlet_id = 5;

// Helper to get Recipe ID
function getRecipeId($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE name = ? AND outlet_id = 5");
    $stmt->execute([$name]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ? $res['id'] : null;
}

$cone_id = 370;

// 1. Create Product
$prod_name = 'Es Krim Lumero';
$stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? AND outlet_id = 5");
$stmt->execute([$prod_name]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    $sku = 'ESK-LUMERO';
    $ins = $pdo->prepare("INSERT INTO products (outlet_id, category_id, name, product_type, sku, is_active) VALUES (5, 130127, ?, 'variant_parent', ?, 1)");
    $ins->execute([$prod_name, $sku]);
    $prod_id = $pdo->lastInsertId();
    echo "Created Product ID: $prod_id\n";
} else {
    $prod_id = $prod['id'];
    echo "Product already exists ID: $prod_id\n";
}

// 2. Create Variants & Final Recipes
$variants = [
    [
        'name' => 'Es Krim Lumero Vanilla',
        'base' => '[Base] Eskrim Vanilla'
    ],
    [
        'name' => 'Es Krim Lumero Coklat',
        'base' => '[Base] Eskrim Coklat'
    ],
    [
        'name' => 'Es Krim Lumero Strawberry',
        'base' => '[Base] Eskrim Strawberry'
    ]
];

foreach ($variants as $v) {
    // Check variant
    $stmt = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ? AND variant_name = ?");
    $stmt->execute([$prod_id, $v['name']]);
    $var = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$var) {
        $sku = 'LUM-' . strtoupper(substr(md5($v['name']), 0, 5));
        $ins = $pdo->prepare("INSERT INTO product_variants (product_id, outlet_id, variant_name, sku, selling_price, is_active) VALUES (?, 5, ?, ?, 10000, 1)");
        $ins->execute([$prod_id, $v['name'], $sku]);
        $var_id = $pdo->lastInsertId();
        echo "Created Variant: {$v['name']} (ID: $var_id)\n";
    } else {
        $var_id = $var['id'];
        echo "Variant already exists: {$v['name']} (ID: $var_id)\n";
    }
    
    // Check Final Recipe
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id = ? AND recipe_type = 'final'");
    $stmt->execute([$var_id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rec) {
        $ins = $pdo->prepare("INSERT INTO recipes (outlet_id, product_variant_id, recipe_type, name, yield_qty, yield_unit_id, is_active) VALUES (5, ?, 'final', ?, 1, 4, 1)");
        $ins->execute([$var_id, 'Resep: ' . $v['name']]);
        $recipe_id = $pdo->lastInsertId();
        
        $base_id = getRecipeId($pdo, $v['base']);
        
        // Items
        // Base Eskrim (150 ml, unit_id=2)
        $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, sub_recipe_id, qty, unit_id) VALUES (?, 'sub_recipe', ?, 150, 2)")->execute([$recipe_id, $base_id]);
        
        // Kemasan (1 pcs, unit_id=3)
        $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1, 3)")->execute([$recipe_id, $cone_id]);
        
        echo "Created Final Recipe for: {$v['name']}\n";
    } else {
        echo "Final Recipe already exists for: {$v['name']}\n";
    }
}
