<?php

if (!function_exists('sim_pos_img_asset')) {
    function sim_pos_img_asset(string $name): string { return asset('images/pos-products/' . ltrim($name, '/')); }
}
if (!function_exists('sim_contains_any')) {
    function sim_contains_any(string $haystack, array $needles): bool { foreach ($needles as $needle) if (strpos($haystack, $needle) !== false) return true; return false; }
}
if (!function_exists('sim_pos_category_image')) {
    function sim_pos_category_image(array $cat): string {
        $n = strtolower($cat['name'] ?? '');
        if (sim_contains_any($n, ['ayam', 'chicken', 'crispy'])) return sim_pos_img_asset('original.png');
        if (sim_contains_any($n, ['kentang', 'potato'])) return sim_pos_img_asset('kentang-kriwil.png');
        if (sim_contains_any($n, ['matcha', 'minuman', 'drink'])) return sim_pos_img_asset('matcha.png');
        if (sim_contains_any($n, ['kopi', 'coffee'])) return sim_pos_img_asset('kopi.png');
        if (sim_contains_any($n, ['saus', 'sauce', 'celup'])) return sim_pos_img_asset('celup-saus.png');
        if (sim_contains_any($n, ['nasi', 'rice'])) return sim_pos_img_asset('nasi.png');
        return sim_pos_img_asset('product-dummy.svg');
    }
}
if (!function_exists('sim_pos_product_image')) {
    function sim_pos_product_image(array $item, array $cat): string {
        if (!empty($item['image'])) {
            $img = trim((string)$item['image']);
            if (preg_match('#^https?://#', $img)) return $img;
            if (strpos($img, '/') === 0) return $img;
            return asset(ltrim($img, '/'));
        }
        $text = strtolower(($cat['name'] ?? '') . ' ' . ($item['product_name'] ?? '') . ' ' . ($item['variant_name'] ?? ''));
        if (strpos($text, 'paha bawah') !== false) return sim_pos_img_asset('paha-bawah.png');
        if (strpos($text, 'paha atas') !== false) return sim_pos_img_asset('paha-atas.png');
        if (strpos($text, 'sayap') !== false) return sim_pos_img_asset('sayap.png');
        if (strpos($text, 'dada') !== false) return sim_pos_img_asset('dada.png');
        if (sim_contains_any($text, ['kentang kriwil', 'kriwil'])) return sim_pos_img_asset('kentang-kriwil.png');
        if (strpos($text, 'kentang') !== false) return sim_pos_img_asset('kentang-lumero.png');
        if (strpos($text, 'taro') !== false) return sim_pos_img_asset('matcha/taro.png');
        if (sim_contains_any($text, ['coklat', 'choco', 'cocolate'])) return sim_pos_img_asset('matcha/choco.png');
        if (strpos($text, 'matcha') !== false || strpos($text, 'latte') !== false) return sim_pos_img_asset('matcha/latte.png');
        if (strpos($text, 'kopi') !== false || strpos($text, 'coffee') !== false) return sim_pos_img_asset('kopi.png');
        if (strpos($text, 'nasi') !== false) return sim_pos_img_asset('nasi.png');
        if (strpos($text, 'tanpa nasi') !== false) return sim_pos_img_asset('tanpa-nasi.png');
        if (strpos($text, 'teriyaki') !== false) return sim_pos_img_asset('sauces/teriyaki.png');
        if (strpos($text, 'bbq') !== false) return sim_pos_img_asset('sauces/bbq.png');
        if (sim_contains_any($text, ['lada hitam', 'blackpepper', 'black pepper'])) return sim_pos_img_asset('sauces/blackpepper.png');
        if (sim_contains_any($text, ['garlic', 'bawang'])) return sim_pos_img_asset('sauces/default.png');
        if (sim_contains_any($text, ['sadis', 'geprek', 'pedas', 'spicy'])) return sim_pos_img_asset('sauces/pedas.png');
        if (strpos($text, 'keju') !== false) return sim_pos_img_asset('sauces/keju.png');
        if (sim_contains_any($text, ['mentai', 'mayo'])) return sim_pos_img_asset('sauces/mayo.png');
        if (sim_contains_any($text, ['saus', 'celup'])) return sim_pos_img_asset('celup-saus.png');
        if (sim_contains_any($text, ['ayam', 'crispy', 'original'])) return sim_pos_img_asset('original.png');
        return sim_pos_img_asset('product-dummy.svg');
    }
}

if (!function_exists('sim_pos_prepare_data')) {
    function sim_pos_prepare_data(array $categories): array {
        $preparedCategories = [];
        $totalVariants = 0;
        foreach ($categories as $cat) {
            $items = [];
            foreach (($cat['items'] ?? []) as $item) {
                $vName = (string)($item['variant_name'] ?? '');
                $pName = (string)($item['product_name'] ?? '');
                $isDefault = strcasecmp($vName, 'Default') === 0;
                $displayName = trim($isDefault ? $pName : ($vName ?: $pName));
                $fullName = trim($pName . ' ' . ($isDefault ? '' : $vName));
                $items[] = [
                    'variant_id' => (int)$item['variant_id'],
                    'sku' => (string)($item['sku'] ?? ''),
                    'product_name' => $pName,
                    'variant_name' => $vName,
                    'name' => $displayName,
                    'full_name' => $fullName,
                    'price' => (float)($item['price'] ?? 0),
                    'hpp' => (float)($item['hpp'] ?? 0),
                    'image' => sim_pos_product_image($item, $cat),
                    'ready_stock' => (float)($item['ready_stock'] ?? 0),
                ];
                $totalVariants++;
            }
            $preparedCategories[] = [
                'id' => (int)$cat['id'],
                'name' => (string)$cat['name'],
                'slug' => (string)($cat['slug'] ?? ''),
                'image' => sim_pos_category_image($cat),
                'items' => $items,
            ];
        }
        $posAssets = [
            'dummy' => sim_pos_img_asset('product-dummy.svg'),
            'original' => sim_pos_img_asset('original.png'),
            'dada' => sim_pos_img_asset('dada.png'),
            'paha_atas' => sim_pos_img_asset('paha-atas.png'),
            'paha_bawah' => sim_pos_img_asset('paha-bawah.png'),
            'sayap' => sim_pos_img_asset('sayap.png'),
            'sauce' => sim_pos_img_asset('celup-saus.png'),
            'rice_yes' => sim_pos_img_asset('nasi.png'),
            'rice_no' => sim_pos_img_asset('tanpa-nasi.png'),
            'kentang' => sim_pos_img_asset('kentang-kriwil.png'),
            'matcha' => sim_pos_img_asset('matcha.png'),
            'kopi' => sim_pos_img_asset('kopi.png'),
            'keju' => sim_pos_img_asset('sauces/keju.png'),
            'sadis' => sim_pos_img_asset('sauces/pedas.png'),
            'teriyaki' => sim_pos_img_asset('sauces/teriyaki.png'),
            'bbq' => sim_pos_img_asset('sauces/bbq.png'),
            'lada_hitam' => sim_pos_img_asset('sauces/blackpepper.png'),
            'garlic' => sim_pos_img_asset('sauces/default.png'),
            'mentai' => sim_pos_img_asset('sauces/mayo.png'),
        ];
        return [
            'categories' => $preparedCategories,
            'assets' => $posAssets,
            'total_variants' => $totalVariants
        ];
    }
}
