<?php
$file = 'C:/xampp/htdocs/kios-lumero/views/reports/financial.php';
$content = file_get_contents($file);
$tfootStart = strpos($content, '<tfoot>');
$tfootEnd = strpos($content, '</tfoot>');
if ($tfootStart !== false && $tfootEnd !== false) {
    $phpTfoot = '<tfoot>
        <tr>
            <td colspan="2">TOTAL PERIODE</td>
            <td class="num"><?= number_format(array_sum(array_column($closings, "total_transactions"))) ?></td>
            <td class="num"><?= rupiah($pl["revenue"]??0) ?></td>
            <td class="num"><?= rupiah($pl["hpp"]??0) ?></td>
            <td class="num"><?= ($pl["revenue"] > 0) ? number_format(($pl["hpp"]/$pl["revenue"]*100),1,",",".") : 0 ?>%</td>
            <td class="num"><?= rupiah($pl["gross_profit"]??0) ?></td>
            <td class="num"><?= rupiah($pl["expense"]??0) ?></td>
            <td class="num"><?= rupiah($pl["net_profit"]??0) ?></td>
            <td class="num"><?= ($pl["revenue"] > 0) ? number_format(($pl["net_profit"]/$pl["revenue"]*100),1,",",".") : 0 ?>%</td>
            <td><?= ($pl["net_profit"] >= 0) ? "UNTUNG" : "RUGI" ?></td>
        </tr>';
    $content = substr_replace($content, $phpTfoot, $tfootStart, $tfootEnd - $tfootStart);
    file_put_contents($file, $content);
    echo "Tfoot replaced.\n";
} else {
    echo "Tfoot not found.\n";
}
