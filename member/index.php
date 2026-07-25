<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';
loyalty_ensure_tables($pdo);

/* ──────────────────────────────────────────────────────
   SURPRISE FLOW: Intercept saat ada ?claim=KODE
   ────────────────────────────────────────────────────── */
$claimCode = strtoupper(trim((string)($_GET['claim'] ?? '')));
$claimCheck = ['valid' => false];

if ($claimCode !== '') {
    $claimCheck = loyalty_check_claim_code($pdo, $claimCode);
}

// ── Pilih variasi anti-ulang ──────────────────────────
function pick_surprise_variant(): string {
    $all = ['A', 'B', 'C'];
    $last = $_SESSION['member_last_variant'] ?? null;
    $candidates = array_values(array_filter($all, fn($v) => $v !== $last));
    $chosen = $candidates[array_rand($candidates)];
    $_SESSION['member_last_variant'] = $chosen;
    return $chosen;
}

// ── Jika kode klaim valid → tampilkan SURPRISE LANDING ──
if ($claimCode !== '' && $claimCheck['valid'] === true) {
    $variant    = pick_surprise_variant();
    $points     = (int)($claimCheck['points'] ?? 0);
    $memberId   = (int)($_SESSION['member_id'] ?? 0);
    $isLoggedIn = $memberId > 0;
    $autoMsg    = '';
    $member     = null;

    // Ambil reward produk terendah untuk Variasi C
    $goalReward = $pdo->query("SELECT * FROM point_reward_products WHERE is_active=1 ORDER BY required_points ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    // Jika sudah login → auto-claim langsung
    if ($isLoggedIn) {
        $member = loyalty_member_by_id($pdo, $memberId);
        try {
            $res     = loyalty_claim_receipt($pdo, $memberId, $claimCode);
            $autoMsg = 'success';
            loyalty_activity($pdo, $memberId, $member['phone'] ?? null, 'member_claim_auto_success', 'Auto klaim surprise dari QR ' . $claimCode);
        } catch (Throwable $e) {
            $autoMsg = $e->getMessage();
        }
    }

    $memberName      = $member['name'] ?? '';
    $isReturning     = $isLoggedIn;
    $loginUrl        = 'login.php?claim=' . urlencode($claimCode);
    $dashboardUrl    = 'dashboard.php';
    $goalName        = htmlspecialchars($goalReward['name'] ?? 'Ayam Crispy Gratis');
    $goalPoints      = (int)($goalReward['required_points'] ?? 150);
    $currentBalance  = $isLoggedIn ? (int)($member['points_balance'] ?? 0) : 0;
    $progressPct     = $goalPoints > 0 ? min(100, round(($points / $goalPoints) * 100)) : 0;
    $pointsNeeded    = max(0, $goalPoints - $points);

    // Gambar goal reward
    $goalImgRaw = trim((string)($goalReward['image_url'] ?? ''));
    if ($goalImgRaw === '') {
        $goalImg = '../public/assets/images/pos-products/original.png';
    } elseif (preg_match('~^https?://~i', $goalImgRaw)) {
        $goalImg = htmlspecialchars($goalImgRaw);
    } else {
        $goalImg = '../public/assets/images/pos-products/' . htmlspecialchars(basename($goalImgRaw));
    }
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Kejutan Spesial Untukmu! — Lumero</title>
    <link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #c41230; --red2: #7a001b; --gold: #ffc72c; --gold2: #e6a800;
            --ink: #0f172a; --muted: #64748b; --surface: #fff;
            --cream: #fffcf5; --border: rgba(0,0,0,0.07);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--cream);
            min-height: 100svh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* ── Animated Background ── */
        .bg-mesh {
            position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none;
            background: radial-gradient(ellipse at 20% 20%, rgba(255,199,44,0.18) 0%, transparent 55%),
                        radial-gradient(ellipse at 80% 80%, rgba(196,18,48,0.12) 0%, transparent 55%),
                        var(--cream);
        }
        .bg-orb {
            position: absolute; border-radius: 50%; filter: blur(70px);
            animation: floatOrb 15s ease-in-out infinite alternate;
        }
        .bg-orb-1 { width: 50vw; height: 50vw; top: -10%; left: -10%; background: rgba(255,199,44,0.25); }
        .bg-orb-2 { width: 60vw; height: 60vw; bottom: -20%; right: -15%; background: rgba(196,18,48,0.15); animation-delay: -7s; }
        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(4vw, 4vh) scale(1.12); }
        }

        /* ── Wrapper ── */
        .surprise-wrapper {
            position: relative; z-index: 2;
            width: min(480px, 100%);
            padding: 32px 20px 48px;
            display: flex; flex-direction: column; align-items: center;
            text-align: center; gap: 0;
        }

        /* ── Brand Logo ── */
        .brand-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.85); backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 99px; padding: 8px 18px;
            font-size: 13px; font-weight: 800; letter-spacing: -0.01em;
            color: var(--ink); margin-bottom: 32px;
        }
        .brand-pill img { width: 24px; height: 24px; border-radius: 6px; }

        /* ── Stage (Container animasi utama) ── */
        .stage {
            width: 100%;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.9);
            border-radius: 32px;
            padding: 40px 28px 36px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.06), 0 0 0 1px rgba(255,255,255,0.5) inset;
            margin-bottom: 20px;
            position: relative; overflow: hidden;
        }
        .stage::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--red), var(--gold), var(--red));
            background-size: 200% 100%;
            animation: shimmerBar 3s linear infinite;
        }
        @keyframes shimmerBar {
            0% { background-position: 0% 0; }
            100% { background-position: 200% 0; }
        }

        .headline {
            font-size: clamp(22px, 5vw, 30px);
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1.15;
            color: var(--ink);
            margin-bottom: 10px;
        }
        .headline span { color: var(--red); }
        .sub-headline {
            font-size: 14px; color: var(--muted); line-height: 1.6;
            margin-bottom: 32px; font-weight: 500;
        }

        /* ── POIN BADGE ── */
        .points-badge {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, var(--ink) 0%, #1e293b 100%);
            color: var(--gold);
            border-radius: 20px; padding: 16px 28px;
            margin-bottom: 28px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.18);
            animation: popIn 0.7s cubic-bezier(0.34,1.56,0.64,1) 0.4s both;
        }
        .points-badge .poin-num {
            font-size: 42px; font-weight: 900; letter-spacing: -0.04em; line-height: 1;
        }
        .points-badge .poin-label {
            display: flex; flex-direction: column; text-align: left;
            font-size: 11px; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; opacity: 0.8; line-height: 1.4;
        }
        @keyframes popIn {
            from { transform: scale(0.5) rotate(-5deg); opacity: 0; }
            to   { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        /* ── CTA Button ── */
        .cta-btn {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 18px 28px;
            background: linear-gradient(135deg, var(--red) 0%, #e01535 100%);
            color: #fff; font-size: 16px; font-weight: 800;
            border: none; border-radius: 16px; cursor: pointer;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(196,18,48,0.3);
            transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
            position: relative; overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16,1,0.3,1) 0.8s both;
        }
        .cta-btn::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 100%);
            opacity: 0; transition: opacity 0.3s;
        }
        .cta-btn:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(196,18,48,0.4); }
        .cta-btn:hover::after { opacity: 1; }
        .cta-btn:active { transform: translateY(0); }
        .cta-pulse {
            position: absolute; inset: 0; border-radius: inherit;
            box-shadow: 0 0 0 0 rgba(196,18,48,0.4);
            animation: pulseCta 2s ease-out 1.5s infinite;
        }
        @keyframes pulseCta {
            0%   { box-shadow: 0 0 0 0 rgba(196,18,48,0.4); }
            70%  { box-shadow: 0 0 0 18px rgba(196,18,48,0); }
            100% { box-shadow: 0 0 0 0 rgba(196,18,48,0); }
        }
        @keyframes slideUp {
            from { transform: translateY(24px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }

        .helper-text {
            font-size: 12px; color: var(--muted); margin-top: 14px; font-weight: 500;
        }

        /* ── Confetti Canvas ── */
        #confetti-canvas {
            position: fixed; inset: 0; pointer-events: none; z-index: 999;
        }

        /* ════════════════════════════════════════════════════
           VARIASI A: SPIN THE WHEEL
           ════════════════════════════════════════════════════ */
        .wheel-container {
            position: relative;
            width: 240px; height: 240px;
            margin: 0 auto 28px;
        }
        .wheel-pointer {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            width: 0; height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 24px solid var(--red);
            z-index: 10;
            filter: drop-shadow(0 4px 8px rgba(196,18,48,0.4));
        }
        #spin-wheel {
            width: 240px; height: 240px;
            border-radius: 50%;
            border: 6px solid #fff;
            box-shadow: 0 0 0 3px var(--border), 0 16px 40px rgba(0,0,0,0.12);
            transform-origin: center;
            transition: transform 4s cubic-bezier(0.17,0.67,0.12,0.99);
            cursor: pointer;
        }
        #spin-wheel.spinning { cursor: default; }
        .wheel-center {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
            width: 52px; height: 52px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; z-index: 5;
        }
        .spin-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 32px; border-radius: 99px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%);
            color: var(--ink); font-size: 15px; font-weight: 800;
            border: none; cursor: pointer;
            box-shadow: 0 8px 20px rgba(255,199,44,0.35);
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .spin-btn:hover { transform: scale(1.05); box-shadow: 0 12px 28px rgba(255,199,44,0.5); }
        .spin-btn:disabled { opacity: 0.5; cursor: default; transform: none; }

        /* ════════════════════════════════════════════════════
           VARIASI B: PETI HARTA KARUN
           ════════════════════════════════════════════════════ */
        .chest-container {
            position: relative;
            width: 180px; height: 180px;
            margin: 0 auto 24px;
        }
        .chest-svg { width: 180px; height: 180px; }
        .chest-lid { transform-origin: 90px 70px; transition: transform 1.2s cubic-bezier(0.34,1.56,0.64,1); }
        .chest-lock { transition: opacity 0.5s 0.8s; }
        .chest-container.opened .chest-lid { transform: rotate(-110deg); }
        .chest-container.opened .chest-lock { opacity: 0; }
        .chest-glow {
            position: absolute; inset: -20px;
            background: radial-gradient(circle, rgba(255,199,44,0.4) 0%, transparent 65%);
            border-radius: 50%; opacity: 0;
            transition: opacity 0.8s 0.5s;
            pointer-events: none;
        }
        .chest-container.opened .chest-glow { opacity: 1; }
        .coin-burst {
            position: absolute; top: 0; left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
        }
        .coin {
            position: absolute;
            font-size: 20px;
            opacity: 0;
        }
        .chest-container.opened .coin {
            animation: coinFly 1.2s cubic-bezier(0.25,0.46,0.45,0.94) forwards;
        }
        @keyframes coinFly {
            0%   { opacity: 1; transform: translate(0, 0) rotate(0deg) scale(0.5); }
            100% { opacity: 0; transform: var(--fly-to) rotate(var(--fly-rot)) scale(1.2); }
        }
        .open-chest-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 28px; border-radius: 99px;
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
            color: var(--ink); font-size: 15px; font-weight: 800;
            border: none; cursor: pointer;
            box-shadow: 0 8px 24px rgba(251,191,36,0.35);
            transition: all 0.3s; margin-bottom: 20px;
        }
        .open-chest-btn:hover { transform: scale(1.05); }
        .open-chest-btn:disabled { opacity: 0.4; cursor: default; transform: none; }

        /* ════════════════════════════════════════════════════
           VARIASI C: TARGET VISUAL (PROGRESS BAR)
           ════════════════════════════════════════════════════ */
        .goal-card {
            background: linear-gradient(135deg, var(--ink) 0%, #1e293b 100%);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 24px;
            position: relative; overflow: hidden;
            text-align: left;
        }
        .goal-card::before {
            content: '';
            position: absolute; top: -40px; right: -40px;
            width: 140px; height: 140px;
            background: rgba(255,199,44,0.08);
            border-radius: 50%;
        }
        .goal-label { font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
        .goal-product-name { font-size: 18px; font-weight: 900; color: #fff; margin-bottom: 16px; line-height: 1.2; }
        .goal-product-img {
            position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
            width: 80px; height: 80px; object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.4));
        }
        .progress-track {
            background: rgba(255,255,255,0.1); border-radius: 99px;
            height: 10px; overflow: hidden; margin-bottom: 8px;
        }
        .progress-fill {
            height: 100%; border-radius: 99px;
            background: linear-gradient(90deg, var(--gold) 0%, #fbbf24 100%);
            width: 0%;
            transition: width 1.8s cubic-bezier(0.25,0.46,0.45,0.94) 0.6s;
            box-shadow: 0 0 12px rgba(255,199,44,0.5);
        }
        .progress-labels {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12px; color: rgba(255,255,255,0.6); font-weight: 600;
        }
        .progress-labels .earned { color: var(--gold); font-weight: 800; font-size: 13px; }
        .need-more {
            background: rgba(255,199,44,0.12); border: 1px solid rgba(255,199,44,0.2);
            border-radius: 12px; padding: 10px 14px;
            font-size: 13px; font-weight: 700; color: var(--gold);
            margin-top: 14px; text-align: center;
        }

        /* ── Success State (member sudah login) ── */
        .success-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: #f0fdf4; border: 1.5px solid #86efac;
            border-radius: 99px; padding: 8px 18px;
            font-size: 13px; font-weight: 700; color: #16a34a;
            margin-bottom: 20px;
            animation: popIn 0.6s cubic-bezier(0.34,1.56,0.64,1) 0.3s both;
        }
    </style>
</head>
<body>

<canvas id="confetti-canvas"></canvas>

<div class="bg-mesh">
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
</div>

<div class="surprise-wrapper">

    <!-- Brand Pill -->
    <div class="brand-pill">
        <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero" onerror="this.style.display='none'">
        Lumero Club
    </div>

    <!-- ═══════════════════════════════════════════════════
         VARIASI A: SPIN THE WHEEL
         ═══════════════════════════════════════════════════ -->
    <?php if ($variant === 'A'): ?>
    <div class="stage" id="stage-A">
        <div class="headline">
            <?php if ($isReturning && $memberName): ?>
                Selamat datang kembali, <span><?= htmlspecialchars(explode(' ', $memberName)[0]) ?>!</span> 👑
            <?php elseif ($isReturning): ?>
                Senang bertemu lagi! <span>Putar Rodamu!</span> 👑
            <?php else: ?>
                Ada <span>Hadiah</span> untukmu! 🎉
            <?php endif; ?>
        </div>
        <p class="sub-headline">Dari pesanan tadi, kamu berhak mendapat poin.<br>Putar roda keberuntunganmu untuk mengungkap hadiahnya!</p>

        <div class="wheel-container">
            <div class="wheel-pointer"></div>
            <canvas id="spin-wheel" width="240" height="240"></canvas>
            <div class="wheel-center">🎰</div>
        </div>

        <button class="spin-btn" id="spinBtn" onclick="startSpin()">
            🎲 Putar Roda!
        </button>

        <div id="spin-result" style="display:none">
            <div class="points-badge">
                <div class="poin-num"><?= $points ?></div>
                <div class="poin-label"><span>POIN</span><span>KAMU!</span></div>
            </div>
            <br>
            <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
                <div class="success-badge">✅ Poin berhasil masuk ke dompetmu!</div>
                <a href="<?= $dashboardUrl ?>" class="cta-btn">
                    <span class="cta-pulse"></span>
                    🏆 Lihat Dompet Saya
                </a>
            <?php elseif ($isLoggedIn): ?>
                <div style="font-size:13px;color:#ef4444;margin-bottom:16px;"><?= htmlspecialchars($autoMsg) ?></div>
                <a href="<?= $dashboardUrl ?>" class="cta-btn">Ke Dashboard</a>
            <?php else: ?>
                <a href="<?= $loginUrl ?>" class="cta-btn">
                    <span class="cta-pulse"></span>
                    🔐 Amankan Poin Saya Sekarang!
                </a>
                <p class="helper-text">Masukkan nomor WhatsApp untuk mengunci poin ini ke akunmu.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         VARIASI B: PETI HARTA KARUN
         ═══════════════════════════════════════════════════ -->
    <?php elseif ($variant === 'B'): ?>
    <div class="stage" id="stage-B">
        <div class="headline">
            <?php if ($isReturning && $memberName): ?>
                Harta menanti, <span><?= htmlspecialchars(explode(' ', $memberName)[0]) ?>!</span> 👑
            <?php elseif ($isReturning): ?>
                Peti harta <span>sudah menunggumu!</span> 👑
            <?php else: ?>
                Peti harta <span>untukmu!</span> 🎁
            <?php endif; ?>
        </div>
        <p class="sub-headline">Dari pesanan tadi, ada poin yang tersimpan di dalam peti ini.<br>Buka peti untuk melihat isinya!</p>

        <div class="chest-container" id="chestContainer">
            <div class="chest-glow"></div>
            <!-- SVG Treasure Chest -->
            <svg class="chest-svg" viewBox="0 0 180 180" xmlns="http://www.w3.org/2000/svg">
                <!-- Body -->
                <rect x="20" y="100" width="140" height="65" rx="10" fill="#8B4513" stroke="#5D2E0C" stroke-width="2"/>
                <rect x="25" y="108" width="130" height="50" rx="8" fill="#A0522D"/>
                <!-- Metal bands body -->
                <rect x="20" y="112" width="140" height="8" rx="2" fill="#8B6914" opacity="0.7"/>
                <rect x="20" y="145" width="140" height="8" rx="2" fill="#8B6914" opacity="0.7"/>
                <!-- Lid (animated) -->
                <g class="chest-lid" id="chestLid">
                    <rect x="20" y="68" width="140" height="38" rx="10 10 0 0" fill="#6B3410" stroke="#5D2E0C" stroke-width="2"/>
                    <rect x="25" y="73" width="130" height="28" rx="8 8 0 0" fill="#8B4513"/>
                    <!-- Metal bands lid -->
                    <rect x="20" y="80" width="140" height="7" rx="2" fill="#8B6914" opacity="0.7"/>
                    <!-- Hinge hint -->
                    <rect x="78" y="103" width="24" height="6" rx="3" fill="#5D2E0C"/>
                </g>
                <!-- Lock -->
                <g class="chest-lock" id="chestLock">
                    <rect x="78" y="94" width="24" height="20" rx="4" fill="#DAA520" stroke="#B8860B" stroke-width="1.5"/>
                    <path d="M 86 94 Q 90 82 94 94" fill="none" stroke="#DAA520" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="90" cy="102" r="3" fill="#5D2E0C"/>
                </g>
            </svg>
            <!-- Coins untuk animasi burst -->
            <div class="coin-burst" id="coinBurst"></div>
        </div>

        <button class="open-chest-btn" id="chestBtn" onclick="openChest()">
            🔓 Buka Peti!
        </button>

        <div id="chest-result" style="display:none">
            <div class="points-badge">
                <div class="poin-num"><?= $points ?></div>
                <div class="poin-label"><span>POIN</span><span>UNTUKMU!</span></div>
            </div>
            <br>
            <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
                <div class="success-badge">✅ Koin masuk ke dompetmu!</div>
                <a href="<?= $dashboardUrl ?>" class="cta-btn">🏆 Lihat Dompet Saya</a>
            <?php elseif ($isLoggedIn): ?>
                <div style="font-size:13px;color:#ef4444;margin-bottom:16px;"><?= htmlspecialchars($autoMsg) ?></div>
                <a href="<?= $dashboardUrl ?>" class="cta-btn">Ke Dashboard</a>
            <?php else: ?>
                <a href="<?= $loginUrl ?>" class="cta-btn">
                    <span class="cta-pulse"></span>
                    🔐 Klaim Hartamu Sekarang!
                </a>
                <p class="helper-text">Daftarkan nomor WhatsApp agar poin ini tidak hangus!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         VARIASI C: TARGET VISUAL (PROGRESS BAR)
         ═══════════════════════════════════════════════════ -->
    <?php else: ?>
    <div class="stage" id="stage-C">
        <div class="headline">
            <?php if ($isReturning && $memberName): ?>
                Hei <span><?= htmlspecialchars(explode(' ', $memberName)[0]) ?></span>, makin dekat! 👑
            <?php elseif ($isReturning): ?>
                Kamu makin dekat ke <span>hadiah!</span> 👑
            <?php else: ?>
                Kamu hampir <span>dapat hadiah!</span> 🎯
            <?php endif; ?>
        </div>
        <p class="sub-headline">Dari pesanan tadi, kamu mendapat poin. Lihat seberapa dekat kamu ke hadiah gratis!</p>

        <!-- Goal Card -->
        <div class="goal-card" id="goalCard">
            <div class="goal-label">Target Hadiahmu</div>
            <div class="goal-product-name"><?= $goalName ?> Gratis!</div>
            <img src="<?= $goalImg ?>" alt="<?= $goalName ?>" class="goal-product-img" onerror="this.src='../public/assets/images/pos-products/original.png'">
            <div class="progress-track">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-labels">
                <span class="earned" id="progressEarned">+<?= $points ?> Pts hari ini!</span>
                <span><?= $goalPoints ?> Pts goal</span>
            </div>
            <?php if ($pointsNeeded > 0): ?>
            <div class="need-more">⚡ Hanya butuh <?= $pointsNeeded ?> Pts lagi untuk <?= $goalName ?> gratis!</div>
            <?php else: ?>
            <div class="need-more" style="color:#4ade80;">🎉 Kamu sudah cukup poin untuk tukar hadiah ini!</div>
            <?php endif; ?>
        </div>

        <div class="points-badge" style="animation-delay:0.2s">
            <div class="poin-num"><?= $points ?></div>
            <div class="poin-label"><span>POIN</span><span>DIDAPAT!</span></div>
        </div>
        <br>
        <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
            <div class="success-badge">✅ Poin berhasil masuk ke dompetmu!</div>
            <a href="<?= $dashboardUrl ?>" class="cta-btn" style="margin-top:16px">🏆 Lihat Progress Saya</a>
        <?php elseif ($isLoggedIn): ?>
            <div style="font-size:13px;color:#ef4444;margin-bottom:16px;"><?= htmlspecialchars($autoMsg) ?></div>
            <a href="<?= $dashboardUrl ?>" class="cta-btn">Ke Dashboard</a>
        <?php else: ?>
            <a href="<?= $loginUrl ?>" class="cta-btn" style="margin-top:16px">
                <span class="cta-pulse"></span>
                ⚡ Amankan Poin Saya!
            </a>
            <p class="helper-text">Jangan biarkan <?= $points ?> poin ini hangus! Daftar sekarang gratis.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /surprise-wrapper -->

<script>
/* ══════════════════════════════════════════
   CONFETTI ENGINE
   ══════════════════════════════════════════ */
const confCanvas = document.getElementById('confetti-canvas');
const ctx = confCanvas.getContext('2d');
confCanvas.width = window.innerWidth;
confCanvas.height = window.innerHeight;
window.addEventListener('resize', () => { confCanvas.width = window.innerWidth; confCanvas.height = window.innerHeight; });

const COLORS = ['#c41230','#ffc72c','#ffffff','#ff5e62','#fde68a','#f59e0b'];
let confetti = [];
let confRunning = false;

function Confetti(x, y) {
    return {
        x, y,
        vx: (Math.random() - 0.5) * 10,
        vy: -(Math.random() * 14 + 4),
        gravity: 0.45,
        w: Math.random() * 10 + 5,
        h: Math.random() * 5 + 3,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
        angle: Math.random() * Math.PI * 2,
        angVel: (Math.random() - 0.5) * 0.3,
        life: 1
    };
}

function spawnConfetti(x, y, n = 80) {
    for (let i = 0; i < n; i++) confetti.push(Confetti(x ?? window.innerWidth / 2, y ?? window.innerHeight / 3));
    if (!confRunning) animateConf();
}

function animateConf() {
    confRunning = true;
    ctx.clearRect(0, 0, confCanvas.width, confCanvas.height);
    confetti.forEach((c, i) => {
        c.x += c.vx; c.y += c.vy; c.vy += c.gravity;
        c.angle += c.angVel; c.life -= 0.012;
        ctx.save();
        ctx.translate(c.x, c.y); ctx.rotate(c.angle);
        ctx.globalAlpha = Math.max(0, c.life);
        ctx.fillStyle = c.color;
        ctx.fillRect(-c.w / 2, -c.h / 2, c.w, c.h);
        ctx.restore();
    });
    confetti = confetti.filter(c => c.life > 0 && c.y < window.innerHeight + 50);
    if (confetti.length > 0) requestAnimationFrame(animateConf);
    else confRunning = false;
}

/* ══════════════════════════════════════════
   VARIASI A: SPIN THE WHEEL
   ══════════════════════════════════════════ */
<?php if ($variant === 'A'): ?>
const wheelCanvas = document.getElementById('spin-wheel');
const wctx = wheelCanvas.getContext('2d');
const segments = [
    { label: '<?= $points ?> Pts', color: '#c41230' },
    { label: '🌟 Bonus!', color: '#ffc72c' },
    { label: '<?= $points ?> Pts', color: '#1e293b' },
    { label: '🎁 Klaim!', color: '#e01535' },
    { label: '<?= $points ?> Pts', color: '#f59e0b' },
    { label: '✨ Yes!', color: '#c41230' },
];
const segCount = segments.length;
const segAngle = (Math.PI * 2) / segCount;
let currentAngle = 0;
let isSpinning = false;

function drawWheel(angle) {
    wctx.clearRect(0, 0, 240, 240);
    const cx = 120, cy = 120, r = 116;
    segments.forEach((seg, i) => {
        const start = angle + i * segAngle;
        const end = start + segAngle;
        wctx.beginPath();
        wctx.moveTo(cx, cy);
        wctx.arc(cx, cy, r, start, end);
        wctx.closePath();
        wctx.fillStyle = seg.color;
        wctx.fill();
        wctx.strokeStyle = 'rgba(255,255,255,0.3)';
        wctx.lineWidth = 1.5;
        wctx.stroke();

        // Label
        wctx.save();
        wctx.translate(cx, cy);
        wctx.rotate(start + segAngle / 2);
        wctx.textAlign = 'right';
        wctx.fillStyle = '#fff';
        wctx.font = 'bold 12px Plus Jakarta Sans, sans-serif';
        wctx.fillText(seg.label, r - 12, 5);
        wctx.restore();
    });
}

drawWheel(0);

// Animate idle rotation
let idleAngle = 0;
let idleRaf;
function idleSpin() {
    idleAngle += 0.003;
    drawWheel(idleAngle);
    idleRaf = requestAnimationFrame(idleSpin);
}
idleSpin();

function startSpin() {
    if (isSpinning) return;
    isSpinning = true;
    const btn = document.getElementById('spinBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Berputar...';
    cancelAnimationFrame(idleRaf);

    const totalRot = (Math.PI * 2) * (5 + Math.random() * 5);
    const duration = 4000;
    const start = performance.now();
    const startAngle = idleAngle;

    function animSpin(now) {
        const elapsed = now - start;
        const t = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - t, 4);
        currentAngle = startAngle + totalRot * ease;
        drawWheel(currentAngle);
        if (t < 1) {
            requestAnimationFrame(animSpin);
        } else {
            onSpinEnd();
        }
    }
    requestAnimationFrame(animSpin);
}

function onSpinEnd() {
    // Show result
    document.getElementById('spin-result').style.display = 'block';
    document.getElementById('spin-result').style.animation = 'popIn 0.7s cubic-bezier(0.34,1.56,0.64,1) both';
    spawnConfetti(window.innerWidth / 2, window.innerHeight / 2, 120);
}
<?php endif; ?>

/* ══════════════════════════════════════════
   VARIASI B: PETI HARTA KARUN
   ══════════════════════════════════════════ */
<?php if ($variant === 'B'): ?>
let chestOpened = false;

// Shake the chest gently
const chest = document.getElementById('chestContainer');
setInterval(() => {
    if (chestOpened) return;
    chest.style.transform = 'rotate(2deg)';
    setTimeout(() => chest.style.transform = 'rotate(-2deg)', 100);
    setTimeout(() => chest.style.transform = 'rotate(0)', 200);
}, 2000);

function openChest() {
    if (chestOpened) return;
    chestOpened = true;
    const btn = document.getElementById('chestBtn');
    btn.disabled = true;
    btn.textContent = '🔓 Terbuka!';

    chest.classList.add('opened');

    // Spawn coin emojis
    const burst = document.getElementById('coinBurst');
    const coinEmojis = ['🪙','💰','⭐','✨','🏆','💛'];
    const flies = [
        'translate(-80px,-120px)',
        'translate(80px,-120px)',
        'translate(-50px,-160px)',
        'translate(50px,-160px)',
        'translate(-110px,-90px)',
        'translate(110px,-90px)',
        'translate(0,-180px)',
    ];
    coinEmojis.forEach((em, i) => {
        const c = document.createElement('span');
        c.className = 'coin';
        c.textContent = em;
        c.style.setProperty('--fly-to', flies[i % flies.length]);
        c.style.setProperty('--fly-rot', (Math.random() * 60 - 30) + 'deg');
        c.style.animationDelay = (i * 0.08) + 's';
        burst.appendChild(c);
    });

    setTimeout(() => {
        document.getElementById('chest-result').style.display = 'block';
        spawnConfetti(window.innerWidth / 2, window.innerHeight / 3, 150);
    }, 1000);
}
<?php endif; ?>

/* ══════════════════════════════════════════
   VARIASI C: PROGRESS BAR
   ══════════════════════════════════════════ */
<?php if ($variant === 'C'): ?>
const progressFill = document.getElementById('progressFill');
const targetPct = <?= $progressPct ?>;
// Trigger after short delay for visual effect
setTimeout(() => {
    progressFill.style.width = targetPct + '%';
}, 300);

// Also spawn some confetti for excitement
setTimeout(() => {
    spawnConfetti(window.innerWidth / 2, window.innerHeight / 2, 60);
}, 1200);
<?php endif; ?>

/* ══════════════════════════════════════════
   AUTO-CLAIM SUCCESS CONFETTI (member logged in)
   ══════════════════════════════════════════ */
<?php if ($isLoggedIn && $autoMsg === 'success'): ?>
window.addEventListener('load', () => {
    setTimeout(() => spawnConfetti(window.innerWidth / 2, window.innerHeight / 3, 100), 600);
});
<?php endif; ?>
</script>

</body>
</html>
<?php
    exit;
}

