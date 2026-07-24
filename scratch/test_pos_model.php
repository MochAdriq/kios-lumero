<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
require_once __DIR__ . '/../modules/pos/POSModel.php';

$dbObj = new Database(); // dummy wrapper if needed, or pass pdo

$posModel = new POSModel($dbObj);
$outletId = 7;
$cats = $posModel->categoriesWithProducts($outletId);

echo "Total Categories: " . count($cats) . "\n";
if (count($cats) > 0) {
    foreach ($cats[0]['items'] as $item) {
        echo "Variant: {$item['variant_name']} | ID: {$item['variant_id']} | Price: {$item['price']} | Ready Stock: {$item['ready_stock']}\n";
    }
} else {
    echo "NO CATEGORIES WITH PRODUCTS FOUND FOR OUTLET 7\n";
}
