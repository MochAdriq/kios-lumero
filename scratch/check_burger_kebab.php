<?php
try {
    $dsn = 'mysql:host=srv1864.hstgr.io;port=3306;dbname=u643003184_kios_lumero;charset=utf8mb4';
    $pdo = new PDO($dsn, 'u643003184_kios_lumero', 'Lawmotion1!@#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Check Categories:\n";
    $cats = $pdo->query("SELECT id, name FROM product_categories WHERE name LIKE '%Burger%' OR name LIKE '%Kebab%'")->fetchAll();
    print_r($cats);

    echo "\nCheck Products:\n";
    $prods = $pdo->query("SELECT id, name, sku FROM products WHERE name LIKE '%Burger%' OR name LIKE '%Kebab%'")->fetchAll();
    print_r($prods);

} catch (Exception $e) {}
