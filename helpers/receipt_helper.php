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

if (!function_exists('build_rawbt_base64')) {
    function build_rawbt_base64(array $order, array $items, int $width = 32, $storeName = 'Lumero'): string
    {
        $lines = build_receipt_text($order, $items, $width, $storeName);
        $text = implode("\n", $lines);
        
        $esc = "\x1B"; 
        $gs = "\x1D";
        $data = $esc . '@'; // init
        
        // Open drawer if cash
        $isCash = strtolower(trim((string)($order['payment_method'] ?? ''))) === 'cash';
        if ($isCash) {
            $data .= $esc . 'p' . chr(0) . chr(25) . chr(100);
        }
        
        $data .= $esc . 't' . chr(0); // code page
        $data .= $esc . 'a' . chr(1); // center
        $data .= $esc . 'E' . chr(1); // bold on
        
        $first = true;
        foreach ($lines as $line) {
            if ($first) {
                $data .= $line . "\n";
                $first = false;
                continue;
            }
            // Keep it simple: regular text
            $data .= $esc . 'E' . chr(0) . $esc . 'a' . chr(0) . $line . "\n";
        }
        $data .= "\n";
        
        // Cek jika ada loyalty code
        $claimCode = trim((string)($order['loyalty_claim_code'] ?? ''));
        if (empty($order['member_id']) && $claimCode !== '') {
            $data .= $esc . 'a' . chr(1); // center
            $data .= "KODE KLAIM POIN\n";
            $data .= $esc . 'E' . chr(1) . $claimCode . $esc . 'E' . chr(0) . "\n\n";
            
            $qrUrl = url('/user/?claim=' . urlencode($claimCode));
            
            // ESC/POS QR Code
            $qrLen = strlen($qrUrl) + 3;
            $pL = $qrLen % 256;
            $pH = floor($qrLen / 256);
            
            $data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(67) . chr(6); // Size 6
            $data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(69) . chr(48); // Error correction
            $data .= $gs . '(k' . chr($pL) . chr($pH) . chr(49) . chr(80) . chr(48) . $qrUrl; // Store data
            $data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(81) . chr(48); // Print QR
            
            $data .= "\nScan untuk klaim poin Anda\n\n";
            $data .= $esc . 'a' . chr(0); // left
        }

        $data .= "\n\n\n";
        $data .= $gs . 'V' . chr(0); // cut
        
        return base64_encode($data);
    }
}
