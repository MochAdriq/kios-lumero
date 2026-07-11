<?php
if (!function_exists('fo_ensure_tables')) {
    function fo_ensure_tables(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS free_orders (
                id INT(11) NOT NULL AUTO_INCREMENT,
                pre_order_no VARCHAR(64) NOT NULL,
                customer_name VARCHAR(150) DEFAULT NULL,
                customer_phone VARCHAR(50) DEFAULT NULL,
                member_id INT(11) DEFAULT NULL,
                pickup_type VARCHAR(50) DEFAULT 'dine_in',
                pickup_date DATE DEFAULT NULL,
                pickup_time TIME DEFAULT NULL,
                payment_method VARCHAR(50) DEFAULT 'qris',
                payment_status VARCHAR(50) DEFAULT 'pending',
                order_status VARCHAR(50) DEFAULT 'waiting',
                subtotal INT(11) DEFAULT 0,
                discount INT(11) DEFAULT 0,
                total INT(11) DEFAULT 0,
                total_hpp INT(11) DEFAULT 0,
                loyalty_points_redeemed INT(11) DEFAULT 0,
                loyalty_point_value INT(11) DEFAULT 0,
                loyalty_redeem_amount INT(11) DEFAULT 0,
                nominal_point INT(11) DEFAULT 0,
                customer_note TEXT DEFAULT NULL,
                cart_json LONGTEXT DEFAULT NULL,
                stock_reserved TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS free_order_items (
                id INT(11) NOT NULL AUTO_INCREMENT,
                free_order_id INT(11) NOT NULL,
                item_type VARCHAR(50) DEFAULT 'menu',
                chicken_part_id INT(11) DEFAULT NULL,
                chicken_style VARCHAR(100) DEFAULT NULL,
                sauce_id INT(11) DEFAULT NULL,
                with_rice TINYINT(1) DEFAULT 0,
                matcha_variant_id INT(11) DEFAULT NULL,
                kentang_variant_id INT(11) DEFAULT NULL,
                menu_item_id INT(11) DEFAULT NULL,
                item_name VARCHAR(200) DEFAULT NULL,
                qty INT(11) DEFAULT 1,
                price INT(11) DEFAULT 0,
                hpp INT(11) DEFAULT 0,
                line_total INT(11) DEFAULT 0,
                line_hpp INT(11) DEFAULT 0,
                payload_json LONGTEXT DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_free_order_id (free_order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
}
