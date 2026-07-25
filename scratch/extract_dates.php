<?php
$html = file_get_contents('C:\xampp\htdocs\kios-lumero\temp\laporankeunganupgrade.html');
preg_match_all('/<td>(\d{2} [A-Za-z]+ 2026)<\/td>/', $html, $matches);
if (!empty($matches[1])) {
    echo "First 5 dates in HTML:\n";
    for($i=0; $i<min(5, count($matches[1])); $i++){
        echo $matches[1][$i] . "\n";
    }
} else {
    echo "No match.\n";
}
