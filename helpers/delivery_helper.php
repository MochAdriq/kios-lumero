<?php
/**
 * Delivery Helper — Leaflet Maps + Haversine Distance + Ongkir Calculator
 * Supports configurable delivery settings via system_settings table.
 */

if (!function_exists('delivery_ensure_columns')) {
    /**
     * Ensure delivery-related columns exist in free_orders and outlets tables.
     * Safe to call multiple times (uses IF NOT EXISTS / IGNORE patterns).
     */
    function delivery_ensure_columns(PDO $pdo): void
    {
        // --- free_orders: delivery columns ---
        $deliveryCols = [
            'delivery_address'     => "TEXT DEFAULT NULL",
            'delivery_lat'         => "DECIMAL(10,8) DEFAULT NULL",
            'delivery_lng'         => "DECIMAL(11,8) DEFAULT NULL",
            'delivery_fee'         => "INT(11) DEFAULT 0",
            'delivery_distance_km' => "DECIMAL(6,2) DEFAULT NULL",
            'delivery_status'      => "VARCHAR(50) DEFAULT NULL",
            'delivery_courier_name'=> "VARCHAR(100) DEFAULT NULL",
        ];

        foreach ($deliveryCols as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE free_orders ADD COLUMN {$col} {$def}");
            } catch (Throwable $e) {
                // Column likely already exists — ignore
            }
        }

        // --- outlets: lat/lng columns ---
        foreach (['latitude' => 'DECIMAL(10,8) DEFAULT NULL', 'longitude' => 'DECIMAL(11,8) DEFAULT NULL'] as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE outlets ADD COLUMN {$col} {$def}");
            } catch (Throwable $e) {
                // Column likely already exists — ignore
            }
        }
    }
}

if (!function_exists('delivery_settings')) {
    /**
     * Load all delivery-related settings from system_settings.
     * Returns associative array with defaults for every key.
     */
    function delivery_settings(PDO $pdo): array
    {
        $defaults = [
            'delivery_enabled'           => '0',
            'delivery_max_radius_km'     => '5',
            'delivery_fee_model'         => 'per_km',   // 'flat' or 'per_km'
            'delivery_flat_fee'          => '5000',
            'delivery_per_km_fee'        => '3000',
            'delivery_min_fee'           => '5000',
            'delivery_free_above'        => '0',         // 0 = disabled
            'delivery_free_km_limit'     => '2',         // 2 km free
        ];

        try {
            $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
            $scope = function_exists('outlet_scope_sql') ? outlet_scope_sql('outlet_id', $outletId) : ['sql' => 'outlet_id = ?', 'params' => [$outletId]];

            $keys = array_keys($defaults);
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $st = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE ({$scope['sql']}) AND setting_key IN ({$placeholders})");
            $st->execute(array_merge($scope['params'], $keys));

            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            // Table may not exist yet — return defaults
        }

        return $defaults;
    }
}

