<?php
try {
    $dsn = 'mysql:host=srv1864.hstgr.io;port=3306;dbname=u643003184_kios_lumero;charset=utf8mb4';
    $pdo = new PDO($dsn, 'u643003184_kios_lumero', 'Lawmotion1!@#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Prod gagal: " . $e->getMessage() . "\n");
}

$outletId = 5; // Kalibunder KLB

$kebabItems = [
    'DC-MNU-21-PSKL-VAR' => [
        'name' => 'Kebab Ayam',
        'ingredients' => ['Kulit Kebab (Tortilla)', 'Daging Kebab Ayam', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 'Kantong Kebab'],
        'selling_price' => 13000
    ],
    'DC-MNU-22-PSKL-VAR' => [
        'name' => 'Kebab Beef',
        'ingredients' => ['Kulit Kebab (Tortilla)', 'Daging Kebab Sapi', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 'Kantong Kebab']
    ]
];

$pdo->beginTransaction();
try {
    foreach ($kebabItems as $sku => $data) {
        // Find the variant
        $stVar = $pdo->prepare("SELECT id FROM product_variants WHERE sku = ? AND outlet_id = ?");
        $stVar->execute([$sku, $outletId]);
        $var = $stVar->fetch();
        
        if (!$var) {
            echo "ERROR: Variant $sku not found in Outlet $outletId\n";
            continue;
        }
        $varId = $var['id'];
        
        // Update selling price if specified
        if (isset($data['selling_price'])) {
            $pdo->prepare("UPDATE product_variants SET selling_price = ? WHERE id = ?")->execute([$data['selling_price'], $varId]);
        }

        // Check existing recipe
        $stRec = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id = ?");
        $stRec->execute([$varId]);
        $rec = $stRec->fetch();
        if ($rec) {
            $recipeId = $rec['id'];
            // Hapus isi resep lama biar bersih
            $pdo->prepare("DELETE FROM recipe_items WHERE recipe_id = ?")->execute([$recipeId]);
            echo "Updated Recipe for {$data['name']} (SKU: $sku, Recipe ID: $recipeId)\n";
        } else {
            $pdo->prepare("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, version, is_active, created_at) VALUES (?, ?, ?, 'final', 1, 1, 1, NOW())")->execute([$outletId, $varId, $data['name']]);
            $recipeId = $pdo->lastInsertId();
            echo "Created Recipe for {$data['name']} (SKU: $sku, Recipe ID: $recipeId)\n";
        }

        // Insert ingredients
        foreach ($data['ingredients'] as $ingName) {
            $stMat = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? LIMIT 1");
            $stMat->execute([$ingName]);
            $mat = $stMat->fetch();
            if ($mat) {
                $matId = $mat['id'];
                $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1, 1)")->execute([$recipeId, $matId]);
            } else {
                echo "Warning: Raw material '$ingName' not found!\n";
            }
        }
    }

    $pdo->commit();
    echo "\nSelesai! Resep untuk Kebab (-VAR) berhasil di-update/inject ke Database PROD Outlet $outletId.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
