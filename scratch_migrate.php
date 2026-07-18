<?php
require 'helpers/functions.php';
require 'core/Database.php';

$db = Database::connection();

try {
    $db->beginTransaction();

    // 1. Get all branches except 1 (Pusat)
    $branches = $db->query("SELECT id FROM outlets WHERE id != 1")->fetchAll(PDO::FETCH_COLUMN);

    // 2. Clone product_categories
    $masterCategories = $db->query("SELECT * FROM product_categories WHERE outlet_id = 1 OR outlet_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Clone products
    $masterProducts = $db->query("SELECT * FROM products WHERE outlet_id = 1 OR outlet_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Clone variants
    $masterVariants = $db->query("
        SELECT pv.* FROM product_variants pv
        JOIN products p ON p.id = pv.product_id
        WHERE p.outlet_id = 1 OR p.outlet_id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($branches as $branchId) {
        $catMap = [];
        // Insert categories
        $stmtCat = $db->prepare("INSERT INTO product_categories (outlet_id, name, slug, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($masterCategories as $cat) {
            $slug = $cat['slug'] . '-b' . $branchId; // Ensure unique slug
            $stmtCat->execute([
                $branchId, $cat['name'], $slug, $cat['sort_order'], $cat['is_active'],
                $cat['created_at'], $cat['updated_at']
            ]);
            $catMap[$cat['id']] = $db->lastInsertId();
        }

        $prodMap = [];
        // Insert products
        $stmtProd = $db->prepare("INSERT INTO products (category_id, outlet_id, sku, name, description, image, product_type, unit_name, base_hpp, base_price, margin_amount, margin_percent, lifetime_qty_sold, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($masterProducts as $p) {
            $newCatId = $catMap[$p['category_id']] ?? $p['category_id'];
            $stmtProd->execute([
                $newCatId, $branchId, $p['sku'], $p['name'], $p['description'], $p['image'],
                $p['product_type'], $p['unit_name'], $p['base_hpp'], $p['base_price'],
                $p['margin_amount'], $p['margin_percent'], $p['lifetime_qty_sold'],
                $p['is_active'], $p['created_at'], $p['updated_at']
            ]);
            $prodMap[$p['id']] = $db->lastInsertId();
        }

        // Insert variants
        $stmtVar = $db->prepare("INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, image, hpp, selling_price, margin_amount, margin_percent, is_default, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($masterVariants as $v) {
            $newProdId = $prodMap[$v['product_id']] ?? $v['product_id'];
            
            // Check if there was an override for this branch
            $override = $db->query("SELECT * FROM product_branch_overrides WHERE product_variant_id = {$v['id']} AND outlet_id = {$branchId}")->fetch(PDO::FETCH_ASSOC);
            
            $hpp = $v['hpp'];
            $selling_price = $v['selling_price'];
            $is_active = $v['is_active'];

            if ($override) {
                if ($override['hpp'] !== null) $hpp = $override['hpp'];
                if ($override['selling_price'] !== null) $selling_price = $override['selling_price'];
                if ($override['is_active'] !== null) $is_active = $override['is_active'];
            }
            
            $margin = (float)$selling_price - (float)$hpp;
            $mp = ((float)$selling_price > 0) ? ($margin / (float)$selling_price * 100) : 0;

            $stmtVar->execute([
                $newProdId, $branchId, $v['sku'], $v['variant_name'], $v['image'],
                $hpp, $selling_price, $margin, $mp,
                $v['is_default'], $is_active, $v['created_at'], $v['updated_at']
            ]);
        }
    }

    // Now update existing master products to outlet_id = 1
    $db->query("UPDATE products SET outlet_id = 1 WHERE outlet_id IS NULL");
    $db->query("UPDATE product_variants SET outlet_id = 1 WHERE outlet_id IS NULL");

    // We MUST execute schema changes outside transaction in MySQL typically, but we can try
    // We will drop indexes manually after this script to be safe.

    $db->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
