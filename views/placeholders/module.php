<?php
[$title, $icon, $scope, $description] = $module;
$nextFeatures = [
    'Data table dengan search, filter periode, dan pagination',
    'Form create/edit berbasis modal atau halaman detail',
    'Integrasi audit trail untuk setiap perubahan penting',
    'Export Excel/PDF untuk modul laporan',
];
?>
<div class="module-placeholder mb-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div class="d-flex gap-3 align-items-start">
            <div class="quick-card" style="width:74px;height:74px;padding:0;display:flex;align-items:center;justify-content:center">
                <?= sim_icon($icon, '', 'width:36px;height:36px;color:#dc2626;') ?>
            </div>
            <div>
                <span class="module-status"><?= sim_icon('ti-tools', 'me-1') ?>Struktur modul siap</span>
                <h3 class="fw-bold mt-2 mb-2"><?= htmlspecialchars($title) ?></h3>
                <p class="text-muted mb-0" style="max-width:760px"><?= htmlspecialchars($description) ?></p>
            </div>
        </div>
        <a href="<?= url('/dashboard') ?>" class="btn btn-outline-secondary rounded-pill">Kembali ke Dashboard</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="soft-card p-4">
            <h5 class="fw-bold mb-3">Rencana Pengembangan Modul</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Fitur</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($nextFeatures as $feature): ?>
                        <tr>
                            <td><?= htmlspecialchars($feature) ?></td>
                            <td><span class="badge-soft">Next Phase</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="soft-card p-4">
            <h5 class="fw-bold mb-3">Akses Modul</h5>
            <p class="text-muted mb-2">Scope akses awal:</p>
            <h4 class="fw-bold text-uppercase mb-3"><?= htmlspecialchars($scope) ?></h4>
            <p class="text-muted mb-0">Menu sudah muncul sesuai role. Setelah controller detail dibuat, halaman ini akan diganti dengan fitur operasional sebenarnya.</p>
        </div>
    </div>
</div>
