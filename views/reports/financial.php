<?php $role = Auth::role(); ?>
<div class="sim-hero mb-4">
    <div>
        <span class="sim-kicker"><?= $role === 'super_admin' ? 'Owner Report' : 'Outlet Report' ?></span>
        <h2>Laporan Keuangan</h2>
        <p>Laba rugi komprehensif berbasis closing harian.</p>
    </div>
    <form class="d-flex gap-2">
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
        <button class="btn btn-dark">Filter</button>
    </form>
</div>
<div class="row g-3 mb-4">
    <?php foreach(['revenue'=>'Pendapatan','hpp'=>'HPP','gross_profit'=>'Laba Kotor','expense'=>'Total Biaya','net_profit'=>'Laba Bersih'] as $k=>$l): ?>
        <div class="col-md">
            <div class="sim-card stat-mini">
                <small><?= $l ?></small>
                <strong><?= rupiah($pl[$k]??0) ?></strong>
            </div>
        </div>
    <?php endforeach ?>
</div>
<div class="sim-card">
    <h5>Closing Harian</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-end">Omzet</th>
                    <th class="text-end">HPP</th>
                    <th class="text-end">Biaya</th>
                    <th class="text-end">Net Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($closings as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['business_date']) ?></td>
                        <td class="text-end"><?= rupiah($c['total_revenue']) ?></td>
                        <td class="text-end"><?= rupiah($c['total_hpp']) ?></td>
                        <td class="text-end"><?= rupiah($c['total_expense']) ?></td>
                        <td class="text-end fw-bold"><?= rupiah($c['net_profit']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
