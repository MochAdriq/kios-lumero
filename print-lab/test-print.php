<?php
header('Content-Type: application/json; charset=utf-8');

// Fungsi Pembantu
function bp_text(&$arr, $content, $bold=0, $align=0, $format=0) {
    $arr[] = ['type' => 0, 'content' => $content, 'bold' => $bold, 'align' => $align, 'format' => $format];
}

function bp_qr_type2(&$arr, $content) {
    // Tipe 2: Native QR Code Thermer
    $arr[] = ['type' => 2, 'content' => $content, 'align' => 1];
}

function bp_qr_type5(&$arr, $content) {
    // Tipe 5: Barcode/QR (Beberapa app mendukung type 5 sebagai QR)
    $arr[] = ['type' => 5, 'content' => $content, 'align' => 1];
}

function bp_html(&$arr, $html) {
    $arr[] = ['type' => 4, 'content' => $html];
}

function bp_image_html(&$arr, $base64) {
    // Tipe 4 HTML Base64
    $html = '<div style="text-align:center;"><img src="data:image/jpeg;base64,' . $base64 . '" style="width:160px; height:160px; object-fit:contain;" /></div>';
    bp_html($arr, $html);
}

function bp_image_type3(&$arr, $base64) {
    // Tipe 3: Native Image Thermer (Base64 raw string)
    $arr[] = ['type' => 3, 'content' => $base64, 'align' => 1];
}


$a = [];

bp_text($a, '=== TEST PRINTER ===', 1, 1, 0);
bp_text($a, 'Ini adalah file pengetesan cetak.', 0, 1, 0);

// 1. Uji Coba Logo menggunakan tipe HTML (4)
$logoPath = __DIR__ . '/../public/assets/images/pos-products/black-white-logo.jpg';
$base64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';

bp_text($a, '--- LOGO VIA HTML ---', 1, 1, 0);
if ($base64) {
    bp_image_html($a, $base64);
} else {
    bp_text($a, '(File logo tidak ditemukan)', 0, 1, 0);
}

// 2. Uji Coba Logo menggunakan tipe Image (3)
bp_text($a, '--- LOGO VIA NATIVE (Tipe 3) ---', 1, 1, 0);
if ($base64) {
    bp_image_type3($a, $base64);
} else {
    bp_text($a, '(File logo tidak ditemukan)', 0, 1, 0);
}

// 3. Uji Coba QR Code menggunakan tipe Native (2)
bp_text($a, '--- QR VIA NATIVE (Tipe 2) ---', 1, 1, 0);
bp_qr_type2($a, 'https://lokapedia.id');

// 4. Uji Coba QR Code menggunakan tipe Barcode (5)
bp_text($a, '--- QR VIA BARCODE (Tipe 5) ---', 1, 1, 0);
bp_qr_type5($a, 'https://lokapedia.id');

// 5. Uji Coba QR Code menggunakan Google Chart API (HTML)
bp_text($a, '--- QR VIA HTML IMG ---', 1, 1, 0);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://lokapedia.id';
$htmlQr = '<div style="text-align:center;"><img src="'.$qrUrl.'" width="150" height="150" /></div>';
bp_html($a, $htmlQr);

bp_text($a, '=== SELESAI ===', 1, 1, 0);
bp_text($a, ' ', 0, 0, 0); // Spasi bawah

echo json_encode($a, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
