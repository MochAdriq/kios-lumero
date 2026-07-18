<?php
/** * @var array $trendRows @var array $experiments @var string $store */
include __DIR__ . '/../shared-flash.php';

// View helpers
$_e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$_m = fn($n) => rupiah((int)round($n));
$_p = fn($n) => number_format((float)$n, 2, ',', '.') . '%';
$_n = fn($n, $d = 0) => number_format((float)$n, $d, ',', '.');
$_months = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$_did = function($date) use ($_months) { 
    $ts = strtotime($date); 
    return $ts ? date('d',$ts).' '.($_months[date('m',$ts)] ?? date('m',$ts)).' '.date('Y',$ts) : $date; 
};
?>

<link rel="stylesheet" href="<?= asset('css/executive.css') ?>?v=001">

<div class="executive-dashboard-wrapper">
    <header class="ex-hero mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Inovasi & Tren Menu</h1>
            <p class="mb-0 text-muted">
                <?= $_e($store) ?> — Eksplorasi tren kuliner dan uji coba menu baru (A/B Testing 7 Hari).
            </p>
        </div>
        <div class="ex-hero-actions">
            <a class="btn btn-outline-secondary fw-bold shadow-sm" href="<?= url('/executive') ?>">
                &larr; Kembali ke Navigator
            </a>
        </div>
    </header>

    <section class="ex-grid-2 mb-4">
        <!-- Panel Tren Pasar & AI Translator -->
        <article class="ex-panel">
            <div class="ex-section-title d-flex justify-content-between align-items-start">
                <div>
                    <h2>Market Trend Intelligence</h2>
                    <div class="ex-hint">Rekomendasi adaptasi menu berdasarkan kata kunci tren pasar, margin, dan skor kecocokan.</div>
                </div>
            </div>
            
            <?php $topTrend = $trendRows[0] ?? null; if($topTrend): ?>
                <div class="ex-forecast w-100 bg-white shadow-sm border mb-4">
                    <small class="text-muted">Tren Teratas</small>
                    <h3 class="mb-1"><?= $_e($topTrend['keyword']) ?></h3>
                    <p class="mb-3 text-primary fw-bold">Adaptasi Lumero: <?= $_e($topTrend['product_idea'] ?: $topTrend['keyword']) ?></p>
                    
                    <div class="ex-cash-grid mb-3" style="grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <div class="ex-cashbox"><small>Est HPP</small><b><?= $_m($topTrend['base_hpp_estimate']) ?></b></div>
                        <div class="ex-cashbox"><small>Saran Harga</small><b><?= $_m($topTrend['suggested_price']) ?></b></div>
                        <div class="ex-cashbox"><small>Gross/Unit</small><b><?= $_m($topTrend['gross_profit_per_unit']) ?></b></div>
                        <div class="ex-cashbox"><small>Skor AI</small><b><?= $_n($topTrend['final_score'], 0) ?>/100</b></div>
                    </div>
                    
                    <div class="ex-hint bg-light p-2 rounded mb-3 text-dark">
                        <b>Rekomendasi:</b> Uji selama 7 hari dengan target 10-20 porsi/hari. Jadikan menu permanen hanya jika margin tercapai dan ada pesanan ulang.
                    </div>

                    <form method="post" action="<?= url('/innovation/experiment') ?>" class="ex-form d-flex flex-wrap gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="source_keyword" value="<?= $_e($topTrend['keyword']) ?>">
                        <label style="flex: 1 1 100%;">Eksperimen <input name="experiment_name" class="form-control form-control-sm" value="<?= $_e($topTrend['product_idea'] ?: $topTrend['keyword']) ?>"></label>
                        <label style="flex: 1 1 45%;">Target /Hari <input name="target_orders_per_day" inputmode="numeric" class="form-control form-control-sm" value="15"></label>
                        <label style="flex: 1 1 45%;">Margin % <input name="target_margin_pct" class="form-control form-control-sm" value="<?= $_n(max(35, $topTrend['margin_pct']), 1) ?>"></label>
                        <label style="flex: 1 1 45%;">Est HPP <input name="estimated_hpp" inputmode="numeric" class="form-control form-control-sm" value="<?= $_e($topTrend['base_hpp_estimate']) ?>"></label>
                        <label style="flex: 1 1 45%;">Harga Jual <input name="suggested_price" inputmode="numeric" class="form-control form-control-sm" value="<?= $_e($topTrend['suggested_price']) ?>"></label>
                        <button class="btn btn-sm btn-danger fw-bold w-100 mt-2">Buat Eksperimen 7 Hari Sekarang</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="ex-table-wrap">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Ide Trend / Keyword</th>
                            <th class="text-center">Skor</th>
                            <th class="ex-num">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$trendRows): ?>
                        <tr><td colspan="3" class="text-center text-muted">Belum ada data tren.</td></tr>
                        <?php endif; ?>
                        <?php foreach($trendRows as $t): ?>
                        <tr>
                            <td>
                                <b><?= $_e($t['product_idea'] ?: $t['keyword']) ?></b>
                                <div class="ex-hint text-muted"><?= $_e($t['keyword']) ?> &bull; <?= $_e($t['category']) ?></div>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge <?= ($t['final_score'] >= 70 ? 'bg-success' : ($t['final_score'] < 45 ? 'bg-danger' : 'bg-primary')) ?>">
                                    <?= $_n($t['final_score'], 0) ?>
                                </span>
                            </td>
                            <td class="ex-num align-middle">
                                <b><?= $_p($t['margin_pct']) ?></b>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Form Tambah Trend Baru -->
            <div class="mt-4 p-3 border rounded bg-light">
                <h5 class="mb-3 text-dark fw-bold">Masukan Tren Baru</h5>
                <form method="post" action="<?= url('/innovation/trend') ?>" class="ex-form d-flex flex-wrap gap-2">
                    <?= csrf_field() ?>
                    <label style="flex:2">Keyword (Mis. Seblak) <input name="keyword" class="form-control form-control-sm" required></label>
                    <label style="flex:3">Ide Adaptasi <input name="product_idea" class="form-control form-control-sm" placeholder="Mis. Seblak Crispy Level 5"></label>
                    <div class="w-100 d-flex gap-2">
                        <label style="flex:1">Est HPP <input name="base_hpp_estimate" inputmode="numeric" class="form-control form-control-sm" required></label>
                        <label style="flex:1">Saran Harga <input name="suggested_price" inputmode="numeric" class="form-control form-control-sm" required></label>
                    </div>
                    <div class="w-100 d-flex gap-2">
                        <label style="flex:1">Risiko Produksi (1-5) 
                            <select name="complexity_score" class="form-select form-select-sm">
                                <option value="1">1 - Sangat Mudah</option>
                                <option value="3" selected>3 - Sedang</option>
                                <option value="5">5 - Sangat Sulit</option>
                            </select>
                        </label>
                        <label style="flex:1">Cocok Stok (1-5) 
                            <select name="stock_fit_score" class="form-select form-select-sm">
                                <option value="1">1 - Beli Bahan Baru Semua</option>
                                <option value="3" selected>3 - Mix Bahan Lama & Baru</option>
                                <option value="5">5 - Pakai Stok Tersedia Semua</option>
                            </select>
                        </label>
                    </div>
                    <button class="btn btn-sm btn-dark w-100 mt-2">Simpan Tren</button>
                </form>
            </div>
        </article>

        <!-- Panel Eksperimen Menu -->
        <article class="ex-panel">
            <div class="ex-section-title d-flex justify-content-between align-items-start">
                <div>
                    <h2>Experiment Tracker (7 Hari)</h2>
                    <div class="ex-hint">Jalankan uji coba menu sebelum memutuskan untuk dipatenkan atau distop.</div>
                </div>
            </div>

            <?php if(!$experiments): ?>
                <div class="ex-empty text-center p-4 border rounded text-muted bg-light">Belum ada eksperimen menu berjalan.</div>
            <?php endif; ?>

            <div class="d-flex flex-column gap-3 mt-3">
                <?php foreach($experiments as $ex): ?>
                    <div class="border rounded p-3 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h4 class="mb-1 text-dark fw-bold"><?= $_e($ex['experiment_name']) ?></h4>
                                <small class="text-muted"><?= $_did($ex['start_date']) ?> s/d <?= $_did($ex['end_date']) ?></small>
                            </div>
                            <span class="badge <?= $ex['status'] === 'running' ? 'bg-primary' : ($ex['status'] === 'completed' ? 'bg-success' : 'bg-secondary') ?>">
                                <?= strtoupper($_e($ex['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="ex-cash-grid mb-3" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="ex-cashbox"><small>Est HPP</small><b><?= $_m($ex['estimated_hpp']) ?></b></div>
                            <div class="ex-cashbox"><small>Harga Jual</small><b><?= $_m($ex['suggested_price']) ?></b></div>
                            <div class="ex-cashbox"><small>Target Omzet/Hr</small><b><?= $_m($ex['suggested_price'] * $ex['target_orders_per_day']) ?></b></div>
                        </div>

                        <form method="post" action="<?= url('/innovation/experiment/update') ?>" class="ex-form p-2 bg-light rounded d-flex flex-wrap gap-2 align-items-end">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                            <label style="flex:1">Ubah Status
                                <select name="status" class="form-select form-select-sm">
                                    <option value="running" <?= $ex['status']==='running'?'selected':'' ?>>Running (Berjalan)</option>
                                    <option value="completed" <?= $ex['status']==='completed'?'selected':'' ?>>Completed (Selesai)</option>
                                    <option value="stopped" <?= $ex['status']==='stopped'?'selected':'' ?>>Stopped (Dihentikan)</option>
                                </select>
                            </label>
                            <label style="flex:1">Keputusan Akhir
                                <select name="decision" class="form-select form-select-sm">
                                    <option value="pending" <?= $ex['decision']==='pending'?'selected':'' ?>>Belum Ada Keputusan</option>
                                    <option value="make_permanent" <?= $ex['decision']==='make_permanent'?'selected':'' ?>>✓ Jadikan Menu Paten</option>
                                    <option value="continue_test" <?= $ex['decision']==='continue_test'?'selected':'' ?>>⏳ Perpanjang Uji Coba</option>
                                    <option value="stop" <?= $ex['decision']==='stop'?'selected':'' ?>>✗ Gagal, Hapus Menu</option>
                                </select>
                            </label>
                            <button class="btn btn-sm btn-dark">Update</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[inputmode="numeric"]').forEach(function(el){
        el.addEventListener('input', function() { 
            this.value = this.value.replace(/[^\d]/g, ''); 
        });
    });
});
</script>
