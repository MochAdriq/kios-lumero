<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';
loyalty_ensure_tables($pdo);

$claimCode = strtoupper(trim((string)($_GET['claim'] ?? '')));

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

        /* 1. FLUID STRIPE MESH BACKGROUND */
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

        /* LAYOUT ARCHITECTURE */
        .wrapper {
            width: min(960px, 100%);
            margin: 0 auto;
            padding: 40px 24px;
        }

        /* 2. DYNAMIC CLAIM BANNER (Stripe Glass Style) */
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

        /* 3. HERO SECTION - STRIPE ASYMMETRIC / 3D FEEL */
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

        /* STRIPE GLOW BUTTONS */
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

        /* 3D FLOATING HERO CARD (Stripe Isometric Illustration Style) */
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

        /* 4. STRIPE-STYLE BOUNDLESS REWARD CATALOG */
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
            padding-top: 40px; /* Space for levitating items */
        }

        /* The Boundless Card (No white box prison!) */
        .reward-item {
            position: relative;
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 28px;
            padding: 80px 24px 24px; /* Top padding is huge for the floating image */
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

        /* FLOATING 3D OBJECT OUT OF BOX */
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
        /* Give different delay to each card so they don't bounce like robots */
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

        /* 5. STRIPE BENTO GRID FOR PERKS */
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

        /* ANIMATIONS ALCHEMY */
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

        /* FREE BADGE */
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

        /* MAIN MENU SECTION (MARQUEE) */
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
            width: 280px; /* Fixed width to make marquee smooth */
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
    </style>
</head>
<body>

<!-- STRIPE FLUID MESH BACKGROUND -->
<div class="stripe-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
</div>

<div class="wrapper">
    <?php if ($claimCode !== ''): ?>
    <div class="claim-banner">
        <div class="claim-info">
            <div class="live-dot"></div>
            <div>
                <b style="font-size: 15px; display:block;">Struk Tertunda Terdeteksi</b>
                <span style="font-size: 13px; color: var(--muted);">Kode <strong><?= htmlspecialchars($claimCode) ?></strong> siap dikonversi ke saldo poin Anda.</span>
            </div>
        </div>
        <a href="login.php&claim=<?= urlencode($claimCode) ?>" class="btn-stripe btn-primary" style="padding: 10px 20px; font-size: 13px;">Klaim &rarr;</a>
    </div>
    <?php endif; ?>

    <!-- HERO SECTION WITH STRIPE ISOMETRIC FEEL -->
    <header class="hero-section">
        <div class="hero-content">
            <div class="badge-pill">Lumero POS Loyalty Club</div>
            <h1 class="hero-title">Pesananmu,<br>Kini Jadi <span>Hadiah.</span></h1>
            <p class="hero-subtitle">Bukan sekadar transaksi. Kumpulkan poin secara otomatis dari setiap hidangan favoritmu dan nikmati berbagai sajian eksklusif secara cuma-cuma.</p>
            <div class="cta-stack">
                <a href="login.php<?= $claimCode !== '' ? '&claim=' . urlencode($claimCode) : '' ?>" class="btn-stripe btn-primary">
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

    <!-- REWARD SHOWCASE: STRIPE BOUNDLESS CARDS (NO WHITE BOX PRISON!) -->
    <section>
        <div class="section-header">
            <h2>Katalog Hadiah</h2>
            <p>Pilih sajian lezat yang siap Anda tukarkan dengan poin terkumpul.</p>
        </div>

        <div class="reward-showcase">
            <!-- BONUS REGISTRASI (STATIC) -->
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

    <!-- MAIN MENU SHOWCASE (STATIC FOR NOW) -->
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

    <!-- STRIPE BENTO GRID FOR PERKS -->
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