<?php
header('Content-Type: application/json; charset=utf-8');

function bp_text(&$arr, $content, $bold=0, $align=0, $format=0) {
    $arr[] = ['type' => 0, 'content' => $content, 'bold' => $bold, 'align' => $align, 'format' => $format];
}

$a = [];
bp_text($a, '=== THERMER ADVANCED TEST ===', 1, 1, 0);

// Tes 1: QR Type 2 polos (tanpa align)
bp_text($a, '-- QR Type 2 Polos --', 0, 1, 0);
$a[] = ['type' => 2, 'content' => 'https://lokapedia.id'];

// Tes 2: QR Type 2 dengan parameter size
bp_text($a, '-- QR Type 2 (Size 3) --', 0, 1, 0);
$a[] = ['type' => 2, 'content' => 'https://lokapedia.id', 'size' => 3];

// Tes 3: QR Type 2 dengan format parameter string
bp_text($a, '-- QR Type 2 (String Format) --', 0, 1, 0);
$a[] = ['type' => '2', 'content' => 'https://lokapedia.id'];

// Tes 4: Image Tipe 3 (Base64) tanpa atribut tambahan
bp_text($a, '-- Image Type 3 --', 0, 1, 0);
$logoPath = __DIR__ . '/../public/assets/images/pos-products/black-white-logo.jpg';
$base64 = base64_encode(file_get_contents($logoPath));
$a[] = ['type' => 3, 'content' => $base64];

// Tes 5: HTML sederhana tanpa CSS rumit
bp_text($a, '-- HTML Simpel --', 0, 1, 0);
$a[] = ['type' => 4, 'content' => '<img src="data:image/jpeg;base64,'.$base64.'" width="100">'];

bp_text($a, '=== SELESAI ===', 1, 1, 0);
bp_text($a, ' ', 0, 0, 0);

echo json_encode($a, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
