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

$kemasan_id = getRecipeId($pdo, '[Base] Kemasan Es Krim 14 oz');
$stmt = $pdo->prepare("SELECT id FROM products WHERE name = 'Es Krim Cup 14 oz' AND outlet_id = 5");
$stmt->execute();
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
$prod_id = $prod['id'];

$variants = [
    [
        'name' => 'Vanilla Topping Strawberry',
        'base' => '[Base] Eskrim Vanilla',
        'sirup' => 'Sirup Es Krim Strawberry',
        'serbuk' => 'Serbuk Topping Strawberry'
    ],
    [
        'name' => 'Vanilla Topping Matcha',
        'base' => '[Base] Eskrim Vanilla',
        'sirup' => 'Sirup Es Krim Matcha',
        'serbuk' => 'Serbuk Topping Matcha'
    ],
    [
        'name' => 'Coklat Topping Strawberry',
        'base' => '[Base] Eskrim Coklat',
        'sirup' => 'Sirup Es Krim Strawberry',
        'serbuk' => 'Serbuk Topping Strawberry'
    ],
    [
        'name' => 'Coklat Topping Matcha',
        'base' => '[Base] Eskrim Coklat',
        'sirup' => 'Sirup Es Krim Matcha',
        'serbuk' => 'Serbuk Topping Matcha'
    ],
    [
        'name' => 'Strawberry Topping Coklat',
        'base' => '[Base] Eskrim Strawberry',
        'sirup' => 'Sirup Es Krim Coklat',
        'serbuk' => 'Serbuk Topping Coklat'
    ],
    [
        'name' => 'Strawberry Topping Matcha',
        'base' => '[Base] Eskrim Strawberry',
        'sirup' => 'Sirup Es Krim Matcha',
        'serbuk' => 'Serbuk Topping Matcha'
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
