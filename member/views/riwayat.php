<!-- ═══ RIWAYAT TRANSAKSI & MUTASI POIN ═══ -->

<!-- Riwayat Belanja -->
<section class="section reveal reveal-delay-1">
  <div class="section-row">
    <div class="section-header">
      <h2 class="section-title">Riwayat Belanja <span class="section-badge badge-gold"><?=count($orders)?> Transaksi</span></h2>
      <p class="section-subtitle">Daftar pesanan dan poin yang Anda peroleh dari setiap transaksi.</p>
    </div>
  </div>

  <div class="card card-static" style="padding:8px 28px;">
    <?php if($orders): ?>
      <?php foreach($orders as $i => $o): ?>
      <div class="activity-item">
        <div class="activity-left">
          <span class="activity-dot activity-dot-order"></span>
          <div>
            <div class="activity-title"><?=mem_e($o['order_no'])?></div>
            <div class="activity-desc">
              <span class="badge" style="font-size:10px; padding:3px 8px;"><?=mem_e(strtoupper($o['payment_method']))?></span>
            </div>
            <div class="activity-date"><?=mem_e(date('d M Y, H:i', strtotime($o['created_at'])))?></div>
          </div>
        </div>
        <div class="activity-right">
          <div style="font-size:15px; font-weight:900; color:var(--ink);"><?=mem_money($o['total'])?></div>
          <?php if((int)($o['loyalty_points_earned'] ?? 0) > 0): ?>
            <span class="badge badge-green" style="font-size:11px; margin-top:6px;">+<?=(int)$o['loyalty_points_earned']?> Poin</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state" style="border:none; margin:24px 0;">
        <div class="empty-state-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        </div>
        <div class="empty-state-title">Belum ada riwayat belanja</div>
        <div class="empty-state-desc">Lakukan transaksi pertama di outlet Lumero dan nikmati poin rewardnya.</div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Mutasi Poin -->
<section class="section reveal reveal-delay-2">
  <div class="section-row">
    <div class="section-header">
      <h2 class="section-title">Mutasi Poin <span class="section-badge badge-green"><?=count($logs)?> Aktivitas</span></h2>
      <p class="section-subtitle">Catatan masuk dan keluar poin loyalty Anda.</p>
    </div>
  </div>

  <div class="card card-static" style="padding:8px 28px;">
    <?php if($logs): ?>
      <?php foreach($logs as $l): $isIn = ((int)$l['points_in'] > 0); ?>
      <div class="activity-item">
        <div class="activity-left">
          <span class="activity-dot <?=$isIn ? 'activity-dot-in' : 'activity-dot-out'?>"></span>
          <div>
            <div class="activity-title"><?=mem_e($l['type'])?></div>
            <div class="activity-desc"><?=mem_e($l['description'])?></div>
            <div class="activity-date"><?=mem_e(date('d M Y, H:i', strtotime($l['created_at'])))?></div>
          </div>
        </div>
        <div class="activity-right">
          <div class="activity-amount <?=$isIn ? 'positive' : 'negative'?>">
            <?=$isIn ? ('+' . (int)$l['points_in']) : ('-' . (int)$l['points_out'])?> Poin
          </div>
          <div class="activity-balance">Saldo: <?=(int)$l['balance_after']?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state" style="border:none; margin:24px 0;">
        <div class="empty-state-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="empty-state-title">Belum ada riwayat poin</div>
        <div class="empty-state-desc">Poin yang Anda dapatkan atau tukarkan akan otomatis tercatat di sini.</div>
      </div>
    <?php endif; ?>
  </div>
</section>
