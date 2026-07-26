<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';
loyalty_ensure_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'spin_wheel') {
    header('Content-Type: application/json');
    try {
        require_once __DIR__ . '/../helpers/RouletteHelper.php';
        $prize = RouletteHelper::spinWheel($pdo, 'kalibunder_go');
        $_SESSION['pending_event_reward'] = $prize;
        echo json_encode(['success' => true, 'prize' => $prize]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* ──────────────────────────────────────────────────────
   SURPRISE FLOW: Intercept saat ada ?claim=KODE
   ────────────────────────────────────────────────────── */
$claimCode = strtoupper(trim((string)($_GET['claim'] ?? '')));
$claimCheck = ['valid' => false];

if ($claimCode !== '') {
    $claimCheck = loyalty_check_claim_code($pdo, $claimCode);
}

// ── Pilih variasi anti-ulang ──────────────────────────
function pick_surprise_variant(string $claimCode): string {
    if (isset($_SESSION['surprise_variant_cache'][$claimCode])) {
        return $_SESSION['surprise_variant_cache'][$claimCode];
    }
    $all = ['A', 'B'];
    $last = $_SESSION['member_last_variant'] ?? null;
    $candidates = array_values(array_filter($all, fn($v) => $v !== $last));
    $chosen = $candidates[array_rand($candidates)];
    $_SESSION['member_last_variant'] = $chosen;
    $_SESSION['surprise_variant_cache'][$claimCode] = $chosen;
    return $chosen;
}

// ── Jika kode klaim valid → tampilkan SURPRISE LANDING ──
if ($claimCode !== '' && $claimCheck['valid'] === true) {
    $variant    = pick_surprise_variant($claimCode);
    $points     = (int)($claimCheck['points'] ?? 0);
    $memberId   = (int)($_SESSION['member_id'] ?? 0);
    $isLoggedIn = $memberId > 0;
    $autoMsg    = '';
    $member     = null;

    $memberName      = $member['name'] ?? '';
    $isReturning     = $isLoggedIn;
    $loginUrl        = url('/member/login.php') . '?claim=' . urlencode($claimCode);
    $dashboardUrl    = url('/member/dashboard.php');
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
           VARIASI A: ROULETTE TICKER
           ════════════════════════════════════════════════════ */
        .ticker-viewport {
            position: relative; width: 100%; height: 120px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.04) 0%, rgba(15, 23, 42, 0.01) 100%);
            border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; margin: 28px 0;
            display: flex; align-items: center; overflow: hidden;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .ticker-viewport::before, .ticker-viewport::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 50px; z-index: 5; pointer-events: none;
        }
        .ticker-viewport::before { left: 0; background: linear-gradient(90deg, rgba(255, 255, 255, 0.9) 0%, transparent 100%); }
        .ticker-viewport::after { right: 0; background: linear-gradient(-90deg, rgba(255, 255, 255, 0.9) 0%, transparent 100%); }

        .ticker-selector {
            position: absolute; left: 50%; top: 8px; bottom: 8px; width: 3px;
            background: var(--red); transform: translateX(-50%); z-index: 10;
            border-radius: 99px; box-shadow: 0 0 14px rgba(196, 18, 48, 0.6);
        }
        .ticker-selector::before, .ticker-selector::after {
            position: absolute; left: 50%; transform: translateX(-50%);
            color: var(--red); font-size: 11px; font-weight: 900;
        }
        .ticker-selector::before { content: '▼'; top: -10px; }
        .ticker-selector::after { content: '▲'; top: auto; bottom: -10px; }

        .ticker-track {
            display: flex; gap: 12px; padding-left: 50%; 
            will-change: transform;
        }
        .ticker-card {
            width: 96px; height: 88px; background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 16px;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); flex-shrink: 0;
            transition: border-color 0.3s, transform 0.3s;
        }
        .ticker-card.gold-card {
            background: linear-gradient(135deg, #fffcf0 0%, #fef3c7 100%);
            border: 1.5px solid #f59e0b; box-shadow: 0 8px 20px rgba(245, 158, 11, 0.15);
        }
        .ticker-card .card-icon { font-size: 26px; line-height: 1; margin-bottom: 2px; }
        @keyframes iconIdle {
            0%, 100% { transform: translateY(0) scale(1) rotate(0deg); }
            50% { transform: translateY(-4px) scale(1.1) rotate(4deg); }
        }
        .ticker-card .card-icon i {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 4px 6px rgba(245,158,11,0.3));
            animation: iconIdle 2.5s ease-in-out infinite;
            transform-origin: bottom center;
        }
        .ticker-card:nth-child(odd) .card-icon i { animation-delay: 0.3s; animation-duration: 2.8s; }
        .ticker-card:nth-child(3n) .card-icon i { animation-delay: 0.8s; animation-duration: 3.1s; }
        .ticker-card:nth-child(5n) .card-icon i { animation-delay: 1.2s; }
        .ticker-card.gold-card .card-icon i {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 4px 6px rgba(239,68,68,0.3));
        }
        .ticker-card .card-val { font-size: 13px; font-weight: 800; color: var(--ink); letter-spacing: -0.02em; }

        .btn-gacha {
            width: 100%; padding: 18px 28px;
            background: linear-gradient(135deg, var(--red) 0%, #e01535 100%);
            color: #ffffff; font-size: 16px; font-weight: 800;
            border: none; border-radius: 16px; cursor: pointer;
            box-shadow: 0 12px 30px rgba(196, 18, 48, 0.3);
            transition: transform 0.2s, box-shadow 0.2s; margin-bottom: 20px;
        }
        .btn-gacha:hover { transform: translateY(-2px); box-shadow: 0 16px 36px rgba(196, 18, 48, 0.4); }
        .btn-gacha:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    </style>
    <!-- FontAwesome for Solid SVG Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ════════════════════════════════════════════════════
           VARIASI B: 3D MYSTERY POD (Lottie)
           ════════════════════════════════════════════════════ */
        .pod-container {
            position: relative; width: 220px; height: 220px; margin: 0 auto 24px;
            cursor: pointer; transition: transform 0.15s ease-out;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, transparent 70%);
        }
        .pod-container:active { transform: scale(0.95); }
        .pod-container lottie-player { width: 100%; height: 100%; pointer-events: none; }


        
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
         VARIASI A: ROULETTE TICKER
         ═══════════════════════════════════════════════════ -->
    <?php if ($variant === 'A'): ?>
    <div class="stage" id="stage-A">
        <div class="headline">
            <?php if ($isReturning && $memberName): ?>
                Selamat kembali, <span><?= htmlspecialchars(explode(' ', $memberName)[0]) ?>!</span> <i class="fa-solid fa-crown" style="color:#f59e0b;"></i>
            <?php else: ?>
                Kejutan <span>Poin Hadiah!</span> <i class="fa-solid fa-wand-magic-sparkles" style="color:#f59e0b;"></i>
            <?php endif; ?>
        </div>
        <p class="sub-headline">Pesananmu telah dikonversi. Undi roulette sekarang untuk mengamankan poin ekstra ke dompetmu!</p>

        <!-- Roulette Ticker Viewport -->
        <div class="ticker-viewport">
            <div class="ticker-selector"></div>
            <div class="ticker-track" id="tickerTrack">
                <!-- Dynamically populated by JS -->
            </div>
        </div>

        <button class="btn-gacha" id="spinTickerBtn" onclick="startTickerSpin()">
            <i class="fa-solid fa-bolt" style="margin-right:6px;"></i> Putar Roulette Sekarang
        </button>

        <div id="ticker-result" style="display:none; margin-top: 24px;">
            <div class="points-badge" style="animation: popIn 0.6s cubic-bezier(0.34,1.56,0.64,1) both;">
                <div class="poin-num"><?= $points ?></div>
                <div class="poin-label"><span>POIN</span><span>BERHASIL!</span></div>
            </div>
            <br>
            <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
                <div class="success-badge"><i class="fa-solid fa-check-circle" style="color:#10b981; margin-right:4px;"></i> Poin otomatis masuk ke dompetmu!</div>
                <a href="<?= $dashboardUrl ?>" class="cta-btn"><i class="fa-solid fa-trophy" style="margin-right:6px;"></i> Lihat Dompet Saya</a>
            <?php else: ?>
                <a href="<?= $loginUrl ?>" class="cta-btn">
                    <span class="cta-pulse"></span>
                    <i class="fa-solid fa-lock" style="margin-right:6px;"></i> Amankan Poin Ini Sekarang!
                </a>
                <p class="helper-text">Masuk dengan WhatsApp agar poin tidak hangus.</p>
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
                Kejutan menanti, <span><?= htmlspecialchars(explode(' ', $memberName)[0]) ?>!</span> <i class="fa-solid fa-crown" style="color:#f59e0b;"></i>
            <?php else: ?>
                Sebuah <span>Misteri!</span> <i class="fa-solid fa-gift" style="color:#ef4444;"></i>
            <?php endif; ?>
        </div>
        <p class="sub-headline">Ada hadiah rahasia di dalam pod ini.<br>Ketuk pod misteri ini untuk membukanya!</p>

        <div class="pod-container" id="podContainer" onclick="openPod()">
            <lottie-player src="../public/assets/images/reward.json" background="transparent" speed="1" style="width: 100%; height: 100%; pointer-events: none;" loop autoplay></lottie-player>
        </div>

        <div id="pod-result" style="display:none; margin-top:24px;">
            <div class="points-badge" style="animation: popIn 0.6s cubic-bezier(0.34,1.56,0.64,1) both;">
                <div class="poin-num"><?= $points ?></div>
                <div class="poin-label"><span>POIN</span><span>UNTUKMU!</span></div>
            </div>
            <br>
            <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
                <div class="success-badge"><i class="fa-solid fa-check-circle" style="color:#10b981; margin-right:4px;"></i> Koin berhasil masuk ke dompetmu!</div>
                <a href="<?= $dashboardUrl ?>" class="cta-btn"><i class="fa-solid fa-trophy" style="margin-right:6px;"></i> Lihat Dompet Saya</a>
            <?php else: ?>
                <a href="<?= $loginUrl ?>" class="cta-btn">
                    <span class="cta-pulse"></span>
                    <i class="fa-solid fa-lock" style="margin-right:6px;"></i> Klaim Hadiah Sekarang!
                </a>
                <p class="helper-text">Daftarkan nomor WhatsApp agar poin ini tidak hangus!</p>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>

