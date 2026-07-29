<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 

// Find Product ID for 'Es Krim Cup 14 oz'
$stmt = $pdo->query("SELECT id FROM products WHERE name = 'Es Krim Cup 14 oz' AND outlet_id = 5");
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    die("Product not found.\n");
}
$product_id = $product['id'];

// Find all variants for this product
$stmt = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ?");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($variants)) {
    die("No variants found.\n");
}

$placeholders = implode(',', array_fill(0, count($variants), '?'));

// Find all final recipes for these variants
$stmt = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id IN ($placeholders) AND recipe_type = 'final'");
$stmt->execute($variants);
$recipes = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($recipes)) {
    die("No recipes found.\n");
}

$recipe_placeholders = implode(',', array_fill(0, count($recipes), '?'));

// Find all sub_recipe IDs that are base ice creams (names starting with '[Base] Eskrim')
$stmt = $pdo->query("SELECT id FROM recipes WHERE name LIKE '[Base] Eskrim%' AND outlet_id = 5");
$base_recipes = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($base_recipes)) {
    die("No base recipes found.\n");
}

$base_placeholders = implode(',', array_fill(0, count($base_recipes), '?'));

// Now update recipe_items where recipe_id is in $recipes and sub_recipe_id is in $base_recipes
$update_sql = "UPDATE recipe_items 
               SET qty = 120 
               WHERE recipe_id IN ($recipe_placeholders) 
                 AND item_type = 'sub_recipe' 
                 AND sub_recipe_id IN ($base_placeholders)
                 AND qty = 150";

$params = array_merge($recipes, $base_recipes);
$stmt = $pdo->prepare($update_sql);
$stmt->execute($params);

echo "Updated " . $stmt->rowCount() . " recipe items to 120 ml.\n";
