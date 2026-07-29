<?php
$envFile = __DIR__ . '/../.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$token = '';
foreach ($lines as $line) {
    if (strpos(trim($line), 'WA_GATEWAY_TOKEN') === 0) {
        $token = trim(explode('=', $line, 2)[1]);
        break;
    }
}
echo "Using Token: $token\n";

$ch = curl_init('https://api.fonnte.com/send');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'target' => '0895338977816',
    'message' => 'Test Ping Fonnte TERBARU pada ' . date('H:i:s d-m-Y') . '. Apakah sudah masuk Boss?'
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $token
]);
$res = curl_exec($ch);
echo "Response Fonnte API: \n" . $res . "\n";
