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

// 1. Dapatkan Unit ID untuk "porsi"
$stmt = $pdo->prepare("SELECT id FROM units WHERE name LIKE 'porsi' OR symbol LIKE 'porsi' LIMIT 1");
$stmt->execute();
$unitPorsi = $stmt->fetchColumn() ?: 4; // Asumsi default 4 jika tidak ada (biasanya 4)

// 2. Buat Sub-Resep Induk
$recipeName = "[Base-bumbu] Sambal Ijo";
$yieldQty = 13.0000;
$stmt = $pdo->prepare("INSERT INTO recipes (outlet_id, name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at) VALUES (NULL, ?, 'sub_recipe', ?, ?, 1, NOW(), NOW())");
$stmt->execute([$recipeName, $yieldQty, $unitPorsi]);
$recipeId = $pdo->lastInsertId();

echo "Sub-Recipe created with ID: $recipeId\n";

// 3. Dapatkan Raw Material IDs
function getRmId($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id, unit_id FROM raw_materials WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$items = [
    ['name' => 'Cabe Keriting Hijau', 'qty' => 250],
    ['name' => 'Cabe Rawit Hijau', 'qty' => 250],
    ['name' => 'Bumbu 1 (2 sdm)', 'qty' => 30],
    ['name' => 'Minyak Goreng', 'qty' => 400],
    ['name' => 'Gula Pasir', 'qty' => 30],
    ['name' => 'Gas 3kg untuk 200 potong ayam', 'qty' => 0.01],
];

foreach ($items as $item) {
    $rm = getRmId($pdo, $item['name']);
    if ($rm) {
        $stmtItem = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id, cost_per_unit, total_cost) VALUES (?, 'raw_material', ?, ?, ?, 0, 0)");
        $stmtItem->execute([
            $recipeId,
            $rm['id'],
            $item['qty'],
            $rm['unit_id']
        ]);
        echo "Inserted item: {$item['name']} (Qty: {$item['qty']})\n";
    } else {
        echo "WARNING: Raw material not found for: {$item['name']}\n";
    }
}

echo "Sub-recipe Sambal Ijo successfully created!\n";
