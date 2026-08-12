<style>
/* ── Base ──────────────────────────────────────────────────────── */
.back-link {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 13px; font-weight: 700; color: #64748b;
  text-decoration: none; margin-bottom: 28px;
  padding: 8px 16px; border-radius: 99px; background: #f1f5f9;
  transition: background 0.2s, color 0.2s;
}
.back-link:hover { background: #e2e8f0; color: #334155; }

/* ── Page Header ───────────────────────────────────────────────── */
.batch-header {
  display: flex; justify-content: space-between;
  align-items: flex-start; gap: 16px; margin-bottom: 32px;
}
.batch-title {
  font-size: 26px; font-weight: 900; letter-spacing: -0.03em;
  color: #0f172a; margin-bottom: 6px;
}
.batch-period { font-size: 13px; color: #64748b; font-weight: 600; }
.status-pill {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 12px; font-weight: 800; padding: 8px 16px;
  border-radius: 99px; white-space: nowrap; flex-shrink: 0;
}
.status-active   { background: #dcfce7; color: #15803d; }
.status-completed { background: #e0e7ff; color: #4338ca; }
.status-draft    { background: #fef9c3; color: #854d0e; }

/* ── Stats Row ─────────────────────────────────────────────────── */
.stats-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 16px; margin-bottom: 32px;
}
.stat-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 20px; padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.stat-icon {
  width: 40px; height: 40px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 14px;
}
.stat-value {
  font-size: 28px; font-weight: 900; letter-spacing: -0.03em;
  color: #0f172a; margin-bottom: 4px;
}
.stat-label { font-size: 13px; font-weight: 600; color: #64748b; }

/* ── Prize Section ─────────────────────────────────────────────── */
.prize-section {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 24px; overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.prize-section-hd {
  display: flex; justify-content: space-between; align-items: center;
  padding: 22px 28px; border-bottom: 1px solid #f3f4f6;
}
.prize-section-title { font-size: 16px; font-weight: 900; color: #0f172a; }
.btn-add {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 12px;
  background: #c41230; color: #fff; font-size: 13px; font-weight: 800;
  border: none; cursor: pointer; transition: background 0.2s;
}
.btn-add:hover { background: #a00e27; }
.prize-table { width: 100%; border-collapse: collapse; }
.prize-table th {
  font-size: 10px; font-weight: 800; color: #94a3b8;
  text-transform: uppercase; letter-spacing: 0.08em;
  padding: 11px 28px; text-align: left; border-bottom: 1px solid #f3f4f6;
}
.prize-table td {
  padding: 18px 28px; border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}
.prize-table tr:last-child td { border-bottom: none; }
.prize-thumb {
  width: 54px; height: 54px; border-radius: 12px;
  object-fit: cover; border: 1px solid #f3f4f6;
}
.prize-thumb-placeholder {
  width: 54px; height: 54px; border-radius: 12px;
  background: #f8fafc; border: 1px solid #e5e7eb;
  display: flex; align-items: center; justify-content: center;
}
.prize-name-cell { font-size: 15px; font-weight: 800; color: #0f172a; }
.winner-badge {
  display: inline-block; background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 12px; padding: 10px 14px;
}
.winner-badge-name { font-size: 14px; font-weight: 800; color: #15803d; }
.winner-badge-detail {
  font-size: 11px; font-weight: 600;
  color: #4ade80; margin-top: 3px; font-family: 'Courier New', monospace;
}
.no-winner-text { font-size: 13px; color: #94a3b8; font-weight: 600; }
.action-group { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }
.btn-sm-action {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 13px; border-radius: 10px;
  font-size: 12px; font-weight: 700; border: none; cursor: pointer;
  transition: background 0.2s;
}
.btn-edit   { background: #f1f5f9; color: #475569; }
.btn-edit:hover { background: #e2e8f0; }
.btn-delete { background: #fff1f2; color: #be123c; }
.btn-delete:hover { background: #ffe4e6; }
.btn-draw {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 12px; margin-top: 6px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff; font-size: 13px; font-weight: 800; border: none;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 16px rgba(245,158,11,0.28);
}
.btn-draw:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(245,158,11,0.38); }
.empty-prize-row td {
  text-align: center; padding: 56px 28px; color: #94a3b8;
}
.empty-prize-label { font-size: 15px; font-weight: 700; color: #64748b; margin-top: 14px; }
.empty-prize-sub   { font-size: 13px; font-weight: 600; color: #94a3b8; margin-top: 5px; }

/* ── Rolling Overlay ───────────────────────────────────────────── */
.roll-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(4, 4, 12, 0.97);
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
}
.roll-overlay[hidden] { display: none; }

.roll-panel {
  position: relative; z-index: 2;
  width: min(540px, 92vw); text-align: center;
}
.roll-eyebrow {
  font-size: 11px; font-weight: 900; letter-spacing: 0.14em;
  text-transform: uppercase; color: #f59e0b; margin-bottom: 8px;
}
.roll-heading {
  font-size: 26px; font-weight: 900; color: #fff;
  letter-spacing: -0.025em; margin-bottom: 36px;
}
.roll-prize-label {
  font-size: 14px; font-weight: 700;
  color: rgba(255,255,255,0.5); margin-bottom: 16px;
}
.roll-prize-name {
  font-size: 20px; font-weight: 900; color: #fff;
  margin-bottom: 28px; letter-spacing: -0.02em;
}

/* Slot machine window */
.roll-machine {
  position: relative; height: 100px; border-radius: 20px;
  overflow: hidden; margin: 0 auto 12px;
  background: rgba(255,255,255,0.04);
  border: 2px solid rgba(255,255,255,0.1);
}
@keyframes pulse {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.95); }
  100% { opacity: 1; transform: scale(1); }
}
.spotlight {
  position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
  background: conic-gradient(from 0deg at 50% 50%, rgba(255,255,255,0) 0%, rgba(245,158,11,0.06) 10%, rgba(255,255,255,0) 20%);
  animation: rotateSpotlight 10s linear infinite; pointer-events: none; z-index: -1;
}
@keyframes rotateSpotlight { 100% { transform: rotate(360deg); } }
.giant-countdown {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-size: 220px; font-weight: 900; color: #fff; z-index: 9999;
  text-shadow: 0 0 80px rgba(245,158,11,0.8); opacity: 0; pointer-events: none;
}
@keyframes popIn { 
  0% { transform: scale(0.3); opacity: 0; } 
  30% { transform: scale(1.1); opacity: 1; } 
  80% { transform: scale(1); opacity: 1; } 
  100% { transform: scale(0.9); opacity: 0; } 
}
.roll-strip {
  display: flex; flex-direction: column;
  will-change: transform;
}
.roll-strip.spinning {
  animation: spinSlot 0.25s linear infinite;
  filter: blur(1px);
}
@keyframes spinSlot {
  0% { transform: translateY(0); }
  100% { transform: translateY(calc(-100% + 100px)); }
}
.roll-strip.stopping {
  transition: transform 3.8s cubic-bezier(0.12, 0.85, 0.2, 1);
}
.roll-item {
  height: 100px; display: flex; align-items: center; justify-content: center;
  font-family: 'Courier New', monospace; font-size: 32px; font-weight: 900;
  color: #fff; letter-spacing: 0.05em; flex-shrink: 0;
}
.roll-item.winner-item { color: #f59e0b; font-size: 38px; text-shadow: 0 0 20px rgba(245,158,11,0.5); }
.roll-mask {
  position: absolute; left: 0; right: 0;
  height: 38px; pointer-events: none; z-index: 2;
}
.roll-mask-top    { top: 0;    background: linear-gradient(to bottom, rgba(4,4,12,0.9), transparent); }
.roll-mask-bottom { bottom: 0; background: linear-gradient(to top,   rgba(4,4,12,0.9), transparent); }

.btn-start-roll {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 16px 48px; border-radius: 16px;
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: #fff; font-size: 16px; font-weight: 900; letter-spacing: 0.08em;
  border: none; cursor: pointer;
  box-shadow: 0 8px 32px rgba(22, 163, 74, 0.3);
  transition: transform 0.2s, box-shadow 0.2s;
  text-transform: uppercase; margin-bottom: 20px;
}
.btn-start-roll:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(22, 163, 74, 0.45); }
.btn-start-roll:active { transform: translateY(1px); }

.roll-status {
  font-size: 13px; font-weight: 700;
  color: rgba(255,255,255,0.45); margin-top: 12px; margin-bottom: 32px;
  min-height: 20px;
}
.roll-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 32px; }
.roll-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: rgba(255,255,255,0.2);
  animation: dotPulse 1.4s ease-in-out infinite;
}
.roll-dot:nth-child(2) { animation-delay: 0.22s; }
.roll-dot:nth-child(3) { animation-delay: 0.44s; }
@keyframes dotPulse {
  0%, 100% { background: rgba(255,255,255,0.18); transform: scale(1); }
  50%       { background: #f59e0b; transform: scale(1.35); }
}

/* Winner reveal */
.winner-reveal { display: none; }
.winner-reveal.visible {
  display: block;
  animation: revealIn 0.65s cubic-bezier(0.16,1,0.3,1) forwards;
}
@keyframes revealIn {
  from { opacity: 0; transform: scale(0.82) translateY(28px); }
  to   { opacity: 1; transform: scale(1)    translateY(0); }
}
.winner-trophy-wrap { margin-bottom: 20px; }
.winner-trophy-wrap svg {
  filter: drop-shadow(0 8px 28px rgba(245,158,11,0.45));
}
.winner-tag {
  display: inline-block; font-size: 11px; font-weight: 900;
  letter-spacing: 0.12em; text-transform: uppercase;
  color: #f59e0b; background: rgba(245,158,11,0.14);
  border: 1px solid rgba(245,158,11,0.28);
  border-radius: 99px; padding: 6px 18px; margin-bottom: 18px;
}
.winner-big-name {
  font-size: 34px; font-weight: 900; color: #fff;
  letter-spacing: -0.03em; margin-bottom: 8px;
}
.winner-ticket-num {
  font-family: 'Courier New', monospace;
  font-size: 15px; color: rgba(255,255,255,0.4);
  margin-bottom: 5px;
}
.winner-phone-num {
  font-size: 14px; color: rgba(255,255,255,0.5);
  font-weight: 600; margin-bottom: 36px;
}
.btn-close-roll {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 15px 36px; border-radius: 16px;
  background: linear-gradient(135deg, #c41230, #7a001b);
  color: #fff; font-size: 14px; font-weight: 800;
  border: none; cursor: pointer;
  box-shadow: 0 8px 32px rgba(196,18,48,0.3);
  transition: transform 0.2s, box-shadow 0.2s;
}
.btn-close-roll:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(196,18,48,0.4); }

/* Confetti canvas */
#confettiCanvas {
  position: fixed; inset: 0;
  pointer-events: none; z-index: 9998;
  display: none;
}

/* ── Prize Modal ───────────────────────────────────────────────── */
.modal-bg {
  position: fixed; inset: 0; z-index: 500;
  background: rgba(0,0,0,0.48); backdrop-filter: blur(4px);
  display: none; align-items: center; justify-content: center;
}
.modal-bg.open {
  display: flex;
  animation: fadeIn 0.2s;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal-box {
  background: #fff; border-radius: 24px;
  width: min(460px, 92vw);
  box-shadow: 0 24px 80px rgba(0,0,0,0.18);
  animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1);
  overflow: hidden;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}
.modal-hd {
  display: flex; justify-content: space-between; align-items: center;
  padding: 22px 26px; border-bottom: 1px solid #f3f4f6;
}
.modal-hd-title { font-size: 16px; font-weight: 900; color: #0f172a; }
.modal-close-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: #f1f5f9; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #64748b; transition: background 0.2s;
}
.modal-close-btn:hover { background: #e2e8f0; }
.modal-body { padding: 26px; }
.modal-ft {
  padding: 18px 26px; border-top: 1px solid #f3f4f6;
  display: flex; justify-content: flex-end; gap: 10px;
}
.form-group { margin-bottom: 18px; }
.form-lbl {
  display: block; font-size: 11px; font-weight: 800; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 7px;
}
.form-ctrl {
  width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb;
  border-radius: 12px; font-size: 14px; font-weight: 600;
  color: #0f172a; background: #f9fafb; font-family: inherit;
  transition: border-color 0.2s, background 0.2s;
}
.form-ctrl:focus {
  outline: none; border-color: #c41230; background: #fff;
  box-shadow: 0 0 0 4px rgba(196,18,48,0.08);
}
.form-hint { font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 5px; }
.btn-cancel { padding: 10px 20px; border-radius: 11px; background: #f1f5f9; color: #475569; font-size: 13px; font-weight: 700; border: none; cursor: pointer; }
.btn-save   { padding: 10px 20px; border-radius: 11px; background: #c41230; color: #fff;    font-size: 13px; font-weight: 700; border: none; cursor: pointer; }

/* ── Responsive ───────────────────────────────────────────────── */
@media(max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .batch-header { flex-direction: column; }
  .prize-table th:first-child,
  .prize-table td:first-child { display: none; }
  .prize-section-hd { padding: 18px 20px; }
  .prize-table th, .prize-table td { padding-left: 20px; padding-right: 20px; }
}
</style>

<?php
// SVG helpers
function svgArrowLeft(): string {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>';
}
function svgPlus(): string {
    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
}
function svgTicket(): string {
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a1 1 0 0 1 1-1h18a1 1 0 0 1 1 1v2a2 2 0 0 0 0 4v2a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-2a2 2 0 0 0 0-4Z"/></svg>';
}
function svgPeople(): string {
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
}
function svgFire(): string {
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z"/></svg>';
}
function svgGift(): string {
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>';
}
function svgDice(): string {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><circle cx="15.5" cy="8.5" r="1.5" fill="currentColor"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor"/><circle cx="8.5" cy="15.5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>';
}
function svgEdit(): string {
    return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
}
function svgTrash(): string {
    return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
}
function svgTrophy(): string {
    return '<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8M12 17v4"/><path d="M7 4H4a2 2 0 0 0-2 2v1c0 2.76 2.24 5 5 5h.5"/><path d="M17 4h3a2 2 0 0 1 2 2v1c0 2.76-2.24 5-5 5h-.5"/><path d="M7 4h10v8a5 5 0 0 1-10 0V4z"/></svg>';
}
function svgCheck(): string {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
}
function svgImg(): string {
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
}
?>

<a href="<?= url('/raffle') ?>" class="back-link">
  <?= svgArrowLeft() ?> Kembali ke Daftar Undian
</a>

<div class="batch-header">
  <div>
    <div class="batch-title"><?= htmlspecialchars($batch['name']) ?></div>
    <div class="batch-period">
      <?= date('d M Y', strtotime($batch['start_date'])) ?> &ndash; <?= date('d M Y', strtotime($batch['end_date'])) ?>
    </div>
  </div>
  <?php
    $sc = match($batch['status']) {
        'active'    => 'status-active',
        'completed' => 'status-completed',
        default     => 'status-draft',
    };
    $sl = match($batch['status']) {
        'active'    => 'Sedang Berlangsung',
        'completed' => 'Siap Diundi',
        default     => 'Draft',
    };
  ?>
  <span class="status-pill <?= $sc ?>"><?= $sl ?></span>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><?= svgTicket() ?></div>
    <div class="stat-value"><?= number_format($stats['total_tickets']) ?></div>
    <div class="stat-label">Total Tiket Terjual</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f0fdf4;color:#16a34a"><?= svgPeople() ?></div>
    <div class="stat-value"><?= number_format($stats['total_participants']) ?></div>
    <div class="stat-label">Total Peserta</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fff7ed;color:#f59e0b"><?= svgFire() ?></div>
    <div class="stat-value"><?= number_format($stats['total_tickets'] * 10) ?></div>
    <div class="stat-label">Poin Terbakar</div>
  </div>
</div>

<div class="prize-section">
  <div class="prize-section-hd">
    <div class="prize-section-title">Katalog Hadiah</div>
    <button class="btn-add" onclick="openPrizeModal()">
      <?= svgPlus() ?> Tambah Hadiah
    </button>
  </div>
  <div class="table-responsive">
    <table class="prize-table">
      <thead>
        <tr>
          <th>Gambar</th>
          <th>Nama Hadiah</th>
          <th>Status Pemenang</th>
          <th style="text-align:right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($prizes)): ?>
        <tr class="empty-prize-row">
          <td colspan="4">
            <div style="color:#94a3b8"><?= svgGift() ?></div>
            <div class="empty-prize-label">Belum ada hadiah</div>
            <div class="empty-prize-sub">Tambahkan hadiah untuk periode undian ini</div>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($prizes as $p): ?>
        <tr>
          <td>
            <?php if ($p['image_url']): ?>
              <img class="prize-thumb" src="<?= asset($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <?php else: ?>
              <div class="prize-thumb-placeholder"><?= svgImg() ?></div>
            <?php endif ?>
          </td>
          <td class="prize-name-cell"><?= htmlspecialchars($p['name']) ?></td>
          <td>
            <?php if ($p['winner_ticket_id']): ?>
              <div class="winner-badge">
                <div class="winner-badge-name"><?= htmlspecialchars($p['winner_name'] ?? '-') ?></div>
                <div class="winner-badge-detail">
                  <?= htmlspecialchars($p['ticket_code'] ?? '') ?>
                  <?php if (!empty($p['winner_phone'])): ?>
                    &bull; <?= htmlspecialchars($p['winner_phone']) ?>
                  <?php endif ?>
                </div>
              </div>
            <?php else: ?>
              <span class="no-winner-text">Belum diundi</span>
            <?php endif ?>
          </td>
          <td style="text-align:right">
            <?php if (!$p['winner_ticket_id']): ?>
              <div class="action-group">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                  <button class="btn-sm-action btn-edit" onclick='openEditModal(<?= json_encode($p) ?>)'>
                    <?= svgEdit() ?> Edit
                  </button>
                  <form method="POST" action="<?= url('/raffle/delete-prize') ?>" class="d-inline"
                        onsubmit="return confirm('Hapus hadiah ini?')">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                    <button type="submit" class="btn-sm-action btn-delete"><?= svgTrash() ?> Hapus</button>
                  </form>
                </div>
                <?php if ($batch['status'] === 'completed'): ?>
                <button class="btn-draw"
                        onclick="prepareRoll(<?= (int)$p['id'] ?>, <?= (int)$batch['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= $p['image_url'] ? asset($p['image_url']) : '' ?>')">
                  <?= svgDice() ?> Kocok Undian
                </button>
                <?php endif ?>
              </div>
            <?php endif ?>
          </td>
        </tr>
        <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ─── Rolling Overlay ───────────────────────────────────────── -->
<div id="rollOverlay" class="roll-overlay" hidden>
  <div id="giantCountdown" class="giant-countdown"></div>
  <div class="roll-panel" id="rollPanel" style="position:relative; overflow:hidden;">
    <div class="spotlight"></div>
    <div class="spotlight" style="animation-direction: reverse; animation-duration: 15s; opacity: 0.5;"></div>
    
    <!-- Phase 1: Idle & Rolling -->
    <div id="rollPhase" style="position:relative; z-index:1; transition: opacity 0.5s;">
      <div class="roll-eyebrow">Pengundian Berlangsung</div>
      
      <div id="rollPrizeImgWrap" style="text-align:center; margin-bottom: 24px; display:none;">
        <img id="rollPrizeImg" src="" alt="Prize" style="width: 130px; height: 130px; object-fit: cover; border-radius: 20px; border: 4px solid rgba(255,255,255,0.15); box-shadow: 0 15px 35px rgba(0,0,0,0.4);">
      </div>

      <div class="roll-heading" id="rollPrizeName"></div>
      
      <div class="roll-machine">
        <div class="roll-mask roll-mask-top"></div>
        <div class="roll-strip" id="rollStrip">
          <div class="roll-item">[ SIAP DIUNDI ]</div>
        </div>
        <div class="roll-mask roll-mask-bottom"></div>
      </div>
      
      <div class="roll-status" id="rollStatus">Tekan tombol di bawah atau tombol Spasi</div>
      
      <div id="rollParticipantCount" style="color:#4ade80; font-weight:800; margin-bottom: 15px; font-size:15px; letter-spacing:0.05em; text-transform:uppercase; text-shadow: 0 0 10px rgba(74,222,128,0.3);">
         <?= number_format(count($tickets), 0, ',', '.') ?> Tiket Siap Diundi
      </div>
      
      <button id="btnStartRoll" class="btn-start-roll">Mulai Pengundian</button>
      
      <div class="roll-dots" id="rollDots" style="display:none;">
        <div class="roll-dot"></div>
        <div class="roll-dot"></div>
        <div class="roll-dot"></div>
      </div>
    </div>

    <!-- Phase 2: Winner Reveal -->
    <div class="winner-reveal" id="winnerReveal">
      <div class="winner-trophy-wrap"><?= svgTrophy() ?></div>
      <div class="winner-tag">Pemenang Undian</div>
      <div class="winner-big-name" id="winnerName"></div>
      <div class="winner-ticket-num" id="winnerTicketCode"></div>
      <div class="winner-phone-num" id="winnerPhone"></div>
      <button class="btn-close-roll" onclick="closeRoll()">
        <?= svgCheck() ?> Tutup &amp; Perbarui Halaman
      </button>
    </div>
  </div>
</div>

<!-- Confetti canvas (behind overlay text, above overlay bg) -->
<canvas id="confettiCanvas"></canvas>

<!-- ─── Prize Modal ───────────────────────────────────────────── -->
<div class="modal-bg" id="prizeModal">
  <div class="modal-box">
    <form method="POST" action="<?= url('/raffle/save-prize') ?>" enctype="multipart/form-data">
      <div class="modal-hd">
        <div class="modal-hd-title" id="prizeModalTitle">Tambah Hadiah</div>
        <button type="button" class="modal-close-btn" onclick="closePrizeModal()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id"       id="prizeId">
        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
        <div class="form-group">
          <label class="form-lbl">Nama Hadiah</label>
          <input type="text" name="name" id="prizeName" class="form-ctrl"
                 required placeholder="Contoh: Tablet Samsung S9">
        </div>
        <div class="form-group">
          <label class="form-lbl">Gambar Hadiah</label>
          <input type="file" name="image" class="form-ctrl" accept="image/*">
          <div class="form-hint">Biarkan kosong jika tidak ingin mengubah gambar.</div>
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn-cancel" onclick="closePrizeModal()">Batal</button>
        <button type="submit" class="btn-save">Simpan Hadiah</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Ticket pool for animation ─────────────────────────────── */
const TICKET_POOL = <?= json_encode(
    !empty($tickets)
        ? array_column($tickets, 'ticket_code')
        : ['UND-XXXXXX', 'UND-YYYYYY', 'UND-ZZZZZZ']
) ?>;

/* ── Modal helpers ─────────────────────────────────────────── */
function openPrizeModal() {
    document.getElementById('prizeId').value   = '';
    document.getElementById('prizeName').value = '';
    document.getElementById('prizeModalTitle').textContent = 'Tambah Hadiah Baru';
    document.getElementById('prizeModal').classList.add('open');
}
function openEditModal(p) {
    document.getElementById('prizeId').value   = p.id;
    document.getElementById('prizeName').value = p.name;
    document.getElementById('prizeModalTitle').textContent = 'Edit Hadiah';
    document.getElementById('prizeModal').classList.add('open');
}
function closePrizeModal() {
    document.getElementById('prizeModal').classList.remove('open');
}
document.getElementById('prizeModal').addEventListener('click', function(e) {
    if (e.target === this) closePrizeModal();
});

/* ── Rolling animation ─────────────────────────────────────── */
let currentPrizeId, currentBatchId;
let rollDone = false;
let isRollingStarted = false;
let teaserInterval, heartbeatInterval, rollMachineSound;

// Synth Audio Helper for sound effects without needing MP3 files
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
function playBeep(freq, type, duration, vol=0.1) {
    if(audioCtx.state === 'suspended') audioCtx.resume();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = type; osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
    gain.gain.setValueAtTime(vol, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
    osc.connect(gain); gain.connect(audioCtx.destination);
    osc.start(); osc.stop(audioCtx.currentTime + duration);
}
function playWinnerSound() {
    playBeep(440, 'triangle', 0.2, 0.2); 
    setTimeout(() => playBeep(554, 'triangle', 0.2, 0.2), 150);
    setTimeout(() => playBeep(659, 'triangle', 0.8, 0.2), 300);
}

function startTeaser() {
    clearInterval(teaserInterval);
    const strip = document.getElementById('rollStrip');
    teaserInterval = setInterval(() => {
        if (!isRollingStarted && Math.random() > 0.6) {
            strip.innerHTML = `<div class="roll-item" style="color:rgba(255,255,255,0.15); font-size:24px; font-weight:normal;">${TICKET_POOL[Math.floor(Math.random()*TICKET_POOL.length)]}</div>`;
            setTimeout(() => {
                if(!isRollingStarted) strip.innerHTML = '<div class="roll-item" style="color:rgba(255,255,255,0.4)">[ SIAP DIUNDI ]</div>';
            }, 80);
        }
    }, 1500);
}

function startHeartbeat() {
    clearInterval(heartbeatInterval);
    heartbeatInterval = setInterval(() => {
        if (!isRollingStarted) {
            playBeep(60, 'sine', 0.4, 0.2);
            setTimeout(() => playBeep(60, 'sine', 0.4, 0.1), 250);
        }
    }, 1200);
}

function prepareRoll(prizeId, batchId, prizeName, prizeImgUrl) {
    currentPrizeId = prizeId;
    currentBatchId = batchId;
    rollDone = false;
    isRollingStarted = false;
    
    document.getElementById('rollPhase').style.display   = '';
    document.getElementById('rollPhase').style.opacity   = '1';
    document.getElementById('winnerReveal').classList.remove('visible');
    document.getElementById('rollPrizeName').textContent = prizeName;
    document.getElementById('rollStatus').innerHTML      = '<span style="animation: pulse 1.5s infinite;">Tekan tombol di bawah atau tombol SPASI di keyboard</span>';
    
    if (prizeImgUrl) {
        document.getElementById('rollPrizeImg').src = prizeImgUrl;
        document.getElementById('rollPrizeImgWrap').style.display = 'block';
    } else {
        document.getElementById('rollPrizeImgWrap').style.display = 'none';
    }

    const strip = document.getElementById('rollStrip');
    strip.className = 'roll-strip'; 
    strip.style.transform = '';
    strip.innerHTML = '<div class="roll-item" style="color:rgba(255,255,255,0.4)">[ SIAP DIUNDI ]</div>';
    
    document.getElementById('btnStartRoll').style.display = 'inline-flex';
    document.getElementById('rollDots').style.display = 'none';
    
    document.getElementById('rollOverlay').removeAttribute('hidden');
    document.body.style.overflow = 'hidden';
    
    // User interacted with "Kocok Undian", we can resume audio context
    if(audioCtx.state === 'suspended') audioCtx.resume();
    startTeaser();
    startHeartbeat();
}

document.getElementById('btnStartRoll').addEventListener('click', function() {
    this.style.display = 'none';
    isRollingStarted = true;
    clearInterval(teaserInterval);
    clearInterval(heartbeatInterval);
    
    // Dim background for countdown
    document.getElementById('rollPhase').style.opacity = '0.15';
    
    const cdEl = document.getElementById('giantCountdown');
    let count = 3;
    
    function tickCountdown() {
        if (count > 0) {
            cdEl.textContent = count;
            cdEl.style.animation = 'none';
            void cdEl.offsetWidth;
            cdEl.style.animation = 'popIn 1s ease-out forwards';
            playBeep(400, 'square', 0.2, 0.1);
            count--;
            setTimeout(tickCountdown, 1000);
        } else {
            cdEl.textContent = 'GO!';
            cdEl.style.animation = 'none';
            void cdEl.offsetWidth;
            cdEl.style.animation = 'popIn 1s ease-out forwards';
            playBeep(800, 'square', 0.4, 0.15);
            
            setTimeout(() => {
                document.getElementById('rollPhase').style.opacity = '1';
                document.getElementById('rollDots').style.display = 'flex';
                document.getElementById('rollStatus').textContent = 'Mengumpulkan seluruh tiket peserta...';
                
                setTimeout(() => { document.getElementById('rollStatus').textContent = 'Memasukkan tiket ke dalam mesin acak...'; }, 1800);
                setTimeout(() => { document.getElementById('rollStatus').textContent = 'Mengacak secara acak! Siap-siap...'; }, 3800);
                setTimeout(() => { document.getElementById('rollStatus').textContent = 'Mencari kandidat pemenang...'; }, 5500);
                
                startActualRoll();
            }, 600);
        }
    }
    tickCountdown();
});

// Spacebar shortcut
document.addEventListener('keydown', function(e) {
    if (e.code === 'Space' && !document.getElementById('rollOverlay').hasAttribute('hidden')) {
        const btn = document.getElementById('btnStartRoll');
        if (btn.style.display !== 'none') {
            e.preventDefault();
            btn.click();
        }
    }
});

function startActualRoll() {
    rollMachineSound = setInterval(() => {
        playBeep(150 + Math.random()*200, 'sawtooth', 0.05, 0.03);
    }, 60);

    const strip = document.getElementById('rollStrip');
    const pool = TICKET_POOL.length ? TICKET_POOL : ['UND-1234', 'UND-5678', 'UND-9999'];
    
    // Create spinning strip
    let html = '';
    for (let i = 0; i < 30; i++) {
        html += `<div class="roll-item">${pool[Math.floor(Math.random() * pool.length)]}</div>`;
    }
    strip.innerHTML = html;
    strip.className = 'roll-strip spinning';
    
    const startTime = Date.now();
    const formData  = new FormData();
    formData.append('prize_id', currentPrizeId);
    formData.append('batch_id', currentBatchId);
    formData.append('_ajax',    '1');

    fetch('<?= url('/raffle/draw-winner') ?>', {
        method: 'POST',
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        const elapsed = Date.now() - startTime;
        const delay   = Math.max(0, 7000 - elapsed); // spin for at least 7.0s for massive suspense

        setTimeout(() => {
            if (!data.success) {
                strip.className = 'roll-strip';
                strip.innerHTML = '<div class="roll-item" style="color:#ef4444;">[ ERROR ]</div>';
                document.getElementById('rollStatus').textContent = data.message || 'Gagal mengundi.';
                setTimeout(closeRoll, 3000);
                return;
            }
            revealWinnerSmooth(data, pool);
        }, delay);
    })
    .catch(() => {
        strip.className = 'roll-strip';
        document.getElementById('rollStatus').textContent = 'Terjadi kesalahan jaringan.';
        setTimeout(closeRoll, 3000);
    });
}

function revealWinnerSmooth(data, pool) {
    clearInterval(rollMachineSound);
    
    let slowInterval = 60;
    let slowTimer;
    function playSlowDown() {
        playBeep(200, 'sine', 0.1, 0.04);
        slowInterval *= 1.35;
        if(slowInterval < 800) slowTimer = setTimeout(playSlowDown, slowInterval);
    }
    playSlowDown();

    const strip = document.getElementById('rollStrip');
    const finalCode = data.ticket_code || pool[Math.floor(Math.random() * pool.length)];
    
    strip.className = 'roll-strip';
    
    let html = '';
    const itemsCount = 40;
    for (let i = 0; i < itemsCount; i++) {
        html += `<div class="roll-item" style="color:rgba(255,255,255,0.7)">${pool[Math.floor(Math.random() * pool.length)]}</div>`;
    }
    html += `<div class="roll-item winner-item">${finalCode}</div>`;
    strip.innerHTML = html;
    
    strip.style.transform = `translateY(0px)`;
    document.getElementById('rollStatus').textContent = 'Menemukan pemenang...';
    
    // trigger reflow
    void strip.offsetWidth; 
    
    // transition down
    strip.className = 'roll-strip stopping';
    strip.style.transform = `translateY(-${itemsCount * 100}px)`;
    
    setTimeout(() => {
        playWinnerSound();
        showWinnerCard(data);
    }, 4100); 
}

function showWinnerCard(data) {
    document.getElementById('rollPhase').style.display = 'none';
    document.getElementById('winnerName').textContent      = data.winner_name  || 'Pemenang';
    document.getElementById('winnerTicketCode').textContent = data.ticket_code  || '';
    document.getElementById('winnerPhone').textContent     = data.winner_phone || '';
    document.getElementById('winnerReveal').classList.add('visible');
    startConfetti();
}

function closeRoll() {
    clearInterval(teaserInterval);
    clearInterval(heartbeatInterval);
    clearInterval(rollMachineSound);
    document.getElementById('rollOverlay').setAttribute('hidden', '');
    document.body.style.overflow = '';
    stopConfetti();
    location.reload();
}

/* ── Confetti ──────────────────────────────────────────────── */
let confettiRaf = null;
const COLORS = ['#c41230', '#ffc72c', '#16a34a', '#3b82f6', '#f59e0b', '#fff'];

function startConfetti() {
    const canvas = document.getElementById('confettiCanvas');
    canvas.style.display = 'block';
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    const ctx = canvas.getContext('2d');

    const particles = Array.from({ length: 160 }, () => ({
        x:     Math.random() * canvas.width,
        y:     -10 - Math.random() * 120,
        vx:    (Math.random() - 0.5) * 5,
        vy:    Math.random() * 4 + 2,
        w:     Math.random() * 10 + 4,
        h:     Math.random() * 6  + 3,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
        angle: Math.random() * 360,
        spin:  (Math.random() - 0.5) * 5,
        alpha: 1,
    }));

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = 0;
        particles.forEach(p => {
            if (p.y > canvas.height + 20) { p.alpha = 0; return; }
            p.x     += p.vx;
            p.y     += p.vy;
            p.vy    += 0.06;
            p.angle += p.spin;
            if (p.y > canvas.height * 0.7) p.alpha = Math.max(0, p.alpha - 0.012);

            ctx.save();
            ctx.globalAlpha = p.alpha;
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
            if (p.alpha > 0) alive++;
        });
        if (alive > 0) confettiRaf = requestAnimationFrame(draw);
        else stopConfetti();
    }
    draw();
}

function stopConfetti() {
    if (confettiRaf) cancelAnimationFrame(confettiRaf);
    const canvas = document.getElementById('confettiCanvas');
    canvas.style.display = 'none';
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}
</script>
