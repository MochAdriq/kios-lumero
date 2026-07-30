<?php
require_once __DIR__ . '/../core/Database.php';

try {
    $pdo = Database::connection();
    
    // Add columns if they don't exist
    $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('customer_name', $cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(150) NULL AFTER customer_id");
        echo "Added customer_name.\n";
    }
    
    if (!in_array('change_owed_amount', $cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN change_owed_amount DECIMAL(15,2) DEFAULT 0.00 AFTER grand_total");
        echo "Added change_owed_amount.\n";
    }

    // Modify enums
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid','waiting_verification','paid','partial','refunded','void','owes_change') DEFAULT 'unpaid'");
    echo "Modified payment_status enum.\n";

    $pdo->exec("ALTER TABLE orders MODIFY COLUMN order_status ENUM('draft','pending','preparing','ready','completed','cancelled','void') DEFAULT 'pending'");
    echo "Modified order_status enum.\n";

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
