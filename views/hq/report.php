<?php include __DIR__.'/../shared-flash.php'; ?>
<?php
$from = (string)($from ?? date('Y-m-01'));
$to = (string)($to ?? today());
$branchReport = array_values(array_filter($branchReport ?? [], fn($row) => is_array($row)));
$totals = is_array($totals ?? null) ? $totals : [];
$topNet = array_values(array_filter($topNet ?? [], fn($row) => is_array($row)));

$totalRevenue = (float)($totals['revenue'] ?? 0);
$totalNet = (float)($totals['net_profit'] ?? 0);
$totalGross = (float)($totals['gross_profit'] ?? 0);
$totalExpense = (float)($totals['expense'] ?? 0);
$periodDays = (int)($totals['period_days'] ?? 1);
$branchCount = (int)($totals['branches'] ?? count($branchReport));
$avgDailyRevenue = (float)($totals['avg_daily_revenue'] ?? 0);
$avgDailyNet = (float)($totals['avg_daily_net_profit'] ?? 0);
$marginPct = $totalRevenue > 0 ? (($totalNet / $totalRevenue) * 100) : 0;
$reportRows = $branchReport;
usort($reportRows, fn(array $a, array $b): int => ((float)($b['net_profit'] ?? 0)) <=> ((float)($a['net_profit'] ?? 0)));
?>

<div class="hq-hero mb-4">
    <div>
        <span class="sim-kicker hq-kicker"><?= sim_icon('ti-chart-bar') ?> Laporan Gabungan HQ</span>
        <h2 class="hq-hero-title">Laporan Semua Cabang</h2>
        <p class="hq-hero-subtitle mb-0">
            Ringkasan keuangan lintas cabang untuk periode
            <strong><?= htmlspecialchars($from) ?></strong> s/d <strong><?= htmlspecialchars($to) ?></strong>.
        </p>
    </div>
    <div class="hq-hero-chip-wrap">
        <span class="hq-hero-chip"><?= sim_icon('ti-building-store') ?> <?= number_format($branchCount) ?> cabang</span>
        <span class="hq-hero-chip"><?= sim_icon('ti-wallet') ?> <?= rupiah($totalRevenue) ?></span>
        <span class="hq-hero-chip"><?= sim_icon('ti-chart-infographic') ?> Margin <?= number_format($marginPct, 1) ?>%</span>
    </div>
</div>

