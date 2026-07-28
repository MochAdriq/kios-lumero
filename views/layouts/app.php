<?php
$routePath = current_route_path();
$relativePath = $routePath === '/' ? '/dashboard' : $routePath;
$role = Auth::role();
$user = Auth::user() ?? [];
$branch = branch_context();
$branchConfig = app_branch_config();
$currentBranchSlug = $branch['slug'] ?? '';
$isHQContext = Auth::isHQ();
$businessDate = function_exists('business_date') ? business_date() : today();

$branchLinks = [];
$defaultBranch = $branchConfig['default'] ?? [];
$branchLinks[] = [
    'slug' => '',
    'name' => (string)($defaultBranch['name'] ?? 'Outlet Utama'),
];
foreach (($branchConfig['map'] ?? []) as $slug => $branchItem) {
    $slug = trim((string)$slug);
    if ($slug === '') {
        continue;
    }
    $branchLinks[] = [
        'slug' => $slug,
        'name' => (string)($branchItem['name'] ?? strtoupper($slug)),
    ];
}

$canSee = function(array $roles) use ($role): bool {
    return $role === 'super_admin' || in_array($role, $roles, true);
};
$isActive = function($paths) use ($relativePath): string {
    $paths = (array)$paths;
    foreach ($paths as $path) {
        if ($path === $relativePath || ($path !== '/' && str_starts_with($relativePath, rtrim($path, '/') . '/'))) {
            return 'active';
        }
    }
    return '';
};

// ── Role shorthand constants ────────────────────────────
$ALL   = ['super_admin', 'administrator', 'cashier'];
$ADMIN = ['super_admin', 'administrator'];
$OWNER = ['super_admin'];

// ── Menu groups — ordered by natural daily workflow ─────
// ── Menu groups — ordered by role & natural daily workflow ─────
$menuGroups = [];

$isHQUser = (Auth::role() === 'super_admin');

if ($isHQUser) {
    $menuGroups['Pusat & Monitoring'] = [
        ['label' => 'Dashboard Pusat paesekon',      'icon' => 'ti ti-star',          'url' => '/hq',                 'roles' => $OWNER],
        ['label' => 'Data Cabang',          'icon' => 'ti ti-building',      'url' => '/branches',           'roles' => $OWNER],
        ['label' => 'Laporan Semua Cabang', 'icon' => 'ti ti-chart-bar',     'url' => '/hq/report',          'roles' => $OWNER],
    ];
}

$menuGroups += [
    'Kasir & Penjualan' => [
        ['label' => 'Dashboard Toko',      'icon' => 'ti ti-layout-dashboard', 'url' => '/dashboard', 'roles' => $ALL],
        ['label' => 'POS Kasir',           'icon' => 'ti ti-cash-register',    'url' => '/pos',       'roles' => $ALL],
        ['label' => 'Order & Transaksi',   'icon' => 'ti ti-receipt',          'url' => '/orders',    'roles' => $ALL],
        ['label' => 'Delivery & Kurir',    'icon' => 'ti ti-truck-delivery',   'url' => '/delivery',  'roles' => $ALL],
        ['label' => 'Verifikasi Payment',  'icon' => 'ti ti-qrcode',           'url' => '/payments',  'roles' => $ALL],
    ],
    'Katalog Produk & Bahan Baku' => [
        ['label' => 'Sentral Data & Wizard', 'icon' => 'ti ti-database-cog',  'url' => '/central-settings', 'roles' => $ADMIN],
        ['label' => 'Produk Jual (Kasir)',   'icon' => 'ti ti-burger',         'url' => '/products',         'roles' => $ADMIN],
        ['label' => 'Bahan Baku (Gudang)',   'icon' => 'ti ti-packages',       'url' => '/inventory',        'roles' => $ALL],
        ['label' => 'Resep & Komposisi HPP', 'icon' => 'ti ti-list-details',   'url' => '/recipes',          'roles' => $ADMIN],
        ['label' => 'Kategori & Varian',     'icon' => 'ti ti-category-2',     'url' => '/categories',       'roles' => $ADMIN],
        ['label' => 'Supplier Bahan',        'icon' => 'ti ti-truck-delivery', 'url' => '/vendors',          'roles' => $ADMIN],
    ],
    'Stok & Operasional Harian' => [
        ['label' => 'Buka/Tutup Toko',       'icon' => 'ti ti-login',              'url' => '/store',               'roles' => $ADMIN],
        ['label' => 'Stok Harian Siap Jual', 'icon' => 'ti ti-building-store',     'url' => '/daily-stock',         'roles' => $ALL],
        ['label' => 'Pembelian & Restock',   'icon' => 'ti ti-shopping-cart-plus', 'url' => '/purchases',           'roles' => $ADMIN],
        ['label' => 'Peringatan Stok',       'icon' => 'ti ti-alert-triangle',     'url' => '/inventory/low-stock', 'roles' => $ALL],
        ['label' => 'Koreksi & Void',        'icon' => 'ti ti-adjustments',        'url' => '/corrections',         'roles' => $ADMIN],
    ],
    'Loyalty & Member Poin' => [
        ['label' => 'Data Member & Poin',       'icon' => 'ti ti-award',            'url' => '/loyalty/members',        'roles' => $ALL],
        ['label' => 'Katalog Hadiah Poin',      'icon' => 'ti ti-gift',             'url' => '/loyalty/rewards',        'roles' => $ADMIN],
        ['label' => 'Validasi Penukaran Poin',  'icon' => 'ti ti-checkup-list',     'url' => '/loyalty/redemptions',    'roles' => $ALL],
        ['label' => 'Pengaturan Hadiah Undian', 'icon' => 'ti ti-settings',         'url' => '/loyalty/eventSettings',  'roles' => $ADMIN],
        ['label' => 'Validasi Hadiah Undian',   'icon' => 'ti ti-ticket',           'url' => '/loyalty/eventClaims',    'roles' => $ALL],
        ['label' => 'Buka Portal Member',       'icon' => 'ti ti-external-link',    'url' => '/user/index.php', 'root' => true, 'roles' => $ALL],
    ],
    'Keuangan & Laporan' => [
        ['label' => 'Biaya Operasional',     'icon' => 'ti ti-wallet',                 'url' => '/expenses',          'roles' => $ADMIN],
        ['label' => 'Laporan Harian',        'icon' => 'ti ti-report-analytics',       'url' => '/reports/daily',     'roles' => $ADMIN],
        ['label' => 'Laporan Keuangan',      'icon' => 'ti ti-chart-infographic',      'url' => '/reports/financial', 'roles' => $ADMIN],
    ],
];

