<?php
$file = 'C:/xampp/htdocs/kios-lumero/views/reports/financial.php';
$content = file_get_contents($file);

// Remove the top header card entirely
$content = preg_replace('/<header class="top">.*?<\/header>/is', '', $content);

// Replace footer text to be dynamic
$content = preg_replace(
    '/<div>Periode: <b>01 Mei 2026 s\.d\. 25 Jul 2026<\/b>.*?Dicetak 25\/07\/2026 14:02 WIB<\/div>/',
    '<div>Periode: <b><?= date(\'d M Y\', strtotime($from)) ?> s.d. <?= date(\'d M Y\', strtotime($to)) ?></b> &bull; Dicetak <?= date(\'d/m/Y H:i\') ?> WIB</div>',
    $content
);

file_put_contents($file, $content);
echo "Header removed and footer fixed.\n";
