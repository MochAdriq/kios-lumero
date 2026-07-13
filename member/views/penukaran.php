<!-- ═══ PENUKARAN POIN ═══ -->

<!-- Katalog Header -->
<section class="section reveal reveal-delay-1">
  <div class="section-row" style="align-items:center;">
    <div class="section-header" style="margin-bottom:0;">
      <h2 class="section-title">Katalog Reward</h2>
      <p class="section-subtitle">Pilih menu reward impian Anda. Setelah diklaim, tunjukkan tiket digital ke kasir.</p>
    </div>
    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px 20px; text-align:right; white-space:nowrap; box-shadow:var(--shadow-sm);">
      <span style="font-size:11px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; display:block;">Saldo Poin</span>
      <strong style="font-size:22px; color:var(--red); font-weight:900; letter-spacing:-0.03em;"><?=number_format((int)$member['total_points'],0,',','.')?></strong>
    </div>
  </div>
</section>

<!-- Reward Grid -->
<section class="section reveal reveal-delay-2">
  <?php if($rewardProducts): ?>
  <div class="grid-auto">
    <?php foreach($rewardProducts as $r): 
      $need=(int)$r['required_points']; 
      $saldo=(int)$member['total_points']; 
      $img=trim((string)($r['image_url'] ?? '')); 
      if($img==='' && !empty($r['source_menu_image_url'])) $img=trim((string)$r['source_menu_image_url']); 
      $stockLimited=($r['stock_qty'] !== null && $r['stock_qty'] !== ''); 
      $stockHabis=$stockLimited && (int)$r['stock_qty']<=0; 
      $notEnough=$saldo<$need; 
      $disabled=$stockHabis || $notEnough; 
    ?>
    <div class="reward-card">
      <div class="reward-img">
        <img src="<?=mem_e(mem_reward_img_src($img, $r['name'] ?? ''))?>" alt="<?=mem_e($r['name'])?>">
        <?php if(!empty($r['category'])): ?>
          <span class="reward-img-cat"><?=mem_e($r['category'])?></span>
        <?php endif; ?>
        <span class="reward-img-badge"><?=number_format($need,0,',','.')?> Poin</span>
      </div>
      <div class="reward-body">
        <div>
          <h3 class="reward-name"><?=mem_e($r['name'])?></h3>
          <?php if(!empty($r['description'])): ?>
            <p class="reward-desc"><?=mem_e($r['description'])?></p>
          <?php endif; ?>
        </div>

        <?php if(!empty($r['terms'])): ?>
          <div class="reward-terms"><?=mem_e($r['terms'])?></div>
        <?php endif; ?>

        <form method="post" style="margin-top:auto; display:grid; gap:10px;">
          <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
          <input type="hidden" name="action" value="redeem_reward">
          <input type="hidden" name="reward_id" value="<?=(int)$r['id']?>">
          
          <?php if($stockHabis): ?>
            <div class="reward-status reward-status-err">Stok reward sedang habis</div>
          <?php elseif($notEnough): ?>
            <div class="reward-status reward-status-warn">Kurang <?=number_format($need-$saldo,0,',','.')?> poin lagi</div>
          <?php else: ?>
            <div class="reward-status reward-status-ok">Poin cukup untuk klaim</div>
          <?php endif; ?>

          <button class="btn btn-red btn-full" <?=$disabled?'disabled':''?>>Tukar Sekarang</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg>
      </div>
      <div class="empty-state-title">Belum Ada Produk Penukaran</div>
      <div class="empty-state-desc">Produk reward baru sedang disiapkan oleh admin. Cek kembali secara berkala.</div>
    </div>
  <?php endif; ?>
</section>

<!-- Riwayat Penukaran CTA -->
<section class="section reveal reveal-delay-3">
  <div class="card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <h3 style="font-size:18px; font-weight:900; letter-spacing:-0.02em;">Sudah Menukarkan Poin?</h3>
      <p style="font-size:13px; color:var(--muted); font-weight:600; margin-top:4px;">Buka halaman tiket digital Anda untuk ditunjukkan ke kasir saat pengambilan hadiah.</p>
    </div>
    <a class="btn btn-gold" href="redemption-history.php">Buka Riwayat Penukaran</a>
  </div>
</section>
