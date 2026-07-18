<?php
$s=$summary['sales'] ?? [];
$store=$summary['store'] ?? [];
$weeklyJson=json_encode($weekly ?? [], JSON_UNESCAPED_UNICODE);
$role = Auth::role();
$user = Auth::user() ?? [];
$quickMenus = [
    ['Dashboard', '/dashboard', 'ti ti-layout-dashboard', 'Ringkasan owner & operasional', ['super_admin','administrator','cashier']],
    ['POS Kasir', '/pos', 'ti ti-cash-register', 'Mulai transaksi kasir', ['super_admin','administrator','cashier']],
    ['Order & Transaksi', '/orders', 'ti ti-receipt', 'Pantau order paid/unpaid', ['super_admin','administrator','cashier']],
    ['Verifikasi Payment', '/payments', 'ti ti-qrcode', 'Kontrol QRIS/e-wallet', ['super_admin','administrator','cashier']],
    ['Buka/Tutup Toko', '/store', 'ti ti-login', 'Kontrol sesi harian outlet', ['super_admin','administrator']],
    ['Stok Siap Jual', '/daily-stock', 'ti ti-building-store', 'Stok produk hari ini', ['super_admin','administrator','cashier']],
    ['Gudang Bahan', '/inventory', 'ti ti-packages', 'Stok bahan baku', ['super_admin','administrator','cashier']],
    ['Pembelian', '/purchases', 'ti ti-shopping-cart-plus', 'Catat belanja bahan', ['super_admin','administrator']],
    ['Biaya', '/expenses', 'ti ti-wallet', 'Catat biaya operasional', ['super_admin','administrator']],
    ['Produk', '/products', 'ti ti-burger', 'Kelola menu dan varian', ['super_admin','administrator']],
    ['Resep & Biaya', '/recipes', 'ti ti-list-details', 'Atur komposisi menu', ['super_admin','administrator']],
    ['Kategori', '/categories', 'ti ti-category-2', 'Kelola kategori dan varian', ['super_admin','administrator']],
    ['Supplier', '/vendors', 'ti ti-truck-delivery', 'Data pemasok', ['super_admin','administrator']],
    ['Laporan Harian', '/reports/daily', 'ti ti-report-analytics', 'Closing dan laba harian', ['super_admin','administrator']],
    ['Laporan Keuangan', '/reports/financial', 'ti ti-chart-infographic', 'P&L dan arus kas', ['super_admin','administrator']],
    ['Analisis Bisnis', '/executive', 'ti ti-presentation-analytics', 'ROI, BEP, target bisnis', ['super_admin','administrator']],
    ['Rencana Belanja', '/forecasting', 'ti ti-chart-line', 'Rencana belanja otomatis', ['super_admin','administrator']],
    ['Pengguna', '/users', 'ti ti-users', 'Akun dan akses', ['super_admin','administrator']],
    ['Jejak Aktivitas', '/audit-logs', 'ti ti-history', 'Riwayat perubahan', ['super_admin']],
    ['Setting Sistem', '/settings', 'ti ti-settings', 'Printer, gateway, profil', ['super_admin']],
];
$quickMenus = array_values(array_filter($quickMenus, fn($m) => $role === 'super_admin' || in_array($role, $m[4], true)));
$status = strtolower($store['status'] ?? 'closed');
?>
<div class="dashboard-hero mb-4">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <span class="hero-chip mb-3"><?= sim_icon('ti-tools-kitchen-2') ?> Lumero POS & Restoran</span>
            <h2 class="mb-2">Dashboard Cabang <?= htmlspecialchars(function_exists('current_outlet_name') ? current_outlet_name() : ($user['outlet_name'] ?? 'Outlet')) ?></h2>
            <p class="mb-0">Pantau penjualan, biaya, laba, status toko, dan akses cepat modul inti dari satu halaman.</p>
        </div>
        <div class="col-lg-4 text-lg-end hero-actions">
            <a href="<?= url('/pos') ?>" class="btn btn-light fw-bold rounded-pill px-4 me-2 mb-2"><?= sim_icon('ti-cash-register', 'me-1') ?> POS Kasir</a>
            <a href="<?= url('/store') ?>" class="btn btn-dark fw-bold rounded-pill px-4 mb-2"><?= sim_icon('ti-login', 'me-1') ?> Buka/Tutup</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex justify-content-between gap-3">
            <div><div class="label">Omzet Hari Ini</div><div class="value"><?= rupiah($s['omzet'] ?? 0) ?></div><div class="metric-sub">Pendapatan paid hari ini</div></div>
            <div class="metric-icon"><?= sim_icon('ti-wallet') ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex justify-content-between gap-3">
            <div><div class="label">HPP Hari Ini</div><div class="value"><?= rupiah($s['hpp'] ?? 0) ?></div><div class="metric-sub">Cost of goods sold</div></div>
            <div class="metric-icon"><?= sim_icon('ti-database') ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex justify-content-between gap-3">
            <div><div class="label">Gross Profit</div><div class="value"><?= rupiah($s['laba'] ?? 0) ?></div><div class="metric-sub">Omzet dikurangi HPP</div></div>
            <div class="metric-icon"><?= sim_icon('ti-chart-line') ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex justify-content-between gap-3">
            <div><div class="label">Status Toko</div><div class="value"><span class="badge <?= $status==='open'?'badge-open':'badge-closed' ?>"><?= strtoupper($status) ?></span></div><div class="metric-sub">Sesi operasional harian</div></div>
            <div class="metric-icon"><?= sim_icon('ti-building-store') ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="soft-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="card-title-sim">Omzet vs HPP vs Laba 7 Hari</h5>
                    <small class="text-muted">Grafik performa operasional mingguan</small>
                </div>
            </div>
            <div id="weeklyChart" style="min-height:310px"></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="soft-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="card-title-sim">Top Selling Hari Ini</h5>
                    <small class="text-muted">Item terlaris berdasarkan qty</small>
                </div>
            </div>
            <?php if(empty($topItems)): ?><p class="text-muted mb-0">Belum ada transaksi paid hari ini.</p><?php endif; ?>
            <div class="list-group list-group-flush">
                <?php foreach(($topItems ?? []) as $idx=>$i): ?>
                    <div class="list-group-item px-0 d-flex align-items-center gap-3">
                        <div class="top-item-rank"><?= $idx+1 ?></div>
                        <div class="flex-fill min-w-0">
                            <strong class="d-block text-truncate"><?= htmlspecialchars($i['product_name_snapshot']) ?></strong>
                            <small class="text-muted text-truncate d-block"><?= htmlspecialchars($i['variant_name_snapshot'] ?? '') ?></small>
                        </div>
                        <span class="badge-soft"><?= number_format($i['qty'],0) ?>x</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-end justify-content-between mb-3 gap-3 flex-wrap">
    <div>
        <h5 class="card-title-sim mb-1">Menu Cepat <?= $role === 'super_admin' ? 'Super Admin' : '' ?></h5>
        <p class="text-muted mb-0">Akses modul utama sesuai hak akses user.</p>
    </div>
