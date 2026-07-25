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

$KALI_ID   = 5;
$PSKL_ID   = 8;

echo "=== MEMETAKAN SISA RESEP (CHEESE -> KEJU, ITALIAN BBQ SPICY -> BBQ SPICY) ===\n\n";

// Map dari nama resep/varian di Kalibunder ke nama varian di Pasekon
$manualRemap = [
    'dada cheese + nasi' => 'dada keju + nasi',
    'dada cheese tanpa nasi' => 'dada keju tanpa nasi',
    'paha atas cheese + nasi' => 'paha atas keju + nasi',
    'paha atas cheese tanpa nasi' => 'paha atas keju tanpa nasi',
    'paha bawah cheese + nasi' => 'paha bawah keju + nasi',
    'paha bawah cheese tanpa nasi' => 'paha bawah keju tanpa nasi',
    'sayap cheese + nasi' => 'sayap keju + nasi',
    'sayap cheese tanpa nasi' => 'sayap keju tanpa nasi',
    
    'dada italian barbeque spicy + nasi' => 'dada bbq spicy + nasi',
    'dada italian barbeque spicy tanpa nasi' => 'dada bbq spicy tanpa nasi',
    'paha atas italian barbeque spicy + nasi' => 'paha atas bbq spicy + nasi',
    'paha atas italian barbeque spicy tanpa nasi' => 'paha atas bbq spicy tanpa nasi',
    'paha bawah italian barbeque spicy + nasi' => 'paha bawah bbq spicy + nasi',
    'paha bawah italian barbeque spicy tanpa nasi' => 'paha bawah bbq spicy tanpa nasi',
    'sayap italian barbeque spicy + nasi' => 'sayap bbq spicy + nasi',
    'sayap italian barbeque spicy tanpa nasi' => 'sayap bbq spicy tanpa nasi',
];

// Ambil varian Pasekon
$pasekonVariants = $pdo->query("
    SELECT pv.id, pv.variant_name
    FROM product_variants pv
    WHERE pv.outlet_id = $PSKL_ID
")->fetchAll(PDO::FETCH_ASSOC);

$pasekonByName = [];
foreach ($pasekonVariants as $pv) {
    $pasekonByName[strtolower(trim($pv['variant_name']))] = $pv['id'];
}

// Ambil resep Kalibunder yang masih ada di manualRemap
$kaliRecipes = $pdo->query("
    SELECT r.id, r.name, r.recipe_type, r.yield_qty, r.yield_unit_id, r.yield_unit_label, r.is_active, r.notes,
           pv.variant_name
    FROM recipes r
    LEFT JOIN product_variants pv ON r.product_variant_id = pv.id
    WHERE r.outlet_id = $KALI_ID AND r.recipe_type = 'final'
")->fetchAll(PDO::FETCH_ASSOC);

$stmtRecipe = $pdo->prepare("INSERT INTO recipes 
    (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, yield_unit_label, is_active, notes, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$stmtItem = $pdo->prepare("INSERT INTO recipe_items 
    (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$cloned = 0;

foreach ($kaliRecipes as $rec) {
    $varNameLower = strtolower(trim($rec['variant_name'] ?? ''));
    
    if (isset($manualRemap[$varNameLower])) {
        $remappedName = $manualRemap[$varNameLower];
        $pasekonVariantId = $pasekonByName[$remappedName] ?? null;
        
        if ($pasekonVariantId) {
            // Cek apakah resep Pasekon dengan variantId ini sudah ada
            $check = $pdo->prepare("SELECT id FROM recipes WHERE outlet_id = ? AND product_variant_id = ?");
            $check->execute([$PSKL_ID, $pasekonVariantId]);
            if ($check->fetchColumn()) {
                continue; // Sudah ada
            }

            // Ganti nama resep agar sesuai
            $newName = str_ireplace('Cheese', 'Keju', $rec['name']);
            $newName = str_ireplace('Italian Barbeque Spicy', 'BBQ Spicy', $newName);

            // Insert recipe
            $stmtRecipe->execute([
                $PSKL_ID,
                $pasekonVariantId,
                $newName,
                $rec['recipe_type'],
                $rec['yield_qty'],
                $rec['yield_unit_id'],
                $rec['yield_unit_label'],
                $rec['is_active'],
                $rec['notes'],
            ]);
            $newRecipeId = $pdo->lastInsertId();

            // Clone recipe_items
            $items = $pdo->prepare("SELECT item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes FROM recipe_items WHERE recipe_id = ?");
            $items->execute([$rec['id']]);
            $itemRows = $items->fetchAll(PDO::FETCH_ASSOC);

            foreach ($itemRows as $item) {
                $stmtItem->execute([
                    $newRecipeId,
                    $item['item_type'],
                    $item['raw_material_id'],
                    $item['sub_recipe_id'],
                    $item['qty'],
                    $item['unit_id'],
                    $item['cost_per_unit'],
                    $item['total_cost'],
                    $item['notes'],
                ]);
            }
            $cloned++;
            echo "  ✅ Cloned: '{$newName}' (dipetakan dari '{$rec['variant_name']}')\n";
        }
    }
}

echo "\nTotal resep yang berhasil dipetakan & diclone: $cloned\n";
