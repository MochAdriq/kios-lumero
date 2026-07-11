<?php include __DIR__.'/../shared-flash.php'; ?>
<?php
$bizDate = $bizDate ?? (function_exists('business_date') ? business_date() : today());
$totals = is_array($totals ?? null) ? $totals : [];
$branches = array_values(array_filter($branches ?? [], fn($row) => is_array($row)));
$stats = is_array($stats ?? null) ? $stats : [];
$weeklyChart = is_array($weeklyChart ?? null) ? $weeklyChart : [];

$totalRevenue = (float)($totals['total_revenue'] ?? 0);
$totalProfit = (float)($totals['total_profit'] ?? 0);
$totalTrx = (int)($totals['total_trx'] ?? 0);
$branchCount = (int)($stats['branch_count'] ?? count($branches));
$openBranchCount = (int)($stats['open_branch_count'] ?? 0);
$closedBranchCount = (int)($stats['closed_branch_count'] ?? max(0, $branchCount - $openBranchCount));
$criticalBranchCount = (int)($stats['critical_branch_count'] ?? 0);
$totalUsers = (int)($stats['total_users'] ?? 0);
$avgTicket = (float)($stats['avg_ticket'] ?? ($totalTrx > 0 ? ($totalRevenue / $totalTrx) : 0));
$avgHealthScore = (float)($stats['avg_health_score'] ?? 0);

$statusMeta = function(array $branch): array {
    $status = (string)($branch['store_status'] ?? 'not_opened');
    if ($status === 'open') {
        return ['status' => 'open', 'label' => 'Buka', 'badge_class' => 'hq-badge-open'];
    }
    if ($status === 'closed') {
        return ['status' => 'closed', 'label' => 'Tutup', 'badge_class' => 'hq-badge-closed'];
    }
    return ['status' => 'not_opened', 'label' => 'Belum Buka', 'badge_class' => 'hq-badge-muted'];
};

$healthMeta = function(float $score): array {
    if ($score >= 80) {
        return ['label' => 'Sehat', 'bar' => 'hq-health-bar-healthy', 'badge' => 'hq-health-score-healthy'];
    }
    if ($score >= 60) {
        return ['label' => 'Waspada', 'bar' => 'hq-health-bar-warning', 'badge' => 'hq-health-score-warning'];
    }
    return ['label' => 'Kritis', 'bar' => 'hq-health-bar-critical', 'badge' => 'hq-health-score-critical'];
};

$branchesSorted = $branches;
usort($branchesSorted, function(array $a, array $b): int {
    $aRev = (float)($a['today_revenue'] ?? 0);
    $bRev = (float)($b['today_revenue'] ?? 0);
    if ($aRev !== $bRev) {
        return $bRev <=> $aRev;
    }
    $aTrx = (int)($a['today_trx'] ?? 0);
    $bTrx = (int)($b['today_trx'] ?? 0);
    if ($aTrx !== $bTrx) {
        return $bTrx <=> $aTrx;
    }
    return strcmp((string)($a['outlet_name'] ?? ''), (string)($b['outlet_name'] ?? ''));
});

$attentionBranches = array_values(array_filter($branchesSorted, function(array $branch): bool {
    return (float)($branch['health_score'] ?? 0) < 70;
}));
$topBranches = array_slice($branchesSorted, 0, 3);

$healthyBranchCount = 0;
$warningBranchCount = 0;
$criticalBranchCountView = 0;
foreach ($branches as $branch) {
    $score = (float)($branch['health_score'] ?? 0);
    if ($score >= 80) {
        $healthyBranchCount++;
    } elseif ($score >= 60) {
        $warningBranchCount++;
    } else {
        $criticalBranchCountView++;
    }
}
?>

