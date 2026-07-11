<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <span class="sim-kicker"><?= sim_icon('ti-database-cog') ?> Master Data Management</span>
        <h2 class="mb-1">Sentral Data Setting</h2>
        <p class="mb-0 text-muted">Satu pintu untuk mengelola penambahan Bahan Baku, Sub-Resep, dan Produk Jual.</p>
    </div>
    <div>
        <a href="<?= url('/central-settings/wizard') ?>" class="btn btn-primary rounded-pill shadow-sm">
            <?= sim_icon('ti-magic', 'me-1') ?> Wizard Tambah Data Baru
        </a>
    </div>
</div>

<div class="row g-4 mb-4 justify-content-center">
    <div class="col-md-4">
        <div class="sim-card border-0 shadow-sm h-100 p-4">
            <h5 class="fw-bold mb-3"><?= sim_icon('ti-box', 'text-primary me-2') ?> 1. Bahan Baku Mentah</h5>
            <p class="text-muted small mb-0">Barang fisik yang dibeli dari supplier (misal: Tepung, Minyak, Telur). Data ini akan masuk ke modul Gudang Bahan dan digunakan sebagai komponen resep.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sim-card border-0 shadow-sm h-100 p-4">
            <h5 class="fw-bold mb-3"><?= sim_icon('ti-components', 'text-warning me-2') ?> 2. Sub-Resep</h5>
            <p class="text-muted small mb-0">Barang setengah jadi yang diolah (misal: Gula Cair, Adonan). Memiliki resep dan dihitung HPP-nya, lalu dipakai lagi sebagai bahan untuk Produk Final.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sim-card border-0 shadow-sm h-100 p-4">
            <h5 class="fw-bold mb-3"><?= sim_icon('ti-burger', 'text-success me-2') ?> 3. Produk Jual Final</h5>
            <p class="text-muted small mb-0">Barang akhir yang tampil di Kasir POS (misal: Nasi Goreng Spesial, Kopi Susu). Memiliki resep langsung dari bahan baku dan harga jual final.</p>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm bg-info-subtle d-flex gap-3 align-items-center">
    <div><?= sim_icon('ti-info-circle', 'fs-3') ?></div>
    <div class="small">
        <strong>Aturan Sentralisasi Data:</strong>
        Mulai versi terbaru, penambahan data master dan komponen resep hanya dilakukan melalui modul Sentral Data Setting ini. Modul Produk, Gudang Bahan, dan Resep berfungsi sebagai <em>Read-Only</em> (hanya tampil data).
    </div>
</div>
