<?php
include __DIR__ . '/../shared-flash.php';
$d = $data;
$role = Auth::role();
?>
<div class="sim-hero mb-4">
    <div>
        <span class="sim-kicker"><?= $role === 'super_admin' ? 'End-of-Day Closing' : 'Outlet Report' ?></span>
        <h2>Laporan Harian</h2>
        <p>Rekap omzet, HPP, biaya, laba bersih, pembayaran, dan rekonsiliasi kas.</p>
    </div>
    <form method="get" class="d-flex gap-2">
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control">
        <button class="btn btn-dark">Tampilkan</button>
    </form>
</div>
<div class="row g-3 mb-4">
    <?php foreach(['total_revenue'=>'Omzet','total_hpp'=>'HPP','gross_profit'=>'Gross Profit','total_expense'=>'Biaya','net_profit'=>'Laba Bersih'] as $k=>$l): ?>
        <div class="col-md">
            <div class="sim-card stat-mini">
                <small><?= $l ?></small>
                <strong><?= rupiah($d[$k]??0) ?></strong>
            </div>
        </div>
    <?php endforeach ?>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="sim-card">
            <h5>Generate Closing</h5>
            <form method="post" action="<?= url('/reports/daily/generate') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="business_date" value="<?= htmlspecialchars($date) ?>">
                <label class="form-label">Cash fisik di laci</label>
                <input type="number" name="cash_physical" class="form-control" value="<?= (float)($d['cash']??0) ?>">
                <button class="btn btn-danger rounded-pill mt-3 w-100">Generate / Update Closing</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="sim-card">
            <h5>Breakdown Pembayaran</h5>
            <table class="table">
                <tbody>
                    <tr><td>Cash</td><td class="text-end"><?= rupiah($d['cash']??0) ?></td></tr>
                    <tr><td>QRIS</td><td class="text-end"><?= rupiah($d['qris']??0) ?></td></tr>
                    <tr><td>Debit/Credit</td><td class="text-end"><?= rupiah($d['debit_credit']??0) ?></td></tr>
                    <tr><td>E-Wallet</td><td class="text-end"><?= rupiah($d['ewallet']??0) ?></td></tr>
                </tbody>
            </table>
            <p class="text-muted mb-0">Transaksi: <?= (int)($d['trx']??0) ?> | Item terjual: <?= number_format((float)($d['total_items_sold']??0),0,',','.') ?></p>
        </div>
    </div>
</div>