<div class="hq-hero mb-4">
    <div>
        <span class="sim-kicker hq-kicker"><?= sim_icon('ti-star') ?> Headquarters Monitor</span>
        <h2 class="hq-hero-title">Dashboard Pusat Cabang</h2>
        <p class="hq-hero-subtitle mb-0">
            Rekap operasional seluruh cabang untuk hari bisnis
            <strong><?= htmlspecialchars($bizDate) ?></strong>.
        </p>
    </div>
    <div class="hq-hero-chip-wrap">
        <span class="hq-hero-chip"><?= sim_icon('ti-building-store') ?> <?= $branchCount ?> cabang</span>
        <span class="hq-hero-chip"><?= sim_icon('ti-login') ?> <?= $openBranchCount ?> buka</span>
        <span class="hq-hero-chip"><?= sim_icon('ti-alert-triangle') ?> <?= $criticalBranchCount ?> kritis</span>
        <span class="hq-hero-chip"><?= sim_icon('ti-activity-heartbeat') ?> Health <?= number_format($avgHealthScore, 1) ?></span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Cabang Aktif</small>
            <h3><?= number_format($branchCount) ?></h3>
            <div class="hq-kpi-icon hq-kpi-purple"><?= sim_icon('ti-building-store') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Cabang Buka</small>
            <h3><?= number_format($openBranchCount) ?></h3>
            <div class="hq-kpi-icon hq-kpi-green"><?= sim_icon('ti-login') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Cabang Tutup/Belum Buka</small>
            <h3><?= number_format($closedBranchCount) ?></h3>
            <div class="hq-kpi-icon hq-kpi-slate"><?= sim_icon('ti-logout') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>User Aktif Cabang</small>
            <h3><?= number_format($totalUsers) ?></h3>
            <div class="hq-kpi-icon hq-kpi-teal"><?= sim_icon('ti-users') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Total Transaksi</small>
            <h3><?= number_format($totalTrx) ?></h3>
            <div class="hq-kpi-icon hq-kpi-blue"><?= sim_icon('ti-receipt') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Omset Hari Ini</small>
            <h3><?= rupiah($totalRevenue) ?></h3>
            <div class="hq-kpi-icon hq-kpi-indigo"><?= sim_icon('ti-chart-bar') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Laba Kotor</small>
            <h3><?= rupiah($totalProfit) ?></h3>
            <div class="hq-kpi-icon hq-kpi-orange"><?= sim_icon('ti-chart-infographic') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Rata-rata/Transaksi</small>
            <h3><?= rupiah($avgTicket) ?></h3>
            <div class="hq-kpi-icon hq-kpi-red"><?= sim_icon('ti-wallet') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hq-kpi-card">
            <small>Skor Health Rata-rata</small>
            <h3><?= number_format($avgHealthScore, 1) ?></h3>
            <div class="hq-kpi-icon hq-kpi-amber"><?= sim_icon('ti-heart-rate-monitor') ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="sim-card hq-panel p-0 overflow-hidden">
            <div class="hq-panel-head">
                <div>
                    <h5 class="mb-0"><?= sim_icon('ti-list-details', 'me-1') ?> Ringkasan Cabang</h5>
                    <small class="text-muted">Ranking berdasarkan omset hari ini</small>
                </div>
                <div class="hq-toolbar">
                    <input type="text" id="hqBranchSearch" class="form-control form-control-sm" placeholder="Cari cabang...">
                    <select id="hqStatusFilter" class="form-select form-select-sm">
                        <option value="all">Semua status</option>
                        <option value="open">Buka</option>
                        <option value="closed">Tutup</option>
                        <option value="not_opened">Belum Buka</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 hq-table">
                    <thead>
                    <tr>
                        <th style="width:58px">Rank</th>
                        <th>Cabang</th>
                        <th>Status</th>
                        <th class="text-end">Trx</th>
                        <th class="text-end">Omset</th>
                        <th class="text-end">Laba</th>
                        <th style="width:170px">Kontribusi</th>
                        <th style="width:190px">Health</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="hqBranchRows">
                    <?php if (empty($branchesSorted)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Belum ada cabang aktif.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($branchesSorted as $idx => $branch):
                        $isHQ = !empty($branch['is_hq']);
                        $slug = (string)($branch['slug'] ?? '');
                        $rev = (float)($branch['today_revenue'] ?? 0);
                        $profit = (float)($branch['today_profit'] ?? 0);
                        $trx = (int)($branch['today_trx'] ?? 0);
                        $users = (int)($branch['user_count'] ?? 0);
                        $contribution = $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100, 1) : 0;
                        $avgBranchTicket = $trx > 0 ? $rev / $trx : 0;
                        $meta = $statusMeta($branch);
                        $healthScore = (float)($branch['health_score'] ?? 0);
                        $healthMetaItem = $healthMeta($healthScore);
                        $branchUrl = branch_url($slug, '/dashboard');
                        $searchKey = strtolower(trim(((string)($branch['outlet_name'] ?? '')) . ' ' . $slug));
                        $lastOrderAt = $branch['last_order_at'] ?? null;
                    ?>
                        <tr
                            data-hq-branch-row
                            data-branch-name="<?= htmlspecialchars($searchKey) ?>"
                            data-branch-status="<?= htmlspecialchars($meta['status']) ?>"
                        >
                            <td>
                                <span class="hq-rank-pill"><?= $idx + 1 ?></span>
                            </td>
                            <td>
                                <div class="hq-branch-name">
                                    <strong><?= htmlspecialchars((string)($branch['outlet_name'] ?? '-')) ?></strong>
                                    <?php if ($isHQ): ?>
                                        <span class="badge bg-dark ms-1"><?= sim_icon('ti-star') ?> HQ</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    URL: <?= $slug !== '' ? '/' . htmlspecialchars($slug) : '/' ?>
                                    &middot; <?= sim_icon('ti-users') ?> <?= $users ?>
                                    &middot; Close <?= htmlspecialchars(substr((string)($branch['closing_hour'] ?? '21:00:00'), 0, 5)) ?>
                                </small>
                                <div class="hq-branch-sub">
                                    Ticket rata-rata: <strong><?= rupiah($avgBranchTicket) ?></strong>
                                    <?php if (!empty($lastOrderAt)): ?>
                                        &middot; Last order: <?= htmlspecialchars(date('d M H:i', strtotime((string)$lastOrderAt))) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="hq-status-badge <?= htmlspecialchars($meta['badge_class']) ?>">
                                    <?= $meta['status'] === 'open' ? sim_icon('ti-login', 'me-1') : sim_icon('ti-logout', 'me-1') ?>
                                    <?= htmlspecialchars($meta['label']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold"><?= number_format($trx) ?></td>
                            <td class="text-end hq-money"><?= rupiah($rev) ?></td>
                            <td class="text-end hq-profit"><?= rupiah($profit) ?></td>
                            <td>
                                <div class="hq-contrib-head">
                                    <span><?= $contribution ?>%</span>
                                    <small class="text-muted"><?= rupiah($rev) ?></small>
                                </div>
                                <div class="progress hq-contrib-progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $contribution ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <div class="hq-health-cell">
                                    <div class="hq-health-head">
                                        <span class="hq-health-score <?= htmlspecialchars($healthMetaItem['badge']) ?>"><?= number_format($healthScore, 0) ?></span>
                                        <small><?= htmlspecialchars($healthMetaItem['label']) ?></small>
                                    </div>
                                    <div class="progress hq-health-progress">
                                        <div class="progress-bar <?= htmlspecialchars($healthMetaItem['bar']) ?>" role="progressbar" style="width: <?= max(0, min(100, $healthScore)) ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="<?= htmlspecialchars($branchUrl) ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <?= sim_icon('ti-external-link', 'me-1') ?> Buka
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!empty($branchesSorted)): ?>
                        <tr id="hqBranchEmpty" style="display:none;">
                            <td colspan="9" class="text-center text-muted py-4">Tidak ada cabang yang cocok dengan filter.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="sim-card hq-panel mb-3">
            <h5 class="mb-2"><?= sim_icon('ti-alert-triangle', 'me-1') ?> Cabang Perlu Perhatian</h5>
            <?php if (empty($attentionBranches)): ?>
                <div class="hq-ok-box">
                    <?= sim_icon('ti-circle-check') ?>
                    <span>Tidak ada cabang perlu perhatian saat ini.</span>
                </div>
            <?php else: ?>
                <ul class="hq-attention-list">
                    <?php foreach (array_slice($attentionBranches, 0, 6) as $branch):
                        $meta = $statusMeta($branch);
                        $slug = (string)($branch['slug'] ?? '');
                        $trx = (int)($branch['today_trx'] ?? 0);
                        $users = (int)($branch['user_count'] ?? 0);
                        $healthScore = (float)($branch['health_score'] ?? 0);
                        $warnings = [];
                        if ($healthScore < 60) $warnings[] = 'Health kritis';
                        if ($healthScore >= 60 && $healthScore < 70) $warnings[] = 'Health waspada';
                        if ($meta['status'] !== 'open') $warnings[] = $meta['label'];
                        if ($trx <= 0) $warnings[] = 'Trx 0';
                        if ($users <= 0) $warnings[] = 'User 0';
                    ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars((string)($branch['outlet_name'] ?? '-')) ?></strong>
                                <small><?= htmlspecialchars(implode(' | ', $warnings)) ?></small>
                            </div>
                            <a href="<?= htmlspecialchars(branch_url($slug, '/dashboard')) ?>" class="btn btn-sm btn-light">Cek</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="sim-card hq-panel mb-3">
            <h5 class="mb-2"><?= sim_icon('ti-heart-rate-monitor', 'me-1') ?> Ringkasan Health</h5>
            <div class="hq-health-summary">
                <div class="hq-health-summary-item is-healthy">
                    <span>Sehat</span>
                    <strong><?= number_format($healthyBranchCount) ?></strong>
                </div>
                <div class="hq-health-summary-item is-warning">
                    <span>Waspada</span>
                    <strong><?= number_format($warningBranchCount) ?></strong>
                </div>
                <div class="hq-health-summary-item is-critical">
                    <span>Kritis</span>
                    <strong><?= number_format(max($criticalBranchCount, $criticalBranchCountView)) ?></strong>
                </div>
            </div>
            <small class="text-muted">Rata-rata skor health jaringan: <strong><?= number_format($avgHealthScore, 1) ?></strong></small>
        </div>

        <div class="sim-card hq-panel mb-3">
            <h5 class="mb-2"><?= sim_icon('ti-trophy', 'me-1') ?> Top Performer Hari Ini</h5>
            <?php if (empty($topBranches)): ?>
                <p class="text-muted mb-0">Belum ada data cabang.</p>
            <?php else: ?>
                <?php foreach ($topBranches as $rank => $branch):
                    $rev = (float)($branch['today_revenue'] ?? 0);
                    $trx = (int)($branch['today_trx'] ?? 0);
                ?>
                    <div class="hq-top-item">
                        <div class="hq-top-rank"><?= $rank + 1 ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <strong class="d-block text-truncate"><?= htmlspecialchars((string)($branch['outlet_name'] ?? '-')) ?></strong>
                            <small class="text-muted"><?= number_format($trx) ?> transaksi</small>
                        </div>
                        <div class="hq-top-rev"><?= rupiah($rev) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="sim-card hq-panel">
            <h5 class="mb-2"><?= sim_icon('ti-bolt', 'me-1') ?> Aksi Cepat HQ</h5>
            <div class="hq-quick-links">
                <a href="<?= url('/branches') ?>">
                    <span><?= sim_icon('ti-building') ?></span>
                    <div>
                        <strong>Kelola Cabang</strong>
                        <small>Tambah/edit outlet & slug</small>
                    </div>
                </a>
                <a href="<?= url('/products/overrides') ?>">
                    <span><?= sim_icon('ti-adjustments') ?></span>
                    <div>
                        <strong>Override Harga</strong>
                        <small>Harga khusus per cabang</small>
                    </div>
                </a>
                <a href="<?= url('/hq/report') ?>">
                    <span><?= sim_icon('ti-chart-bar') ?></span>
                    <div>
                        <strong>Laporan Gabungan</strong>
                        <small>Analisa cross-branch</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($weeklyChart['series']) && !empty($weeklyChart['categories'])): ?>
    <div class="sim-card hq-panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h5 class="mb-0"><?= sim_icon('ti-chart-bar', 'me-1') ?> Tren Omset 7 Hari Terakhir</h5>
            <small class="text-muted">
                Periode: <?= htmlspecialchars((string)($weeklyChart['from'] ?? '-')) ?> s/d <?= htmlspecialchars((string)($weeklyChart['to'] ?? '-')) ?>
            </small>
        </div>
        <div id="chartWeeklyBranch" style="min-height:320px;"></div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof ApexCharts === 'undefined') return;

        var options = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            series: <?= json_encode($weeklyChart['series']) ?>,
            xaxis: { categories: <?= json_encode($weeklyChart['categories']) ?> },
            colors: ['#dc2626', '#f97316', '#ea580c', '#fb7185', '#16a34a', '#0ea5e9', '#1d4ed8'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '58%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
                    }
                }
            }
        };
        new ApexCharts(document.querySelector('#chartWeeklyBranch'), options).render();
    });
    </script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('hqBranchSearch');
    var statusFilter = document.getElementById('hqStatusFilter');
    var rows = Array.from(document.querySelectorAll('[data-hq-branch-row]'));
    var emptyRow = document.getElementById('hqBranchEmpty');

    function applyBranchFilter() {
        var query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
        var status = statusFilter ? statusFilter.value : 'all';
        var visibleCount = 0;

        rows.forEach(function(row) {
            var name = (row.dataset.branchName || '').toLowerCase();
            var rowStatus = row.dataset.branchStatus || '';
            var matchName = !query || name.indexOf(query) !== -1;
            var matchStatus = (status === 'all') || (rowStatus === status);
            var visible = matchName && matchStatus;
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (emptyRow) {
            emptyRow.style.display = visibleCount > 0 ? 'none' : '';
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyBranchFilter);
    if (statusFilter) statusFilter.addEventListener('change', applyBranchFilter);
    applyBranchFilter();
});
</script>

