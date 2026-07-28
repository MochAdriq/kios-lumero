<!-- ═══ RIWAYAT TRANSAKSI & MUTASI POIN ═══ -->
<style>
.riwayat-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 32px;
}
@media (min-width: 992px) {
    .riwayat-grid {
        grid-template-columns: 1fr 1fr;
    }
}
.load-more-btn {
    width: 100%;
    padding: 12px;
    margin-top: 16px;
    background: transparent;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 12px;
    color: var(--ink);
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}
.load-more-btn:hover {
    background: rgba(0,0,0,0.03);
}
.hidden-row { display: none !important; }
</style>

<div class="riwayat-grid">
  <!-- KOLOM 1: Riwayat Belanja -->
  <div class="riwayat-col">
    <section class="section reveal reveal-delay-1">
      <div class="section-row">
        <div class="section-header">
          <h2 class="section-title">Riwayat Belanja <span class="section-badge badge-gold"><?=count($orders)?> Transaksi</span></h2>
          <p class="section-subtitle">Daftar pesanan dan poin yang Anda peroleh.</p>
        </div>
      </div>

      <div class="card card-static" style="padding:8px 28px;">
        <?php if($orders): ?>
          <div id="orders-container">
          <?php foreach($orders as $i => $o): ?>
          <div class="activity-item order-item <?= $i >= 5 ? 'hidden-row' : '' ?>">
            <div class="activity-left">
              <span class="activity-dot activity-dot-order"></span>
              <div>
                <div class="activity-title"><?=mem_e($o['order_no'])?></div>
                <div class="activity-desc">
                  <span class="badge" style="font-size:10px; padding:3px 8px;"><?=mem_e(strtoupper($o['payment_method']))?></span>
                  <?php if (($o['status'] ?? '') === 'pending'): ?>
                     <span class="badge" style="font-size:10px; padding:3px 8px; margin-left:4px; background:var(--dp-yellow); color:#fff;">MENUNGGU KASIR</span>
                  <?php endif; ?>
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
          </div>
          <?php if(count($orders) > 5): ?>
            <button id="btn-load-orders" class="load-more-btn">Tampilkan Lebih Banyak</button>
          <?php endif; ?>
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
  </div>

  <!-- KOLOM 2: Mutasi Poin -->
  <div class="riwayat-col">
    <section class="section reveal reveal-delay-2">
      <div class="section-row">
        <div class="section-header">
          <h2 class="section-title">Mutasi Poin <span class="section-badge badge-green"><?=count($logs)?> Aktivitas</span></h2>
          <p class="section-subtitle">Catatan masuk dan keluar poin loyalty Anda.</p>
        </div>
      </div>

      <div class="card card-static" style="padding:8px 28px;">
        <?php if($logs): ?>
          <div id="logs-container">
          <?php foreach($logs as $i => $l): $isIn = ((int)$l['points_in'] > 0); ?>
          <div class="activity-item log-item <?= $i >= 5 ? 'hidden-row' : '' ?>">
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
          </div>
          <?php if(count($logs) > 5): ?>
            <button id="btn-load-logs" class="load-more-btn">Tampilkan Lebih Banyak</button>
          <?php endif; ?>
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
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupLoadMore(btnId, itemClass, step) {
        const btn = document.getElementById(btnId);
        if(!btn) return;
        let visibleCount = step;
        btn.addEventListener('click', function() {
            const items = document.querySelectorAll(itemClass);
            let shown = 0;
            for(let i = visibleCount; i < visibleCount + step && i < items.length; i++) {
                items[i].classList.remove('hidden-row');
                shown++;
            }
            visibleCount += step;
            if(visibleCount >= items.length) {
                btn.style.display = 'none';
            }
        });
    }
    
    setupLoadMore('btn-load-orders', '.order-item', 5);
    setupLoadMore('btn-load-logs', '.log-item', 5);
});
</script>
