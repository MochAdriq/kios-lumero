<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== CHECKING OUTLET 8 RECIPE ITEMS HPP VS OUTLET 5 ===\n";
// Outlet 5 is kalibunder (source), Outlet 8 is pasekon (dest)

$items8 = $pdo->query("SELECT ri.id, r.name, ri.qty, ri.cost_per_unit, ri.total_cost FROM recipe_items ri JOIN recipes r ON ri.recipe_id = r.id WHERE r.outlet_id = 8")->fetchAll(PDO::FETCH_ASSOC);

$items5 = $pdo->query("SELECT r.name, sum(ri.total_cost) as total_hpp FROM recipe_items ri JOIN recipes r ON ri.recipe_id = r.id WHERE r.outlet_id = 5 GROUP BY r.id")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalHpp8 = $pdo->query("SELECT r.name, sum(ri.total_cost) as total_hpp FROM recipe_items ri JOIN recipes r ON ri.recipe_id = r.id WHERE r.outlet_id = 8 GROUP BY r.id")->fetchAll(PDO::FETCH_KEY_PAIR);

$anomalies = [];
foreach ($totalHpp8 as $name => $hpp8) {
    if (isset($items5[$name])) {
        $hpp5 = $items5[$name];
        if (abs($hpp8 - $hpp5) > 100) {
            $anomalies[] = [
                'name' => $name,
                'hpp_kalibunder' => $hpp5,
                'hpp_pasekon' => $hpp8
            ];
        }
    }
}

if (count($anomalies) > 0) {
    echo "Found " . count($anomalies) . " anomalous recipes where Pasekon HPP differs greatly from Kalibunder:\n";
    print_r(array_slice($anomalies, 0, 10)); // just print first 10
} else {
    echo "All HPP match nicely!\n";
}
