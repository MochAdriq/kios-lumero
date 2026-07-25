<?php
/**
 * CLONE RESEP KALIBUNDER → PASEKON (OUTLET 8)
 * ============================================
 * 1. Rename Mix Tea → Max Tea di Pasekon
 * 2. Clone semua sub_recipe langsung
 * 3. Clone final recipes yang match by variant_name
 * 4. Manual remap: Matcha & Kentang
 */

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

set_time_limit(120);

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$KALI_ID   = 5;
$PSKL_ID   = 8;

// ============================================================
// STEP 1: Rename Mix Tea → Max Tea di Pasekon
// ============================================================
echo "=== STEP 1: RENAME MIX TEA → MAX TEA DI PASEKON ===\n";

$renamed = $pdo->exec("
    UPDATE product_variants 
    SET variant_name = REPLACE(variant_name, 'Mix Tea', 'Max Tea'),
        updated_at = NOW()
    WHERE outlet_id = $PSKL_ID 
    AND variant_name LIKE '%Mix Tea%'
");
echo "  ✅ $renamed varian di-rename\n";

// Verifikasi
$rows = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = $PSKL_ID AND variant_name LIKE '%Max Tea%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  → ID {$r['id']}: {$r['variant_name']}\n";

// ============================================================
// STEP 2: Build mapping Pasekon variant_name → id
// ============================================================
echo "\n=== STEP 2: BUILD PASEKON VARIANT MAP ===\n";

$pasekonVariants = $pdo->query("
    SELECT pv.id, pv.variant_name, p.name as product_name
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    WHERE pv.outlet_id = $PSKL_ID
")->fetchAll(PDO::FETCH_ASSOC);

$pasekonByName = [];
foreach ($pasekonVariants as $pv) {
    $pasekonByName[strtolower(trim($pv['variant_name']))] = $pv['id'];
}
echo "  Total varian Pasekon: " . count($pasekonByName) . "\n";

// Manual remap: Kalibunder variant_name → Pasekon variant_name
// Untuk yang namanya sedikit beda
$manualRemap = [
    // Matcha
    'minuman matcha latte'  => 'matcha latte',
    'minuman matcha coklat' => 'matcha coklat',
    'minuman matcha taro'   => 'matcha taro',
    // Kentang Kriwil mapping dari Potato Crispy Kalibunder
    'original reguler'              => 'kentang kriwil original',
    'saus sadis reguler'            => 'kentang kriwil saus sadis',
    'saus italian barbeque spicy reguler' => 'kentang kriwil saus barbeque spicy',
    'saus teriyaki reguler'         => 'kentang kriwil saus teriyaki',
    'saus lada hitam reguler'       => 'kentang kriwil saus lada hitam',
    'saus cheese reguler'           => 'kentang kriwil saus keju',
    'saus mentai reguler'           => 'kentang kriwil saus mentai',
    'sambal master reguler'         => 'kentang kriwil sambal master',
    'saus garlic reguler'           => 'kentang kriwil saus garlic',
    'smocky saus mentai reguler'    => 'kentang kriwil smocky saus mentai',
    'smocky cheese mozzarella reguler' => 'kentang kriwil smocky keju mozzarella',
    // Mix Tea → Max Tea (sudah direname, tapi kalibunder recipe punya nama lama)
    'max tea hot'   => 'max tea hot',
    'max tea ice'   => 'max tea ice',
];

// ============================================================
// STEP 3: Ambil semua resep Kalibunder + items-nya
// ============================================================
echo "\n=== STEP 3: CLONE RESEP ===\n";

$kaliRecipes = $pdo->query("
    SELECT r.id, r.name, r.recipe_type, r.yield_qty, r.yield_unit_id, r.yield_unit_label,
           r.is_active, r.notes, pv.variant_name
    FROM recipes r
    LEFT JOIN product_variants pv ON r.product_variant_id = pv.id
    WHERE r.outlet_id = $KALI_ID
    ORDER BY r.recipe_type DESC, r.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$stmtRecipe = $pdo->prepare("INSERT INTO recipes 
    (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, yield_unit_label, is_active, notes, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$stmtItem = $pdo->prepare("INSERT INTO recipe_items 
    (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$cloned   = 0;
$skipped  = 0;
$noMatch  = [];

foreach ($kaliRecipes as $rec) {
    $pasekonVariantId = null;

    if ($rec['recipe_type'] === 'sub_recipe') {
        // Sub-recipe: tidak perlu variant_id
        $pasekonVariantId = null;
    } else {
        // Final recipe: cari variant di Pasekon
        $varNameLower = strtolower(trim($rec['variant_name'] ?? ''));

        // Coba direct match
        if (isset($pasekonByName[$varNameLower])) {
            $pasekonVariantId = $pasekonByName[$varNameLower];
        }
        // Coba manual remap
        elseif (isset($manualRemap[$varNameLower])) {
            $remappedName = $manualRemap[$varNameLower];
            $pasekonVariantId = $pasekonByName[$remappedName] ?? null;
        }

        // Jika tidak ketemu, skip
        if (!$pasekonVariantId && $rec['recipe_type'] === 'final') {
            $noMatch[] = $rec['name'] . " (variant: '{$rec['variant_name']}')";
            $skipped++;
            continue;
        }
    }

    // Insert recipe
    $stmtRecipe->execute([
        $PSKL_ID,
        $pasekonVariantId,
        $rec['name'],
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
    $type = $rec['recipe_type'] === 'sub_recipe' ? '[sub]' : '[final]';
    echo "  ✅ $type '{$rec['name']}'\n";
}

// ============================================================
// RINGKASAN
// ============================================================
echo "\n" . str_repeat("=", 55) . "\n";
echo "✅ CLONE RESEP SELESAI!\n";
echo str_repeat("=", 55) . "\n";
echo "📋 Resep di-clone  : $cloned\n";
echo "⏭️  Resep di-skip   : $skipped (tidak relevan di Pasekon)\n";

if (!empty($noMatch)) {
    echo "\nResep yang di-skip (varian tidak ada di Pasekon):\n";
    foreach ($noMatch as $nm) echo "  - $nm\n";
}