<div class="sim-card hq-panel mb-4">
    <form method="get" class="row g-3 align-items-end hq-range-form">
        <div class="col-sm-6 col-lg-3">
            <label class="form-label">Dari</label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label class="form-label">Sampai</label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label class="form-label">Durasi</label>
            <div class="hq-range-pill"><?= number_format($periodDays) ?> hari</div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <button class="btn btn-danger rounded-pill w-100"><?= sim_icon('ti-search', 'me-1') ?>Terapkan Filter</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Total Revenue</small>
            <h3><?= rupiah($totalRevenue) ?></h3>
            <div class="hq-kpi-icon hq-kpi-indigo"><?= sim_icon('ti-wallet') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Laba Bersih</small>
            <h3><?= rupiah($totalNet) ?></h3>
            <div class="hq-kpi-icon hq-kpi-green"><?= sim_icon('ti-chart-infographic') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Rata-rata Omset/Hari</small>
            <h3><?= rupiah($avgDailyRevenue) ?></h3>
            <div class="hq-kpi-icon hq-kpi-orange"><?= sim_icon('ti-chart-bar') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Rata-rata Laba/Hari</small>
            <h3><?= rupiah($avgDailyNet) ?></h3>
            <div class="hq-kpi-icon hq-kpi-red"><?= sim_icon('ti-bolt') ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sim-card hq-panel p-0 overflow-hidden">
            <div class="hq-panel-head">
                <div>
                    <h5 class="mb-0"><?= sim_icon('ti-file-analytics', 'me-1') ?> Ringkasan Per Cabang</h5>
                    <small class="text-muted">Diurutkan berdasarkan laba bersih tertinggi</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 hq-table hq-report-table">
                    <thead>
                    <tr>
                        <th>Cabang</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">HPP</th>
                        <th class="text-end">Laba Kotor</th>
                        <th class="text-end">Biaya</th>
                        <th class="text-end">Laba Bersih</th>
                        <th class="text-end">Margin</th>
                        <th class="text-center">Hari Report</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reportRows)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada data laporan. Pastikan closing harian cabang sudah dijalankan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($reportRows as $row):
                        $isHQ = !empty($row['is_hq']);
                        $revenue = (float)($row['revenue'] ?? 0);
                        $net = (float)($row['net_profit'] ?? 0);
                        $margin = $revenue > 0 ? (($net / $revenue) * 100) : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string)($row['outlet_name'] ?? '-')) ?></strong>
                                <?php if ($isHQ): ?>
                                    <span class="badge bg-dark ms-1"><?= sim_icon('ti-star') ?> HQ</span>
                                <?php endif; ?>
                                <div class="hq-branch-sub">
                                    <?= !empty($row['slug']) ? '/' . htmlspecialchars((string)$row['slug']) : '/' ?>
                                </div>
                            </td>
                            <td class="text-end hq-money"><?= rupiah($revenue) ?></td>
                            <td class="text-end"><?= rupiah((float)($row['hpp'] ?? 0)) ?></td>
                            <td class="text-end hq-profit"><?= rupiah((float)($row['gross_profit'] ?? 0)) ?></td>
                            <td class="text-end text-danger"><?= rupiah((float)($row['expense'] ?? 0)) ?></td>
                            <td class="text-end fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($net) ?></td>
                            <td class="text-end fw-bold"><?= number_format($margin, 1) ?>%</td>
                            <td class="text-center"><?= number_format((int)($row['days_reported'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="fw-bold">
                        <td>TOTAL</td>
                        <td class="text-end"><?= rupiah($totalRevenue) ?></td>
                        <td class="text-end"><?= rupiah((float)($totals['hpp'] ?? 0)) ?></td>
                        <td class="text-end"><?= rupiah($totalGross) ?></td>
                        <td class="text-end text-danger"><?= rupiah($totalExpense) ?></td>
                        <td class="text-end <?= $totalNet >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($totalNet) ?></td>
                        <td class="text-end"><?= number_format($marginPct, 1) ?>%</td>
                        <td class="text-center"><?= number_format((int)($totals['days_reported'] ?? 0)) ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="sim-card hq-panel mb-3">
            <h5 class="mb-2"><?= sim_icon('ti-trophy', 'me-1') ?> Top Net Profit</h5>
            <?php if (empty($topNet)): ?>
                <p class="text-muted mb-0">Belum ada data cabang.</p>
            <?php else: ?>
                <?php foreach ($topNet as $idx => $row): ?>
                    <div class="hq-top-item">
                        <div class="hq-top-rank"><?= $idx + 1 ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <strong class="d-block text-truncate"><?= htmlspecialchars((string)($row['outlet_name'] ?? '-')) ?></strong>
                            <small class="text-muted"><?= number_format((int)($row['days_reported'] ?? 0)) ?> hari report</small>
                        </div>
                        <div class="hq-top-rev"><?= rupiah((float)($row['net_profit'] ?? 0)) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="sim-card hq-panel">
            <h5 class="mb-2"><?= sim_icon('ti-clipboard-data', 'me-1') ?> Catatan Periode</h5>
            <ul class="hq-report-notes mb-0">
                <li>Total cabang terlapor: <strong><?= number_format($branchCount) ?></strong></li>
                <li>Total hari laporan: <strong><?= number_format((int)($totals['days_reported'] ?? 0)) ?></strong></li>
                <li>Rata-rata laba bersih/hari: <strong><?= rupiah($avgDailyNet) ?></strong></li>
                <li>Margin bersih jaringan: <strong><?= number_format($marginPct, 1) ?>%</strong></li>
            </ul>
        </div>
    </div>
</div>
