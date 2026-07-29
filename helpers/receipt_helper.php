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
            $pName = trim((string)($it['product_name_snapshot'] ?? ''));
            $vName = trim((string)($it['variant_name_snapshot'] ?? ''));
            $name = $pName;
            if ($vName !== '' && strtolower($vName) !== 'default' && $vName !== $pName) {
                $name .= ' - ' . $vName;
            }
            if ($name === '') $name = $vName ?: 'ITEM';
            $name = strtoupper($name);
            
            // Split name if too long
            if (strlen($name) > $width) {
                $lines[] = substr($name, 0, $width);
                $name = substr($name, $width);
            }
            $lines[] = substr($name, 0, $width);
            
            $qtyPrice = ((int)($it['qty'] ?? $it['quantity'] ?? 1)) . ' x ' . rupiahPlain($it['selling_price'] ?? $it['price'] ?? 0);
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
        
        // Print Logo at the top if exists
        $logoPath = __DIR__ . '/../public/assets/images/pos-products/black-white-logo.jpg';
        $logoData = build_escpos_image($logoPath);
        if ($logoData !== '') {
            $data .= $esc . 'a' . chr(1); // center
            $data .= $logoData;
        }
        
        // Open drawer if cash
        $isCash = strtolower(trim((string)($order['payment_method'] ?? ''))) === 'cash';
        if ($isCash) {
            $data .= $esc . 'p' . chr(0) . chr(25) . chr(100);
        }
        
        $data .= $esc . 't' . chr(0); // code page
        $data .= $esc . 'E' . chr(0); // bold off
        $data .= $esc . 'a' . chr(0); // left align
        
        foreach ($lines as $idx => $line) {
            if ($idx === 0) {
                // First line (Store Name) make it bold
                $data .= $esc . 'E' . chr(1) . $line . $esc . 'E' . chr(0) . "\n";
            } else {
                $data .= $line . "\n";
            }
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
            
            $data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(67) . chr(4); // Size 4
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

if (!function_exists('build_escpos_image')) {
    function build_escpos_image(string $imagePath): string
    {
        if (!file_exists($imagePath)) return '';
        
        $img = @imagecreatefromjpeg($imagePath);
        if (!$img) $img = @imagecreatefrompng($imagePath);
        if (!$img) return '';
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        // Ensure width is a multiple of 8
        $widthBytes = (int)ceil($width / 8.0);
        $paddedWidth = $widthBytes * 8;
        
        // ESC/POS raster image command
        $gs = "\x1D";
        $data = $gs . "v0" . chr(0); // m=0
        
        // Width in bytes
        $xL = $widthBytes % 256;
        $xH = floor($widthBytes / 256);
        $data .= chr($xL) . chr($xH);
        
        // Height in pixels
        $yL = $height % 256;
        $yH = floor($height / 256);
        $data .= chr($yL) . chr($yH);
        
        // Raster data
        for ($y = 0; $y < $height; $y++) {
            for ($xByte = 0; $xByte < $widthBytes; $xByte++) {
                $byte = 0;
                for ($b = 0; $b < 8; $b++) {
                    $x = $xByte * 8 + $b;
                    $bit = 0;
                    if ($x < $width) {
                        $rgb = imagecolorat($img, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b_col = $rgb & 0xFF;
                        $luminance = ($r * 0.299) + ($g * 0.587) + ($b_col * 0.114);
                        // If pixel is dark, set bit to 1 (print dot)
                        if ($luminance < 128) {
                            $bit = 1;
                        }
                    }
                    $byte = ($byte << 1) | $bit;
                }
                $data .= chr($byte);
            }
        }
        
        imagedestroy($img);
        
        // Add some spacing
        $data .= "\n\n";
        return $data;
    }
}

