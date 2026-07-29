<?php
try {
    $dsn = 'mysql:host=srv1864.hstgr.io;port=3306;dbname=u643003184_kios_lumero;charset=utf8mb4';
    $pdo = new PDO($dsn, 'u643003184_kios_lumero', 'Lawmotion1!@#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $skus = ['DC-MNU-21-PSKL-VAR', 'DC-MNU-22-PSKL-VAR'];
    foreach ($skus as $sku) {
        $stmt = $pdo->prepare("SELECT id, name, sku FROM products WHERE sku LIKE ?");
        $stmt->execute(['%' . substr($sku, 0, 10) . '%']);
        $prods = $stmt->fetchAll();
        echo "Products matching $sku:\n";
        print_r($prods);

        $stmt = $pdo->prepare("SELECT id, variant_name, sku, outlet_id FROM product_variants WHERE sku = ?");
        $stmt->execute([$sku]);
        $vars = $stmt->fetchAll();
        echo "Variants matching $sku:\n";
        print_r($vars);
    }
} catch (Exception $e) {}
