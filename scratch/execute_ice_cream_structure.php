<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$outlet_id = 5;

// Helper to get RM ID
function getRmId($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? AND outlet_id = 5 ORDER BY id DESC LIMIT 1");
    $stmt->execute([$name]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ? $res['id'] : null;
}

// Helper to get Recipe ID
function getRecipeId($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE name = ? AND outlet_id = 5");
    $stmt->execute([$name]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ? $res['id'] : null;
}

$cup = getRmId($pdo, 'Cup 14 oz');
$tutup = getRmId($pdo, 'Tutup Cup');
$sendok = getRmId($pdo, 'Sendok Es Krim');

echo "Cup: $cup, Tutup: $tutup, Sendok: $sendok\n";

// 1. Create Sub-Recipe [Base] Kemasan Es Krim 14 oz
$kemasan_name = '[Base] Kemasan Es Krim 14 oz';
$kemasan_id = getRecipeId($pdo, $kemasan_name);
if (!$kemasan_id) {
    $ins = $pdo->prepare("INSERT INTO recipes (outlet_id, recipe_type, name, yield_qty, yield_unit_id, is_active) VALUES (5, 'sub_recipe', ?, 1, 4, 1)"); // yield 1 porsi (unit_id=4)
    $ins->execute([$kemasan_name]);
    $kemasan_id = $pdo->lastInsertId();
    
    // items (1 pcs each, unit_id=3)
    $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1, 3)")->execute([$kemasan_id, $cup]);
    $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1, 3)")->execute([$kemasan_id, $tutup]);
    $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1, 3)")->execute([$kemasan_id, $sendok]);
    echo "Created Kemasan Sub-Recipe ID: $kemasan_id\n";
} else {
    echo "Kemasan Sub-Recipe already exists.\n";
}

// 2. Create Product
$prod_name = 'Es Krim Cup 14 oz';
$stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? AND outlet_id = 5");
$stmt->execute([$prod_name]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    $sku = 'ESK-CUP-14';
    $ins = $pdo->prepare("INSERT INTO products (outlet_id, category_id, name, product_type, sku, is_active) VALUES (5, 130127, ?, 'variant_parent', ?, 1)");
    $ins->execute([$prod_name, $sku]);
    $prod_id = $pdo->lastInsertId();
    echo "Created Product ID: $prod_id\n";
} else {
    $prod_id = $prod['id'];
    echo "Product already exists ID: $prod_id\n";
}

// 3. Create Variants & Final Recipes
$variants = [
    [
        'name' => 'Vanilla Topping Coklat',
        'base' => '[Base] Eskrim Vanilla',
        'sirup' => 'Sirup Es Krim Coklat',
        'serbuk' => 'Serbuk Topping Coklat'
    ],
    [
        'name' => 'Coklat Topping Coklat',
        'base' => '[Base] Eskrim Coklat',
        'sirup' => 'Sirup Es Krim Coklat',
        'serbuk' => 'Serbuk Topping Coklat'
    ],
    [
        'name' => 'Strawberry Topping Strawberry',
        'base' => '[Base] Eskrim Strawberry',
        'sirup' => 'Sirup Es Krim Strawberry',
        'serbuk' => 'Serbuk Topping Strawberry'
    ]
];

foreach ($variants as $v) {
    // Check variant
    $stmt = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ? AND variant_name = ?");
    $stmt->execute([$prod_id, $v['name']]);
    $var = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$var) {
        $sku = 'ESK-' . strtoupper(substr(md5($v['name']), 0, 5));
        $ins = $pdo->prepare("INSERT INTO product_variants (product_id, outlet_id, variant_name, sku, selling_price, is_active) VALUES (?, 5, ?, ?, 15000, 1)");
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
        $sirup_id = getRmId($pdo, $v['sirup']);
        $serbuk_id = getRmId($pdo, $v['serbuk']);
        
        // Items
        // Base Eskrim (150 ml, unit_id=2)
        $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, sub_recipe_id, qty, unit_id) VALUES (?, 'sub_recipe', ?, 150, 2)")->execute([$recipe_id, $base_id]);
        
        // Kemasan (1 porsi, unit_id=4)
        $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, sub_recipe_id, qty, unit_id) VALUES (?, 'sub_recipe', ?, 1, 4)")->execute([$recipe_id, $kemasan_id]);
        
        // Sirup (15 ml, unit_id=2)
        $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 15, 2)")->execute([$recipe_id, $sirup_id]);
        
        // Serbuk (5 gr, unit_id=1)
        $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 5, 1)")->execute([$recipe_id, $serbuk_id]);
        
        echo "Created Final Recipe for: {$v['name']}\n";
    } else {
        echo "Final Recipe already exists for: {$v['name']}\n";
    }
}
