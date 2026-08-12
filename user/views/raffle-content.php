<?php
/* ── raffle-content.php ──────────────────────────────────────────────────────
   Konten halaman Event Undian — di-render di dalam layout.php (bukan standalone).
   Variabel yang tersedia dari layout scope:
     $member, $memberId, $pdo, $flashOk, $flashErr, $activeBatch, $prizes, $myTickets, $bal
   ──────────────────────────────────────────────────────────────────────────── */
?>

<div class="section reveal">
  <div class="section-header">
    <div class="section-row">
      <div>
        <div class="section-title">🎫 Event Undian</div>
        <div class="section-subtitle">Tukar poin, raih hadiah impian! 10 Poin = 1 Tiket</div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:4px">Saldo Poin Anda</div>
        <div style="font-size:28px;font-weight:900;color:var(--ink);letter-spacing:-0.03em"><?= number_format($bal) ?></div>
        <div style="font-size:12px;font-weight:600;color:var(--muted)">poin</div>
      </div>
    </div>
  </div>

  <?php if ($flashOk): ?>
    <div class="alert alert-ok">✅ <?= htmlspecialchars($flashOk) ?></div>
  <?php endif ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-err">⚠️ <?= htmlspecialchars($flashErr) ?></div>
  <?php endif ?>

  <?php if (!$activeBatch): ?>

    <div class="empty-state">
      <div class="empty-state-icon">🎯</div>
      <div class="empty-state-title">Belum Ada Event Aktif</div>
      <div class="empty-state-desc">Terus kumpulkan poin dan nantikan event undian berikutnya!</div>
    </div>

  <?php else: ?>

    <?php /* ── Info Event ───────────────────────────────── */ ?>
    <div class="card card-static" style="margin-bottom:24px;border-left:4px solid #f59e0b;border-radius:var(--radius)">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="font-size:36px;flex-shrink:0">🎉</div>
        <div>
          <span class="badge badge-gold" style="margin-bottom:6px">Sedang Berlangsung</span>
          <div style="font-size:17px;font-weight:900;color:var(--ink);letter-spacing:-0.02em"><?= htmlspecialchars($activeBatch['name']) ?></div>
          <div style="font-size:13px;font-weight:600;color:var(--muted);margin-top:2px">
            Berakhir: <?= date('d F Y', strtotime($activeBatch['end_date'])) ?>
          </div>
        </div>
      </div>
    </div>

    <?php /* ── Hadiah ───────────────────────────────────── */ ?>
    <?php if (!empty($prizes)): ?>
    <div class="section">
      <div class="section-header">
        <div class="section-title" style="font-size:18px">🎁 Daftar Hadiah</div>
      </div>
      <div class="grid-auto">
        <?php foreach ($prizes as $p): ?>
        <div class="reward-card">
          <div class="reward-img">
            <?php if (!empty($p['image_url'])): ?>
              <img src="../public/assets/<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <?php else: ?>
              <div style="font-size:48px">🎁</div>
            <?php endif ?>
          </div>
          <div class="reward-body">
            <div class="reward-name"><?= htmlspecialchars($p['name']) ?></div>
            <?php if (!empty($p['description'])): ?>
              <div class="reward-desc"><?= htmlspecialchars($p['description']) ?></div>
            <?php endif ?>
          </div>
        </div>
        <?php endforeach ?>
      </div>
    </div>
    <?php endif ?>

    <?php /* ── Form Tukar Tiket ─────────────────────────── */ ?>
    <div class="section">
      <div class="section-header">
        <div class="section-title" style="font-size:18px">🔄 Tukar Poin ke Tiket</div>
        <div class="section-subtitle">Poin yang ditukar tidak dapat dikembalikan</div>
      </div>
      <div class="card card-static">
        <form method="POST" action="raffle.php" onsubmit="return confirm('Poin yang ditukar tidak bisa dikembalikan. Lanjutkan?')">
          <div class="form-grid">
            <div class="grid-3" style="gap:16px">
              <button type="submit" name="qty" value="1" class="btn btn-outline" <?= $bal < 10  ? 'disabled' : '' ?> style="flex-direction:column;height:80px;gap:4px">
                <span style="font-size:18px">🎫</span>
                <span style="font-size:13px;font-weight:900">1 Tiket</span>
                <span style="font-size:11px;color:var(--muted);font-weight:700">10 Poin</span>
              </button>
              <button type="submit" name="qty" value="5" class="btn btn-gold" <?= $bal < 50  ? 'disabled' : '' ?> style="flex-direction:column;height:80px;gap:4px">
                <span style="font-size:18px">🎫🎫</span>
                <span style="font-size:13px;font-weight:900">5 Tiket</span>
                <span style="font-size:11px;font-weight:700;opacity:0.8">50 Poin</span>
              </button>
              <button type="submit" name="qty" value="10" class="btn btn-red" <?= $bal < 100 ? 'disabled' : '' ?> style="flex-direction:column;height:80px;gap:4px">
                <span style="font-size:18px">🎫🎫🎫</span>
                <span style="font-size:13px;font-weight:900">10 Tiket</span>
                <span style="font-size:11px;font-weight:700;opacity:0.8">100 Poin</span>
              </button>
            </div>
            <?php if ($bal < 10): ?>
              <div class="alert alert-err" style="margin-top:8px;margin-bottom:0">
                Poin Anda belum cukup untuk mendapatkan tiket. Terus belanja untuk kumpulkan poin!
              </div>
            <?php endif ?>
          </div>
        </form>
      </div>
    </div>

    <?php /* ── Tiket Saya ───────────────────────────────── */ ?>
    <div class="section">
      <div class="section-header">
        <div class="section-row">
          <div class="section-title" style="font-size:18px">🎫 Tiket Saya</div>
          <span class="badge"><?= count($myTickets) ?> tiket</span>
        </div>
      </div>
      <?php if (empty($myTickets)): ?>
        <div class="empty-state" style="padding:40px 24px">
          <div class="empty-state-icon">🎫</div>
          <div class="empty-state-title">Belum Ada Tiket</div>
          <div class="empty-state-desc">Tukar poin di atas untuk mendapatkan tiket undian!</div>
        </div>
      <?php else: ?>
        <div class="card card-static" style="padding:0;overflow:hidden">
          <?php foreach ($myTickets as $t): ?>
          <div class="activity-item" style="padding:16px 24px">
            <div class="activity-left">
              <div class="activity-dot" style="background:#f59e0b;box-shadow:0 0 0 4px rgba(245,158,11,0.1)"></div>
              <div>
                <div class="activity-title" style="font-family:'Courier New',monospace;letter-spacing:.05em">
                  <?= htmlspecialchars($t['ticket_code']) ?>
                </div>
                <div class="activity-desc">Tiket Undian — <?= htmlspecialchars($activeBatch['name']) ?></div>
              </div>
            </div>
            <div class="activity-right">
              <div class="activity-date"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></div>
            </div>
          </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>

  <?php endif ?>
</div>
