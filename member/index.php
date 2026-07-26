<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';
loyalty_ensure_tables($pdo);

// Fetch active prizes for wheel rendering
$stmtPrizes = $pdo->query("SELECT name, image_url FROM event_prizes WHERE event_id = 'kalibunder_go' AND is_active = 1 ORDER BY id ASC");
$prizeIconsMap = [];
while ($row = $stmtPrizes->fetch()) {
    $imgUrl = !empty($row['image_url']) ? loyalty_resolve_image_url($row['image_url']) : loyalty_resolve_image_url('images/pos-products/product-dummy.svg');
    $prizeIconsMap[$row['name']] = $imgUrl;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'spin_wheel') {
    header('Content-Type: application/json');
    try {
        // 1. Hole 4: API Throttle (max 1 request per 10 seconds per session)
        if (isset($_SESSION['last_spin_time']) && time() - $_SESSION['last_spin_time'] < 10) {
            throw new Exception("Harap tunggu beberapa saat sebelum memutar lagi.");
        }
        $_SESSION['last_spin_time'] = time();

        // 2. Hole 1: IP Limitation (max 3 spins per day per IP)
        $pdo->exec("CREATE TABLE IF NOT EXISTS event_spin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            prize_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ip_date (ip_address, created_at)
        )");

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_spin_logs WHERE ip_address = ? AND DATE(created_at) = ?");
        $stmt->execute([$ip, $today]);
        $spinCount = (int)$stmt->fetchColumn();

        if ($spinCount >= 3) {
            throw new Exception("Batas harian tercapai. Perangkat ini sudah memutar maksimal 3 kali hari ini.");
        }

        require_once __DIR__ . '/../helpers/RouletteHelper.php';
        $prize = RouletteHelper::spinWheel($pdo, 'kalibunder_go');

        // Log spin
        $pdo->prepare("INSERT INTO event_spin_logs (ip_address, prize_id) VALUES (?, ?)")->execute([$ip, $prize['id']]);

        $_SESSION['pending_event_reward'] = $prize;
        echo json_encode(['success' => true, 'prize' => $prize]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SURPRISE FLOW: Intercept saat ada ?claim=KODE
   â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$claimCode = strtoupper(trim((string)($_GET['claim'] ?? '')));
$claimCheck = ['valid' => false];

if ($claimCode !== '') {
    $claimCheck = loyalty_check_claim_code($pdo, $claimCode);
}

// â”€â”€ Pilih variasi anti-ulang â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Jika kode klaim valid â†’ tampilkan SURPRISE LANDING â”€â”€
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
    <title>Kejutan Spesial Untukmu! â€” Lumero</title>
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

        /* â”€â”€ Animated Background â”€â”€ */
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

        /* â”€â”€ Wrapper â”€â”€ */
        .surprise-wrapper {
            position: relative; z-index: 2;
            width: min(480px, 100%);
            padding: 32px 20px 48px;
            display: flex; flex-direction: column; align-items: center;
            text-align: center; gap: 0;
        }

        /* â”€â”€ Brand Logo â”€â”€ */
        .brand-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.85); backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 99px; padding: 8px 18px;
            font-size: 13px; font-weight: 800; letter-spacing: -0.01em;
            color: var(--ink); margin-bottom: 32px;
        }
        .brand-pill img { width: 24px; height: 24px; border-radius: 6px; }

        /* â”€â”€ Stage (Container animasi utama) â”€â”€ */
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

        /* â”€â”€ POIN BADGE â”€â”€ */
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

        /* â”€â”€ CTA Button â”€â”€ */
        .cta-btn {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 18px 28px;
            background: linear-gradient(135deg, var(--red) 0%, #e01535 100%);
            color: var(--text-main); font-size: 16px; font-weight: 800;
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

        /* â”€â”€ Confetti Canvas â”€â”€ */
        #confetti-canvas {
            position: fixed; inset: 0; pointer-events: none; z-index: 999;
        }

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           VARIASI A: ROULETTE TICKER
           â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
        .ticker-selector::before { content: 'â–¼'; top: -10px; }
        .ticker-selector::after { content: 'â–²'; top: auto; bottom: -10px; }

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
        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           VARIASI B: 3D MYSTERY POD (Lottie)
           â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
        .pod-container {
            position: relative; width: 220px; height: 220px; margin: 0 auto 24px;
            cursor: pointer; transition: transform 0.15s ease-out;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, transparent 70%);
        }
        .pod-container:active { transform: scale(0.95); }
        .pod-container lottie-player { width: 100%; height: 100%; pointer-events: none; }


        
        /* â”€â”€ Success State (member sudah login) â”€â”€ */
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

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         VARIASI A: ROULETTE TICKER
         â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <?php if ($variant === 'A'): ?>
    <div class="stage" id="stage-A">
        <div class="headline">
            <?php if ($isReturning && $memberName): ?>
                Selamat kembali, <span><?= htmlspecialchars(explode(' ', $memberName)[0]) ?>!</span> <i class="fa-solid fa-crown" style="color:#f59e0b;"></i>
            <?php else: ?>
                Kejutan <span>Poin Hadiah!</span> <i class="fa-solid fa-wand-magic-sparkles" style="color:#f59e0b;"></i>
            <?php endif; ?>
        </div>
        <p class="sub-headline">Pesananmu telah dikonversi. Putar undian sekarang untuk mengamankan poin ekstra ke dompetmu!</p>

        <!-- Roulette Ticker Viewport -->
        <div class="ticker-viewport">
            <div class="ticker-selector"></div>
            <div class="ticker-track" id="tickerTrack">
                <!-- Dynamically populated by JS -->
            </div>
        </div>

        <button class="btn-gacha" id="spinTickerBtn" onclick="startTickerSpin()">
            <i class="fa-solid fa-bolt" style="margin-right:6px;"></i> Putar Undian Sekarang
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

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         VARIASI B: PETI HARTA KARUN
         â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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

    /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       VARIASI A: ROULETTE TICKER
       â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
        btnSpin.innerHTML = 'âš¡ Mengundi...';
        
        state = 'spin';
        const randomOffset = Math.floor(Math.random() * 24) - 12; // +/- 12px natural offset
        targetX = (TARGET_INDEX * CARD_WIDTH) + (96 / 2) + randomOffset;
    };

    /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       VARIASI B: 3D MYSTERY POD (Lottie)
       â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

    /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       AUTO-CLAIM SUCCESS CONFETTI (member logged in)
       â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

    <?php if ($isLoggedIn && $autoMsg === 'success'): ?>
    window.addEventListener('load', () => {
        setTimeout(() => fireConfetti(), 600);
    });
    <?php endif; ?>
