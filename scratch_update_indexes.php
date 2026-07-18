<?php
require 'helpers/functions.php';
require 'core/Database.php';

$db = Database::connection();

try {
    // Drop old unique indexes
    $db->exec("ALTER TABLE products DROP INDEX uq_products_sku");
    $db->exec("ALTER TABLE product_variants DROP INDEX uq_product_variants_sku");

    // Add new unique indexes involving outlet_id
    // If outlet_id is NULL, MySQL allows multiple NULLs. So for outlet_id=1, it will be enforced.
    // Wait, earlier I decided to UPDATE all NULL to 1 in the migration script.
    // So all products will have a specific outlet_id.
    $db->exec("ALTER TABLE products ADD UNIQUE KEY uq_products_outlet_sku (outlet_id, sku)");
    $db->exec("ALTER TABLE product_variants ADD UNIQUE KEY uq_variants_outlet_sku (outlet_id, sku)");

    echo "Indexes updated successfully.\n";
} catch (Exception $e) {
    echo "Error updating indexes: " . $e->getMessage() . "\n";
}