</div><!-- /surprise-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<script>
    // Variables passed from PHP
    const variant = '<?= $variant ?>';
    const rewardPoints = <?= $points ?>;

    function fireConfetti() {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#c41230', '#ffc72c', '#ffffff', '#ff5e62', '#fde68a', '#f59e0b']
            });
        }
    }

    /* ══════════════════════════════════════════
       VARIASI A: ROULETTE TICKER
       ══════════════════════════════════════════ */
    <?php if ($variant === 'A'): ?>
    const track = document.getElementById('tickerTrack');
    const btnSpin = document.getElementById('spinTickerBtn');
    const resultDiv = document.getElementById('ticker-result');
    const CARD_WIDTH = 96 + 12; // width + gap
    
    // Generate Items (Removed Zonk)
    const items = [
        { icon: '<i class="fa-solid fa-gift"></i>', val: '10 Pts' }, 
        { icon: '<i class="fa-solid fa-money-bill-wave"></i>', val: '50 Pts' }, 
        { icon: '<i class="fa-solid fa-hand-holding-heart"></i>', val: '20 Pts' }, 
        { icon: '<i class="fa-solid fa-star"></i>', val: 'Bonus Spesial' },
        { icon: '<i class="fa-solid fa-fire"></i>', val: '5 Pts' }, 
        { icon: '<i class="fa-solid fa-gem"></i>', val: '100 Pts' }
    ];

    // Create a base block of 10 items for seamless looping
    let baseItems = [];
    for (let i = 0; i < 10; i++) {
        baseItems.push(items[Math.floor(Math.random() * items.length)]);
    }
    
    const TARGET_INDEX = 65; // Place target far ahead
    
    // Build DOM (80 cards for a long runway)
    let cardsHTML = '';
    for (let i = 0; i < 80; i++) {
        const item = baseItems[i % 10];
        const isTarget = i === TARGET_INDEX;
        const isGold = i % 2 === 0;
        const cardClass = isGold ? 'ticker-card gold-card' : 'ticker-card';
        
        if (isTarget) {
            cardsHTML += `<div class="${cardClass}"><div class="card-icon"><i class="fa-solid fa-crown"></i></div><div class="card-val">${rewardPoints} Pts</div></div>`;
        } else {
            cardsHTML += `<div class="${cardClass}"><div class="card-icon">${item.icon}</div><div class="card-val">${item.val}</div></div>`;
        }
    }
    if (track) track.innerHTML = cardsHTML;

    // Physics Engine Variables
    let state = 'idle'; // idle, spin, decel, done
    let currentX = 0;
    let velocity = 0.5; // pixels per frame
    let targetX = 0;
    const LOOP_RESET_X = 10 * CARD_WIDTH; // Reset after 10 cards
    
    function updatePhysics() {
        if (state === 'done') return;
        
        currentX += velocity;
        
        if (state === 'idle') {
            // Infinite seamless loop
            if (currentX >= LOOP_RESET_X) {
                currentX -= LOOP_RESET_X;
            }
        } else if (state === 'spin') {
            // Accelerate
            if (velocity < 38) velocity += 0.8;
            
            const decel = 0.2;
            const distToStop = (velocity * velocity) / (2 * decel);
            const distToTarget = targetX - currentX;
            
            // Start decelerating when distance to target matches stopping distance
            if (distToTarget <= distToStop) {
                state = 'decel';
            }
        } else if (state === 'decel') {
            const decel = 0.2;
            const distToTarget = Math.max(0, targetX - currentX);
            
            let idealVelocity = Math.sqrt(2 * decel * distToTarget);
            if (idealVelocity < 0.6) idealVelocity = 0.6; // Creep speed so it doesn't freeze
            
            velocity = idealVelocity;
            
            if (distToTarget <= 0.6) {
                velocity = 0;
                currentX = targetX;
                state = 'done';
                
                setTimeout(() => {
                    fireConfetti();
                    btnSpin.style.display = 'none';
                    resultDiv.style.display = 'block';
                }, 300);
            }
        }
        
        track.style.transform = `translate3d(-${currentX}px, 0, 0)`;
        requestAnimationFrame(updatePhysics);
    }
    
    // Start engine
    requestAnimationFrame(updatePhysics);

    window.startTickerSpin = function() {
        if (state !== 'idle') return;
        
        btnSpin.disabled = true;
        btnSpin.innerHTML = '⚡ Mengundi...';
        
        state = 'spin';
        const randomOffset = Math.floor(Math.random() * 24) - 12; // +/- 12px natural offset
        targetX = (TARGET_INDEX * CARD_WIDTH) + (96 / 2) + randomOffset;
    };

    /* ══════════════════════════════════════════
       VARIASI B: 3D MYSTERY POD (Lottie)
       ══════════════════════════════════════════ */
    <?php elseif ($variant === 'B'): ?>
    let podOpened = false;
    window.openPod = function() {
        if (podOpened) return;
        podOpened = true;
        const pod = document.getElementById('podContainer');
        const result = document.getElementById('pod-result');
        
        // Scale down effect via JS if needed
        pod.style.transform = 'scale(0.95)';
        setTimeout(() => { pod.style.transform = 'scale(1)'; }, 150);

        setTimeout(() => {
            fireConfetti();
            pod.style.opacity = '0.5';
            pod.style.pointerEvents = 'none';
            result.style.display = 'block';
        }, 300);
    };

    <?php endif; ?>

    /* ══════════════════════════════════════════
       AUTO-CLAIM SUCCESS CONFETTI (member logged in)
       ══════════════════════════════════════════ */

    <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
    window.addEventListener('load', () => {
        setTimeout(() => fireConfetti(), 600);
    });
    <?php endif; ?>
