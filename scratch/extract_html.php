<?php
$html = file_get_contents('C:\xampp\htdocs\kios-lumero\temp\laporankeunganupgrade.html');
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);
$rows = $xpath->query('//tbody/tr');
foreach ($rows as $row) {
    $cols = $row->getElementsByTagName('td');
    if ($cols->length > 3) {
        $date = trim($cols->item(1)->textContent);
        if (strpos($date, '18 Mei') !== false || strpos($date, '19 Mei') !== false) {
            echo 'Date: ' . $date . ' | Orders: ' . trim($cols->item(2)->textContent) . ' | Omzet: ' . trim($cols->item(3)->textContent) . "\n";
        }
    }
}
