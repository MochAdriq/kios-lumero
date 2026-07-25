<?php
echo "Membaca SQL dump...\n";
$sql = file_get_contents('C:/Users/HYPE R Series/Downloads/u643003184_newcelup (5).sql');
echo "Mengganti collation...\n";
$sql = str_replace('utf8mb4_uca1400_ai_ci', 'utf8mb4_unicode_ci', $sql);
$sql = str_replace('COLLATE utf8mb4_uca1400_ai_ci', '', $sql);
echo "Menyimpan file fixed...\n";
file_put_contents('C:/Users/HYPE R Series/Downloads/dcelup_fixed.sql', $sql);
echo "DONE — file: C:/Users/HYPE R Series/Downloads/dcelup_fixed.sql\n";
echo "Size: " . round(filesize('C:/Users/HYPE R Series/Downloads/dcelup_fixed.sql') / 1024 / 1024, 2) . " MB\n";
