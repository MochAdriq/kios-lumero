<?php
$ch = curl_init('http://localhost/kios-lumero/api/print/rawbt.php?id=10');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "RAWBT:\n$res\n\n";

$ch2 = curl_init('http://localhost/kios-lumero/api/print/btapp-json.php?id=10');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$res2 = curl_exec($ch2);
echo "BTAPP:\n$res2\n";