</script>
</body>
</html>
<?php 
    exit;
} 
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Grand Opening Kalibunder — Lumero | Putar & Menang!</title>
    <meta name="description" content="Putar roulette GRATIS dan menangkan hadiah eksklusif dari Lumero Kalibunder. 100% pasti menang!">
    <link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #c41230;
            --red-dark: #7a001b;
            --gold: #ffc72c;
            --gold-dark: #e6a800;
            --ink: #0f172a;
            --muted: #64748b;
            --surface: #fff;
            --cream: #fffcf5;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #0a0a0f;
            color: #fff;
            min-height: 100svh;
            overflow-x: hidden;
        }

        /* ── Animated BG ── */
        .bg-wrap {
            position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
            background: radial-gradient(ellipse at 15% 10%, rgba(196,18,48,0.25) 0%, transparent 50%),
                        radial-gradient(ellipse at 85% 80%, rgba(255,199,44,0.15) 0%, transparent 50%),
                        radial-gradient(ellipse at 50% 50%, rgba(10,10,20,1) 0%, #0a0a0f 100%);
        }
        .bg-grid {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
        }
        .pulse-orb {
            position: absolute; border-radius: 50%; pointer-events: none;
            animation: pulseGlow 6s ease-in-out infinite alternate;
        }
        .orb-1 { width: 500px; height: 500px; top: -200px; left: -150px; background: radial-gradient(circle, rgba(196,18,48,0.3) 0%, transparent 70%); animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(255,199,44,0.2) 0%, transparent 70%); animation-delay: -3s; }
        @keyframes pulseGlow { 0% { transform: scale(1); opacity: 0.6; } 100% { transform: scale(1.2); opacity: 1; } }

        /* ── Layout ── */
        .wrapper { width: min(820px, 100%); margin: 0 auto; padding: 0 20px; position: relative; z-index: 10; }

        /* ── Navbar ── */
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 20px; position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,20,0.7); backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .nav-logo { display: flex; align-items: center; gap: 10px; font-weight: 900; font-size: 17px; color: #fff; }
        .nav-logo img { width: 30px; height: 30px; border-radius: 8px; }
        .nav-badge {
            background: linear-gradient(135deg, var(--red) 0%, #ff3d5a 100%);
            color: #fff; font-size: 11px; font-weight: 800; padding: 4px 10px;
            border-radius: 99px; letter-spacing: 0.04em; text-transform: uppercase;
            animation: badgePulse 2s ease-in-out infinite;
        }
        @keyframes badgePulse { 0%,100% { box-shadow: 0 0 0 0 rgba(196,18,48,0.5); } 50% { box-shadow: 0 0 0 8px rgba(196,18,48,0); } }
        .btn-nav {
            border: 1px solid rgba(255,255,255,0.2); padding: 9px 18px; border-radius: 99px;
            text-decoration: none; color: #fff; font-weight: 700; font-size: 12px;
            background: rgba(255,255,255,0.08); backdrop-filter: blur(10px);
            transition: all 0.2s;
        }
        .btn-nav:hover { background: rgba(255,255,255,0.18); }

        /* ── Urgency Banner ── */
        .urgency-banner {
            background: linear-gradient(90deg, var(--red-dark) 0%, var(--red) 50%, var(--red-dark) 100%);
            background-size: 200% 100%;
            animation: shimmerBg 3s linear infinite;
            text-align: center; padding: 10px 20px;
            font-size: 13px; font-weight: 700; letter-spacing: 0.02em;
        }
        @keyframes shimmerBg { 0% { background-position: 0 0; } 100% { background-position: 200% 0; } }
        .urgency-banner span { animation: textBlink 1.5s step-end infinite; }
        @keyframes textBlink { 0%,100% { opacity: 1; } 50% { opacity: 0.6; } }

        /* ── Live Counter ── */
        .live-counter {
            display: flex; align-items: center; justify-content: center; gap: 24px;
            padding: 16px 20px; background: rgba(255,255,255,0.04);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .counter-item { text-align: center; }
        .counter-number { font-size: 22px; font-weight: 900; color: var(--gold); font-variant-numeric: tabular-nums; }
        .counter-label { font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.08em; }
        .counter-sep { width: 1px; height: 36px; background: rgba(255,255,255,0.1); }

        /* ── Hero Section ── */
        .hero-section { padding: 36px 0 20px; text-align: center; }
        .hero-label {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,199,44,0.12); border: 1px solid rgba(255,199,44,0.25);
            color: var(--gold); font-size: 12px; font-weight: 800; padding: 6px 14px;
            border-radius: 99px; margin-bottom: 20px; letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .hero-title {
            font-size: clamp(30px, 7vw, 52px); font-weight: 900; line-height: 1.1;
            letter-spacing: -0.03em; margin-bottom: 16px;
        }
        .hero-title .accent { 
            background: linear-gradient(135deg, var(--gold) 0%, #ff9500 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-title .accent-red {
            background: linear-gradient(135deg, #ff6b6b 0%, var(--red) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-sub {
            font-size: 15px; color: rgba(255,255,255,0.55); font-weight: 500;
            line-height: 1.7; max-width: 380px; margin: 0 auto 32px;
        }
        .hero-sub b { color: rgba(255,255,255,0.85); }

        /* ── Guarantee Badges ── */
        .guarantee-row {
            display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;
            margin-bottom: 32px;
        }
        .g-badge {
            display: flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            padding: 8px 14px; border-radius: 99px; font-size: 12px; font-weight: 700;
            color: rgba(255,255,255,0.8);
        }

        /* ── Slot Machine Card ── */
        .slot-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 28px; padding: 28px 20px 24px;
            margin-bottom: 20px; position: relative; overflow: hidden;
            backdrop-filter: blur(20px);
        }
        .slot-card::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(196,18,48,0.08) 0%, rgba(255,199,44,0.05) 100%);
            pointer-events: none;
        }
        .slot-card-title {
            font-size: 13px; font-weight: 800; color: rgba(255,255,255,0.4);
            text-transform: uppercase; letter-spacing: 0.1em; text-align: center;
            margin-bottom: 20px;
        }

        .slot-viewport {
            position: relative; width: 100%; height: 120px;
            background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; margin: 0 auto 24px; overflow: hidden;
            display: flex; align-items: center;
            box-shadow: inset 0 0 30px rgba(0,0,0,0.3);
        }
        .slot-viewport::before, .slot-viewport::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 80px; z-index: 2; pointer-events: none;
        }
        .slot-viewport::before { left: 0; background: linear-gradient(90deg, rgba(10,10,15,0.95) 0%, transparent 100%); }
        .slot-viewport::after  { right: 0; background: linear-gradient(-90deg, rgba(10,10,15,0.95) 0%, transparent 100%); }

        .slot-target-line {
            position: absolute; left: 50%; top: 10px; bottom: 10px; width: 2px;
            background: linear-gradient(180deg, var(--gold) 0%, var(--red) 100%);
            transform: translateX(-50%); z-index: 3;
            box-shadow: 0 0 16px var(--gold), 0 0 6px var(--red);
        }
        .slot-target-line::before, .slot-target-line::after {
            content: ''; position: absolute; left: 50%; transform: translateX(-50%);
            width: 10px; height: 10px; background: var(--gold);
            clip-path: polygon(50% 100%, 0 0, 100% 0);
        }
        .slot-target-line::before { top: -2px; }
        .slot-target-line::after  { bottom: -2px; transform: translateX(-50%) rotate(180deg); }

        .slot-track {
            display: flex; gap: 12px; padding-left: 50%; will-change: transform; align-items: center;
        }
        .slot-item {
            width: 88px; height: 88px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 6px; flex-shrink: 0; padding: 8px; text-align: center;
            transition: border-color 0.3s;
        }
        .slot-item.winner {
            border-color: var(--gold);
            box-shadow: 0 0 20px rgba(255,199,44,0.3), inset 0 0 20px rgba(255,199,44,0.05);
        }
        .slot-item img { width: 44px; height: 44px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4)); }
        .slot-item span { font-size: 10px; font-weight: 800; color: rgba(255,255,255,0.85); line-height: 1.2; }

        /* ── CTA Button ── */
        .btn-spin {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 18px 24px;
            background: linear-gradient(135deg, var(--red) 0%, #ff2244 50%, var(--red) 100%);
            background-size: 200% 100%;
            color: #fff; font-size: 17px; font-weight: 900; border: none; border-radius: 18px;
            cursor: pointer; letter-spacing: -0.01em;
            box-shadow: 0 12px 40px rgba(196,18,48,0.5), 0 0 0 1px rgba(255,255,255,0.1) inset;
            transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
            animation: ctaPulse 2.5s ease-in-out infinite;
            position: relative; overflow: hidden;
        }
        .btn-spin::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.4s;
        }
        .btn-spin:hover::before { left: 100%; }
        .btn-spin:hover { transform: translateY(-2px); box-shadow: 0 20px 50px rgba(196,18,48,0.6), 0 0 0 1px rgba(255,255,255,0.15) inset; }
        .btn-spin:disabled { opacity: 0.6; cursor: not-allowed; transform: none; animation: none; }
        @keyframes ctaPulse { 0%,100% { box-shadow: 0 12px 40px rgba(196,18,48,0.5); } 50% { box-shadow: 0 12px 60px rgba(196,18,48,0.7); } }

        .btn-spin .spin-icon { font-size: 22px; animation: spinIcon 3s linear infinite; }
        @keyframes spinIcon { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .hint-text {
            text-align: center; font-size: 12px; color: rgba(255,255,255,0.3); font-weight: 600;
            margin-top: 12px;
        }

        /* ── Result Modal (inline, not popup) ── */
        #result-modal {
            display: none; margin-top: 20px; padding: 24px;
            background: linear-gradient(145deg, rgba(255,199,44,0.1) 0%, rgba(196,18,48,0.08) 100%);
            border: 1px solid rgba(255,199,44,0.25); border-radius: 20px;
            text-align: center; animation: popIn 0.5s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes popIn { from { opacity:0; transform:scale(0.9) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .result-congrats { font-size: 32px; margin-bottom: 4px; }
        .result-title { font-size: 20px; font-weight: 900; color: #fff; margin-bottom: 4px; }
        .result-prize-name { font-size: 26px; font-weight: 900; color: var(--gold); margin-bottom: 8px; letter-spacing: -0.02em; }
        .result-sub { font-size: 13px; color: rgba(255,255,255,0.5); font-weight: 600; margin-bottom: 20px; line-height: 1.6; }

        .btn-claim {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 16px 24px;
            background: linear-gradient(135deg, var(--gold) 0%, #ffb700 100%);
            color: var(--ink); font-size: 16px; font-weight: 900; border: none; border-radius: 16px;
            cursor: pointer; text-decoration: none;
            box-shadow: 0 8px 30px rgba(255,199,44,0.4);
            transition: all 0.3s; letter-spacing: -0.01em;
            animation: claimPulse 1.5s ease-in-out infinite;
        }
        .btn-claim:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(255,199,44,0.6); }
        @keyframes claimPulse { 0%,100% { box-shadow: 0 8px 30px rgba(255,199,44,0.4); } 50% { box-shadow: 0 8px 50px rgba(255,199,44,0.7); } }
        .claim-warning { font-size: 11px; color: rgba(255,255,255,0.3); margin-top: 10px; font-weight: 600; }

        /* ── Social Proof Ticker ── */
        .ticker-wrap {
            overflow: hidden; padding: 14px 0;
            border-top: 1px solid rgba(255,255,255,0.06);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 40px; background: rgba(0,0,0,0.2);
        }
        .ticker-track {
            display: inline-flex; gap: 48px; white-space: nowrap;
            animation: ticker 25s linear infinite;
            font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.5);
        }
        .ticker-track .win { color: var(--gold); font-weight: 800; }
        @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        /* ── Section ── */
        .section { margin-bottom: 48px; }
        .section-head { text-align: center; margin-bottom: 24px; }
        .section-tag {
            display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--gold); margin-bottom: 8px;
        }
        .section-title { font-size: 22px; font-weight: 900; color: #fff; margin-bottom: 8px; }
        .section-sub { font-size: 14px; color: rgba(255,255,255,0.4); font-weight: 500; line-height: 1.6; }

        /* ── Prize Grid ── */
        .prize-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        .prize-card {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; padding: 20px 16px; text-align: center;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .prize-card:hover { border-color: rgba(255,199,44,0.3); background: rgba(255,199,44,0.05); transform: translateY(-2px); }
        .prize-card::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, transparent 70%, rgba(255,199,44,0.03) 100%); }
        .prize-card img { width: 72px; height: 72px; object-fit: contain; margin-bottom: 12px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4)); transition: transform 0.3s; }
        .prize-card:hover img { transform: scale(1.1) rotate(-3deg); }
        .prize-card h4 { font-size: 13px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .prize-card .prize-badge {
            font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 99px;
            background: rgba(255,199,44,0.15); color: var(--gold); letter-spacing: 0.04em;
        }
        .prize-marquee { position: absolute; top: 10px; right: 10px; font-size: 16px; animation: prizeFloat 2s ease-in-out infinite alternate; }
        @keyframes prizeFloat { 0% { transform: translateY(0) rotate(0deg); } 100% { transform: translateY(-4px) rotate(8deg); } }

        /* ── How It Works ── */
        .steps { display: flex; flex-direction: column; gap: 16px; }
        .step {
            display: flex; align-items: flex-start; gap: 16px;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px; padding: 18px;
        }
        .step-num {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 900;
            background: linear-gradient(135deg, var(--red-dark) 0%, var(--red) 100%);
        }
        .step-text h4 { font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .step-text p { font-size: 13px; color: rgba(255,255,255,0.45); font-weight: 500; line-height: 1.5; }

        /* ── Location Card ── */
        .location-card {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px; overflow: hidden;
        }
        .location-header {
            padding: 24px 20px 16px;
            background: linear-gradient(135deg, rgba(196,18,48,0.15) 0%, rgba(255,199,44,0.08) 100%);
        }
        .event-countdown {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(196,18,48,0.2); border: 1px solid rgba(196,18,48,0.3);
            color: #ff6b6b; font-size: 12px; font-weight: 800;
            padding: 6px 12px; border-radius: 99px; margin-bottom: 12px;
            animation: countdownBlink 2s step-end infinite;
        }
        @keyframes countdownBlink { 0%,100% { opacity: 1; } 50% { opacity: 0.6; } }
        .location-header h3 { font-size: 20px; font-weight: 900; color: #fff; margin-bottom: 4px; }
        .location-header p { font-size: 14px; color: rgba(255,255,255,0.45); font-weight: 500; }
        .map-placeholder {
            height: 160px; background: rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .map-placeholder iframe { width: 100%; height: 100%; border: 0; }

        /* ── Bottom CTA ── */
        .bottom-cta {
            text-align: center; padding: 40px 0 60px;
        }
        .bottom-cta h2 { font-size: 24px; font-weight: 900; color: #fff; margin-bottom: 8px; }
        .bottom-cta p { font-size: 14px; color: rgba(255,255,255,0.4); margin-bottom: 24px; font-weight: 500; line-height: 1.6; }

        .btn-cta-big {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            padding: 18px 40px; border-radius: 18px;
            background: linear-gradient(135deg, var(--red) 0%, #ff2244 100%);
            color: #fff; font-size: 16px; font-weight: 900; text-decoration: none;
            box-shadow: 0 12px 40px rgba(196,18,48,0.4);
            transition: all 0.3s;
        }
        .btn-cta-big:hover { transform: translateY(-2px); box-shadow: 0 20px 50px rgba(196,18,48,0.6); }

        /* ── Misc ── */
        @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

        /* ── Mobile fixes ── */
        @media (max-width: 400px) {
            .live-counter { gap: 12px; }
            .guarantee-row .g-badge { font-size: 11px; padding: 7px 10px; }
        }
    </style>
</head>
<body>

<div class="bg-wrap">
    <div class="bg-grid"></div>
    <div class="pulse-orb orb-1"></div>
    <div class="pulse-orb orb-2"></div>
</div>

<!-- Urgency Banner -->
<div class="urgency-banner">
    🔴 <span>LIVE</span> &nbsp;·&nbsp; GRAND OPENING KALIBUNDER &nbsp;·&nbsp; Event Terbatas — Hadiah Habis, Kesempatan Hangus!
</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-logo">
        <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
        Lumero
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
        <div class="nav-badge">100% Menang</div>
        <a href="<?= url('/member/login.php') ?>?source=organic" class="btn-nav">Cek Tiket</a>
    </div>
</nav>

<!-- Live Counter -->
<div class="live-counter">
    <div class="counter-item">
        <div class="counter-number" id="counter-spins">247</div>
        <div class="counter-label">Putaran Hari Ini</div>
    </div>
    <div class="counter-sep"></div>
    <div class="counter-item">
        <div class="counter-number" id="counter-left">53</div>
        <div class="counter-label">Sisa Putaran</div>
    </div>
    <div class="counter-sep"></div>
    <div class="counter-item">
        <div class="counter-number" id="counter-timer">02:47:09</div>
        <div class="counter-label">Waktu Tersisa Hari Ini</div>
    </div>
</div>

<div class="wrapper">
    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-label">✦ Grand Opening Eksklusif</div>
        <h1 class="hero-title">
            Putar. Menang.<br>
            <span class="accent">Langsung Klaim.</span>
        </h1>
        <p class="hero-sub">
            Setiap putaran <b>dijamin menang.</b> Hadiah langsung diamankan ke nomor WhatsApp Anda dalam hitungan detik.
        </p>

        <!-- Guarantee Badges -->
        <div class="guarantee-row">
            <div class="g-badge">✅ 100% Pasti Menang</div>
            <div class="g-badge">🔒 Tanpa Syarat Tersembunyi</div>
            <div class="g-badge">⚡ Klaim Instan</div>
        </div>
    </header>

    <!-- Slot Machine Card -->
    <div class="slot-card">
        <div class="slot-card-title">🎰 Roulette Kejutan — Putar Sekarang</div>

        <div class="slot-viewport">
            <div class="slot-target-line"></div>
            <div class="slot-track" id="slot-track"><!-- JS populated --></div>
        </div>

        <div id="action-area">
            <button id="btn-spin" onclick="spinRoulette()" class="btn-spin">
                <span class="spin-icon">🎰</span>
                Putar Roulette — GRATIS!
            </button>
            <p class="hint-text">🔒 Tanpa login · Tanpa syarat · Tanpa biaya tersembunyi</p>
        </div>

        <!-- Result Modal (inline) -->
        <div id="result-modal">
            <div class="result-congrats">🎉🎊🎉</div>
            <div class="result-title">Selamat! Anda Menang!</div>
            <div class="result-prize-name" id="prize-name">Hadiah</div>
            <p class="result-sub">
                Tiket hadiah ini <b style="color:rgba(255,255,255,0.8);">akan hangus dalam 48 jam</b> jika tidak diamankan ke nomor WhatsApp Anda sekarang.
                <br>Jangan biarkan orang lain mengklaimnya!
            </p>
            <a id="claim-btn" href="<?= url('/member/login.php') ?>?source=event_kalibunder" class="btn-claim">
                🔐 Amankan Hadiah Ke WhatsApp Saya
            </a>
            <p class="claim-warning">*Masukkan nomor WA · Tiket digital dikirim otomatis · Klaim di Outlet Kalibunder</p>
        </div>
    </div>

    <!-- Social Proof Ticker -->
    <div class="ticker-wrap">
        <div class="ticker-track">
            <span>🎉 <span class="win">0812-xxxx-9912</span> baru saja mengamankan <span class="win">Paket Ayam 1 Ekor!</span></span>
            <span>🔥 <span class="win">0857-xxxx-2234</span> memenangkan <span class="win">Tumbler Eksklusif!</span></span>
            <span>⚡ <span class="win">0896-xxxx-1122</span> dapat <span class="win">Es Krim Lumero!</span></span>
            <span>🏆 <span class="win">0821-xxxx-4451</span> berhasil klaim <span class="win">Paket Ayam + Saos!</span></span>
            <span>🎉 <span class="win">0812-xxxx-9912</span> baru saja mengamankan <span class="win">Paket Ayam 1 Ekor!</span></span>
            <span>🔥 <span class="win">0857-xxxx-2234</span> memenangkan <span class="win">Tumbler Eksklusif!</span></span>
            <span>⚡ <span class="win">0896-xxxx-1122</span> dapat <span class="win">Es Krim Lumero!</span></span>
            <span>🏆 <span class="win">0821-xxxx-4451</span> berhasil klaim <span class="win">Paket Ayam + Saos!</span></span>
        </div>
    </div>

    <!-- Prize Showcase -->
    <section class="section">
        <div class="section-head">
            <div class="section-tag">✦ Koleksi Hadiah</div>
            <h2 class="section-title">Ada Apa Di Dalam Roulette?</h2>
            <p class="section-sub">Ini bukan undian kosong. Semua hadiah nyata, bisa langsung diambil di outlet kami.</p>
        </div>
        <div class="prize-grid">
            <div class="prize-card">
                <div class="prize-marquee">🔥</div>
                <img src="../public/assets/images/pos-products/original.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'" alt="Paket Ayam">
                <h4>Paket Ayam 1 Ekor</h4>
                <div class="prize-badge">Stok Terbatas</div>
            </div>
            <div class="prize-card">
                <div class="prize-marquee">📱</div>
                <img src="../public/assets/images/pos-products/icon-192.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'" alt="Handphone">
                <h4>Handphone</h4>
                <div class="prize-badge">Grand Prize</div>
            </div>
            <div class="prize-card">
                <div class="prize-marquee">✨</div>
                <img src="../public/assets/images/pos-products/matcha.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'" alt="Es Krim">
                <h4>Es Krim Lumero</h4>
                <div class="prize-badge">Paling Banyak</div>
            </div>
            <div class="prize-card">
                <div class="prize-marquee">💼</div>
                <img src="../public/assets/images/pos-products/kopi.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'" alt="Tumbler">
                <h4>Tumbler Eksklusif</h4>
                <div class="prize-badge">Edisi Terbatas</div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="section">
        <div class="section-head">
            <div class="section-tag">✦ Cara Klaim</div>
            <h2 class="section-title">Semudah 3 Langkah</h2>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">
                    <h4>Putar Roulette — GRATIS</h4>
                    <p>Klik tombol merah di atas. Tidak perlu daftar, tidak perlu bayar. Semua putaran dijamin mendapatkan hadiah nyata.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">
                    <h4>Amankan Ke WhatsApp</h4>
                    <p>Setelah melihat hadiah Anda, masukkan nomor WA untuk mengunci tiket digital. Ini adalah "kunci brankas" hadiah Anda — jangan tunda!</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">
                    <h4>Klaim di Outlet Kalibunder</h4>
                    <p>Tunjukkan tiket digital dari WA ke kasir Lumero Kalibunder. Hadiah langsung diserahkan di tempat!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Location Card -->
    <section class="section">
        <div class="section-head">
            <div class="section-tag">✦ Lokasi Klaim</div>
            <h2 class="section-title">Lumero Outlet Kalibunder</h2>
        </div>
        <div class="location-card">
            <div class="location-header">
                <div class="event-countdown">
                    🔴 Event Terbatas · Jangan Sampai Kehabisan
                </div>
                <h3>Lumero Outlet Kalibunder</h3>
                <p>Tukarkan tiket digital Anda langsung di kasir kami sebelum hangus. Stok hadiah terbatas!</p>
            </div>
            <div class="map-placeholder">
                <span style="color:rgba(255,255,255,0.25); font-size:14px; font-weight:700;">📍 Peta Google Maps Kalibunder</span>
            </div>
        </div>
    </section>

    <!-- Bottom CTA -->
    <div class="bottom-cta">
        <h2>Masih Ragu? Stok Hadiah Makin Menipis!</h2>
        <p>Sudah <b style="color:#fff;">247 orang</b> memutar hari ini. Semakin lama menunggu, semakin besar kemungkinan hadiah terbaik habis.</p>
        <a href="#action-area" class="btn-cta-big" onclick="document.getElementById('btn-spin').click(); return false;">
            ⚡ Putar Sekarang — GRATIS
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    const track = document.getElementById('slot-track');
    const btnSpin = document.getElementById('btn-spin');
    const CARD_W = 88 + 12;

    const itemIcons = {
        'Es Krim Lumero':          '../public/assets/images/pos-products/matcha.png',
        'Paket Ayam 1 Ekor':       '../public/assets/images/pos-products/original.png',
        'Paket Ayam + Saos Favorit':'../public/assets/images/pos-products/sayap.png',
        'Tumbler Eksklusif':       '../public/assets/images/pos-products/kopi.png',
        'Handphone':               '../public/assets/images/pos-products/icon-192.png'
    };
    const fallback = '../public/assets/images/pos-products/product-dummy.svg';
    const baseItems = Object.keys(itemIcons);
    const TARGET_IDX = 40;

    let state = 'idle', currentX = 0, velocity = 0.5, targetX = 0;
    const LOOP_X = baseItems.length * CARD_W;

    function buildTrack(winner = null) {
        let h = '';
        for (let i = 0; i < 60; i++) {
            let name = baseItems[i % baseItems.length];
            if (i === TARGET_IDX && winner) name = winner;
            let icon = itemIcons[name] || fallback;
            h += `<div class="slot-item" id="si-${i}"><img src="${icon}" onerror="this.src='${fallback}'"><span>${name}</span></div>`;
        }
        track.innerHTML = h;
    }
    buildTrack();

    function raf() {
        if (state === 'done') return;
        currentX += velocity;
        if (state === 'idle') {
            if (currentX >= LOOP_X) currentX -= LOOP_X;
        } else if (state === 'spin') {
            if (velocity < 45) velocity += 2;
            const d = 0.35, dist = velocity * velocity / (2 * d);
            if ((targetX - currentX) <= dist) state = 'decel';
        } else if (state === 'decel') {
            const dist = Math.max(0, targetX - currentX);
            let v = Math.sqrt(2 * 0.35 * dist);
            if (v < 0.4) v = 0.4;
            velocity = v;
            if (dist <= 0.4) {
                velocity = 0; currentX = targetX; state = 'done';
                const winEl = document.getElementById(`si-${TARGET_IDX}`);
                if (winEl) winEl.classList.add('winner');
                setTimeout(() => {
                    confetti({ particleCount: 120, spread: 80, origin: { y: 0.6 }, colors: ['#c41230','#ffc72c','#ffffff','#ff6b6b'] });
                    document.getElementById('action-area').style.display = 'none';
                    document.getElementById('result-modal').style.display = 'block';
                    document.getElementById('result-modal').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 200);
            }
        }
        track.style.transform = `translate3d(-${currentX}px, 0, 0)`;
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    function spinRoulette() {
        if (state !== 'idle') return;
        btnSpin.disabled = true;
        btnSpin.innerHTML = '<span style="font-size:18px;">⏳</span> Mengundi Hadiah Anda...';
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=spin_wheel'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const prizeName = data.prize.name;
                document.getElementById('prize-name').innerText = prizeName;
                document.getElementById('claim-btn').href = `<?= url('/member/login.php') ?>?source=event_kalibunder&prize=${encodeURIComponent(prizeName)}`;
                buildTrack(prizeName);
                state = 'spin';
                targetX = (TARGET_IDX * CARD_W) + (CARD_W / 2) + (Math.floor(Math.random() * 10) - 5);
            } else {
                alert('Gagal memutar: ' + (data.error || 'Server error'));
                btnSpin.disabled = false;
                btnSpin.innerHTML = '<span class="spin-icon">🎰</span> Putar Roulette — GRATIS!';
            }
        })
        .catch(() => {
            alert('Koneksi gagal. Coba lagi.');
            btnSpin.disabled = false;
            btnSpin.innerHTML = '<span class="spin-icon">🎰</span> Putar Roulette — GRATIS!';
        });
    }

    // Live counter simulation
    let spins = 247, left = 53;
    setInterval(() => {
        if (Math.random() < 0.3) {
            spins++;
            if (left > 0) left--;
            document.getElementById('counter-spins').textContent = spins;
            document.getElementById('counter-left').textContent  = left;
        }
    }, 4000);

    // Countdown timer (visual — resets each day)
    function updateTimer() {
        const now = new Date();
        const endOfDay = new Date(now); endOfDay.setHours(23, 59, 59, 0);
        const diff = Math.max(0, endOfDay - now);
        const h = String(Math.floor(diff / 3600000)).padStart(2,'0');
        const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2,'0');
        const s = String(Math.floor((diff % 60000) / 1000)).padStart(2,'0');
        document.getElementById('counter-timer').textContent = `${h}:${m}:${s}`;
    }
    updateTimer();
    setInterval(updateTimer, 1000);
</script>

</body>
</html>

    <link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #c41230;
            --red-dark: #6e0015;
            --gold: #ffc72c;
            --cream: #fffcf5;
            --ink: #0f172a;
            --muted: #64748b;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
        
        body {
            background-color: var(--cream);
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--ink);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            padding-bottom: 60px;
        }

        .stripe-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; pointer-events: none;
            background: linear-gradient(180deg, #fffcf5 0%, #ffffff 100%);
        }
        .blob {
            position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.45;
            animation: floatBlob 12s infinite alternate cubic-bezier(0.45, 0.05, 0.55, 0.95);
        }
        .blob-1 { top: -10%; left: -10%; width: 60vw; height: 60vw; background: radial-gradient(circle, var(--gold) 0%, transparent 70%); }
        .blob-2 { top: 20%; right: -20%; width: 70vw; height: 70vw; background: radial-gradient(circle, var(--red) 0%, transparent 70%); animation-delay: -5s; }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(5vw, 5vh) scale(1.15); }
        }

        .wrapper { width: min(800px, 100%); margin: 0 auto; padding: 0 24px; position: relative; }

        /* Navbar */
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; position: absolute; top: 0; left: 0; width: 100%; z-index: 100;
        }
        .nav-logo { display: flex; align-items: center; gap: 10px; font-weight: 900; font-size: 18px; color: var(--ink); }
        .nav-logo img { width: 32px; height: 32px; border-radius: 8px; }
        .btn-nav {
            border: 1.5px solid rgba(15,23,42,0.15); padding: 10px 20px; border-radius: 99px;
            text-decoration: none; color: var(--ink); font-weight: 700; font-size: 13px;
            background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);
            transition: all 0.2s;
        }
        .btn-nav:hover { background: var(--ink); color: #fff; }

        /* Hero */
        .hero-section { padding-top: 100px; text-align: center; }
        .hero-card {
            background: #fff; border-radius: 36px; padding: 40px 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.04), 0 0 0 1px rgba(0,0,0,0.02);
            margin-bottom: 40px; position: relative; overflow: hidden;
        }
        .hero-title { font-size: clamp(28px, 6vw, 36px); font-weight: 900; color: var(--ink); margin-bottom: 12px; letter-spacing: -0.03em; line-height: 1.2; }
        .hero-title span { color: var(--red); }
        .hero-subtitle { color: var(--muted); font-size: 15px; margin-bottom: 32px; font-weight: 500; line-height: 1.6; max-width: 400px; margin-left: auto; margin-right: auto; }

        /* Slot Machine */
        .slot-viewport {
            position: relative; width: 100%; max-width: 440px; height: 130px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid rgba(0,0,0,0.06); border-radius: 24px;
            margin: 0 auto 32px; overflow: hidden; display: flex; align-items: center;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.02);
        }
        .slot-viewport::before, .slot-viewport::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 60px; z-index: 2; pointer-events: none;
        }
        .slot-viewport::before { left: 0; background: linear-gradient(90deg, #fff 0%, transparent 100%); }
        .slot-viewport::after { right: 0; background: linear-gradient(-90deg, #fff 0%, transparent 100%); }
        
        .slot-target-line {
            position: absolute; left: 50%; top: 0; bottom: 0; width: 3px;
            background: var(--red); transform: translateX(-50%); z-index: 3;
            border-radius: 99px; box-shadow: 0 0 12px rgba(196,18,48,0.5);
        }
        .slot-target-line::before, .slot-target-line::after {
            content: ''; position: absolute; left: 50%; transform: translateX(-50%);
            width: 12px; height: 12px; background: var(--red); clip-path: polygon(50% 100%, 0 0, 100% 0);
        }
        .slot-target-line::before { top: 0; }
        .slot-target-line::after { bottom: 0; transform: translateX(-50%) rotate(180deg); }

        .slot-track {
            display: flex; gap: 16px; padding-left: 50%; will-change: transform; align-items: center;
        }
        .slot-item {
            width: 96px; height: 96px; background: #fff; border: 1px solid rgba(0,0,0,0.08);
            border-radius: 16px; display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 8px; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 8px; text-align: center;
        }
        .slot-item img { width: 44px; height: 44px; object-fit: contain; }
        .slot-item span { font-size: 11px; font-weight: 800; color: var(--ink); line-height: 1.2; }

        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center;
            width: 100%; max-width: 440px; padding: 18px 24px;
            background: linear-gradient(135deg, var(--red) 0%, #e01535 100%);
            color: #fff; font-size: 16px; font-weight: 800; border: none; border-radius: 18px;
            cursor: pointer; text-decoration: none; box-shadow: 0 12px 30px rgba(196,18,48,0.3);
            transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(196,18,48,0.4); }
        .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        @keyframes slideDown { from { opacity:0; transform:translateY(-15px); } to { opacity:1; transform:translateY(0); } }

        /* Marquee Ticker */
        .marquee-container {
            width: 100%; overflow: hidden; background: #fff; border: 1px solid rgba(0,0,0,0.06);
            border-radius: 99px; padding: 12px 24px; margin-bottom: 40px; white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; align-items: center;
        }
        .marquee-content { display: inline-flex; gap: 32px; animation: marquee 20s linear infinite; font-size: 13px; font-weight: 600; color: var(--muted); }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        /* Gallery */
        .section-title { font-size: 20px; font-weight: 900; color: var(--ink); margin-bottom: 8px; text-align: center; }
        .section-subtitle { font-size: 14px; color: var(--muted); text-align: center; margin-bottom: 24px; font-weight: 500; }
        .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 48px; }
        .gallery-item {
            background: #fff; border-radius: 20px; overflow: hidden; padding: 20px;
            text-align: center; border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        .gallery-item img { width: 100px; height: 100px; object-fit: contain; margin-bottom: 12px; transition: transform 0.3s; }
        .gallery-item:hover img { transform: scale(1.1); }
        .gallery-item h4 { font-size: 14px; font-weight: 800; color: var(--ink); }

        /* Map */
        .map-card {
            background: #fff; border-radius: 24px; padding: 24px; text-align: center;
            border: 1px solid rgba(0,0,0,0.04); box-shadow: 0 8px 24px rgba(0,0,0,0.03);
        }
        .countdown { display: inline-block; background: rgba(196,18,48,0.1); color: var(--red); padding: 8px 16px; border-radius: 99px; font-weight: 800; font-size: 14px; margin-bottom: 16px; }
    </style>
</head>
<body>

<div class="stripe-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<nav class="navbar">
    <div class="nav-logo">
        <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero"> Lumero
    </div>
    <a href="<?= url('/member/login.php') ?>?source=organic" class="btn-nav">Masuk / Cek Tiket</a>
</nav>

<div class="wrapper">
    <header class="hero-section">
        <div class="hero-card">
            <h1 class="hero-title">Raih Kejutan <span>Spesial!</span> ✨</h1>
            <p class="hero-subtitle">Pesananmu telah dikonversi. Undi roulette sekarang untuk mengamankan hadiah kejutanmu!</p>
            
            <div class="slot-viewport">
                <div class="slot-target-line"></div>
                <div class="slot-track" id="slot-track">
                    <!-- JS populated -->
                </div>
            </div>

            <div id="action-area">
                <button id="btn-spin" onclick="spinRoulette()" class="btn-primary">
                    ⚡ Putar Roulette Sekarang
                </button>
            </div>
            
            <div id="result-modal" style="display:none; margin-top:24px; animation:slideDown 0.5s ease-out; padding-top:24px; border-top:1px solid rgba(0,0,0,0.05);">
                <h3 style="font-size:22px; font-weight:900; color:var(--ink); margin-bottom:8px;">SELAMAT! 🎉</h3>
                <p style="font-size:15px; color:var(--muted); margin-bottom:20px; line-height:1.6;">1 <b id="prize-name" style="color:var(--red);">Hadiah</b> resmi menjadi milik Anda. Ke WhatsApp mana tiket ini dititipkan?</p>
                <a id="claim-btn" href="<?= url('/member/login.php') ?>?source=event_kalibunder" class="btn-primary" style="margin:0 auto;">
                    Amankan Tiket Ke WhatsApp
                </a>
                <p style="font-size:12px; color:var(--muted); margin-top:16px;">*Kunci brankas akan dikirim. Klaim di Outlet Kalibunder.</p>
            </div>
        </div>
    </header>

    <!-- Social Proof -->
    <div class="marquee-container">
        <div class="marquee-content">
            <span>🎉 0812-xxxx-9912 baru saja mengamankan Paket Ayam!</span>
            <span>🔥 0857-xxxx-2234 mendapat Tumbler Eksklusif!</span>
            <span>✨ 0896-xxxx-1122 meraih Es Krim Lumero!</span>
            <span>🎉 0812-xxxx-9912 baru saja mengamankan Paket Ayam!</span>
            <span>🔥 0857-xxxx-2234 mendapat Tumbler Eksklusif!</span>
        </div>
    </div>

    <!-- Gallery -->
    <h2 class="section-title">Kelezatan Menanti Anda</h2>
    <p class="section-subtitle">Rasa premium yang menanti Anda di outlet terbaru kami. Buktikan sendiri.</p>
    <div class="gallery-grid">
        <div class="gallery-item">
            <img src="../public/assets/images/pos-products/sayap.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'">
            <h4>Paket Ayam Spesial</h4>
        </div>
        <div class="gallery-item">
            <img src="../public/assets/images/pos-products/saus.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'">
            <h4>Ayam + Saos Favorit</h4>
        </div>
        <div class="gallery-item">
            <img src="../public/assets/images/pos-products/matcha.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'">
            <h4>Lumero Soft Serve</h4>
        </div>
        <div class="gallery-item">
            <img src="../public/assets/images/pos-products/kentang-dcelup.png" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'">
            <h4>Kentang Krispy</h4>
        </div>
    </div>

    <!-- Location -->
    <div class="map-card">
        <div class="countdown">⏳ Event Berakhir Dalam: 3 Hari Lagi</div>
        <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:8px;">Lumero Outlet Kalibunder</h3>
        <p style="font-size:14px; color:var(--muted); margin-bottom:16px;">Tukarkan tiket digital Anda langsung di kasir kami sebelum hangus.</p>
        <div style="background:#f1f5f9; height:150px; border-radius:16px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-weight:600;">
            [ Peta Google Maps Kalibunder ]
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    const track = document.getElementById('slot-track');
    const btnSpin = document.getElementById('btn-spin');
    const CARD_WIDTH = 96 + 16; // width + gap
    
    // Dummy icons for visual representation on slot machine
    const itemIcons = {
        'Es Krim Lumero': '../public/assets/images/pos-products/matcha.png',
        'Paket Ayam 1 Ekor': '../public/assets/images/pos-products/original.png',
        'Paket Ayam + Saos Favorit': '../public/assets/images/pos-products/sayap.png',
        'Tumbler Eksklusif': '../public/assets/images/pos-products/kopi.png',
        'Handphone': '../public/assets/images/pos-products/icon-192.png'
    };
    const defaultIcon = '../public/assets/images/pos-products/product-dummy.svg';

    let baseItems = Object.keys(itemIcons);
    const TARGET_INDEX = 40; // Stop at 40th item
    
    let state = 'idle';
    let currentX = 0;
    let velocity = 0.5;
    let targetX = 0;
    const LOOP_RESET_X = baseItems.length * CARD_WIDTH;

    function buildTrack(wonPrizeName = null) {
        let html = '';
        for (let i = 0; i < 60; i++) {
            let itemName = baseItems[i % baseItems.length];
            if (i === TARGET_INDEX && wonPrizeName) itemName = wonPrizeName;
            
            let icon = itemIcons[itemName] || defaultIcon;
            html += `<div class="slot-item"><img src="${icon}" onerror="this.src='${defaultIcon}'"><span>${itemName}</span></div>`;
        }
        track.innerHTML = html;
    }
    
    // Initial build
    buildTrack();

    function updatePhysics() {
        if (state === 'done') return;
        currentX += velocity;
        
        if (state === 'idle') {
            if (currentX >= LOOP_RESET_X) currentX -= LOOP_RESET_X;
        } else if (state === 'spin') {
            if (velocity < 40) velocity += 1.5;
            const decel = 0.3;
            const distToStop = (velocity * velocity) / (2 * decel);
            if ((targetX - currentX) <= distToStop) state = 'decel';
        } else if (state === 'decel') {
            const distToTarget = Math.max(0, targetX - currentX);
            let idealVelocity = Math.sqrt(2 * 0.3 * distToTarget);
            if (idealVelocity < 0.5) idealVelocity = 0.5;
            velocity = idealVelocity;
            
            if (distToTarget <= 0.5) {
                velocity = 0; currentX = targetX; state = 'done';
                setTimeout(() => {
                    confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 }, colors: ['#c41230', '#ffc72c', '#ffffff'] });
                    document.getElementById('action-area').style.display = 'none';
                    document.getElementById('result-modal').style.display = 'block';
                }, 300);
            }
        }
        
        track.style.transform = `translate3d(-${currentX}px, 0, 0)`;
        requestAnimationFrame(updatePhysics);
    }
    requestAnimationFrame(updatePhysics);

    function spinRoulette() {
        if (state !== 'idle') return;
        
        btnSpin.disabled = true;
        btnSpin.innerHTML = '⚡ Mengundi...';
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=spin_wheel'
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                document.getElementById('prize-name').innerText = data.prize.name;
                buildTrack(data.prize.name); // Rebuild with winner at TARGET_INDEX
                
                state = 'spin';
                const randomOffset = Math.floor(Math.random() * 20) - 10;
                targetX = (TARGET_INDEX * CARD_WIDTH) + (CARD_WIDTH / 2) + randomOffset;
            } else {
                alert("Gagal memutar: " + (data.error || 'Server error'));
                btnSpin.disabled = false; btnSpin.innerHTML = '⚡ Putar Roulette Sekarang';
            }
        })
        .catch(e => {
            alert("Koneksi gagal.");
            btnSpin.disabled = false; btnSpin.innerHTML = '⚡ Putar Roulette Sekarang';
        });
    }
</script>

</body>
</html>