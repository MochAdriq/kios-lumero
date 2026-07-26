<?php include __DIR__.'/../shared-flash.php'; ?>
<div class="sim-hero mb-4"><div><span class="sim-kicker">System Configuration</span><h2>Setting Sistem</h2><p>Profil outlet, service charge, printer, payment gateway, dan konfigurasi umum.</p></div></div>
<form method="post" action="<?= url('/settings') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="sim-card">
                <h5>Profil Outlet</h5>
                <label class="form-label mt-2">Nama Outlet</label>
                <input name="outlet_name" value="<?= htmlspecialchars($outlet['name']??'') ?>" class="form-control">
                
                <label class="form-label mt-2">Alamat</label>
                <textarea name="outlet_address" class="form-control" rows="3"><?= htmlspecialchars($outlet['address']??'') ?></textarea>
                
                <label class="form-label mt-2">Telepon</label>
                <input name="outlet_phone" value="<?= htmlspecialchars($outlet['phone']??'') ?>" class="form-control">
            </div>
            
            <div class="sim-card mt-4">
                <h5>Pengaturan Pembayaran QRIS</h5>
                <label class="form-label mt-2">Metode Pembayaran QRIS Aktif</label>
                <select name="settings[qris_payment_method]" class="form-select">
                    <option value="manual" <?= ($settings['qris_payment_method']??'manual')==='manual'?'selected':'' ?>>Manual (QRIS Statis / Transfer)</option>
                    <option value="midtrans" <?= ($settings['qris_payment_method']??'')==='midtrans'?'selected':'' ?>>Otomatis (Midtrans)</option>
                </select>
                <div class="form-text">Pilih 'Otomatis' jika Anda ingin Midtrans langsung memproses QRIS. Pilih 'Manual' jika menggunakan gambar QRIS statis di bawah ini.</div>

                <label class="form-label mt-3">Gambar QRIS Statis (Manual)</label>
                <?php if (!empty($settings['payment_qris_image'])): 
                    $qrisPath = $settings['payment_qris_image'];
                    if (str_starts_with($qrisPath, 'public/assets/')) {
                        $qrisUrl = asset(substr($qrisPath, 14));
                    } else {
                        $qrisUrl = url('/' . ltrim($qrisPath, '/'), false);
                    }
                ?>
                    <div class="mb-2">
                        <img src="<?= $qrisUrl ?>" alt="QRIS" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="qris_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">Unggah gambar QRIS statis toko Anda untuk metode pembayaran Manual. Biarkan kosong jika tidak ingin mengubah.</div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="sim-card">
                <h5>Konfigurasi Operasional</h5>
                <label class="form-label mt-2">Service Charge Percent</label>
                <input name="settings[service_charge_percent]" value="<?= htmlspecialchars($settings['service_charge_percent']??'0') ?>" class="form-control">
                
                <label class="form-label mt-2">Printer Enabled</label>
                <select name="settings[printer_enabled]" class="form-select">
                    <option value="true" <?= ($settings['printer_enabled']??'true')==='true'?'selected':'' ?>>Aktif</option>
                    <option value="false" <?= ($settings['printer_enabled']??'')==='false'?'selected':'' ?>>Nonaktif</option>
                </select>
                

            </div>
        </div>
    </div>
    </div>
    
    <?php
    $midtrans = null;
    foreach($gateways as $g) {
        if ($g['provider'] === 'midtrans') {
            $midtrans = $g;
            break;
        }
    }
    ?>
    <div class="sim-card mt-4">
        <h5>Gateway Config (Midtrans)</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Client Key</label>
                <input type="text" name="gateway[midtrans][client_id]" value="<?= htmlspecialchars($midtrans['client_id']??'') ?>" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Server Key</label>
                <input type="text" name="gateway[midtrans][client_secret]" value="<?= htmlspecialchars($midtrans['client_secret']??'') ?>" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Merchant ID</label>
                <input type="text" name="gateway[midtrans][merchant_id]" value="<?= htmlspecialchars($midtrans['merchant_id']??'') ?>" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Mode</label>
                <select name="gateway[midtrans][mode]" class="form-select">
                    <option value="sandbox" <?= ($midtrans['mode']??'sandbox')==='sandbox'?'selected':'' ?>>Sandbox</option>
                    <option value="production" <?= ($midtrans['mode']??'')==='production'?'selected':'' ?>>Production</option>
                </select>
            </div>
        </div>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="gateway[midtrans][is_active]" value="1" <?= (!empty($midtrans['is_active']))?'checked':'' ?> id="midtransActive">
            <label class="form-check-label" for="midtransActive">Aktifkan Midtrans untuk Outlet Ini</label>
        </div>
    </div>

    <button class="btn btn-danger rounded-pill px-5 mt-4">Simpan Setting</button>
</form>
