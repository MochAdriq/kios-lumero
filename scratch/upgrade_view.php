<?php
$html = file_get_contents('C:/xampp/htdocs/kios-lumero/temp/laporankeunganupgrade.html');

// Extract first style block
preg_match('/<style>(.*?)<\/style>/is', $html, $matches);
$style = $matches[1] ?? '';

// Extract wrap div
preg_match('/<div class="wrap">(.*?)<\/body>/is', $html, $matchesWrap);
$wrap = $matchesWrap[1] ?? '';
// Remove the very last </div> if it's there
$wrap = preg_replace('/<\/div>\s*$/i', '', trim($wrap));

if (!$wrap) {
    echo "Could not extract wrap.\n";
    exit;
}

// Convert HTML to PHP View
$view = "<?php \$role = Auth::role(); ?>\n";
$view .= "<style>\n" . trim($style) . "\n</style>\n";
$view .= "<div class=\"wrap\">\n" . trim($wrap) . "\n</div>\n";

// Replace hardcoded dates in form
$view = str_replace(
    '<label>Dari Tanggal<input type="date" name="from" value="2026-05-01"></label>',
    '<label>Dari Tanggal<input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>',
    $view
);
$view = str_replace(
    '<label>Sampai Tanggal<input type="date" name="to" value="2026-07-25"></label>',
    '<label>Sampai Tanggal<input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>',
    $view
);
$view = preg_replace('/href="\?from=[^"]*"/', 'href="#"', $view); // Simplified

// We need to properly generate the table body.
// I will just replace the whole tbody with the correct PHP loop, since the raw HTML has 67 static rows!
$tbodyStart = strpos($view, '<tbody>');
$tbodyEnd = strpos($view, '</tbody>') + 8;

if ($tbodyStart !== false && $tbodyEnd !== false) {
    $phpTbody = <<<PHP
<tbody>
    <?php foreach (\$closings as \$i => \$c): 
        \$margin = \$c['total_revenue'] > 0 ? (\$c['net_profit'] / \$c['total_revenue'] * 100) : 0;
        \$hppPercent = \$c['total_revenue'] > 0 ? (\$c['total_hpp'] / \$c['total_revenue'] * 100) : 0;
        \$isProfit = \$c['net_profit'] >= 0;
        \$rowClass = \$c['total_revenue'] == 0 ? 'row-empty' : (\$isProfit ? 'row-profit' : 'row-loss');
        \$statusClass = \$c['total_revenue'] == 0 ? 'status empty' : (\$isProfit ? 'status profit' : 'status loss');
        \$statusText = \$c['total_revenue'] == 0 ? 'Tidak ada aktivitas' : (\$isProfit ? 'Untung' : 'Rugi');
        \$timestamp = strtotime(\$c['business_date']);
        \$hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', \$timestamp)];
    ?>
    <tr class="<?= \$rowClass ?>">
        <td><?= \$i + 1 ?></td>
        <td class="date-cell"><b><?= date('d M Y', \$timestamp) ?></b><span><?= \$hari ?></span></td>
        <td class="num"><?= number_format(\$c['total_transactions']) ?></td>
        <td class="num"><?= rupiah(\$c['total_revenue']) ?></td>
        <td class="num"><?= rupiah(\$c['total_hpp']) ?></td>
        <td class="num"><?= number_format(\$hppPercent, 1, ',', '.') ?>%</td>
        <td class="num"><?= rupiah(\$c['gross_profit']) ?></td>
        <td class="num"><?= rupiah(\$c['total_expense']) ?></td>
        <td class="num <?= \$isProfit ? 'money-profit' : 'money-loss' ?>"><?= rupiah(\$c['net_profit']) ?></td>
        <td class="num"><?= number_format(\$margin, 1, ',', '.') ?>%</td>
        <td><span class="<?= \$statusClass ?>"><?= \$statusText ?></span></td>
    </tr>
    <?php endforeach; ?>
</tbody>
PHP;
    $view = substr_replace($view, $phpTbody, $tbodyStart, $tbodyEnd - $tbodyStart);
}

// Replace Summary Cards
// Pendapatan
$view = preg_replace('/<b[^>]*>Rp68\.359\.500<\/b>/', '<b><?= rupiah($pl[\'revenue\']??0) ?></b>', $view);
// HPP
$view = preg_replace('/<b[^>]*>Rp36\.539\.861<\/b>/', '<b><?= rupiah($pl[\'hpp\']??0) ?></b>', $view);
// Laba Kotor
$view = preg_replace('/<b[^>]*>Rp31\.819\.639<\/b>/', '<b><?= rupiah($pl[\'gross_profit\']??0) ?></b>', $view);
// Pengeluaran (Wait, the raw html has 15.6M now?) I'll just regex the pattern.
$view = preg_replace('/<div class="card red">(.*?)<b[^>]*>Rp.*?<\/b>/s', '<div class="card red">$1<b><?= rupiah($pl[\'expense\']??0) ?></b>', $view);
$view = preg_replace('/<div class="card blue">(.*?)<b[^>]*>Rp.*?<\/b>/s', '<div class="card blue">$1<b><?= rupiah($pl[\'net_profit\']??0) ?></b>', $view);
$view = preg_replace('/<div class="card green">(.*?)<b[^>]*>Rp.*?<\/b>/s', '<div class="card green">$1<b><?= rupiah($pl[\'revenue\']??0) ?></b>', $view);
// Wait, the gross profit is orange probably. Let's just blindly replace the <b> elements inside the cards section.
// Actually, I can just replace the specific static numbers I know are there.
$view = str_replace('Rp68.359.500', '<?= rupiah($pl[\'revenue\']??0) ?>', $view);
$view = str_replace('Rp36.539.861', '<?= rupiah($pl[\'hpp\']??0) ?>', $view);
$view = str_replace('Rp31.819.639', '<?= rupiah($pl[\'gross_profit\']??0) ?>', $view);
$view = str_replace('Rp15.698.000', '<?= rupiah($pl[\'expense\']??0) ?>', $view);
$view = str_replace('Rp16.121.639', '<?= rupiah($pl[\'net_profit\']??0) ?>', $view);

file_put_contents('C:/xampp/htdocs/kios-lumero/views/reports/financial.php', $view);
echo "Upgraded view successfully!\n";
