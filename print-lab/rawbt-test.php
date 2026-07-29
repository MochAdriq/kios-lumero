<?php
header('Access-Control-Allow-Origin: *');

// Perintah dasar ESC/POS untuk RawBT
$esc = "\x1B";
$gs = "\x1D";

$data = $esc . '@'; // Initialize printer
$data .= $esc . 'a' . chr(1); // Center align

// Header Text
$data .= $esc . 'E' . chr(1); // Bold on
$data .= "=== TEST RAWBT ===\n";
$data .= $esc . 'E' . chr(0); // Bold off
$data .= "Berhasil mencetak via RawBT!\n\n";

// Test QR Code
$qrData = "https://lokapedia.id";
$qrLen = strlen($qrData) + 3;
$pL = $qrLen % 256;
$pH = floor($qrLen / 256);

$data .= "--- TES QR CODE ---\n";
// Set QR Size (1-16)
$data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(67) . chr(6); // Size 6
// Set Error Correction Level
$data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(69) . chr(48);
// Store Data
$data .= $gs . '(k' . chr($pL) . chr($pH) . chr(49) . chr(80) . chr(48) . $qrData;
// Print QR
$data .= $gs . '(k' . chr(3) . chr(0) . chr(49) . chr(81) . chr(48);

$data .= "\n\n";
$data .= "Test Selesai.\n\n\n";
$data .= $gs . 'V' . chr(0); // Cut paper

echo base64_encode($data);
