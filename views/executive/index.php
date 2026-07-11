<?php
/** * @var array $kpi @var array $capitals @var array $capitalByCat @var array $topProducts 
 * @var array $menuMatrix @var array $inventoryDecision @var array $experiments 
 * @var array $targets @var array $chartRows @var float $chartMax @var ?array $edit 
 * @var array $trendRows 
 */
include __DIR__ . '/../shared-flash.php';

// View helpers (scoped to this file only)
$_e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$_m = fn($n) => rupiah((int)round($n));
$_p = fn($n) => number_format((float)$n, 2, ',', '.') . '%';
$_n = fn($n, $d = 0) => number_format((float)$n, $d, ',', '.');
$_months = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$_did = function($date) use ($_months) { 
    $ts = strtotime($date); 
    return $ts ? date('d',$ts).' '.($_months[date('m',$ts)] ?? date('m',$ts)).' '.date('Y',$ts) : $date; 
};

// Unpack KPIs for convenience
extract($kpi);
$store = current_outlet_name();
?>

<link rel="stylesheet" href="<?= asset('css/executive.css') ?>?v=001">

<div class="executive-dashboard-wrapper">
    <header class="ex-hero mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Business Navigator</h1>
            <p class="mb-0 text-muted">
                <?= $_e($store) ?> — ROI, BEP, cash allocation, growth forecast, trend menu, dan rekomendasi keputusan bisnis.
            </p>
        </div>
        <div class="ex-hero-actions">
            <a class="btn btn-warning fw-bold shadow-sm" href="<?= url('/executive/print?from=' . $_e($from) . '&to=' . $_e($to)) ?>" target="_blank">
                Print Laporan
            </a>
        </div>
    </header>

    <section class="ex-filter-section mb-4">
        <form class="ex-form d-flex gap-3 align-items-end" method="get" action="<?= url('/executive') ?>">
            <div class="form-group" style="flex: 0 0 160px">
                <label class="d-block mb-1">Dari</label>
                <input type="date" name="from" class="form-control" value="<?= $_e($from) ?>">
            </div>
            <div class="form-group" style="flex: 0 0 160px">
                <label class="d-block mb-1">Sampai</label>
                <input type="date" name="to" class="form-control" value="<?= $_e($to) ?>">
            </div>
            <div class="form-group d-flex gap-2">
                <button class="btn btn-danger rounded-pill fw-bold" type="submit">Hitung Periode</button>
                <a class="btn btn-outline-secondary rounded-pill" href="<?= url('/executive?from=' . date('Y-m-01') . '&to=' . $today) ?>">Bulan Ini</a>
                <a class="btn btn-outline-secondary rounded-pill" href="<?= url('/executive?from=' . $today . '&to=' . $today) ?>">Hari Ini</a>
            </div>
        </form>
    </section>

    <section class="ex-kpi-grid mb-4">
        <article class="ex-card gold">
            <small>Total Modal Aktif</small>
            <b><?= $_m($activeCapital) ?></b>
            <div class="ex-hint">Investasi yang harus kembali.</div>
        </article>
        
        <article class="ex-card <?= $cumNet >= 0 ? 'green' : 'red' ?>">
            <small>Laba Bersih Kumulatif</small>
            <b><?= $_m($cumNet) ?></b>
            <div class="ex-hint">Sejak <?= $_did($startDate) ?>.</div>
        </article>
        
        <article class="ex-card blue">
            <small>ROI Aktual</small>
            <b><?= $_p($roiPct) ?></b>
            <div class="ex-progress"><span style="width:<?= min(100, max(0, $roiPct)) ?>%"></span></div>
        </article>
        
        <article class="ex-card <?= $remaining <= 0 ? 'green' : 'red' ?>">
            <small>Sisa Menuju ROI/BEP</small>
            <b><?= $_m($remaining) ?></b>
            <div class="ex-hint">
                <?= $remaining <= 0 ? 'Sudah balik modal. Profit setelah BEP: ' . $_m($profitAfterBep) : 'Butuh laba bersih tambahan.' ?>
            </div>
        </article>
        
        <article class="ex-card">
            <small>BEP Operasional Harian</small>
            <b><?= $_m($dailyOperationalBep) ?></b>
            <div class="ex-hint">Omzet minimum menutup biaya operasional.</div>
        </article>
        
        <article class="ex-card <?= $todayOperationalProfit >= 0 ? 'green' : 'red' ?>">
            <small>Laba Bersih Hari Ini</small>
            <b><?= $_m($todayOperationalProfit) ?></b>
            <div class="ex-hint">Omzet - HPP - Pengeluaran.</div>
        </article>
        
        <article class="ex-card blue">
            <small>Estimasi BEP Investasi</small>
            <b><?= $remaining <= 0 ? 'Sudah BEP' : ($avgNetActive > 0 ? $_n($daysToBep) . ' hari' : 'Belum bisa') ?></b>
            <div class="ex-hint">
                <?= $bepDate ? 'Perkiraan: ' . $_did($bepDate) : 'Laba bersih aktif masih 0/minus.' ?>
            </div>
        </article>
        
        <article class="ex-card gold">
            <small>Target Omzet/Hari ROI 6 Bulan</small>
            <b><?= $remaining <= 0 ? 'Sudah ROI' : ($targetSalesDaily6m > 0 ? $_m($targetSalesDaily6m) : 'Belum bisa') ?></b>
            <div class="ex-hint">
                Estimasi order: <?= $targetOrdersDaily6m > 0 ? $_n($targetOrdersDaily6m, 1) . ' /hari' : '-' ?>
            </div>
        </article>
    </section>

    <section class="ex-panel mb-4">
        <div class="ex-section-title d-flex justify-content-between">
            <div>
                <h2>Business Command Today</h2>
                <div class="ex-hint">Ringkasan tindakan paling penting untuk owner hari ini.</div>
            </div>
            <span class="ex-badge <?= $healthScore >= 70 ? 'ex-badge-green' : ($healthScore < 45 ? 'ex-badge-red' : 'ex-badge-blue') ?>">
                Health Score <?= $healthScore ?>/100
            </span>
        </div>
        
        <?php foreach ($commands as $i => $cmd): ?>
            <div class="ex-command">
                <span><?= $i + 1 ?></span>
                <div><?= $_e($cmd) ?></div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="ex-grid-nav mb-4">
        <article class="ex-decision <?= $decisionClass ?> p-4 rounded">
            <h2><?= $_e($decisionTitle) ?></h2>
            <p><?= $_e($decisionText) ?></p>
            <div class="d-flex gap-3 align-items-start mt-3">
                <div class="ex-score-ring" style="--score:<?= $healthScore ?>">
                    <div><?= $healthScore ?></div>
                </div>
                <div>
                    <div class="ex-tip text-white border-light bg-white bg-opacity-10 mb-2">
                        <span>↗</span>
                        <div>Status: <b><?= $_e($expansionStatus) ?></b></div>
                    </div>
                    <div class="ex-tip text-white border-light bg-white bg-opacity-10">
                        <span>★</span>
                        <div>Trend score: <b><?= $_n($trendScore, 0) ?>/100</b></div>
                    </div>
                </div>
            </div>
        </article>

        <article class="ex-panel">
            <h2>Alokasi Uang Hari Ini</h2>
            <div class="ex-cash-grid mb-2">
                <div class="ex-cashbox"><small>Omzet</small><b><?= $_m($todayFin['gross_sales']) ?></b></div>
                <div class="ex-cashbox"><small>Simpan HPP</small><b><?= $_m($cashHpp) ?></b></div>
                <div class="ex-cashbox"><small>Operasional</small><b><?= $_m($cashOp) ?></b></div>
                <div class="ex-cashbox"><small>Cadangan+ROI</small><b><?= $_m($cashReserve + $cashRoi) ?></b></div>
                <div class="ex-cashbox"><small>Aman Ditarik</small><b><?= $_m($safeDraw) ?></b></div>
            </div>
            <div class="ex-hint text-danger">
                * Jangan pakai uang HPP untuk kebutuhan lain. Uang aman ditarik hanya setelah HPP, operasional, cadangan, dan setoran ROI dipisahkan.
            </div>
        </article>
    </section>

    <section class="ex-grid-3 mb-4">
        <article class="ex-panel">
            <h2>Momentum Pertumbuhan</h2>
            <div class="ex-cash-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="ex-cashbox"><small>Growth 7 Hari</small><b><?= $_p($growth7) ?></b></div>
                <div class="ex-cashbox"><small>Growth 3 Hari</small><b><?= $_p($growth3) ?></b></div>
                <div class="ex-cashbox"><small>Momentum</small><b><?= $_n($momentumScore, 0) ?>/100</b></div>
                <div class="ex-cashbox"><small>Trend Boost</small><b><?= $_p($trendBoost * 100) ?></b></div>
            </div>
        </article>
        
        <article class="ex-panel">
            <h2>Navigator Risiko</h2>
            <div class="ex-risk-item"><span>Rasio HPP</span><b><?= $_p($hppRatio) ?></b></div>
            <div class="ex-risk-item"><span>Rasio Pengeluaran</span><b><?= $_p($expenseRatio) ?></b></div>
            <div class="ex-risk-item"><span>Margin Bersih</span><b><?= $_p($netMargin) ?></b></div>
            <div class="ex-risk-item"><span>Stok Kritis</span><b><?= count($inventoryDecision['urgent']) ?> item</b></div>
        </article>
        
        <article class="ex-panel">
            <h2>Aksi Prioritas Owner</h2>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($ownerActions as $a): 
                    $cls = strtolower($a['prio']) === 'high' ? 'high' : (strtolower($a['prio']) === 'med' ? 'med' : 'info'); 
                ?>
                    <div class="ex-tip <?= $cls ?>">
                        <span><?= $_e($a['prio']) ?></span>
                        <div>
                            <b><?= $_e($a['title']) ?></b><br>
                            <?= $_e($a['text']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="ex-panel mb-4">
        <div class="ex-section-title d-flex justify-content-between align-items-start">
            <div>
                <h2>Proyeksi Pertumbuhan Berbasis Trend</h2>
                <div class="ex-hint">Skenario memakai rata-rata hari aktif, margin aktual, momentum, dan trend score.</div>
            </div>
            <span class="ex-badge ex-badge-blue">Trend Score <?= $_n($trendScore, 0) ?>/100</span>
        </div>
        
        <div class="ex-forecast-grid mt-3">
            <?php 
            $forecastScenarios = [
                'conservative' => 'Konservatif', 
                'base' => 'Base Plan', 
                'aggressive' => 'Agresif'
            ];
            foreach ($forecastScenarios as $key => $label): 
            ?>
                <div class="ex-forecast">
                    <h3><?= $label ?></h3>
                    <small>Omzet 30 hari</small><br>
                    <b><?= $_m($forecast[$key]['sales30']) ?></b>
                    <div class="ex-hint mt-2">
                        Net 30 hari: <?= $_m($forecast[$key]['net30']) ?> &bull; 
                        BEP: <?= $forecast[$key]['bepDays'] > 0 ? $_n($forecast[$key]['bepDays']) . ' hari' : '-' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="ex-grid-nav mb-4">
        <article class="ex-panel">
            <div class="ex-section-title d-flex justify-content-between">
                <div>
                    <h2>Tren Harian</h2>
                    <div class="ex-hint">Omzet, HPP, Pengeluaran, dan Laba Bersih.</div>
                </div>
                <div class="ex-legend">
                    <span class="ex-pill sales">Omzet</span>
                    <span class="ex-pill hpp">HPP</span>
                    <span class="ex-pill exp">Pengeluaran</span>
                    <span class="ex-pill net">Net</span>
                </div>
            </div>
            
            <div class="ex-chart-area my-3">
                <?php foreach ($chartRows as $i => $cr):
                    $sales = (int)($cr['sales'] ?? 0); 
                    $hpp   = (int)($cr['hpp'] ?? 0); 
                    $exp   = (int)($cr['expenses'] ?? 0); 
                    $net   = (int)($cr['net_profit'] ?? 0);
                    $label = $cr['date'] ? date('d/m', strtotime($cr['date'])) : '-';
                    $tip   = $_did($cr['date']) . ' | Order: ' . $_n($cr['orders'] ?? 0) . ' | Omzet: ' . $_m($sales) . ' | HPP: ' . $_m($hpp) . ' | Net: ' . $_m($net);
                ?>
                    <div class="ex-day-group" data-tip="<?= $_e($tip) ?>">
                        <div class="ex-bars">
                            <span class="ex-bar sales" style="height:<?= max(4, round(($sales / $chartMax) * 210)) ?>px; animation-delay:<?= ($i * .025) ?>s"></span>
                            <span class="ex-bar hpp"   style="height:<?= max(4, round(($hpp / $chartMax) * 210)) ?>px; animation-delay:<?= ($i * .025 + .04) ?>s"></span>
                            <span class="ex-bar exp"   style="height:<?= max(4, round(($exp / $chartMax) * 210)) ?>px; animation-delay:<?= ($i * .025 + .08) ?>s"></span>
                            <span class="ex-bar net"   style="height:<?= max(4, round((max(0, $net) / $chartMax) * 210)) ?>px; animation-delay:<?= ($i * .025 + .12) ?>s"></span>
                        </div>
                        <div class="ex-day-label"><?= $label ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button class="btn btn-dark rounded-pill mb-3 ex-no-print" type="button" onclick="document.getElementById('dailyTable').classList.toggle('ex-collapsed')">
                Toggle Tabel Detail
            </button>
            
            <div id="dailyTable" class="ex-table-wrap ex-collapsed">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="ex-num">Order</th>
                            <th class="ex-num">Omzet</th>
                            <th class="ex-num">HPP</th>
                            <th class="ex-num">Pengeluaran</th>
                            <th class="ex-num">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily as $r): ?>
                            <tr>
                                <td><?= $_did($r['date']) ?></td>
                                <td class="ex-num"><?= $_n($r['orders']) ?></td>
                                <td class="ex-num"><?= $_m($r['sales']) ?></td>
                                <td class="ex-num"><?= $_m($r['hpp']) ?></td>
                                <td class="ex-num"><?= $_m($r['expenses']) ?></td>
                                <td class="ex-num"><b><?= $_m($r['net_profit']) ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
        
        <article class="ex-panel">
            <h2>Top Produk</h2>
            <?php if (!$topProducts): ?>
                <div class="ex-empty text-center p-3 text-muted">Belum ada data produk.</div>
            <?php endif; ?>
            
            <div class="d-flex flex-column gap-2 mt-3">
                <?php foreach ($topProducts as $p): 
                    $rev = (float)$p['revenue']; 
                    $gp  = (float)$p['gross_profit']; 
                    $m   = $rev > 0 ? $gp / $rev * 100 : 0; 
                ?>
                    <div class="ex-product-item d-flex justify-content-between p-2 border rounded">
                        <div>
                            <b><?= $_e($p['item_name']) ?></b><br>
                            <small class="text-muted"><?= $_n($p['qty']) ?> item &bull; Margin <?= $_p($m) ?></small>
                        </div>
                        <div class="ex-num text-end">
                            <b><?= $_m($rev) ?></b><br>
                            <small class="text-success">GP <?= $_m($gp) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="ex-grid-2 mb-4">
        <article class="ex-panel">
            <h2>Komponen Modal</h2>
            <form class="ex-form d-flex flex-wrap gap-3 mb-4" method="post" action="<?= url('/executive/capital') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $_e($edit['id'] ?? '') ?>">
                
                <div class="form-group w-100 d-flex gap-3">
                    <label class="flex-fill">
                        Tanggal 
                        <input type="date" name="capital_date" class="form-control" value="<?= $_e($edit['capital_date'] ?? $today) ?>">
                    </label>
                    <label class="flex-fill">
                        Kategori 
                        <select name="category" class="form-select">
                            <?php 
                            $categories = ['Modal Awal','Peralatan','Renovasi/Outlet','Bahan Awal','Marketing','Legal/Perizinan','Lain-lain'];
                            foreach ($categories as $cat): 
                            ?>
                                <option <?= ($edit['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                
                <label style="flex:2">
                    Nama Komponen 
                    <input name="component_name" class="form-control" value="<?= $_e($edit['component_name'] ?? '') ?>" required>
                </label>
                <label style="flex:1">
                    Nominal 
                    <input name="amount" inputmode="numeric" class="form-control" value="<?= $_e($edit['amount'] ?? '') ?>" required>
                </label>
                
                <div class="w-100">
                    <label class="d-block w-100 mb-2">
                        Supplier 
                        <input name="supplier" class="form-control" value="<?= $_e($edit['supplier'] ?? '') ?>">
                    </label>
                    <label class="d-block w-100 mb-3">
                        Catatan 
                        <textarea name="description" class="form-control" rows="2"><?= $_e($edit['description'] ?? '') ?></textarea>
                    </label>
                </div>
                
                <?php if ($edit): ?>
                    <label class="mb-3">
                        Status 
                        <select name="is_active" class="form-select">
                            <option value="1" <?= (int)$edit['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (int)$edit['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </label>
                <?php endif; ?>
                
                <div class="w-100 d-flex gap-2">
                    <button class="btn btn-danger rounded-pill fw-bold px-4"><?= $edit ? 'Update' : 'Tambah' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-outline-secondary rounded-pill px-4" href="<?= url('/executive?from=' . $_e($from) . '&to=' . $_e($to)) ?>">Batal</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="ex-table-wrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Komponen</th>
                            <th class="ex-num">Nominal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($capitals as $c): ?>
                            <tr class="<?= (int)($c['is_active'] ?? 1) === 1 ? '' : 'ex-inactive opacity-50' ?>">
                                <td><?= $_did($c['capital_date']) ?></td>
                                <td><span class="ex-badge ex-badge-gold"><?= $_e($c['category'] ?? $c['capital_type'] ?? '') ?></span></td>
                                <td>
                                    <b><?= $_e($c['component_name'] ?? $c['description'] ?? '') ?></b>
                                    <?php if (!empty($c['description']) && !empty($c['component_name']) && $c['description'] !== $c['component_name']): ?>
                                        <div class="ex-hint mt-1"><?= $_e($c['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="ex-num fw-bold"><?= $_m($c['amount']) ?></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= url('/executive?from=' . $_e($from) . '&to=' . $_e($to) . '&edit=' . $c['id']) ?>">Edit</a>
                                        <?php if ((int)($c['is_active'] ?? 1) === 1): ?>
                                            <form method="post" action="<?= url('/executive/capital/delete') ?>" class="d-inline" onsubmit="return confirm('Nonaktifkan modal ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger rounded-pill">Nonaktif</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[inputmode="numeric"]').forEach(function(el){
        el.addEventListener('input', function() { 
            this.value = this.value.replace(/[^\d]/g, ''); 
        });
    });
});
</script>