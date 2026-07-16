<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <span class="sim-kicker"><?= sim_icon('ti-database-cog') ?> Master Logistik & Gudang</span>
        <h2 class="mb-1">Sentral Data Setting</h2>
        <p class="mb-0 text-muted">Pusat pengelolaan master logistik gudang: penambahan Bahan Baku Mentah dan Sub-Resep (Bahan Olahan).</p>
    </div>
    <div>
        <a href="<?= url('/central-settings/wizard') ?>" class="btn btn-primary rounded-pill shadow-sm">
            <?= sim_icon('ti-magic', 'me-1') ?> Wizard Tambah Bahan & Sub-Resep
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
        <div class="sim-card border-0 shadow-sm h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-burger', 'text-success me-2') ?> 3. Produk Jual Final</h5>
                <p class="text-muted small mb-3">Barang akhir yang tampil di Kasir POS (misal: Ayam Crispy, Kopi Susu). Dikelola secara terpisah melalui modul khusus Product Builder agar lebih fokus dan interaktif.</p>
            </div>
            <div>
                <a href="<?= url('/products/builder') ?>" class="btn btn-outline-success btn-sm rounded-pill w-100 fw-bold">
                    <?= sim_icon('ti-magic', 'me-1') ?> Buka Product Builder
                </a>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm bg-info-subtle d-flex gap-3 align-items-center">
    <div><?= sim_icon('ti-info-circle', 'fs-3') ?></div>
    <div class="small">
        <strong>Aturan Sentralisasi Data:</strong>
        Penambahan data master logistik (Bahan Baku Mentah & Sub-Resep) dilakukan di Sentral Data Setting ini. Sedangkan penambahan Menu Jualan (POS) beserta racikan komposisi dan kalkulasi margin profit dilakukan di modul dedicated <a href="<?= url('/products/builder') ?>" class="fw-bold text-decoration-underline text-info-emphasis">Product Builder</a>.
    </div>
</div>
