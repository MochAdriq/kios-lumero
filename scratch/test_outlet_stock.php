<?php
$_GET['outlet_id'] = 5;
require __DIR__ . '/../helpers/functions.php';
echo "Current Outlet ID with GET=5: " . current_outlet_id() . PHP_EOL;
require __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require __DIR__ . '/../helpers/free_order_helper.php';
$d = fo_load_pos_menu_data($pdo, 5);
echo "Parts count for Outlet 5: " . count($d['parts']) . PHP_EOL;
require __DIR__ . '/../core/Model.php';
require __DIR__ . '/../modules/pos/POSModel.php';
$posModel = new POSModel();
$cats = $posModel->categoriesWithProducts(5);
echo "Categories for Outlet 5: " . count($cats) . PHP_EOL;
foreach ($cats as $c) {
    if (strpos(mb_strtolower($c['name']), 'ayam') !== false || strpos(mb_strtolower($c['name']), 'crispy') !== false) {
        foreach ($c['items'] ?? [] as $it) {
            echo "   * [Cat " . $c['name'] . "] " . $it['product_name'] . " - " . $it['variant_name'] . ": Ready Stock = " . $it['ready_stock'] . PHP_EOL;
        }
    }
}
