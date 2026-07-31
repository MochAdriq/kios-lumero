<?php
$_SERVER['HTTP_HOST'] = 'lokapedia.id';
putenv("APP_ENV=production");
require 'helpers/functions.php';
require 'core/Database.php';

try {
    $pdo = Database::connection();
    
    // Using ADD COLUMN ... IF NOT EXISTS is not standard until MariaDB 10.6 or so, 
    // we can do a query to check if it exists first.
    $stmt = $pdo->query('SHOW COLUMNS FROM outlets');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $alterQueries = [];
    if (!in_array('is_online_order_active', $columns)) {
        $alterQueries[] = 'ADD COLUMN is_online_order_active TINYINT(1) NOT NULL DEFAULT 1';
    }
    if (!in_array('online_order_wa', $columns)) {
        $alterQueries[] = 'ADD COLUMN online_order_wa VARCHAR(50) NULL DEFAULT NULL';
    }
    if (!in_array('allow_delivery', $columns)) {
        $alterQueries[] = 'ADD COLUMN allow_delivery TINYINT(1) NOT NULL DEFAULT 0';
    }
    if (!in_array('allow_pickup', $columns)) {
        $alterQueries[] = 'ADD COLUMN allow_pickup TINYINT(1) NOT NULL DEFAULT 1';
    }

    if (!empty($alterQueries)) {
        $sql = 'ALTER TABLE outlets ' . implode(', ', $alterQueries);
        $pdo->exec($sql);
        echo "Table outlets altered successfully.\n";
    } else {
        echo "Columns already exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
