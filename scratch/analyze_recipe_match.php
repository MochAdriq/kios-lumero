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

$KALIBUNDER_ID = 5;
$PASEKON_ID    = 8;

echo "=== RESEP DI KALIBUNDER (outlet 5) ===\n";
$totalRec = $pdo->query("SELECT COUNT(*) FROM recipes WHERE outlet_id = $KALIBUNDER_ID")->fetchColumn();
echo "Total resep Kalibunder: $totalRec\n\n";

echo "=== ANALISIS MATCH: Kalibunder → Pasekon ===\n";

// Ambil semua resep Kalibunder beserta nama variannya
$kaliRecipes = $pdo->query("
    SELECT r.id, r.name, r.recipe_type, r.yield_qty,
           pv.variant_name, pv.id as kali_variant_id
    FROM recipes r
    LEFT JOIN product_variants pv ON r.product_variant_id = pv.id
    WHERE r.outlet_id = $KALIBUNDER_ID
    ORDER BY r.recipe_type, r.name
")->fetchAll(PDO::FETCH_ASSOC);

// Semua varian di Pasekon (nama → id)
$pasekonVariants = $pdo->query("
    SELECT pv.id, pv.variant_name, p.name as product_name, pv.sku
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    WHERE pv.outlet_id = $PASEKON_ID
")->fetchAll(PDO::FETCH_ASSOC);

$pasekonByName = [];
foreach ($pasekonVariants as $pv) {
    $pasekonByName[strtolower(trim($pv['variant_name']))] = $pv;
}

$matched   = [];
$unmatched = [];

foreach ($kaliRecipes as $rec) {
    $variantName = strtolower(trim($rec['variant_name'] ?? $rec['name']));
    if (isset($pasekonByName[$variantName])) {
        $matched[] = [
            'kali_recipe_id'   => $rec['id'],
            'kali_recipe_name' => $rec['name'],
            'recipe_type'      => $rec['recipe_type'],
            'yield_qty'        => $rec['yield_qty'],
            'kali_variant_id'  => $rec['kali_variant_id'],
            'pasekon_variant'  => $pasekonByName[$variantName],
        ];
    } else {
        $unmatched[] = $rec;
    }
}

echo "✅ AUTO-MATCH  : " . count($matched) . " resep\n";
echo "❌ TIDAK MATCH : " . count($unmatched) . " resep\n\n";

echo "--- MATCHED (sample 20 pertama) ---\n";
foreach (array_slice($matched, 0, 20) as $m) {
    echo "  '{$m['kali_recipe_name']}' → pasekon variant: '{$m['pasekon_variant']['variant_name']}' (ID: {$m['pasekon_variant']['id']})\n";
}

echo "\n--- TIDAK MATCH (perlu buat manual) ---\n";
foreach ($unmatched as $u) {
    echo "  [{$u['recipe_type']}] '{$u['name']}' (variant: '{$u['variant_name']}')\n";
}
