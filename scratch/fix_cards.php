<?php
$file = 'C:/xampp/htdocs/kios-lumero/views/reports/financial.php';
$content = file_get_contents($file);

$start = strpos($content, '<section class="cards">');
$end = strpos($content, '</section>', $start) + 10;

$cards = <<<PHP
<section class="cards">
    <?php
        \$totalDays = count(\$closings);
        \$activeDays = count(array_filter(\$closings, fn(\$c) => \$c['total_revenue'] > 0));
        \$totalOrders = array_sum(array_column(\$closings, "total_transactions"));
        \$avgProfit = \$activeDays > 0 ? (\$pl['net_profit']??0) / \$activeDays : 0;
        \$hppRatio = (\$pl['revenue'] > 0) ? number_format(((\$pl['hpp']??0)/\$pl['revenue']*100), 1, ',', '.') : 0;
        \$marginRatio = (\$pl['revenue'] > 0) ? number_format(((\$pl['net_profit']??0)/\$pl['revenue']*100), 1, ',', '.') : 0;
    ?>
    <div class="card green"><small>Total Omzet</small><b><?= rupiah(\$pl['revenue']??0) ?></b>
        <div class="sub"><?= number_format(\$totalOrders) ?> order paid</div>
    </div>
    <div class="card"><small>Total HPP</small><b><?= rupiah(\$pl['hpp']??0) ?></b>
        <div class="sub">Rasio HPP <?= \$hppRatio ?>%</div>
    </div>
    <div class="card blue"><small>Laba Kotor</small><b><?= rupiah(\$pl['gross_profit']??0) ?></b>
        <div class="sub">Omzet dikurangi HPP</div>
    </div>
    <div class="card red"><small>Total Pengeluaran</small><b><?= rupiah(\$pl['expense']??0) ?></b>
        <div class="sub">Pengeluaran periode</div>
    </div>
    <div class="card green"><small>Laba/Rugi Bersih</small><b><?= rupiah(\$pl['net_profit']??0) ?></b>
        <div class="sub">Margin bersih <?= \$marginRatio ?>%</div>
    </div>
    <div class="card"><small>Rata-rata Laba/Hari Aktif</small><b><?= rupiah(\$avgProfit) ?></b>
        <div class="sub"><?= \$activeDays ?> hari aktif dari <?= \$totalDays ?> hari</div>
    </div>
</section>
PHP;

if ($start !== false) {
    $content = substr_replace($content, $cards, $start, $end - $start);
    file_put_contents($file, $content);
    echo "Cards fixed.\n";
} else {
    echo "Section not found.\n";
}
