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

$outletId = 5;

// 1. Raw Materials to ensure exist
$newMaterials = [
    'Bubuk Es Krim Vanilla', 'Cone Es Krim', 'Cup Sundae', 'Tutup Cup Sundae', 'Sendok Es Krim', 
    'Selai Strawberry', 'Saus Cokelat',
    'Roti Burger (Bun)', 'Daging Patty Sapi', 'Kulit Kebab (Tortilla)', 'Daging Kebab Sapi', 
    'Daging Kebab Ayam', 'Telur Ayam', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 
    'Kotak Burger', 'Kantong Kebab'
];

$rawMaterialIds = [];

$pdo->beginTransaction();
try {
    foreach ($newMaterials as $matName) {
        // Cek raw_materials
        $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? LIMIT 1");
        $stmt->execute([$matName]);
        $row = $stmt->fetch();
        if ($row) {
            $matId = $row['id'];
        } else {
            $sku = 'RM-' . strtoupper(substr(md5($matName . time()), 0, 8));
            $stIns = $pdo->prepare("INSERT INTO raw_materials (outlet_id, sku, name, category_id, unit_id, is_active, created_at) VALUES (?, ?, ?, 110009, 1, 1, NOW())");
            $stIns->execute([$outletId, $sku, $matName]);
            $matId = $pdo->lastInsertId();
            echo "Created Raw Material: $matName (ID: $matId, SKU: $sku)\n";
        }
        $rawMaterialIds[$matName] = $matId;

        // Link ke outlet_raw_materials
        $stLink = $pdo->prepare("SELECT id FROM outlet_raw_materials WHERE raw_material_id = ? AND outlet_id = ?");
        $stLink->execute([$matId, $outletId]);
        if (!$stLink->fetch()) {
            $stInsLink = $pdo->prepare("INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, stock_qty, min_stock_qty, average_cost) VALUES (?, ?, 999, 10, 0)");
            $stInsLink->execute([$outletId, $matId]);
        }
    }

    // 2. Ensure Burger & Kebab variants exist in Outlet 8
    $burgerKebabItems = [
        ['name' => 'Cheese Burger', 'sku' => 'DC-MNU-19-PSKL', 'price' => 15000],
        ['name' => 'Kebab Telor', 'sku' => 'DC-MNU-20-PSKL', 'price' => 12000],
        ['name' => 'Kebab Ayam', 'sku' => 'DC-MNU-21-PSKL', 'price' => 15000],
        ['name' => 'Kebab Beef', 'sku' => 'DC-MNU-22-PSKL', 'price' => 18000],
    ];
    $variantIds = [];

    // Juga ambil variant es krim yg tadi dibuat
    $iceCreams = [
        'Classic Cone' => 'ICE-CRM-CONE',
        'Vanilla Sundae' => 'ICE-VAN-SND',
        'Strawberry Sundae' => 'ICE-STR-SND',
        'Choco Sundae' => 'ICE-CHO-SND'
    ];
    
    foreach ($iceCreams as $name => $sku) {
        $stVar = $pdo->prepare("SELECT id FROM product_variants WHERE sku = ? AND outlet_id = ?");
        $stVar->execute([$sku, $outletId]);
        $var = $stVar->fetch();
        if ($var) $variantIds[$name] = $var['id'];
    }

    foreach ($burgerKebabItems as $item) {
        $stProd = $pdo->prepare("SELECT id, category_id FROM products WHERE sku = ? ORDER BY id DESC LIMIT 1");
        $stProd->execute([$item['sku']]);
        $prod = $stProd->fetch();
        if (!$prod) {
            echo "ERROR: Product {$item['name']} not found in products table!\n";
            continue;
        }
        $productId = $prod['id'];

        $stVar = $pdo->prepare("SELECT id FROM product_variants WHERE sku = ? AND outlet_id = ?");
        $stVar->execute([$item['sku'], $outletId]);
        $var = $stVar->fetch();
        if ($var) {
            $variantIds[$item['name']] = $var['id'];
        } else {
            $stInsVar = $pdo->prepare("INSERT INTO product_variants (product_id, outlet_id, variant_name, sku, selling_price, hpp, is_active, is_default, created_at) VALUES (?, ?, ?, ?, ?, 0, 1, 1, NOW())");
            $stInsVar->execute([$productId, $outletId, $item['name'], $item['sku'], $item['price']]);
            $variantIds[$item['name']] = $pdo->lastInsertId();
            echo "Created Variant for {$item['name']} in Outlet 8\n";
        }
    }

    // 3. Define Recipe Compositions
    $compositions = [
        'Classic Cone' => ['Bubuk Es Krim Vanilla', 'Cone Es Krim'],
        'Vanilla Sundae' => ['Bubuk Es Krim Vanilla', 'Cup Sundae', 'Sendok Es Krim'],
        'Strawberry Sundae' => ['Bubuk Es Krim Vanilla', 'Cup Sundae', 'Sendok Es Krim', 'Selai Strawberry'],
        'Choco Sundae' => ['Bubuk Es Krim Vanilla', 'Cup Sundae', 'Sendok Es Krim', 'Saus Cokelat'],
        'Cheese Burger' => ['Roti Burger (Bun)', 'Daging Patty Sapi', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 'Kotak Burger'],
        'Kebab Telor' => ['Kulit Kebab (Tortilla)', 'Telur Ayam', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 'Kantong Kebab'],
        'Kebab Ayam' => ['Kulit Kebab (Tortilla)', 'Daging Kebab Ayam', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 'Kantong Kebab'],
        'Kebab Beef' => ['Kulit Kebab (Tortilla)', 'Daging Kebab Sapi', 'Daun Selada', 'Mayonnaise', 'Saus Tomat/Sambal', 'Kantong Kebab'],
    ];

    foreach ($compositions as $productName => $ingrList) {
        if (!isset($variantIds[$productName])) {
            echo "SKIP Recipe for $productName (Variant ID not found)\n";
            continue;
        }
        $varId = $variantIds[$productName];

        // Cek existing recipe
        $stRec = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id = ?");
        $stRec->execute([$varId]);
        $rec = $stRec->fetch();
        if ($rec) {
            $recipeId = $rec['id'];
            // Hapus isi resep lama biar bersih
            $pdo->prepare("DELETE FROM recipe_items WHERE recipe_id = ?")->execute([$recipeId]);
            echo "Updated Recipe for $productName (ID: $recipeId)\n";
        } else {
            $pdo->prepare("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, version, is_active, created_at) VALUES (?, ?, ?, 'final', 1, 1, 1, NOW())")->execute([$outletId, $varId, $productName]);
            $recipeId = $pdo->lastInsertId();
            echo "Created Recipe for $productName (ID: $recipeId)\n";
        }

        foreach ($ingrList as $ingName) {
            $matId = $rawMaterialIds[$ingName];
            $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id) VALUES (?, 'raw_material', ?, 1, 1)")->execute([$recipeId, $matId]);
        }
    }

    $pdo->commit();
    echo "\nSelesai! Bahan baku dan Resep berhasil di-inject ke Database PROD Outlet 8.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
