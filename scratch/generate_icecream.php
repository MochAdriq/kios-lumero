<?php
// Script to generate ice cream products in PROD for outlet 8
try {
    $dsn = 'mysql:host=srv1864.hstgr.io;port=3306;dbname=u643003184_kios_lumero;charset=utf8mb4';
    $pdo = new PDO($dsn, 'u643003184_kios_lumero', 'Lawmotion1!@#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    echo "Koneksi Prod gagal: " . $e->getMessage() . "\n";
    exit;
}

// 1. Cari kategori "Ice Cream" (kasus-insensitif atau sejenisnya)
$stCat = $pdo->query("SELECT id FROM product_categories WHERE name LIKE '%Ice Cream%' OR name LIKE '%Es Krim%' LIMIT 1");
$cat = $stCat->fetch(PDO::FETCH_ASSOC);

if (!$cat) {
    echo "Kategori 'Ice Cream' belum ditemukan di PROD. Silakan buat kategorinya terlebih dahulu.\n";
    exit;
}

$categoryId = (int)$cat['id'];
echo "Ditemukan Kategori ID: $categoryId\n";

$items = [
    [
        'name' => 'Strawberry Sundae',
        'slug' => 'strawberry-sundae',
        'price' => 10000,
        'sku' => 'ICE-STR-SND'
    ],
    [
        'name' => 'Choco Sundae',
        'slug' => 'choco-sundae',
        'price' => 10000,
        'sku' => 'ICE-CHO-SND'
    ],
    [
        'name' => 'Vanilla Sundae',
        'slug' => 'vanilla-sundae',
        'price' => 10000,
        'sku' => 'ICE-VAN-SND'
    ],
    [
        'name' => 'Classic Cone',
        'slug' => 'classic-cone',
        'price' => 5000,
        'sku' => 'ICE-CRM-CONE'
    ]
];

$pdo->beginTransaction();
try {
    foreach ($items as $item) {
        $outletId = 5;
        // Cek apakah parent product sudah ada
        $stCheck = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND outlet_id = ?");
        $stCheck->execute([$item['sku'], $outletId]);
        $prod = $stCheck->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $productId = $prod['id'];
            echo "Produk {$item['name']} sudah ada (ID: $productId)\n";
        } else {
            // Insert produk baru
            $stInsertProd = $pdo->prepare("
                INSERT INTO products (name, sku, category_id, outlet_id, description, product_type, is_active, created_at)
                VALUES (?, ?, ?, ?, '', 'variant_parent', 1, NOW())
            ");
            $stInsertProd->execute([$item['name'], $item['sku'], $categoryId, $outletId]);
            $productId = $pdo->lastInsertId();
            echo "Berhasil membuat Produk: {$item['name']} (ID: $productId)\n";
        }

        // Cek variant di outlet 8
        $stCheckVar = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ? AND outlet_id = ?");
        $stCheckVar->execute([$productId, $outletId]);
        $var = $stCheckVar->fetch(PDO::FETCH_ASSOC);

        if ($var) {
            echo "Variant untuk {$item['name']} sudah ada di Outlet $outletId (Variant ID: {$var['id']})\n";
        } else {
            // Insert variant
            $stInsertVar = $pdo->prepare("
                INSERT INTO product_variants 
                (product_id, outlet_id, variant_name, sku, selling_price, hpp, is_active, is_default, created_at)
                VALUES (?, ?, ?, ?, ?, 0, 1, 1, NOW())
            ");
            // Variant name kosongin aja atau samakan
            $stInsertVar->execute([
                $productId, 
                $outletId, 
                $item['name'], 
                $item['sku'], 
                $item['price']
            ]);
            $varId = $pdo->lastInsertId();
            echo "Berhasil membuat Variant: {$item['name']} di Outlet $outletId (SKU: {$item['sku']}, Variant ID: $varId)\n";
        }
    }
    
    $pdo->commit();
    echo "\nSelesai! Semua data Ice Cream berhasil ditambahkan di PROD (Outlet 8).\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Terjadi kesalahan: " . $e->getMessage() . "\n";
}