</div>
<div class="row g-3">
    <?php foreach ($quickMenus as $m): ?>
        <div class="col-xxl-3 col-xl-4 col-md-6">
            <a class="quick-card" href="<?= url($m[1]) ?>">
                <div class="icon"><?= sim_icon($m[2]) ?></div>
                <h6 class="mb-1"><?= htmlspecialchars($m[0]) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($m[3]) ?></small>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const data=<?= $weeklyJson ?: '[]' ?>;
    const el=document.querySelector('#weeklyChart');
    if(!el || typeof ApexCharts === 'undefined') return;
    const labels=data.map(x=>x.business_date);
    new ApexCharts(el,{
        chart:{type:'area',height:310,toolbar:{show:false},fontFamily:'Inter, system-ui, sans-serif'},
        series:[
            {name:'Omzet',data:data.map(x=>+x.omzet||0)},
            {name:'HPP',data:data.map(x=>+x.hpp||0)},
            {name:'Laba',data:data.map(x=>+x.laba||0)}
        ],
        xaxis:{categories:labels,labels:{style:{colors:'#64748b'}}},
        yaxis:{labels:{formatter:function(v){return 'Rp '+new Intl.NumberFormat('id-ID').format(v)}}},
        stroke:{curve:'smooth',width:3},dataLabels:{enabled:false},legend:{position:'top'},
        fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:.28,opacityTo:.05,stops:[0,90,100]}},
        colors:['#dc2626','#f59e0b','#16a34a'],grid:{borderColor:'#eef2f7'}
    }).render();
});
</script>
