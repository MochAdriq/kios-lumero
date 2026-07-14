<?php
$user = Auth::user() ?? [];
$role = Auth::role();
$storeName = current_outlet_name();
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f0f13">
    <title><?= htmlspecialchars($pageTitle ?? 'POS Kasir') ?> - <?= htmlspecialchars(app_config('name')) ?></title>

    <link rel="icon" type="image/x-icon" href="<?= url('/public/favicon.ico?v=2', false) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('pos-template/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/animate.css') ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/select2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/owl.carousel.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/owl.theme.default.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('pos-template/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/pos-preadmin-overrides.css') ?>?v=023">
    <link rel="stylesheet" href="<?= asset('css/pos-kasir2-theme.css') ?>?v=007">
</head>
<body class="pos-page sim-pos-template sim-pos-dcelup k2-body <?= isset($_GET['embed']) ? 'is-embed-modal' : '' ?>" style="<?= isset($_GET['embed']) ? 'background:#fff !important; padding:0 !important;' : '' ?>">
<div id="global-loader" style="display:none"><div class="whirly-loader"></div></div>

<div class="main-wrapper pos-five">
    <?php if (!isset($_GET['embed'])): ?>
    <header class="header pos-header sim-pos-header">
        <div class="header-left active sim-pos-brand-wrap">
            <a href="<?= url('/dashboard') ?>" class="logo logo-normal sim-pos-logo">
                <img src="<?= asset('images/pos-products/dclup-pasekon.png') ?>" alt="<?= htmlspecialchars($storeName) ?>">
            </a>
            <a href="<?= url('/dashboard') ?>" class="logo-small sim-pos-logo-small">
                <img src="<?= asset('images/pos-products/icon-192.png') ?>" alt="<?= htmlspecialchars($storeName) ?>">
            </a>
        </div>

        <ul class="nav user-menu sim-pos-top-menu">
            <li class="nav-item time-nav">
                <span class="sim-clock-pill d-inline-flex align-items-center">
                    <?= sim_icon('ti-clock-hour-3', 'me-2') ?>
                    <span id="posTopClock">--:--:--</span>
                </span>
            </li>
            <li class="nav-item pos-nav d-none d-lg-block">
                <a href="<?= url('/dashboard') ?>" class="btn btn-outline-dark btn-md d-inline-flex align-items-center">
                    <?= sim_icon('ti-layout-dashboard', 'me-1') ?>Dashboard
                </a>
            </li>
            <li class="nav-item pos-nav d-none d-lg-block">
                <a href="<?= url('/orders') ?>" class="btn btn-primary btn-md d-inline-flex align-items-center">
                    <?= sim_icon('ti-receipt', 'me-1') ?>Orders
                </a>
            </li>
            <li class="nav-item dropdown has-arrow main-drop select-store-dropdown d-none d-md-block">
                <a href="javascript:void(0);" class="dropdown-toggle nav-link select-store" data-bs-toggle="dropdown">
                    <span class="user-info">
                        <span class="user-letter"><img src="<?= asset('images/pos-products/icon-192.png') ?>" alt="Store" class="img-fluid"></span>
                        <span class="user-detail"><span class="user-name"><?= htmlspecialchars($storeName) ?></span></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="javascript:void(0);" class="dropdown-item">
                        <img src="<?= asset('images/pos-products/icon-192.png') ?>" alt="" class="img-fluid">Outlet Aktif
                    </a>
                </div>
            </li>
            <li class="nav-item nav-item-box"><a href="javascript:void(0);" id="btnFullscreen" title="Fullscreen"><?= sim_icon('ti-maximize') ?></a></li>
            <li class="nav-item nav-item-box"><a href="<?= url('/store') ?>" title="Buka/Tutup Toko"><?= sim_icon('ti-cash') ?></a></li>
            <li class="nav-item dropdown has-arrow main-drop profile-nav">
                <a href="javascript:void(0);" class="nav-link userset" data-bs-toggle="dropdown">
                    <span class="user-info p-0"><span class="user-letter"><img src="<?= asset('pos-template/avator1.jpg') ?>" alt="User" class="img-fluid"></span></span>
                </a>
                <div class="dropdown-menu menu-drop-user">
                    <div class="profilename">
                        <div class="profileset">
                            <span class="user-img"><img src="<?= asset('pos-template/avator1.jpg') ?>" alt=""><span class="status online"></span></span>
                            <div class="profilesets"><h6><?= htmlspecialchars($user['name'] ?? 'User') ?></h6><h5><?= htmlspecialchars($user['role_name'] ?? $role) ?></h5></div>
                        </div>
                        <hr class="m-0">
                        <a class="dropdown-item" href="<?= url('/dashboard') ?>"><?= sim_icon('ti-layout-dashboard', 'me-2') ?>Dashboard</a>
                        <a class="dropdown-item" href="<?= url('/orders') ?>"><?= sim_icon('ti-receipt', 'me-2') ?>Order</a>
                        <a class="dropdown-item logout" href="<?= url('/logout') ?>"><?= sim_icon('ti-logout', 'me-2') ?>Logout</a>
                    </div>
                </div>
            </li>
        </ul>
    </header>
    <?php endif; ?>

    <?= $content ?>
</div>

<script src="<?= asset('pos-template/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= asset('pos-template/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('pos-template/select2.min.js') ?>"></script>
<script src="<?= asset('pos-template/owl.carousel.min.js') ?>"></script>
<script src="<?= asset('pos-template/jquery.slimscroll.min.js') ?>"></script>
<?php if (class_exists('MidtransService') && MidtransService::getClientKey() !== ''): ?>
<script src="<?= MidtransService::snapJsUrl() ?>" data-client-key="<?= htmlspecialchars(MidtransService::getClientKey()) ?>"></script>
<?php endif; ?>
<script src="<?= asset('js/pos-preadmin.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
