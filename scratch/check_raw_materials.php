<?php
try {
    $dsn = 'mysql:host=srv1864.hstgr.io;port=3306;dbname=u643003184_kios_lumero;charset=utf8mb4';
    $pdo = new PDO($dsn, 'u643003184_kios_lumero', 'Lawmotion1!@#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $stmt = $pdo->query("SELECT id, name FROM raw_materials ORDER BY name ASC");
    $materials = $stmt->fetchAll();
    
    echo "Raw Materials:\n";
    foreach ($materials as $m) {
        echo "- {$m['name']} (ID: {$m['id']})\n";
    }
} catch (Exception $e) {}
