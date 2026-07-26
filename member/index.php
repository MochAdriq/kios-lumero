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
    if (str_contains($name, 'kentang')) return '../public/assets/images/pos-products/kentang-dcelup.png';
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
        <a href="<?= url('/member/login.php') ?>?claim=<?= urlencode($claimCode) ?>" class="btn-stripe btn-primary" style="padding: 10px 20px; font-size: 13px;">Klaim &rarr;</a>
    </div>
    <?php endif; ?>

    <header class="hero-section" style="grid-template-columns: 1fr;">
        <div class="hero-content" style="text-align: center; max-width: 800px; margin: 0 auto;">
            <div class="badge-pill">GRAND OPENING KALIBUNDER</div>
            <h1 class="hero-title">Putar & <span>Menangkan</span> Kejutan Spesial!</h1>
            <p class="hero-subtitle" style="margin: 0 auto 36px;">Khusus merayakan pembukaan Outlet Kalibunder. Dapatkan hidangan gratis tanpa syarat rumit. Tersisa <b id="countdown" style="color:var(--red);">3 Hari Lagi</b>!</p>
            
            <div id="roulette-container" style="position: relative; width: 320px; height: 320px; margin: 0 auto 40px; filter: drop-shadow(0 20px 40px rgba(196,18,48,0.3));">
                <div style="position:absolute; top:-15px; left:50%; transform:translateX(-50%); width:30px; height:40px; background:var(--ink); clip-path: polygon(50% 100%, 0 0, 100% 0); z-index:10;"></div>
                <div id="roulette-wheel" style="width: 100%; height: 100%; border-radius: 50%; border: 8px solid #fff; transition: transform 4s cubic-bezier(0.1, 0.7, 0.1, 1); background: conic-gradient(#ffc72c 0deg 72deg, #c41230 72deg 144deg, #ffc72c 144deg 216deg, #c41230 216deg 288deg, #ffc72c 288deg 360deg);"></div>
                <div style="position:absolute; inset: 0; pointer-events:none; border-radius: 50%; box-shadow: inset 0 0 20px rgba(0,0,0,0.5);"></div>
                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:60px; height:60px; background:#fff; border-radius:50%; border:5px solid var(--ink); display:flex; align-items:center; justify-content:center; font-weight:900; font-size:24px; color:var(--ink);">?</div>
            </div>
            
            <div class="cta-stack" style="justify-content: center;" id="action-area">
                <button id="btn-spin" onclick="spinRoulette()" class="btn-stripe btn-primary" style="font-size: 18px; padding: 18px 48px; border:none; cursor:pointer;">
                    SPIN SEKARANG &rarr;
                </button>
            </div>
            
            <div id="result-modal" style="display:none; margin-top:30px; background:#fff; padding:24px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1); border:1px solid var(--red);">
                <h3 style="font-size:24px; font-weight:800; color:var(--ink); margin-bottom:10px;">SELAMAT! 🎉</h3>
                <p style="font-size:16px; color:var(--muted); margin-bottom:20px;">Anda memenangkan <b id="prize-name" style="color:var(--red); font-size:18px;">-</b></p>
                <a href="<?= url('/member/login.php') ?>" class="btn-stripe btn-primary" style="width:100%;">
                    KLAIM TIKET SAYA SEKARANG
                </a>
                <p style="font-size:12px; color:var(--muted); margin-top:12px;">*Wajib klaim ke Outlet Kalibunder.</p>
            </div>
        </div>

        <script>
            let isSpinning = false;
            function spinRoulette() {
                if (isSpinning) return;
                isSpinning = true;
                
                const btn = document.getElementById('btn-spin');
                btn.innerHTML = 'Memutar...';
                btn.style.opacity = '0.7';
                
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=spin_wheel'
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        const wheel = document.getElementById('roulette-wheel');
                        const extraSpins = 5;
                        const degrees = (extraSpins * 360) + Math.floor(Math.random() * 360);
                        
                        wheel.style.transform = `rotate(${degrees}deg)`;
                        
                        setTimeout(() => {
                            document.getElementById('action-area').style.display = 'none';
                            const resModal = document.getElementById('result-modal');
                            resModal.style.display = 'block';
                            document.getElementById('prize-name').innerText = data.prize.name;
                            resModal.scrollIntoView({behavior: 'smooth', block: 'center'});
                        }, 4200);
                    } else {
                        alert("Gagal memutar: " + (data.error || 'Server error'));
                        btn.innerHTML = 'COBA LAGI';
                        isSpinning = false;
                    }
                })
                .catch(e => {
                    alert("Koneksi gagal.");
                    btn.innerHTML = 'COBA LAGI';
                    isSpinning = false;
                });
            }
        </script>
    </header>

    <section>
        <div class="section-header">
            <h2>Katalog Hadiah</h2>
            <p>Pilih sajian lezat yang siap Anda tukarkan dengan poin terkumpul.</p>
        </div>

        <div class="reward-showcase">
            <div class="reward-item" onclick="window.location.href='<?= url('/member/login.php') ?>'" style="cursor:pointer;">
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
            <div class="reward-item" onclick="window.location.href='<?= url('/member/login.php') ?>'" style="cursor:pointer;">
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
            <a href="<?= url('/member/login.php') ?>" class="btn-stripe btn-primary">Login / Daftar untuk Klaim</a>
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
                            <a href="<?= url('/member/online-order.php') ?>" class="menu-btn">Pesan Sekarang</a>
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