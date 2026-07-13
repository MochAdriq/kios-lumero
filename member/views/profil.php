<!-- ═══ PROFIL / DASHBOARD ═══ -->

<!-- Klaim Struk -->
<section class="section reveal reveal-delay-1">
  <div class="grid-2">
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
        Masukkan kode unik pada struk belanja kasir untuk mencairkan poin bonus ke akun VIP Anda.
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
            <div class="form-group"><label class="form-label">Nama Lengkap</label><input class="form-input" name="name" value="<?=mem_e($member['name'] ?? '')?>" placeholder="Nama member"></div>
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
          <button class="btn btn-red btn-full">Simpan & Ambil Bonus Poin</button>
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