if ($isHQUser) {
    $menuGroups['Analisis & Pengaturan'] = [
        ['label' => 'Analisis Bisnis',      'icon' => 'ti ti-presentation-analytics', 'url' => '/executive',  'roles' => $OWNER],
        ['label' => 'Manajemen Modal',      'icon' => 'ti ti-wallet',                 'url' => '/capital',    'roles' => $OWNER],
        ['label' => 'Inovasi & Tren',       'icon' => 'ti ti-bulb',                   'url' => '/innovation', 'roles' => $OWNER],
        ['label' => 'Pengguna & HR',        'icon' => 'ti ti-users',                  'url' => '/users',      'roles' => $OWNER],
        ['label' => 'Jejak Aktivitas',      'icon' => 'ti ti-history',                'url' => '/audit-logs', 'roles' => $OWNER],
        ['label' => 'Setting Sistem',       'icon' => 'ti ti-settings',               'url' => '/settings',   'roles' => $OWNER],
    ];
} else {
    $menuGroups['Pengaturan Cabang'] = [
        ['label' => 'Analisis Bisnis & KPI',     'icon' => 'ti ti-presentation-analytics', 'url' => '/executive',  'roles' => $ADMIN],
        ['label' => 'Manajemen Modal Outlet',    'icon' => 'ti ti-wallet',                 'url' => '/capital',    'roles' => $ADMIN],
        ['label' => 'Inovasi & Uji Coba Menu',   'icon' => 'ti ti-bulb',                   'url' => '/innovation', 'roles' => $ADMIN],
        ['label' => 'Karyawan & Gaji Toko',      'icon' => 'ti ti-users',                  'url' => '/users',      'roles' => $ADMIN],
        ['label' => 'Setting Sistem',            'icon' => 'ti ti-settings',               'url' => '/settings',   'roles' => $ADMIN],
    ];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#b91c1c">
    <title><?= htmlspecialchars($pageTitle ?? app_config('name')) ?> - <?= htmlspecialchars(app_config('name')) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= url('/public/favicon.ico?v=2', false) ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=016">
    <link rel="stylesheet" href="<?= asset('css/daily-stock.css') ?>?v=026">
    <link rel="stylesheet" href="<?= asset('css/module-suite.css') ?>?v=028">
</head>
<body class="sim-body">
<div class="sim-overlay" id="simOverlay"></div>
<aside class="sim-sidebar" id="simSidebar">
    <div class="sim-brand">
        <a href="<?= url('/dashboard') ?>" class="sim-brand-link">
            <span class="sim-brand-mark"><?= sim_icon('ti-tools-kitchen-2') ?></span>
            <span class="sim-brand-text">
                <strong>Lumero</strong>
                <small>POS System</small>
            </span>
        </a>
    </div>

    <div class="sim-outlet-card">
        <div class="sim-outlet-logo">
            <?php if ($isHQContext): ?>
                <?= sim_icon('ti-star', '', 'color:#facc15; width:2rem; height:2rem;') ?>
            <?php else: ?>
                <?= sim_icon('ti-building', '', 'width:2rem; height:2rem;') ?>
            <?php endif; ?>
        </div>
        <div>
            <small><?= $isHQContext ? 'Pusat (HQ)' : 'Outlet Aktif' ?></small>
            <strong><?= htmlspecialchars(current_outlet_name()) ?></strong>
        </div>
    </div>

    <nav class="sim-menu" aria-label="Sidebar Menu">
        <?php foreach ($menuGroups as $groupName => $items): ?>
            <?php $visibleItems = array_filter($items, fn($item) => $canSee($item['roles'])); ?>
            <?php if (!$visibleItems) continue; ?>
            <div class="sim-menu-title"><?= htmlspecialchars($groupName) ?></div>
            <?php foreach ($visibleItems as $item): ?>
                <a class="sim-menu-link <?= $isActive($item['url']) ?>" href="<?= !empty($item['root']) ? url($item['url'], false) : url($item['url']) ?>" <?= !empty($item['root']) ? 'target="_blank"' : '' ?> title="<?= htmlspecialchars($item['label']) ?>">
                    <?= sim_icon($item['icon']) ?>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="sim-menu-title">Akun</div>
        <a class="sim-menu-link" href="<?= url('/logout') ?>"><?= sim_icon('ti-logout') ?> <span>Logout</span></a>
    </nav>
</aside>

<main class="sim-main">
    <?php if (!empty($_SESSION['impersonator'])): ?>
    <div class="alert alert-warning mb-0 rounded-0 d-flex justify-content-between align-items-center py-2 px-3 fw-medium shadow-sm" style="background-color: #fff3cd; border-bottom: 2px solid #ffecb5; z-index: 1050;">
        <div class="d-flex align-items-center gap-2">
            <?= sim_icon('ti-alert-triangle', 'text-warning', 'width:20px;height:20px;') ?>
            <span><strong>Mode Login As:</strong> Anda sedang menjelajah sebagai <u><?= htmlspecialchars($user['name'] ?? '') ?></u> (<?= htmlspecialchars($user['role_name'] ?? '') ?> - <?= htmlspecialchars($user['outlet_name'] ?? 'Global') ?>).</span>
        </div>
        <form action="<?= url('/users/stop-impersonation') ?>" method="post" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger fw-bold rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <?= sim_icon('ti-arrow-back-up', '', 'width:16px;height:16px;') ?> Kembali ke Akun Owner / Pusat
            </button>
        </form>
    </div>
    <?php endif; ?>
    <header class="sim-topbar">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <button class="sim-toggle" id="sidebarToggle" type="button" aria-label="Toggle menu"><?= sim_icon('ti-menu-2') ?></button>
            <div class="min-w-0">
                <div class="d-flex align-items-center flex-wrap">
                    <h1 class="sim-page-title mb-0"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
                    <?= function_exists('render_db_switcher') ? render_db_switcher() : '' ?>
                </div>
                <div class="sim-page-subtitle">
                    <?php if ($isHQContext): ?>
                        <span class="badge bg-warning text-dark" style="font-size:.7rem;vertical-align:middle"><?= sim_icon('ti-star', '', 'width:14px; height:14px;') ?> PUSAT</span>
                    <?php endif; ?>
                    <?= htmlspecialchars(current_outlet_name()) ?> &middot; <?= $businessDate ?>
                    <?php if (($branch['prefix'] ?? '') !== ''): ?>
                        &middot; URL <?= htmlspecialchars($branch['prefix']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sim-user-box">
            <div class="text-end d-none d-sm-block">
                <div class="fw-bold text-truncate"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                <small><?= htmlspecialchars($user['role_name'] ?? $role) ?></small>
            </div>
            <img src="<?= asset('pos-template/avator1.jpg') ?>" width="42" height="42" class="sim-avatar" alt="avatar" onerror="this.style.display='none'">
        </div>
    </header>
    <section class="sim-content"><?= $content ?></section>
</main>

<script src="<?= asset('pos-template/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= asset('pos-template/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('pos-template/apexcharts.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>?v=016"></script>
<script>
(function(){
    const overlay = document.getElementById('simOverlay');
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('simSidebar');

    function closeMenu() { document.body.classList.remove('sidebar-open'); }
    if (toggle) { toggle.addEventListener('click', () => document.body.classList.toggle('sidebar-open')); }
    if (overlay) { overlay.addEventListener('click', closeMenu); }

    // Maintain sidebar scroll position
    const simMenu = document.querySelector('.sim-menu');
    if (simMenu) {
        const scrollKey = 'sim_menu_scroll_position';
        const savedScroll = sessionStorage.getItem(scrollKey);
        if (savedScroll !== null) {
            simMenu.scrollTop = parseInt(savedScroll, 10);
        }
        window.addEventListener('beforeunload', () => {
            sessionStorage.setItem(scrollKey, simMenu.scrollTop);
        });
    }

    // Initialize Bootstrap Tooltips globally
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
})();
</script>
</body>
</html>
