<!-- ═══ PROFIL / DASHBOARD ═══ -->

<?php if (!empty($pendingGrandOpeningClaim)): ?>
<div style="background: linear-gradient(135deg, #c41230 0%, #ffc72c 100%); border-radius: 24px; padding: 24px; margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; color: #fff; box-shadow: 0 10px 30px rgba(196,18,48,0.3);">
    <div>
        <h3 style="margin:0 0 8px; font-size:20px; font-weight:800;">🎉 Anda Punya Kupon Grand Opening!</h3>
        <p style="margin:0; font-size:14px; opacity:0.9;">Tukarkan segera di Outlet Kalibunder sebelum hangus.</p>
    </div>
    <a href="reward-claim.php?id=<?= $pendingGrandOpeningClaim['id'] ?>" class="btn" style="background:#fff; color:#c41230; padding:12px 24px; border-radius:99px; text-decoration:none; font-weight:800;">LIHAT KUPON</a>
</div>
<?php endif; ?>
<?php if ($profilePercent === 0 || $profilePercent < 40): ?>
<!-- ── ONBOARDING BANNER (Member Baru / Profil Kosong) ── -->
<style>
.onboard-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    border-radius: 24px; padding: 28px 32px;
    margin-bottom: 32px; position: relative; overflow: hidden;
    display: flex; align-items: center; gap: 24px;
    animation: slideDownOnboard 0.6s cubic-bezier(0.16,1,0.3,1) both;
}
.onboard-banner::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255,199,44,0.06) 50%, transparent 100%);
    background-size: 200% 100%;
    animation: shimmerOnboard 3s linear infinite;
}
@keyframes shimmerOnboard {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
@keyframes slideDownOnboard {
    from { opacity: 0; transform: translateY(-16px); }
    to { opacity: 1; transform: translateY(0); }
}
.onboard-icon {
    width: 64px; height: 64px; flex-shrink: 0;
    background: linear-gradient(135deg, #ffc72c 0%, #f59e0b 100%);
    border-radius: 20px; display: flex; align-items: center;
    justify-content: center; font-size: 28px;
    box-shadow: 0 8px 24px rgba(255,199,44,0.3);
    position: relative; z-index: 1;
    animation: bounceIcon 2.5s ease-in-out infinite;
}
@keyframes bounceIcon {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
.onboard-content { flex: 1; position: relative; z-index: 1; }
.onboard-tag {
    display: inline-block; font-size: 10px; font-weight: 900;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: #ffc72c; margin-bottom: 6px;
}
.onboard-title {
    font-size: 18px; font-weight: 900; color: #fff;
    letter-spacing: -0.02em; line-height: 1.2; margin-bottom: 8px;
}
.onboard-desc {
    font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.6; font-weight: 500;
}
.onboard-progress {
    margin-top: 12px;
    background: rgba(255,255,255,0.1); border-radius: 99px; height: 6px; overflow: hidden;
}
.onboard-progress-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, #ffc72c, #f59e0b);
    width: <?= $profilePercent ?>%;
    box-shadow: 0 0 10px rgba(255,199,44,0.5);
    transition: width 1s ease;
}
.onboard-progress-label {
    display: flex; justify-content: space-between;
    font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 6px; font-weight: 600;
}
@media (max-width: 640px) {
    .onboard-banner { flex-direction: column; text-align: center; }
    .onboard-icon { width: 56px; height: 56px; font-size: 24px; margin: 0 auto; }
}
</style>
<div class="onboard-banner">
    <div class="onboard-icon">🔐</div>
    <div class="onboard-content">
        <div class="onboard-tag">⚡ Satu Langkah Lagi</div>
        <div class="onboard-title">Lengkapi Profil untuk Membuka Bonus <?= $bonusPoints ?> Poin!</div>
        <div class="onboard-desc">
            Profil Anda masih <?= $profilePercent ?>% terisi. Isi data di bawah ini agar poin kamu langsung bertambah dan akun kamu lebih aman.
        </div>
        <div class="onboard-progress">
            <div class="onboard-progress-fill"></div>
        </div>
        <div class="onboard-progress-label">
            <span>Progres <?= $profilePercent ?>%</span>
            <span>Bonus +<?= $bonusPoints ?> Poin menanti!</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Klaim Struk -->
<section class="section reveal reveal-delay-1">
  <?php if (!empty($isNewMember)): ?>
  <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:16px; padding:20px; margin-bottom:24px; display:flex; gap:16px; align-items:flex-start;">
    <div style="font-size:24px;">⚠️</div>
    <div>
      <h4 style="margin:0 0 8px; color:#991b1b; font-size:16px; font-weight:800;">Keamanan Akun (Wajib)</h4>
      <p style="margin:0; color:#b91c1c; font-size:14px; line-height:1.5;">Anda masih menggunakan Profil Default. Silakan <strong>Ubah Nama Lengkap</strong> dan atur <strong>PIN Baru</strong> Anda sebelum dapat mengakses fitur Loyalty Lumero lainnya (seperti Klaim Struk dan Undian).</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid-2" <?= !empty($isNewMember) ? 'style="grid-template-columns: 1fr;"' : '' ?>>
    <?php if (empty($isNewMember)): ?>
    <div class="card card-static">
      <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
        <div style="width:48px; height:48px; border-radius:14px; background:var(--gold-bg); border:1px solid var(--gold-border); display:flex; align-items:center; justify-content:center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 000-4V7a2 2 0 012-2z"/></svg>
        </div>
        <div>
          <h3 style="font-size:18px; font-weight:900; letter-spacing:-0.02em;">Klaim Poin Struk</h3>
          <span style="font-size:12px; font-weight:700; color:var(--gold2);">Voucher Poin Belanja</span>
        </div>
      </div>
      <p style="font-size:13px; color:var(--muted); font-weight:600; line-height:1.6; margin-bottom:24px;">
        Masukkan kode unik pada struk belanja kasir untuk mencairkan poin bonus ke akun Member Anda.
      </p>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
        <input type="hidden" name="action" value="claim">
        <div class="form-group">
          <label class="form-label">Kode Struk Belanja</label>
          <input class="form-input" name="claim_code" value="<?=mem_e($prefillClaim)?>" required placeholder="CLM-XXXXXX" style="text-transform:uppercase; letter-spacing:0.06em; text-align:center; font-size:17px;">
        </div>
        <button class="btn btn-red btn-full">Klaim Poin Sekarang</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Profil -->
    <div class="card card-static">
      <?php if(!$profileComplete): ?>
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
          <div style="width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg, var(--red), var(--red2)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:900;">
            <?=mem_e(strtoupper(substr($member['name'] ?: 'M',0,1)))?>
          </div>
          <div>
            <h3 style="font-size:18px; font-weight:900; letter-spacing:-0.02em;">Lengkapi Identitas</h3>
            <span class="badge badge-green" style="margin-top:4px;">Bonus +<?=$bonusPoints?> Poin</span>
          </div>
        </div>
        <p style="font-size:13px; color:var(--muted); font-weight:600; line-height:1.6; margin-bottom:24px;">
          Lengkapi data diri Anda untuk keamanan akun dan kejutan spesial di hari ulang tahun.
        </p>
        <form method="post" class="form-grid">
          <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Nama Lengkap</label><input class="form-input" name="name" value="<?=mem_e($member['name'] ?? '')?>" placeholder="Nama member" <?= !empty($isNewMember) ? 'required' : '' ?>></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" name="email" type="email" value="<?=mem_e($member['email'] ?? '')?>" placeholder="email@contoh.com"></div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Jenis Kelamin</label>
              <select class="form-input" name="gender"><option value="">Pilih</option><option value="pria" <?=($member['gender']??'')==='pria'?'selected':''?>>Pria</option><option value="wanita" <?=($member['gender']??'')==='wanita'?'selected':''?>>Wanita</option><option value="lainnya" <?=($member['gender']??'')==='lainnya'?'selected':''?>>Lainnya</option></select>
            </div>
            <div class="form-group"><label class="form-label">Tanggal Lahir</label><input class="form-input" name="birth_date" type="date" value="<?=mem_e($member['birth_date'] ?? '')?>"></div>
          </div>
          <div class="form-group"><label class="form-label">Alamat Domisili</label><textarea class="form-input" name="address" placeholder="Kota & alamat lengkap"><?=mem_e($member['address'] ?? '')?></textarea></div>
          
          <hr style="border:0; border-top:1px dashed var(--border); margin:24px 0;">
          
          <h4 style="font-size:16px; font-weight:800; margin-bottom:16px;">Ganti PIN (Opsional / Wajib jika baru)</h4>
          <div class="form-row">
            <div class="form-group"><label class="form-label">PIN Baru (Min. 4 Angka)</label><input class="form-input" name="pin" type="password" placeholder="Kosongkan jika tidak ingin ganti" <?= !empty($isNewMember) ? 'required' : '' ?>></div>
            <div class="form-group"><label class="form-label">Konfirmasi PIN</label><input class="form-input" name="pin_confirm" type="password" placeholder="Ulangi PIN Baru" <?= !empty($isNewMember) ? 'required' : '' ?>></div>
          </div>

          <button class="btn btn-red btn-full" style="margin-top:12px;">Simpan Profil & PIN</button>
        </form>

      <?php else: ?>
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:28px;">
          <div style="width:56px; height:56px; border-radius:18px; background:linear-gradient(135deg, var(--red), var(--red2)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:900;">
            <?=mem_e(strtoupper(substr($member['name'] ?: 'M',0,1)))?>
          </div>
          <div>
            <h3 style="font-size:20px; font-weight:900; letter-spacing:-0.02em;"><?=mem_e($member['name'] ?: 'Member Setia')?></h3>
            <span class="badge badge-green" style="margin-top:6px;">Profil Terverifikasi</span>
          </div>
        </div>
        <table class="data-table">
          <tr><th>Nomor WhatsApp</th><td style="font-weight:800;"><?=mem_e(loyalty_mask_phone($member['phone']))?></td></tr>
          <tr><th>Email</th><td><?=mem_e($member['email'] ?: '-')?></td></tr>
          <tr><th>Tanggal Lahir</th><td><?=!empty($member['birth_date']) ? mem_e(date('d F Y',strtotime($member['birth_date']))) : '-'?></td></tr>
          <tr><th>Alamat</th><td style="line-height:1.5;"><?=mem_e($member['address'] ?: '-')?></td></tr>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>