</script>

<script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') === 'dark';
        if(isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            document.querySelector('#themeToggle').innerHTML = '<i class="fa-solid fa-moon"></i>';
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            document.querySelector('#themeToggle').innerHTML = '<i class="fa-solid fa-sun"></i>';
        }
    }
    // Init theme
    if(localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        window.addEventListener('DOMContentLoaded', () => {
            const btn = document.querySelector('#themeToggle');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-sun"></i>';
        });
    } else {
        // default to light as requested
    }
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
    <title>Grand Opening Kalibunder â€” Lumero | Putar & Menang!</title>
    <meta name="description" content="Putar undian GRATIS dan menangkan hadiah eksklusif dari Lumero Kalibunder. 100% pasti menang!">
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
            
            /* Light Theme */
            --bg-main: #fffcf5;
            --bg-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.08);
            --card-bg: rgba(255,255,255,0.9);
            --card-border: rgba(0,0,0,0.08);
            --nav-bg: rgba(255, 252, 245, 1);
            --hero-bg: #fffcf5;
            --hero-overlay: linear-gradient(to bottom, rgba(255, 252, 245, 0) 0%, rgba(255, 252, 245, 1) 90%);
        }

        [data-theme="dark"] {
            /* Dark Theme */
            --bg-main: #0a0a0f;
            --bg-surface: #12121a;
            --text-main: #ffffff;
            --text-muted: rgba(255,255,255,0.5);
            --border-color: rgba(255,255,255,0.08);
            --card-bg: rgba(255,255,255,0.04);
            --card-border: rgba(255,255,255,0.1);
            --nav-bg: rgba(10, 10, 20, 1);
            --hero-bg: #0a0a0f;
            --hero-overlay: linear-gradient(to bottom, rgba(10,10,15,0) 0%, rgba(10,10,15,1) 90%);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100svh;
            overflow-x: hidden;
            transition: background 0.3s, color 0.3s;
        }

        /* â”€â”€ Animated BG â”€â”€ */
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

        /* â”€â”€ Layout â”€â”€ */
        .wrapper { width: min(820px, 100%); margin: 0 auto; padding: 0 20px; position: relative; z-index: 10; }

        /* â”€â”€ Navbar â”€â”€ */
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 20px; position: sticky; top: 0; z-index: 100;
            background: var(--nav-bg); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
        }
        .nav-logo { display: flex; align-items: center; gap: 10px; font-weight: 900; font-size: 17px; color: var(--text-main); } .nav-logo img { width: 30px; height: 30px; border-radius: 8px; }
        .nav-badge {
            background: linear-gradient(135deg, var(--red) 0%, #ff3d5a 100%);
            color: var(--text-main); font-size: 11px; font-weight: 800; padding: 4px 10px;
            border-radius: 99px; letter-spacing: 0.04em; text-transform: uppercase;
            animation: badgePulse 2s ease-in-out infinite;
        }
        @keyframes badgePulse { 0%,100% { box-shadow: 0 0 0 0 rgba(196,18,48,0.5); } 50% { box-shadow: 0 0 0 8px rgba(196,18,48,0); } }
        .btn-nav {
            border: 1px solid rgba(255,255,255,0.2); padding: 9px 18px; border-radius: 99px;
            text-decoration: none; color: var(--text-main); font-weight: 700; font-size: 12px; background: var(--border-color); backdrop-filter: blur(10px);
            transition: all 0.2s;
        }
        .btn-nav:hover { background: rgba(255,255,255,0.18); }

        /* â”€â”€ Urgency Banner â”€â”€ */
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

        /* â”€â”€ Live Counter â”€â”€ */
        .live-counter {
            display: flex; align-items: center; justify-content: center; gap: 24px;
            padding: 16px 20px; background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
        }
        .counter-item { text-align: center; }
        .counter-number { font-size: 22px; font-weight: 900; color: var(--gold); font-variant-numeric: tabular-nums; }
        .counter-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; }
        .counter-sep { width: 1px; height: 36px; background: rgba(255,255,255,0.1); }

        /* â”€â”€ Hero Section â”€â”€ */
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
            font-size: 15px; color: var(--text-muted); font-weight: 500;
            line-height: 1.7; max-width: 380px; margin: 0 auto 32px;
        }
        .hero-sub b { color: var(--text-main); }

        /* â”€â”€ Guarantee Badges â”€â”€ */
        .guarantee-row {
            display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;
            margin-bottom: 32px;
        }
        .g-badge {
            display: flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.06); border: 1px solid var(--card-border);
            padding: 8px 14px; border-radius: 99px; font-size: 12px; font-weight: 700;
            color: var(--text-main);
        }

        /* â”€â”€ Slot Machine Card â”€â”€ */
        .slot-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
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
            font-size: 13px; font-weight: 800; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.1em; text-align: center;
            margin-bottom: 20px;
        }

        .slot-viewport {
            position: relative; width: 100%; height: 120px;
            background: rgba(0,0,0,0.4); border: 1px solid var(--card-border);
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
            border: 1px solid var(--card-border);
            border-radius: 16px; display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 6px; flex-shrink: 0; padding: 8px; text-align: center;
            transition: border-color 0.3s;
        }
        .slot-item.winner {
            border-color: var(--gold);
            box-shadow: 0 0 20px rgba(255,199,44,0.3), inset 0 0 20px rgba(255,199,44,0.05);
        }
        .slot-item img { width: 44px; height: 44px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4)); }
        .slot-item span { font-size: 10px; font-weight: 800; color: var(--text-main); line-height: 1.2; }

        /* â”€â”€ CTA Button â”€â”€ */
        .btn-spin {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 18px 24px;
            background: linear-gradient(135deg, var(--red) 0%, #ff2244 50%, var(--red) 100%);
            background-size: 200% 100%;
            color: var(--text-main); font-size: 17px; font-weight: 900; border: none; border-radius: 18px;
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

        /* â”€â”€ Result Modal (inline, not popup) â”€â”€ */
        #result-modal {
            display: none; margin-top: 20px; padding: 24px;
            background: linear-gradient(145deg, rgba(255,199,44,0.1) 0%, rgba(196,18,48,0.08) 100%);
            border: 1px solid rgba(255,199,44,0.25); border-radius: 20px;
            text-align: center; animation: popIn 0.5s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes popIn { from { opacity:0; transform:scale(0.9) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .result-congrats { font-size: 32px; margin-bottom: 4px; }
        .result-title { font-size: 20px; font-weight: 900; color: var(--text-main); margin-bottom: 4px; }
        .result-prize-name { font-size: 26px; font-weight: 900; color: var(--gold); margin-bottom: 8px; letter-spacing: -0.02em; }
        .result-sub { font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 20px; line-height: 1.6; }

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

        /* â”€â”€ Social Proof Ticker â”€â”€ */
        .ticker-wrap {
            overflow: hidden; padding: 14px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 40px; background: var(--card-bg);
        }
        .ticker-track {
            display: inline-flex; gap: 48px; white-space: nowrap;
            animation: ticker 25s linear infinite;
            font-size: 13px; font-weight: 600; color: var(--text-muted);
        }
        .ticker-track .win { color: var(--gold); font-weight: 800; }
        @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        /* â”€â”€ Section â”€â”€ */
        .section { margin-bottom: 48px; }
        .section-head { text-align: center; margin-bottom: 24px; }
        .section-tag {
            display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--gold); margin-bottom: 8px;
        }
        .section-title { font-size: 22px; font-weight: 900; color: var(--text-main); margin-bottom: 8px; }
        .section-sub { font-size: 14px; color: var(--text-muted); font-weight: 500; line-height: 1.6; }

        /* â”€â”€ Prize Grid â”€â”€ */
        .prize-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        .prize-card {
            background: var(--card-bg); border: 1px solid var(--card-border);
            border-radius: 20px; padding: 20px 16px; text-align: center;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .prize-card:hover { border-color: rgba(255,199,44,0.3); background: rgba(255,199,44,0.05); transform: translateY(-2px); }
        .prize-card::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, transparent 70%, rgba(255,199,44,0.03) 100%); }
        .prize-card img { width: 72px; height: 72px; object-fit: contain; margin-bottom: 12px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4)); transition: transform 0.3s; }
        .prize-card:hover img { transform: scale(1.1) rotate(-3deg); }
        .prize-card h4 { font-size: 13px; font-weight: 800; color: var(--text-main); margin-bottom: 4px; }
        .prize-card .prize-badge {
            font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 99px;
            background: rgba(255,199,44,0.15); color: var(--gold); letter-spacing: 0.04em;
        }
        .prize-marquee { position: absolute; top: 10px; right: 10px; font-size: 16px; animation: prizeFloat 2s ease-in-out infinite alternate; }
        @keyframes prizeFloat { 0% { transform: translateY(0) rotate(0deg); } 100% { transform: translateY(-4px) rotate(8deg); } }

        /* â”€â”€ How It Works â”€â”€ */
        .steps { display: flex; flex-direction: column; gap: 16px; }
        .step {
            display: flex; align-items: flex-start; gap: 16px;
            background: var(--card-bg); border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px; padding: 18px;
        }
        .step-num {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 900;
            background: linear-gradient(135deg, var(--red-dark) 0%, var(--red) 100%);
        }
        .step-text h4 { font-size: 15px; font-weight: 800; color: var(--text-main); margin-bottom: 4px; }
        .step-text p { font-size: 13px; color: var(--text-muted); font-weight: 500; line-height: 1.5; }

        /* â”€â”€ Location Card â”€â”€ */
        .location-card {
            background: var(--card-bg); border: 1px solid var(--card-border);
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
        .location-header h3 { font-size: 20px; font-weight: 900; color: var(--text-main); margin-bottom: 4px; }
        .location-header p { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .map-placeholder {
            height: 160px; background: rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            border-top: 1px solid var(--border-color);
        }
        .map-placeholder iframe { width: 100%; height: 100%; border: 0; }

        /* â”€â”€ Bottom CTA â”€â”€ */
        .bottom-cta {
            text-align: center; padding: 40px 0 60px;
        }
        .bottom-cta h2 { font-size: 24px; font-weight: 900; color: var(--text-main); margin-bottom: 8px; }
        .bottom-cta p { font-size: 14px; color: var(--text-muted); margin-bottom: 24px; font-weight: 500; line-height: 1.6; }

        .btn-cta-big {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            padding: 18px 40px; border-radius: 18px;
            background: linear-gradient(135deg, var(--red) 0%, #ff2244 100%);
            color: var(--text-main); font-size: 16px; font-weight: 900; text-decoration: none;
            box-shadow: 0 12px 40px rgba(196,18,48,0.4);
            transition: all 0.3s;
        }
        .btn-cta-big:hover { transform: translateY(-2px); box-shadow: 0 20px 50px rgba(196,18,48,0.6); }

        /* â”€â”€ Misc â”€â”€ */
        @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

        /* â”€â”€ Mobile fixes â”€â”€ */
        @media (max-width: 400px) {
            .live-counter { gap: 12px; }
            .guarantee-row .g-badge { font-size: 11px; padding: 7px 10px; }
        }

        /* ── Live dot ── */
        .live-dot {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            background: #ff4444; vertical-align: middle; margin-right: 2px;
            animation: livePulse 1.2s ease-in-out infinite;
        }
        @keyframes livePulse { 0%,100% { box-shadow: 0 0 0 0 rgba(255,68,68,0.6); } 50% { box-shadow: 0 0 0 5px rgba(255,68,68,0); } }

        /* ── SVG spin icon ── */
        .btn-spin .spin-icon { animation: spinIcon 3s linear infinite; }
        @keyframes spinIcon { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>



<div class="bg-wrap" style="z-index: -1;">
    <div class="bg-grid"></div>
    <div class="pulse-orb orb-1"></div>
    <div class="pulse-orb orb-2"></div>
</div>


<!-- Navbar -->
<nav class="navbar">
    <div class="nav-logo">
        <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
        Lumero
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
        
        <button id="themeToggle" class="btn-nav" style="margin-right:8px; cursor:pointer; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" onclick="toggleTheme()">
            <i class="fa-solid fa-moon"></i>
        </button>
        <div class="nav-badge">Traktiran Eksklusif</div>
        <a href="<?= url('/member/login.php') ?>?source=organic" class="btn-nav">Login</a>
    </div>
</nav>

<!-- Full Width Hero Background (Relative to document flow) -->
<div class="hero-image-wrapper" style="position: relative; width: 100%; height: 60vh; min-height: 400px; z-index: 0; background-color: var(--hero-bg); background-image: url('../public/assets/images/member-hero.jpeg?v=<?= time() ?>'); background-size: contain; background-position: top center; background-repeat: no-repeat; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; margin-top: 0;">
    <!-- Fade out overlay -->
    <div style="position: absolute; inset: 0; background: var(--hero-overlay); border-bottom-left-radius: 40px; border-bottom-right-radius: 40px;"></div>
</div>

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

<div class="wrapper" style="position: relative; z-index: 2;">
    <header class="hero-section" style="height: 50vh; min-height: 300px; margin-bottom: 0;">
    </header>

    <div class="slot-card" style="margin-top: -100px; position: relative; z-index: 10;">
        <div class="slot-card-title">Undian Kejutan &mdash; Putar Sekarang</div>
        <div class="slot-viewport">
            <div class="slot-target-line"></div>
            <div class="slot-track" id="slot-track"></div>
        </div>
        <div id="action-area">
            <button id="btn-spin" onclick="spinRoulette()" class="btn-spin">
                <svg class="spin-icon" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                Putar Undian &mdash; GRATIS!
            </button>
            <p class="hint-text">Tanpa login &middot; Tanpa syarat &middot; Tanpa biaya tersembunyi</p>
        </div>
        <div id="result-modal">
            <div class="result-congrats">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M8 14s-4 2-4 6h16c0-4-4-6-4-6"/></svg>
            </div>
            <div class="result-title">Selamat! Anda Menang!</div>
            <div class="result-prize-name" id="prize-name">Hadiah</div>
            <p class="result-sub">Kupon hadiah ini <b style="color: var(--text-main);">akan hangus dalam 7 hari</b> jika tidak diamankan ke nomor WhatsApp Anda sekarang.<br>Jangan biarkan orang lain mengklaimnya!</p>
            <a id="claim-btn" href="<?= url('/member/login.php') ?>?source=event_kalibunder" class="btn-claim">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                Amankan Hadiah Ke WhatsApp Saya
            </a>
            <p class="claim-warning">*Masukkan nomor WA &middot; Kupon digital dikirim otomatis &middot; Klaim di Outlet Kalibunder</p>
        </div>
    </div>

    <div class="ticker-wrap">
        <div class="ticker-track">
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0812-xxxx-9912</span> baru saja mengamankan <span class="win">Paket Ayam 1 Ekor!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0857-xxxx-2234</span> memenangkan <span class="win">Tumbler Eksklusif!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0896-xxxx-1122</span> dapat <span class="win">Es Krim Lumero!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0821-xxxx-4451</span> berhasil klaim <span class="win">Paket Ayam + Saos!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0812-xxxx-9912</span> baru saja mengamankan <span class="win">Paket Ayam 1 Ekor!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0857-xxxx-2234</span> memenangkan <span class="win">Tumbler Eksklusif!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0896-xxxx-1122</span> dapat <span class="win">Es Krim Lumero!</span></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" style="vertical-align:middle;margin-right:4px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="win">0821-xxxx-4451</span> berhasil klaim <span class="win">Paket Ayam + Saos!</span></span>
        </div>
    </div>

    <section class="section">
        <div class="section-head">
            <div class="section-tag">Koleksi Hadiah</div>
            <h2 class="section-title">Ada Apa Di Dalam Undian?</h2>
            <p class="section-sub">Ini bukan undian kosong. Semua hadiah nyata, bisa langsung diambil di outlet kami.</p>
        </div>
        <div class="prize-grid">
            <?php
            $randomPrizesStmt = $pdo->query("SELECT * FROM event_prizes WHERE event_id = 'kalibunder_go' AND is_active = 1 ORDER BY RAND() LIMIT 4");
            $randomPrizes = $randomPrizesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $svgIcons = [
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="var(--red)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>',
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="var(--gold)"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="var(--gold)"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="rgba(255,255,255,0.4)"><path d="M7 10h10V7a5 5 0 0 0-10 0v3zm-2 0a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5z"/></svg>'
            ];

            foreach ($randomPrizes as $rp):
                $badgeText = "Edisi Terbatas";
                $badgeClass = "";
                if ($rp['is_default_fallback']) {
                    $badgeText = "Paling Banyak";
                } elseif ($rp['chance_percentage'] < 10) {
                    $badgeText = "Grand Prize";
                    $badgeClass = "style='background:var(--gold); color:#000;'";
                } elseif ($rp['stock'] > 0 && $rp['stock'] <= 50) {
                    $badgeText = "Stok Terbatas";
                }

                $imgUrl = !empty($rp['image_url']) ? htmlspecialchars(loyalty_resolve_image_url($rp['image_url'])) : '../public/assets/images/pos-products/product-dummy.svg';
                $randSvg = $svgIcons[array_rand($svgIcons)];
            ?>
            <div class="prize-card">
                <div class="prize-marquee"><?= $randSvg ?></div>
                <img src="<?= $imgUrl ?>" onerror="this.src='../public/assets/images/pos-products/product-dummy.svg'" alt="<?= htmlspecialchars($rp['name']) ?>">
                <h4><?= htmlspecialchars($rp['name']) ?></h4>
                <div class="prize-badge" <?= $badgeClass ?>><?= $badgeText ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div class="section-tag">Cara Klaim</div>
            <h2 class="section-title">Semudah 3 Langkah</h2>
        </div>
        <div class="steps">
            <div class="step"><div class="step-num">1</div><div class="step-text"><h4>Putar Undian &mdash; GRATIS</h4><p>Klik tombol merah di atas. Tidak perlu daftar, tidak perlu bayar. Semua putaran dijamin mendapatkan hadiah nyata.</p></div></div>
            <div class="step"><div class="step-num">2</div><div class="step-text"><h4>Amankan Ke WhatsApp</h4><p>Setelah melihat hadiah Anda, masukkan nomor WA untuk mengunci kupon digital. Ini adalah "kunci brankas" hadiah Anda &mdash; jangan tunda!</p></div></div>
            <div class="step"><div class="step-num">3</div><div class="step-text"><h4>Klaim di Outlet Kalibunder</h4><p>Tunjukkan kupon digital dari WA ke kasir Lumero Kalibunder. Hadiah langsung diserahkan di tempat!</p></div></div>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div class="section-tag">Lokasi Klaim</div>
            <h2 class="section-title">Lumero Outlet Kalibunder</h2>
        </div>
        <div class="location-card">
            <div class="location-header">
                <div class="event-countdown"><span class="live-dot"></span> Event Terbatas &middot; Jangan Sampai Kehabisan</div>
                <h3>Lumero Outlet Kalibunder</h3>
                <p>Tukarkan kupon digital Anda langsung di kasir kami sebelum hangus. Stok hadiah terbatas!</p>
            </div>
            <div class="map-placeholder">
                <span style="color:rgba(255,255,255,0.25); font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="rgba(255,255,255,0.3)"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg> Peta Google Maps Kalibunder</span>
            </div>
        </div>
    </section>

</div>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    const track = document.getElementById('slot-track');
    const btnSpin = document.getElementById('btn-spin');
    const CARD_W = 88 + 12;

    const itemIcons = <?= json_encode($prizeIconsMap ?? new stdClass()) ?>;
    const fallback = '../public/assets/images/pos-products/product-dummy.svg';
    const baseItems = Object.keys(itemIcons).length > 0 ? Object.keys(itemIcons) : ['Mystery Box'];
    const TARGET_IDX = 40;

    const baseLoop = baseItems.length * CARD_W;
    const cycle = Math.ceil(1500 / baseLoop);
    const safeStart = baseLoop * cycle;
    
    let state = 'idle', currentX = safeStart, velocity = 0.5, targetX = 0;

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
            if (currentX >= safeStart + baseLoop) currentX -= baseLoop;
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
        btnSpin.innerHTML = '<svg class="spin-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Mengundi Hadiah Anda...';
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
                btnSpin.innerHTML = '<svg class="spin-icon" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Putar Undian &mdash; GRATIS!';
            }
        })
        .catch(() => {
            alert('Koneksi gagal. Coba lagi.');
            btnSpin.disabled = false;
            btnSpin.innerHTML = '<svg class="spin-icon" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Putar Undian &mdash; GRATIS!';
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

    // Countdown timer (visual â€” resets each day)
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


<script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') === 'dark';
        if(isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            document.querySelector('#themeToggle').innerHTML = '<i class="fa-solid fa-moon"></i>';
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            document.querySelector('#themeToggle').innerHTML = '<i class="fa-solid fa-sun"></i>';
        }
    }
    // Init theme
    if(localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        window.addEventListener('DOMContentLoaded', () => {
            const btn = document.querySelector('#themeToggle');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-sun"></i>';
        });
    } else {
        // default to light as requested
    }
</script>

</body>
</html>



