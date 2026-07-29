<?php

if (!function_exists('build_receipt_text')) {
    function build_receipt_text(array $order, array $items, int $width = 32, $storeName = 'Lumero'): array
    {
        $lines = [];
        
        $center = function($txt) use ($width) {
            $txt = (string)$txt;
            $pad = max(0, intdiv($width - strlen($txt), 2));
            return str_repeat(' ', $pad) . $txt;
        };
        
        $pair = function($l, $r) use ($width) {
            $l = (string)$l;
            $r = (string)$r;
            $sp = max(1, $width - strlen($l) - strlen($r));
            return $l . str_repeat(' ', $sp) . $r;
        };

        // Header
        $lines[] = $center(strtoupper($storeName));
        $lines[] = $center('POS KASIR');
        $lines[] = str_repeat('-', $width);
        
        // Order Info
        $lines[] = $pair('Order', $order['order_number'] ?? '-');
        $lines[] = $pair('Tanggal', date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')));
        $lines[] = $pair('Bayar', strtoupper((string)($order['payment_method'] ?? '-')));
        if (!empty($order['customer_name']) || !empty($order['customer_phone'])) {
            $cName = mb_substr($order['customer_name'] ?? $order['customer_phone'], 0, 15);
            $lines[] = $pair('Pelanggan', $cName);
        }
        $lines[] = str_repeat('-', $width);
        
        // Items
        foreach ($items as $it) {
            $name = strtoupper((string)($it['item_name'] ?? 'ITEM'));
            // Split name if too long
            if (strlen($name) > $width) {
                $lines[] = substr($name, 0, $width);
                $name = substr($name, $width);
            }
            $lines[] = substr($name, 0, $width);
            
            $qtyPrice = ((int)($it['quantity'] ?? 1)) . ' x ' . rupiahPlain($it['price'] ?? 0);
            $lines[] = $pair($qtyPrice, rupiahPlain($it['subtotal'] ?? 0));
        }
        
        $lines[] = str_repeat('-', $width);
        
        // Totals
        $lines[] = $pair('Subtotal', rupiahPlain($order['subtotal'] ?? 0));
        
        if ((float)($order['discount_amount'] ?? 0) > 0) {
            $lines[] = $pair('Diskon', rupiahPlain($order['discount_amount']));
        }
        
        $lines[] = $pair('TOTAL', rupiahPlain($order['grand_total'] ?? 0));
        
        if (($order['payment_method'] ?? '') === 'cash') {
            $lines[] = $pair('Dibayar', rupiahPlain($order['paid_amount'] ?? 0));
            $lines[] = $pair('Kembali', rupiahPlain($order['change_amount'] ?? 0));
        }
        
        $lines[] = str_repeat('-', $width);
        
        // Footer
        $lines[] = $center('Terima kasih');
        $lines[] = $center('Selamat menikmati');
        $lines[] = '';
        $lines[] = '';
        
        return $lines;
    }
}
