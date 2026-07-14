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

if (!function_exists('fo_e')) {
    function fo_e($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fo_money')) {
    function fo_money($v): string {
        return function_exists('rupiah') ? rupiah((int)$v) : 'Rp ' . number_format((int)$v, 0, ',', '.');
    }
}

if (!function_exists('fo_all')) {
    function fo_all(PDO $pdo, string $sql, array $params = []): array {
        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('fo_one')) {
    function fo_one(PDO $pdo, string $sql, array $params = []): ?array {
        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $r = $st->fetch(PDO::FETCH_ASSOC);
            return $r !== false ? $r : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('fo_exec')) {
    function fo_exec(PDO $pdo, string $sql, array $params = []): bool {
        try {
            $st = $pdo->prepare($sql);
            return $st->execute($params);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('fo_normalize_phone')) {
    function fo_normalize_phone(string $phone): string {
        $p = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($p, '62')) {
            $p = '0' . substr($p, 2);
        }
        return $p;
    }
}

if (!function_exists('fo_get_customer_by_phone')) {
    function fo_get_customer_by_phone(PDO $pdo, string $phone): ?array {
        if ($phone === '') return null;
        try {
            $st = $pdo->prepare("SELECT * FROM members WHERE phone=? LIMIT 1");
            $st->execute([$phone]);
            $r = $st->fetch(PDO::FETCH_ASSOC);
            if ($r) return $r;
            $st = $pdo->prepare("SELECT customer_name AS name, customer_phone AS phone, COUNT(*) AS order_count, MAX(order_number) AS last_order_no FROM orders WHERE customer_phone=? GROUP BY customer_phone LIMIT 1");
            $st->execute([$phone]);
            $r = $st->fetch(PDO::FETCH_ASSOC);
            return $r ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('fo_register_phone_only')) {
    function fo_register_phone_only(PDO $pdo, string $phone): bool {
        if ($phone === '') return false;
        try {
            $st = $pdo->prepare("INSERT IGNORE INTO members (name, phone, created_at) VALUES ('Pelanggan', ?, NOW())");
            return $st->execute([$phone]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('fo_valid_date')) {
    function fo_valid_date($date): bool {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        $p = explode('-', $date);
        return checkdate((int)$p[1], (int)$p[2], (int)$p[0]);
    }
}

if (!function_exists('fo_valid_time')) {
    function fo_valid_time($time): bool {
        if (!is_string($time)) return false;
        return (bool)preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $time);
    }
}

if (!function_exists('fo_next_no')) {
    function fo_next_no(PDO $pdo): string {
        $prefix = 'FO-' . date('Ymd') . '-';
        try {
            $st = $pdo->prepare("SELECT pre_order_no FROM free_orders WHERE pre_order_no LIKE ? ORDER BY id DESC LIMIT 1");
            $st->execute([$prefix . '%']);
            $last = $st->fetchColumn();
            $seq = 1;
            if ($last) {
                $parts = explode('-', (string)$last);
                $seq = (int)end($parts) + 1;
            }
            return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return $prefix . rand(1000, 9999);
        }
    }
}

if (!function_exists('fo_upsert_customer')) {
    function fo_upsert_customer(PDO $pdo, string $phone, string $name, string $no): void {
        if ($phone === '') return;
        try {
            $st = $pdo->prepare("SELECT id FROM members WHERE phone=? LIMIT 1");
            $st->execute([$phone]);
            if ($st->fetchColumn()) {
                if ($name !== '' && mb_strtolower($name) !== 'pelanggan') {
                    $pdo->prepare("UPDATE members SET name=? WHERE phone=?")->execute([$name, $phone]);
                }
            } else {
                $pdo->prepare("INSERT INTO members (name, phone, created_at) VALUES (?, ?, NOW())")->execute([$name !== '' ? $name : 'Pelanggan', $phone]);
            }
        } catch (Throwable $e) {}
    }
}

if (!function_exists('fo_calc_item')) {
    function fo_calc_item(PDO $pdo, array $item): array {
        $qty = max(1, (int)($item['qty'] ?? 1));
        
        if (!empty($item['variant_id'])) {
            $st = $pdo->prepare("
                SELECT pv.id AS variant_id, p.name AS product_name, pv.variant_name,
                       COALESCE(NULLIF(pv.selling_price,0), p.base_price, 0) AS price,
                       COALESCE(NULLIF(pv.hpp,0), p.base_hpp, 0) AS hpp
                FROM product_variants pv
                JOIN products p ON p.id = pv.product_id
                WHERE pv.id = ?
            ");
            $st->execute([(int)$item['variant_id']]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $vName = (string)($row['variant_name'] ?? '');
                $pName = (string)($row['product_name'] ?? '');
                $isDefault = strcasecmp($vName, 'Default') === 0;
                $name = trim($pName . ' ' . ($isDefault ? '' : $vName));
                $price = (int)$row['price'];
                $hpp = (int)$row['hpp'];
                return [
                    'menu_item_id' => $row['variant_id'],
                    'type' => 'menu',
                    'name' => $name,
                    'item_name' => $name,
                    'price' => $price,
                    'hpp' => $hpp,
                    'qty' => $qty,
                    'line_total' => $price * $qty,
                    'line_hpp' => $hpp * $qty
                ];
            }
        }
        
        $type = $item['type'] ?? 'menu';
        $price = 0;
        $hpp = 0;
        $name = $item['name'] ?? 'Item';
        
        if ($type === 'chicken') {
            $price = 15000;
            $hpp = 9000;
            if (!empty($item['part_name'])) $name = $item['part_name'];
            if (($item['style'] ?? '') === 'sauce') $price += 3000;
            if (!empty($item['with_rice'])) $price += 5000;
        } elseif ($type === 'menu' && !empty($item['menu_id'])) {
            $row = fo_one($pdo, "SELECT name, price, hpp FROM menu_items WHERE id=?", [(int)$item['menu_id']]);
            if ($row) {
                $name = $row['name'];
                $price = (int)$row['price'];
                $hpp = (int)$row['hpp'];
            }
        }
        return [
            'type' => $type,
            'name' => $name,
            'item_name' => $name,
            'price' => $price,
            'hpp' => $hpp,
            'qty' => $qty,
            'line_total' => $price * $qty,
            'line_hpp' => $hpp * $qty
        ];
    }
}

if (!function_exists('fo_normalize_cart')) {
    function fo_normalize_cart(PDO $pdo, array $cart): array {
        $items = [];
        $subtotal = 0;
        $totalHpp = 0;
        foreach ($cart as $item) {
            if (!is_array($item)) continue;
            $calc = fo_calc_item($pdo, $item);
            $items[] = array_merge($item, $calc);
            $subtotal += $calc['line_total'];
            $totalHpp += $calc['line_hpp'];
        }
        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'total_hpp' => $totalHpp
        ];
    }
}

if (!function_exists('fo_load_pos_menu_data')) {
    function fo_load_pos_menu_data(PDO $pdo): array {
        $st = $pdo->query("
            SELECT pv.id AS variant_id, p.id AS product_id, p.name AS product_name, pv.variant_name,
                   COALESCE(NULLIF(pv.selling_price,0), p.base_price, 0) AS price,
                   COALESCE(NULLIF(pv.hpp,0), p.base_hpp, 0) AS hpp,
                   COALESCE(pv.image, p.image) AS image,
                   pc.name AS category_name
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            JOIN product_categories pc ON pc.id = p.category_id
            WHERE p.is_active = 1 AND pv.is_active = 1 AND pc.is_active = 1
            ORDER BY p.name ASC, pv.variant_name ASC
        ");
        $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];

        $parts = [];
        $sauces = [];
        $kentang = [];
        $matcha = [];
        $others = [];

        foreach ($rows as $r) {
            $cat = mb_strtolower((string)$r['category_name']);
            $pname = mb_strtolower((string)$r['product_name']);
            $vname = mb_strtolower((string)$r['variant_name']);
            $combined = $cat . ' ' . $pname . ' ' . $vname;

            $item = [
                'id' => (int)$r['variant_id'],
                'product_id' => (int)$r['product_id'],
                'name' => trim($r['product_name'] . ($r['variant_name'] !== 'Default' && $r['variant_name'] !== '' ? ' - ' . $r['variant_name'] : '')),
                'short_name' => trim($r['product_name']),
                'price' => (int)$r['price'],
                'hpp' => (int)$r['hpp'],
                'image' => $r['image'],
                'stock_available' => function_exists('check_variant_stock') ? check_variant_stock($pdo, (int)$r['variant_id']) : true
            ];

            if (strpos($combined, 'saus') !== false || strpos($combined, 'sauce') !== false || strpos($combined, 'celup') !== false) {
                $sauces[] = $item;
            } elseif (strpos($combined, 'kentang') !== false || strpos($combined, 'fries') !== false) {
                $kentang[] = $item;
            } elseif (strpos($combined, 'minuman') !== false || strpos($combined, 'matcha') !== false || strpos($combined, 'kopi') !== false || strpos($combined, 'drink') !== false) {
                $matcha[] = $item;
            } elseif (strpos($combined, 'ayam') !== false || strpos($combined, 'dada') !== false || strpos($combined, 'paha') !== false || strpos($combined, 'sayap') !== false || strpos($combined, 'crispy') !== false) {
                $parts[] = $item;
            } else {
                $others[] = $item;
            }
        }

        if (empty($parts)) {
            $parts = [
                ['id'=>1, 'name'=>'Ayam Dada', 'short_name'=>'Ayam Dada', 'price'=>15000, 'hpp'=>9000, 'image'=>'images/pos-products/dada.png'],
                ['id'=>2, 'name'=>'Ayam Paha Atas', 'short_name'=>'Ayam Paha Atas', 'price'=>15000, 'hpp'=>9000, 'image'=>'images/pos-products/paha-atas.png'],
                ['id'=>3, 'name'=>'Ayam Paha Bawah', 'short_name'=>'Ayam Paha Bawah', 'price'=>14000, 'hpp'=>8500, 'image'=>'images/pos-products/paha-bawah.png'],
                ['id'=>4, 'name'=>'Ayam Sayap', 'short_name'=>'Ayam Sayap', 'price'=>13000, 'hpp'=>8000, 'image'=>'images/pos-products/sayap.png']
            ];
        }
        if (empty($sauces)) {
            $sauces = [
                ['id'=>1, 'name'=>'Saus Lumer Keju', 'price'=>3000, 'hpp'=>1500, 'image'=>'images/pos-products/saus.png'],
                ['id'=>2, 'name'=>'Saus BBQ Pedas', 'price'=>3000, 'hpp'=>1500, 'image'=>'images/pos-products/saus.png']
            ];
        }

        return [
            'parts' => $parts,
            'sauces' => $sauces,
            'kentang' => $kentang,
            'matcha' => $matcha,
            'others' => $others
        ];
    }
}
