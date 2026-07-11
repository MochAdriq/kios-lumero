<?php
/** Print-friendly ROI Report */
$_e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$_m = fn($n) => 'Rp ' . number_format((int)round($n), 0, ',', '.');
$_p = fn($n) => number_format((float)$n, 2, ',', '.') . '%';
$_n = fn($n, $d = 0) => number_format((float)$n, $d, ',', '.');
$_months = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$_did = function($date) use ($_months) { $ts = strtotime($date); return $ts ? date('d',$ts).' '.($_months[date('m',$ts)] ?? date('m',$ts)).' '.date('Y',$ts) : $date; };
extract($kpi);
$store = current_outlet_name();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Print ROI - <?= $_e($store) ?></title>
<style>
body{font-family:Arial,sans-serif;color:#111;margin:24px}
h1{margin:0 0 4px;font-size:22px}
p{margin:2px 0 12px}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:16px 0}
.card{border:1px solid #ddd;border-radius:14px;padding:12px}
.card small{display:block;color:#666;font-weight:bold;text-transform:uppercase;font-size:11px}
.card b{display:block;font-size:18px;margin-top:6px}
table{width:100%;border-collapse:collapse;font-size:12px;margin-top:10px}
th,td{border:1px solid #ddd;padding:8px;text-align:left;vertical-align:top}
th{background:#f5f5f5}
.num{text-align:right;white-space:nowrap}
.ok{color:#067a28}.bad{color:#b00020}
@media print{button{display:none}body{margin:10mm}.grid{break-inside:avoid}}
</style></head><body>
<button onclick="window.print()">Print</button>
<h1>Laporan Modal, ROI & BEP</h1>
<p><b><?= $_e($store) ?></b> • Periode: <?= $_did($from) ?> - <?= $_did($to) ?> • Dicetak <?= date('d/m/Y H:i') ?> WIB</p>
<div class="grid">
  <div class="card"><small>Total Modal Aktif</small><b><?= $_m($activeCapital) ?></b></div>
  <div class="card"><small>Laba Bersih Kumulatif</small><b class="<?= $cumNet >= 0 ? 'ok' : 'bad' ?>"><?= $_m($cumNet) ?></b></div>
  <div class="card"><small>ROI Saat Ini</small><b><?= $_p($roiPct) ?></b></div>
  <div class="card"><small>Sisa Menuju BEP</small><b><?= $_m($remaining) ?></b></div>
  <div class="card"><small>Omzet Periode</small><b><?= $_m($period['gross_sales']) ?></b></div>
  <div class="card"><small>Laba Bersih Periode</small><b><?= $_m($period['net_profit']) ?></b></div>
  <div class="card"><small>Avg Laba/Hari</small><b><?= $_m($avgNetActive) ?></b></div>
  <div class="card"><small>Estimasi BEP</small><b><?= $remaining <= 0 ? 'Sudah BEP' : ($avgNetActive > 0 ? $_n($daysToBep) . ' hari' : 'Belum bisa') ?></b></div>
  <div class="card"><small>Target Omzet/Hari 6bln</small><b><?= $remaining <= 0 ? 'Sudah ROI' : ($targetSalesDaily6m > 0 ? $_m($targetSalesDaily6m) : '-') ?></b></div>
  <div class="card"><small>Health Score</small><b><?= $healthScore ?>/100</b></div>
</div>
<p><b>Proyeksi omzet/bulan:</b> <?= $_m($avgSalesActive * $workingDays) ?>. <b>Proyeksi laba/bulan:</b> <?= $_m($avgNetActive * $workingDays) ?>.</p>
<h2>Rincian Komponen Modal</h2>
<table><thead><tr><th>Tanggal</th><th>Kategori</th><th>Komponen</th><th>Deskripsi</th><th class="num">Nominal</th></tr></thead>
<tbody><?php foreach ($capitals as $c): if ((int)($c['is_active'] ?? 1) !== 1) continue; ?><tr><td><?= $_did($c['capital_date']) ?></td><td><?= $_e($c['category'] ?? $c['capital_type'] ?? '') ?></td><td><b><?= $_e($c['component_name'] ?? '') ?></b></td><td><?= $_e($c['description'] ?? '') ?></td><td class="num"><?= $_m($c['amount']) ?></td></tr><?php endforeach; ?></tbody>
<tfoot><tr><th colspan="4" class="num">Total Modal Aktif</th><th class="num"><?= $_m($activeCapital) ?></th></tr></tfoot></table>
<script>setTimeout(function(){ window.print(); }, 400);</script></body></html>