/* ──────────────────────────────────────────────────────
   FLOW NORMAL (tidak ada claim code, atau kode tidak valid)
   Halaman landing biasa
   ────────────────────────────────────────────────────── */

$stmt = $pdo->query("SELECT * FROM point_reward_products WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 4");
$highlights = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtMenu = $pdo->query("
    SELECT p.id, p.name, p.image, MIN(v.selling_price) as min_price 
    FROM products p 
    JOIN product_variants v ON p.id = v.product_id 
    WHERE p.is_active = 1 AND v.is_active = 1
    GROUP BY p.id 
    ORDER BY p.id ASC 
    LIMIT 10
");
$mainMenu = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);


function hook_reward_image(array $rw): string {
    $img = trim((string)($rw['image_url'] ?? ''));
    if ($img === '' && !empty($rw['source_menu_image_url'])) {
        $img = trim((string)$rw['source_menu_image_url']);
    }
    if ($img !== '') {
        return preg_match('~^https?://~i', $img) ? $img : '../public/assets/images/pos-products/' . ltrim(basename($img), '/');
    }
    $name = strtolower((string)($rw['name'] ?? ''));
    if (str_contains($name, 'kentang')) return '../public/assets/images/pos-products/kentang-kriwil.png';
    if (str_contains($name, 'matcha')) return '../public/assets/images/pos-products/matcha.png';
    if (str_contains($name, 'ayam') || str_contains($name, 'original')) return '../public/assets/images/pos-products/original.png';
    if (str_contains($name, 'kopi')) return '../public/assets/images/pos-products/kopi.png';
    return '../public/assets/images/pos-products/product-dummy.svg';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Loyalty Club - Lumero POS</title>
    <link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #c41230;
            --red-dark: #6e0015;
            --gold: #ffc72c;
            --gold-glow: rgba(255, 199, 44, 0.5);
            --cream: #fbf9f5;
            --ink: #0f0e0d;
            --muted: #66605b;
            --glass: rgba(255, 255, 255, 0.65);
            --border-light: rgba(0, 0, 0, 0.06);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
        
        body {
            background-color: var(--cream);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            padding-bottom: 100px;
        }

        .stripe-bg {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
            background: linear-gradient(180deg, #fffcf5 0%, #ffffff 100%);
        }
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.45;
            animation: floatBlob 12s infinite alternate cubic-bezier(0.45, 0.05, 0.55, 0.95);
        }
        .blob-1 {
            top: -10%; left: -10%;
            width: 60vw; height: 60vw;
            background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
        }
        .blob-2 {
            top: 20%; right: -20%;
            width: 70vw; height: 70vw;
            background: radial-gradient(circle, var(--red) 0%, transparent 70%);
            animation-delay: -5s;
        }
        .blob-3 {
            bottom: -10%; left: 20%;
            width: 50vw; height: 50vw;
            background: radial-gradient(circle, #ffeed1 0%, transparent 70%);
            animation-delay: -8s;
        }

        .wrapper {
            width: min(960px, 100%);
            margin: 0 auto;
            padding: 40px 24px;
        }

        .claim-banner {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 199, 44, 0.6);
            padding: 16px 24px;
            border-radius: 20px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 20px 40px rgba(255, 199, 44, 0.15), 0 1px 3px rgba(0,0,0,0.05);
            animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .claim-info { display: flex; align-items: center; gap: 16px; }
        .live-dot {
            width: 12px; height: 12px;
            background: var(--red);
            border-radius: 50%;
            position: relative;
        }
        .live-dot::after {
            content: ''; position: absolute; inset: -5px;
            background: var(--red); border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        .hero-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-bottom: 64px;
            align-items: center;
        }
        @media (min-width: 768px) {
            .hero-section { grid-template-columns: 1.2fr 0.8fr; gap: 48px; }
        }

        .hero-content { z-index: 2; }
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(196, 18, 48, 0.08);
            border: 1px solid rgba(196, 18, 48, 0.15);
            border-radius: 99px;
            color: var(--red);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .hero-title {
            font-size: clamp(36px, 6vw, 56px);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.04em;
            color: var(--ink);
            margin-bottom: 20px;
        }
        .hero-title span {
            background: linear-gradient(135deg, var(--red) 0%, #ff5e62 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 17px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 36px;
            max-width: 480px;
        }

        .cta-stack { display: flex; flex-wrap: wrap; gap: 14px; }
        .btn-stripe {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 32px;
            border-radius: 99px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }
        .btn-primary {
            background: var(--ink);
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            background: var(--red);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: var(--ink);
            border: 1px solid var(--border-light);
            backdrop-filter: blur(10px);
        }
        .btn-secondary:hover {
            background: #ffffff;
            border-color: rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        .hero-visual {
            position: relative;
            perspective: 1000px;
            display: flex;
            justify-content: center;
        }
        .isometric-card {
            width: 100%;
            max-width: 340px;
            background: linear-gradient(135deg, var(--red) 0%, var(--red-dark) 100%);
            border-radius: 32px;
            padding: 32px;
            color: #fff;
            box-shadow: 
                20px 30px 60px rgba(196, 18, 48, 0.3),
                inset 0 1px 1px rgba(255, 255, 255, 0.3);
            transform: rotateY(-12deg) rotateX(8deg);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .hero-visual:hover .isometric-card {
            transform: rotateY(0deg) rotateX(0deg) scale(1.02);
        }
        .card-chip {
            width: 40px; height: 28px;
            background: linear-gradient(135deg, var(--gold) 0%, #d49e00 100%);
            border-radius: 6px;
            margin-bottom: 40px;
            box-shadow: inset 0 1px 2px rgba(255,255,255,0.5);
        }

        .section-header {
            margin-bottom: 40px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .section-header h2 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .section-header p { color: var(--muted); font-size: 15px; }

        .reward-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px 20px;
            padding-top: 40px;
        }

        .reward-item {
            position: relative;
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 28px;
            padding: 80px 24px 24px;
            box-shadow: 0 14px 35px rgba(0,0,0,0.03), inset 0 1px 0 rgba(255,255,255,1);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .reward-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 48px rgba(0,0,0,0.08), 0 0 0 1.5px var(--gold);
            background: rgba(255, 255, 255, 0.9);
        }

        .item-visual-float {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .item-visual-float img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 15px 20px rgba(0,0,0,0.22));
            animation: levitate3D 4s infinite ease-in-out alternate;
        }
        .reward-item:nth-child(2) .item-visual-float img { animation-delay: -1.2s; }
        .reward-item:nth-child(3) .item-visual-float img { animation-delay: -2.4s; }
        .reward-item:nth-child(4) .item-visual-float img { animation-delay: -3.5s; }

        .point-tag {
            align-self: flex-start;
            background: var(--ink);
            color: var(--gold);
            font-size: 11px;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 99px;
            margin-bottom: 12px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .item-title { font-size: 18px; font-weight: 800; margin-bottom: 6px; color: var(--ink); }
        .item-desc { font-size: 13px; color: var(--muted); line-height: 1.5; }

        .bento-section { margin-top: 80px; }
        .bento-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        @media (min-width: 768px) {
            .bento-grid { grid-template-columns: repeat(3, 1fr); }
        }
        .bento-box {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 28px;
            padding: 32px 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            transition: border-color 0.3s ease;
        }
        .bento-box:hover { border-color: rgba(0,0,0,0.2); }
        .bento-num {
            font-size: 40px; font-weight: 900;
            color: rgba(0,0,0,0.06);
            position: absolute; top: 16px; right: 24px;
            line-height: 1;
        }
        .bento-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; color: var(--ink); }
        .bento-desc { font-size: 14px; color: var(--muted); line-height: 1.5; }

        .menu-section { margin-top: 80px; }
        .marquee-wrapper {
            width: 100%;
            overflow: hidden;
            padding: 20px 0;
            position: relative;
        }
        .marquee-wrapper::before, .marquee-wrapper::after {
            content: '';
            position: absolute;
            top: 0; bottom: 0;
            width: 50px;
            z-index: 2;
            pointer-events: none;
        }
        .marquee-wrapper::before {
            left: 0;
            background: linear-gradient(to right, var(--cream), transparent);
        }
        .marquee-wrapper::after {
            right: 0;
            background: linear-gradient(to left, var(--cream), transparent);
        }
        .marquee-content {
            display: flex;
            gap: 24px;
            width: max-content;
            animation: marquee 25s linear infinite;
        }
        .marquee-wrapper:hover .marquee-content {
            animation-play-state: paused;
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .menu-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            transition: all 0.3s;
            width: 280px;
            flex-shrink: 0;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.06);
            border-color: rgba(0,0,0,0.1);
        }
        .menu-img {
            width: 90px;
            height: 90px;
            border-radius: 16px;
            background: var(--cream);
            object-fit: contain;
            padding: 8px;
        }
        .menu-info { flex: 1; }
        .menu-title { font-size: 16px; font-weight: 800; margin-bottom: 4px; color: var(--ink); }
        .menu-price { font-size: 14px; color: var(--red); font-weight: 700; margin-bottom: 12px; }
        .menu-btn {
            display: inline-block;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 99px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .menu-btn:hover { background: var(--red); }
        .free-badge {
            position: absolute;
            top: -15px;
            right: -15px;
            background: linear-gradient(135deg, var(--red) 0%, #ff5e62 100%);
            color: #fff;
            font-weight: 900;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 99px;
            transform: rotate(15deg);
            box-shadow: 0 8px 16px rgba(196, 18, 48, 0.3);
            border: 2px solid #fff;
            z-index: 10;
            letter-spacing: 0.05em;
        }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(5vw, 5vh) scale(1.15); }
        }
        @keyframes levitate3D {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-12px) rotate(3deg); }
        }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="stripe-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
</div>

<div class="wrapper">
    <?php if ($claimCode !== '' && !$claimCheck['valid']): ?>
    <div class="claim-banner">
        <div class="claim-info">
            <div class="live-dot"></div>
            <div>
                <b style="font-size: 15px; display:block;">Struk Tertunda Terdeteksi</b>
                <span style="font-size: 13px; color: var(--muted);">Kode <strong><?= htmlspecialchars($claimCode) ?></strong> siap dikonversi ke saldo poin Anda.</span>
            </div>
        </div>
        <a href="login.php?claim=<?= urlencode($claimCode) ?>" class="btn-stripe btn-primary" style="padding: 10px 20px; font-size: 13px;">Klaim &rarr;</a>
    </div>
    <?php endif; ?>

    <header class="hero-section">
        <div class="hero-content">
            <div class="badge-pill">Lumero POS Loyalty Club</div>
            <h1 class="hero-title">Pesananmu,<br>Kini Jadi <span>Hadiah.</span></h1>
            <p class="hero-subtitle">Bukan sekadar transaksi. Kumpulkan poin secara otomatis dari setiap hidangan favoritmu dan nikmati berbagai sajian eksklusif secara cuma-cuma.</p>
            <div class="cta-stack">
                <a href="login.php<?= $claimCode !== '' ? '?claim=' . urlencode($claimCode) : '' ?>" class="btn-stripe btn-primary">
                    Mulai Gabung Sekarang &rarr;
                </a>
                <a href="login.php" class="btn-stripe btn-secondary">
                    Masuk ke Akun
                </a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="isometric-card">
                <div class="card-chip"></div>
                <div style="font-size: 13px; opacity: 0.7; margin-bottom: 4px;">MEMBER EXCLUSIVE</div>
                <div style="font-size: 22px; font-weight: 800; letter-spacing: 0.05em; margin-bottom: 32px;">LUMERO CLUB</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <div style="font-size: 11px; opacity: 0.7;">STATUS</div>
                        <div style="font-size: 14px; font-weight: 700;">ACTIVE REWARD</div>
                    </div>
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--gold); opacity: 0.9;"></div>
                </div>
            </div>
        </div>
    </header>

    <section>
        <div class="section-header">
            <h2>Katalog Hadiah</h2>
            <p>Pilih sajian lezat yang siap Anda tukarkan dengan poin terkumpul.</p>
        </div>

        <div class="reward-showcase">
            <div class="reward-item" onclick="window.location.href='login.php'" style="cursor:pointer;">
                <div class="free-badge">FREE / GRATIS</div>
                <div class="item-visual-float">
                    <img src="../public/assets/images/pos-products/es-teh.png" alt="Es Teh Manis" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'">
                </div>
                <div>
                    <span class="point-tag" style="background:var(--red); color:#fff;">Bonus Daftar</span>
                    <div class="item-title">Es Teh Manis</div>
                    <div class="item-desc">Dapatkan Es Teh segar gratis hanya dengan mendaftarkan nomor WhatsApp Anda sekarang!</div>
                </div>
            </div>

            <?php foreach ($highlights as $rw): ?>
            <div class="reward-item" onclick="window.location.href='login.php'" style="cursor:pointer;">
                <div class="free-badge">FREE / GRATIS</div>
                <div class="item-visual-float">
                    <img src="<?= htmlspecialchars(hook_reward_image($rw)) ?>" alt="<?= htmlspecialchars($rw['name']) ?>">
                </div>
                
                <div>
                    <span class="point-tag"><?= number_format((int)$rw['required_points'],0,',','.') ?> Pts</span>
                    <div class="item-title"><?= htmlspecialchars($rw['name']) ?></div>
                    <div class="item-desc"><?= htmlspecialchars($rw['description'] ?? 'Hadiah hidangan lezat Lumero') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="login.php" class="btn-stripe btn-primary">Login / Daftar untuk Klaim</a>
        </div>
    </section>

    <section class="menu-section">
        <div class="section-header">
            <h2>Pesan Sekarang</h2>
            <p>Menu unggulan Lumero siap dinikmati. Klik untuk pesan langsung via Online Order.</p>
        </div>
        <div class="marquee-wrapper">
            <div class="marquee-content">
                <?php for($i=0; $i<2; $i++): ?>
                    <?php foreach ($mainMenu as $menu): 
                        $imgPath = trim((string)$menu['image']);
                        if ($imgPath !== '' && !preg_match('~^https?://~i', $imgPath)) {
                            $imgPath = '../public/assets/' . ltrim($imgPath, '/');
                        }
                        if ($imgPath === '') {
                            $imgPath = '../public/assets/images/pos-products/product-dummy.svg';
                        }
                    ?>
                    <div class="menu-card">
                        <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($menu['name']) ?>" class="menu-img" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'">
                        <div class="menu-info">
                            <div class="menu-title"><?= htmlspecialchars($menu['name']) ?></div>
                            <div class="menu-price"><?= rupiah((float)$menu['min_price']) ?></div>
                            <a href="online-order.php" class="menu-btn">Pesan Sekarang</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <section class="bento-section">
        <div class="section-header">
            <h2>Kenapa Lumero Club?</h2>
            <p>Dirancang untuk kecepatan dan kemudahan Anda.</p>
        </div>
        <div class="bento-grid">
            <div class="bento-box">
                <div class="bento-num">01</div>
                <div class="bento-title">Tanpa Aplikasi</div>
                <div class="bento-desc">Tidak perlu download atau memenuhi memori HP. Cukup gunakan nomor WhatsApp Anda saat bertransaksi.</div>
            </div>
            <div class="bento-box">
                <div class="bento-num">02</div>
                <div class="bento-title">Poin Otomatis</div>
                <div class="bento-desc">Sistem POS kami langsung mengonversi total pesanan Anda menjadi poin secara real-time dan akurat.</div>
            </div>
            <div class="bento-box">
                <div class="bento-num">03</div>
                <div class="bento-title">Klaim Instan</div>
                <div class="bento-desc">Punya struk tertunda? Masukkan atau scan kode klaim dan poin akan langsung bertambah detik itu juga.</div>
            </div>
        </div>
    </section>
</div>

</body>
</html>