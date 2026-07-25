<?php
$file = 'C:/xampp/htdocs/kios-lumero/views/reports/financial.php';
$content = file_get_contents($file);

// Replace header text
$content = preg_replace(
    '/<p>D.*?Celup Chicken Crispy.*?01 Mei 2026 s\.d\. 25 Jul 2026(.*?)</',
    '<p><?= $outletName ?? \'Outlet\' ?> &bull; <?= date(\'d M Y\', strtotime($from)) ?> s.d. <?= date(\'d M Y\', strtotime($to)) ?> $1<',
    $content
);

// Replace footer text
$content = preg_replace(
    '/<div>Periode: <b>01 Mei 2026 s\.d\. 25 Jul 2026<\/b>.*?Dicetak 25\/07\/2026 14:02 WIB<\/div>/',
    '<div>Periode: <b><?= date(\'d M Y\', strtotime($from)) ?> s.d. <?= date(\'d M Y\', strtotime($to)) ?></b> &bull; Dicetak <?= date(\'d/m/Y H:i\') ?> WIB</div>',
    $content
);

// Replace badge static counts
$phpBadges = '<span class="badge good">Untung: <?= count(array_filter($closings, fn($c) => $c[\'net_profit\'] > 0)) ?> hari</span>
                    <span class="badge bad">Rugi: <?= count(array_filter($closings, fn($c) => $c[\'net_profit\'] < 0)) ?> hari</span>
                    <span class="badge">Impas: <?= count(array_filter($closings, fn($c) => $c[\'net_profit\'] == 0)) ?> hari</span>';
$content = preg_replace('/<span class="badge good">.*?<\/span>\s*<span class="badge bad">.*?<\/span>\s*<span class="badge">.*?<\/span>/is', $phpBadges, $content);

file_put_contents($file, $content);
echo "Fixed hardcoded strings.\n";
