<?php
try {
    $dsn = 'mysql:host=srv1864.hstgr.io;port=3306;dbname=u643003184_kios_lumero;charset=utf8mb4';
    $pdo = new PDO($dsn, 'u643003184_kios_lumero', 'Lawmotion1!@#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $stmt = $pdo->query("SELECT id, name FROM raw_material_categories");
    $cats = $stmt->fetchAll();
    
    echo "Categories in raw_material_categories:\n";
    foreach ($cats as $c) {
        echo "- {$c['name']} (ID: {$c['id']})\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