if (!function_exists('delivery_is_enabled')) {
    function delivery_is_enabled(PDO $pdo): bool
    {
        $s = delivery_settings($pdo);
        return in_array($s['delivery_enabled'], ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('delivery_outlet_coords')) {
    /**
     * Get lat/lng of the current outlet.
     * Returns ['lat' => float, 'lng' => float] or null if not set.
     */
    function delivery_outlet_coords(PDO $pdo): ?array
    {
        try {
            $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
            $st = $pdo->prepare("SELECT latitude, longitude FROM outlets WHERE id = ? AND latitude IS NOT NULL AND longitude IS NOT NULL");
            $st->execute([$outletId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['latitude'] && $row['longitude']) {
                return ['lat' => (float)$row['latitude'], 'lng' => (float)$row['longitude']];
            }
            // Fallback 1: check system_settings for this outlet
            $s = delivery_settings($pdo);
            if (!empty($s['delivery_outlet_lat']) && !empty($s['delivery_outlet_lng'])) {
                return ['lat' => (float)$s['delivery_outlet_lat'], 'lng' => (float)$s['delivery_outlet_lng']];
            }
            // Fallback 2: if branch coords not set yet, fallback to HQ (outlet 1) coords as default starting point
            if ($outletId != 1) {
                $st = $pdo->prepare("SELECT latitude, longitude FROM outlets WHERE id = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL");
                $st->execute();
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['latitude'] && $row['longitude']) {
                    return ['lat' => (float)$row['latitude'], 'lng' => (float)$row['longitude']];
                }
            }
        } catch (Throwable $e) {}
        return null;
    }
}

if (!function_exists('delivery_haversine')) {
    /**
     * Calculate Haversine distance between two points in kilometers.
     */
    function delivery_haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371; // Earth radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($R * $c, 2);
    }
}

if (!function_exists('delivery_calculate_fee')) {
    /**
     * Calculate delivery fee based on distance and settings.
     * Returns fee in Rupiah (integer).
     */
    function delivery_calculate_fee(PDO $pdo, float $distanceKm, int $subtotal = 0): int
    {
        $s = delivery_settings($pdo);

        // Free delivery threshold (by subtotal)
        $freeAbove = (int)$s['delivery_free_above'];
        if ($freeAbove > 0 && $subtotal >= $freeAbove) {
            return 0;
        }

        // Free delivery threshold (by distance)
        $freeKmLimit = (float)($s['delivery_free_km_limit'] ?? 2);
        if ($distanceKm <= $freeKmLimit) {
            return 0; // Jarak masih di bawah batas gratis
        }

        $model  = $s['delivery_fee_model'];
        $minFee = (int)$s['delivery_min_fee'];

        if ($model === 'flat') {
            return max($minFee, (int)$s['delivery_flat_fee']);
        }

        // per_km model (hitung selisih jaraknya saja: Opsi A)
        $perKm = (int)$s['delivery_per_km_fee'];
        $excessKm = $distanceKm - $freeKmLimit;
        $fee   = (int)ceil($excessKm * $perKm);
        return max($minFee, $fee);
    }
}

if (!function_exists('delivery_validate_radius')) {
    /**
     * Check if distance is within the maximum delivery radius.
     */
    function delivery_validate_radius(PDO $pdo, float $distanceKm): bool
    {
        $s = delivery_settings($pdo);
        $maxRadius = (float)$s['delivery_max_radius_km'];
        return $distanceKm <= $maxRadius;
    }
}

if (!function_exists('delivery_save_settings')) {
    /**
     * Save delivery settings to system_settings table.
     */
    function delivery_save_settings(PDO $pdo, array $data): void
    {
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
        $keys = [
            'delivery_enabled', 'delivery_max_radius_km', 'delivery_fee_model',
            'delivery_flat_fee', 'delivery_per_km_fee', 'delivery_min_fee', 'delivery_free_above',
            'delivery_free_km_limit', 'delivery_outlet_lat', 'delivery_outlet_lng'
        ];

        // Handle checkbox: if delivery_enabled not present in POST, set to 0
        if (!isset($data['delivery_enabled'])) {
            $data['delivery_enabled'] = '0';
        }

        // Handle coordinate alias support
        if (isset($data['outlet_lat']) && !isset($data['delivery_outlet_lat'])) {
            $data['delivery_outlet_lat'] = $data['outlet_lat'];
        }
        if (isset($data['outlet_lng']) && !isset($data['delivery_outlet_lng'])) {
            $data['delivery_outlet_lng'] = $data['outlet_lng'];
        }

        $st = $pdo->prepare("INSERT INTO system_settings (outlet_id, setting_key, setting_value, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");

        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) {
                $st->execute([$outletId, $k, trim((string)$data[$k])]);
            }
        }

        // Save outlet coordinates to outlets table for this specific outlet only
        if (isset($data['delivery_outlet_lat']) && isset($data['delivery_outlet_lng'])) {
            $lat = (float)$data['delivery_outlet_lat'];
            $lng = (float)$data['delivery_outlet_lng'];
            if ($lat != 0 && $lng != 0) {
                $pdo->prepare("UPDATE outlets SET latitude = ?, longitude = ? WHERE id = ?")->execute([$lat, $lng, $outletId]);
            }
        }
    }
}
