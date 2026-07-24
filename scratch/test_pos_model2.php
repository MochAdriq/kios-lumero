<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
require_once __DIR__ . '/../modules/pos/POSModel.php';

$dbObj = new Database(); // dummy wrapper if needed, or pass pdo

$posModel = new POSModel($dbObj);
$outletId = 7;

$pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
$categories = $posModel->all("SELECT pc.id, pc.name, pc.slug, pc.sort_order
            FROM product_categories pc
            WHERE pc.is_active=1 AND {$pcScope['sql']}
            ORDER BY CASE WHEN pc.sort_order = 0 THEN 999 ELSE pc.sort_order END ASC, pc.name ASC", $pcScope['params']);

echo "Total Categories Query: " . count($categories) . "\n";
// print_r($categories);

// Test one category query
if (!empty($categories)) {
    $cat = $categories[0];
    $pScope = ['sql' => 'p.outlet_id = ?', 'params' => [$outletId]];
    $pvScope = ['sql' => 'pv.outlet_id = ?', 'params' => [$outletId]];
    $pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
    
    $items = $posModel->all("SELECT
            pv.id AS variant_id,
            pv.sku,
            p.name AS product_name,
            pv.variant_name,
            COALESCE(NULLIF(pv.selling_price,0), p.base_price, 0) AS price,
            COALESCE(pv.hpp, p.base_hpp, 0) AS hpp,
            COALESCE(pv.image, p.image) AS image
        FROM product_variants pv
        JOIN products p ON p.id=pv.product_id
        JOIN product_categories pc ON pc.id=p.category_id
        WHERE p.category_id=? AND p.is_active=1
            AND pv.is_active = 1
            AND pc.is_active=1
            AND {$pScope['sql']}
            AND {$pvScope['sql']}
            AND {$pcScope['sql']}
        ORDER BY p.name ASC, pv.is_default DESC, pv.variant_name ASC", array_merge([$cat['id']], $pScope['params'], $pvScope['params'], $pcScope['params']));
        
    echo "Items for Category {$cat['name']} (ID {$cat['id']}): " . count($items) . "\n";
    if (!empty($items)) {
        // print_r($items);
        require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
        $mRecipe = new RecipeModel();
        foreach ($items as &$it) {
            $it['ready_stock'] = $mRecipe->calculateMaxYield((int)$it['variant_id'], $outletId);
            echo "   -> Variant {$it['variant_name']} | Max Yield: {$it['ready_stock']}\n";
        }
    } else {
        echo "   Query failed or returned 0 items.\n";
    }
}
