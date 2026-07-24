<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$outletId = 7;
$pcScopeSql = 'pc.outlet_id = ?';

$stmt = $pdo->prepare("SELECT pc.id, pc.name, pc.slug, pc.sort_order
            FROM product_categories pc
            WHERE pc.is_active=1 AND {$pcScopeSql}
            ORDER BY CASE WHEN pc.sort_order = 0 THEN 999 ELSE pc.sort_order END ASC, pc.name ASC");
$stmt->execute([$outletId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total Categories: " . count($categories) . "\n";

if (!empty($categories)) {
    $cat = $categories[0];
    
    $pScopeSql = 'p.outlet_id = ?';
    $pvScopeSql = 'pv.outlet_id = ?';
    
    $stmt = $pdo->prepare("SELECT
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
            AND {$pScopeSql}
            AND {$pvScopeSql}
            AND {$pcScopeSql}
        ORDER BY p.name ASC, pv.is_default DESC, pv.variant_name ASC");
        
    $stmt->execute([$cat['id'], $outletId, $outletId, $outletId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    echo "Items for Category {$cat['name']} (ID {$cat['id']}): " . count($items) . "\n";
    if (!empty($items)) {
        require_once __DIR__ . '/../core/Model.php';
        require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
        // Need a dummy DB for RecipeModel
        $dbObj = new Database();
        $mRecipe = new RecipeModel($dbObj);
        
        foreach ($items as &$it) {
            $it['ready_stock'] = $mRecipe->calculateMaxYield((int)$it['variant_id'], $outletId);
            echo "   -> Variant {$it['variant_name']} | Max Yield (ready_stock): {$it['ready_stock']}\n";
        }
    } else {
        echo "   Query failed or returned 0 items.\n";
    }
}
