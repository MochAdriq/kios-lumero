<?php
$h='srv1864.hstgr.io';$d='u643003184_kios_lumero';$u='u643003184_kios_lumero';$p='Lawmotion1!@#';
try {
    $pdo = new PDO("mysql:host=$h;dbname=$d", $u, $p);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add columns if they don't exist
    $cols = $pdo->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('fulfilled_qty', $cols)) {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN fulfilled_qty DECIMAL(15,3) DEFAULT 0.00 AFTER qty");
        echo "Added fulfilled_qty to order_items on PROD.\n";
    } else {
        echo "fulfilled_qty already exists on PROD.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
