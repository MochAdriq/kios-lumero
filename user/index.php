<?php
/**
 * Landing Page Pengundian Grand Prize Lumero
 * Mobile-first, tanpa dependency eksternal.
 */
$claimUrl = 'login.php';
$orderUrl = 'online-order.php';

// ── Tarik data pemenang dari batch terbaru yang completed ──────────────
$raffleWinners = [];
$raffleBatchName = '';
try {
    require_once __DIR__ . '/../helpers/functions.php';
    require_once __DIR__ . '/../core/Database.php';
    $pdo = Database::connection();

    // Cari batch completed dengan end_date paling baru
    $stBatch = $pdo->query(
        "SELECT id, name FROM raffle_batches WHERE status = 'completed' ORDER BY end_date DESC LIMIT 1"
    );
    $latestBatch = $stBatch ? $stBatch->fetch(PDO::FETCH_ASSOC) : null;

    if ($latestBatch) {
        $raffleBatchName = $latestBatch['name'];
        $stWin = $pdo->prepare(
            "SELECT rp.name AS prize_name, rp.image_url,
                    m.name AS winner_name,
                    CONCAT(LEFT(m.phone, 4), '****', RIGHT(m.phone, 4)) AS winner_phone_masked
             FROM raffle_prizes rp
             JOIN raffle_tickets rt ON rt.id = rp.winner_ticket_id
             JOIN members m ON m.id = rt.member_id
             WHERE rp.batch_id = ?
             ORDER BY rp.id ASC"
        );
        $stWin->execute([$latestBatch['id']]);
        $raffleWinners = $stWin->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $raffleWinners = [];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#b6090d">
    <meta name="description" content="Pengundian Grand Prize Lumero Outlet Kalibunder. Klaim poin, perbesar kesempatan menang smartphone dan tablet.">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="Pengundian Grand Prize Lumero">
    <meta property="og:description" content="Berlumeran hadiah untuk member Lumero yang mengklaim poin.">
    <meta property="og:image" content="assets/poster-undian.jpg">

    <title>Pengundian Grand Prize | Lumero Kalibunder</title>

    <style>
        :root {
            --red-950: #4e0003;
            --red-900: #790006;
            --red-800: #a4070b;
            --red-700: #c81116;
            --gold-100: #fff7d2;
            --gold-300: #ffd86c;
            --gold-500: #f7ad17;
            --cream: #fff9ea;
            --ink: #321008;
            --nav-height: 78px;
            --radius-xl: 26px;
            --shadow: 0 20px 55px rgba(67, 0, 0, .36);
        }

        * { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
            background: var(--red-950);
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: #fff;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 50% -10%, rgba(255, 208, 61, .28), transparent 35%),
                radial-gradient(circle at 15% 35%, rgba(255, 84, 38, .20), transparent 28%),
                linear-gradient(180deg, #d20b10 0%, #9e0509 36%, #570003 100%);
            padding-bottom: calc(var(--nav-height) + 28px + env(safe-area-inset-bottom));
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: .24;
            background-image:
                radial-gradient(circle, rgba(255,255,255,.9) 0 1px, transparent 1.5px),
                radial-gradient(circle, rgba(255,207,76,.9) 0 1px, transparent 1.6px);
            background-position: 0 0, 18px 24px;
            background-size: 42px 42px, 65px 65px;
        }

        a { color: inherit; }

        .page-shell {
            width: min(100%, 720px);
            margin: 0 auto;
            padding: 14px 12px 28px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 5px 14px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 900;
            letter-spacing: -.03em;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border: 2px solid rgba(255,255,255,.85);
            border-radius: 14px;
            background: linear-gradient(145deg, #fff6c5, #f5a90e);
            color: #a00005;
            box-shadow: 0 7px 18px rgba(65,0,0,.25);
            font-size: 22px;
        }

        .brand-copy small {
            display: block;
            margin-top: 1px;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(255,255,255,.72);
        }

        .outlet-badge {
            max-width: 48%;
            padding: 7px 10px;
            border: 1px solid rgba(255,255,255,.32);
            border-radius: 999px;
            background: rgba(78,0,3,.27);
            font-size: 11px;
            font-weight: 850;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .poster-frame {
            position: relative;
            border: 2px solid rgba(255, 225, 132, .72);
            border-radius: var(--radius-xl);
            padding: 5px;
            background: linear-gradient(135deg, rgba(255,247,206,.85), rgba(242,150,10,.65));
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .poster-frame::after {
            content: "";
            position: absolute;
            top: -45%;
            left: -65%;
            width: 44%;
            height: 190%;
            transform: rotate(18deg);
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.30), transparent);
            animation: shine 5.2s ease-in-out infinite;
            pointer-events: none;
        }

        .poster {
            display: block;
            width: 100%;
            height: auto;
            border-radius: calc(var(--radius-xl) - 7px);
            background: #a3070b;
        }

        @keyframes shine {
            0%, 55% { left: -65%; }
            80%, 100% { left: 140%; }
        }

        .quick-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
        }

        .prize-card {
            min-width: 0;
            padding: 14px 12px;
            border: 1px solid rgba(255,230,155,.7);
            border-radius: 18px;
            color: var(--ink);
            background: linear-gradient(180deg, #fffdf3, #ffeec2);
            box-shadow: 0 10px 24px rgba(67,0,0,.18);
        }

        .prize-card span {
            display: block;
            font-size: 10px;
            font-weight: 900;
            color: #b20b10;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .prize-card strong {
            display: block;
            margin: 3px 0 7px;
            font-size: clamp(16px, 4.3vw, 22px);
            line-height: 1.05;
            letter-spacing: -.04em;
        }

        .prize-card time {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            color: #7d1a0d;
        }

        .info-panel {
            margin-top: 12px;
            padding: 18px 16px;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 22px;
            background: linear-gradient(160deg, rgba(92,0,3,.64), rgba(53,0,1,.78));
            box-shadow: 0 14px 38px rgba(49,0,0,.24);
            backdrop-filter: blur(11px);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 9px;
            border-radius: 999px;
            color: #6f1408;
            background: var(--gold-100);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .info-panel h1 {
            margin: 12px 0 8px;
            font-size: clamp(24px, 7.5vw, 38px);
            line-height: .98;
            letter-spacing: -.055em;
        }

        .info-panel p {
            margin: 0;
            color: rgba(255,255,255,.82);
            font-size: 14px;
            line-height: 1.58;
        }

        .steps {
            display: grid;
            gap: 10px;
            margin-top: 15px;
        }

        .step {
            display: grid;
            grid-template-columns: 34px 1fr;
            gap: 10px;
            align-items: start;
        }

        .step-number {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 11px;
            color: #7b080b;
            background: linear-gradient(145deg, #fff9da, #f8b519);
            font-weight: 950;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.72);
        }

        .step strong {
            display: block;
            padding-top: 1px;
            font-size: 13px;
        }

        .step small {
            display: block;
            margin-top: 3px;
            color: rgba(255,255,255,.68);
            font-size: 11px;
            line-height: 1.4;
        }

        .status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 13px 0 0;
            color: rgba(255,255,255,.76);
            font-size: 11px;
            text-align: center;
        }

        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ffd750;
            box-shadow: 0 0 0 0 rgba(255,215,80,.75);
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            70% { box-shadow: 0 0 0 8px rgba(255,215,80,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,215,80,0); }
        }

        footer {
            padding: 20px 10px 4px;
            color: rgba(255,255,255,.60);
            font-size: 10px;
            line-height: 1.5;
            text-align: center;
        }

        /* ── Pemenang Section ── */
        .winners-section {
            margin-top: 14px;
            padding: 20px 16px;
            border: 1px solid rgba(255, 230, 155, .5);
            border-radius: 22px;
            background: linear-gradient(160deg, rgba(92,0,3,.64), rgba(53,0,1,.78));
            box-shadow: 0 14px 38px rgba(49,0,0,.24);
            backdrop-filter: blur(11px);
        }
        .winners-header {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 16px;
        }
        .winners-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 9px;
            border-radius: 999px;
            color: #6f1408;
            background: var(--gold-100);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .07em;
            text-transform: uppercase;
        }
        .winners-batch-name {
            font-size: 11px;
            font-weight: 800;
            color: rgba(255,255,255,.5);
            margin-top: 4px;
            letter-spacing: .02em;
        }
        .winner-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .winner-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,220,100,.15);
        }
        .winner-prize-img {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            background: rgba(0,0,0,.3);
            border: 1px solid rgba(255,200,80,.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            overflow: hidden;
        }
        .winner-prize-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }
        .winner-info {
            flex: 1;
            min-width: 0;
        }
        .winner-prize-name {
            font-size: 12px;
            font-weight: 900;
            color: var(--gold-300);
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .winner-name {
            font-size: 15px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .winner-phone {
            font-size: 11px;
            color: rgba(255,255,255,.5);
            margin-top: 2px;
        }
        .winner-trophy {
            flex: 0 0 auto;
            font-size: 22px;
            animation: trophySway 3s ease-in-out infinite;
        }
        @keyframes trophySway {
            0%, 100% { transform: rotate(-5deg); }
            50%       { transform: rotate(5deg); }
        }
        .winners-empty {
            text-align: center;
            padding: 20px 10px;
            color: rgba(255,255,255,.45);
            font-size: 13px;
            font-weight: 600;
        }
        .winners-empty .empty-icon {
            font-size: 36px;
            display: block;
            margin-bottom: 10px;
            opacity: .6;
        }

        .sticky-nav {
            position: fixed;
            left: 50%;
            bottom: 0;
            z-index: 50;
            width: min(100%, 720px);
            min-height: var(--nav-height);
            transform: translateX(-50%);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
            border-top: 1px solid rgba(255,228,137,.46);
            background: linear-gradient(180deg, rgba(85,0,3,.88), rgba(63,0,2,.97));
            box-shadow: 0 -14px 35px rgba(49,0,0,.30);
            backdrop-filter: blur(16px);
        }

        .nav-button {
            min-width: 0;
            min-height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 11px;
            border-radius: 16px;
            text-decoration: none;
            font-size: clamp(12px, 3.6vw, 15px);
            font-weight: 950;
            letter-spacing: -.015em;
            transition: transform .18s ease, filter .18s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .nav-button:active { transform: scale(.97); }
        .nav-button:hover { filter: brightness(1.04); }

        .nav-button svg {
            flex: 0 0 auto;
            width: 21px;
            height: 21px;
        }

        .nav-button.claim {
            color: #6f080b;
            background: linear-gradient(145deg, #fff7c9, #f8b317);
            box-shadow: 0 8px 20px rgba(247,173,23,.20), inset 0 0 0 1px rgba(255,255,255,.70);
        }

        .nav-button.order {
            color: #fff;
            border: 1px solid rgba(255,255,255,.43);
            background: linear-gradient(145deg, #da161b, #a7070c);
            box-shadow: inset 0 0 0 1px rgba(255,230,180,.11);
        }

        @media (min-width: 600px) {
            .page-shell { padding-top: 22px; }
            .topbar { padding-inline: 9px; }
            .poster-frame { padding: 7px; }
            .info-panel { padding: 24px; }
            .steps { grid-template-columns: 1fr 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <header class="topbar" aria-label="Identitas halaman">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">L</div>
                <div class="brand-copy">
                    LUMERO
                    <small>Fried Chicken Lumer</small>
                </div>
            </div>
            <div class="outlet-badge">Outlet Kalibunder</div>
        </header>

        <section class="poster-frame" aria-label="Poster Pengundian Grand Prize Lumero">
            <img
                class="poster"
                src="assets/poster-undian.jpg"
                alt="Poster Pengundian Grand Prize Lumero, hadiah smartphone diundi 24 Agustus 2026 dan tablet diundi 24 September 2026."
                width="1447"
                height="2048"
                fetchpriority="high"
                decoding="async"
            >
        </section>

        <section class="quick-info" aria-label="Jadwal pengundian">
            <article class="prize-card">
                <span>Tahap Pertama</span>
                <strong>1 Smartphone</strong>
                <time datetime="2026-08-24" aria-label="24 Agustus 2026">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2v3M17 2v3M3.5 9.5h17M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    24 Agustus 2026
                </time>
            </article>

            <article class="prize-card">
                <span>Tahap Kedua</span>
                <strong>1 Tablet</strong>
                <time datetime="2026-09-24" aria-label="24 September 2026">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2v3M17 2v3M3.5 9.5h17M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    24 September 2026
                </time>
            </article>
        </section>

        <section class="info-panel" aria-labelledby="cara-ikut">
            <span class="eyebrow">Khusus Member Lumero</span>
            <h1 id="cara-ikut">Makin banyak poin, makin besar peluang menang.</h1>
            <p>Klaim poin dari transaksi Lumero, lengkapi identitas member, lalu simpan nomor HP yang terdaftar sebagai identitas pengundian.</p>

            <div class="steps" aria-label="Cara mengikuti pengundian">
                <div class="step">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Masuk ke akun member</strong>
                        <small>Gunakan nomor HP yang aktif dan sudah terdaftar.</small>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Klaim poin belanja</strong>
                        <small>Pastikan setiap transaksi tercatat pada akun Anda.</small>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Lengkapi identitas</strong>
                        <small>Data lengkap diperlukan untuk validasi pemenang.</small>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Kumpulkan lebih banyak poin</strong>
                        <small>Setiap poin menambah kesempatan dalam pengundian.</small>
                    </div>
                </div>
            </div>
        </section>

        <div class="status" aria-live="polite">
            <span class="pulse" aria-hidden="true"></span>
            <span id="draw-status">Pengundian tahap pertama akan segera berlangsung.</span>
        </div>

        <!-- ── Pemenang Undian ── -->
        <section class="winners-section" aria-labelledby="pemenang-title">
            <div class="winners-header">
                <div>
                    <div class="winners-eyebrow">🏆 Pemenang Undian</div>
                    <?php if ($raffleBatchName): ?>
                        <div class="winners-batch-name"><?= htmlspecialchars($raffleBatchName, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif ?>
                </div>
            </div>

            <?php if (!empty($raffleWinners)): ?>
                <div class="winner-list">
                    <?php foreach ($raffleWinners as $w): ?>
                        <div class="winner-item">
                            <div class="winner-prize-img">
                                <?php if (!empty($w['image_url'])): ?>
                                    <img src="../public/assets/<?= htmlspecialchars($w['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($w['prize_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    🎁
                                <?php endif ?>
                            </div>
                            <div class="winner-info">
                                <div class="winner-prize-name"><?= htmlspecialchars($w['prize_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="winner-name"><?= htmlspecialchars($w['winner_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="winner-phone"><?= htmlspecialchars($w['winner_phone_masked'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="winner-trophy" aria-hidden="true">🏆</div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php elseif ($raffleBatchName): ?>
                <div class="winners-empty">
                    <span class="empty-icon">⏳</span>
                    Pengundian belum dimulai.<br>Nantikan siapa pemenang beruntungnya!
                </div>
            <?php else: ?>
                <div class="winners-empty">
                    <span class="empty-icon">🎯</span>
                    Pengundian belum dimulai.<br>Kumpulkan poin untuk ikut serta!
                </div>
            <?php endif ?>
        </section>

        <footer>
            Program berlaku sesuai syarat dan ketentuan pada poster. Keputusan pengundian Lumero bersifat final.<br>
            © 2026 Lumero Outlet Kalibunder · www.lumero.co.id
        </footer>
    </main>

    <nav class="sticky-nav" aria-label="Menu utama">
        <a class="nav-button claim" href="<?= htmlspecialchars($claimUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Klaim hadiah dan poin member Lumero">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12v8H4v-8M2 8h20v4H2V8ZM12 8v12M12 8H7.5A2.5 2.5 0 1 1 10 5.5L12 8Zm0 0h4.5A2.5 2.5 0 1 0 14 5.5L12 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Klaim Hadiah
        </a>
        <a class="nav-button order" href="<?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Pesan produk Lumero secara online">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 5h2l2.2 10.1a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 8H6M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Order Online
        </a>
    </nav>

    <script>
        (() => {
            const status = document.getElementById('draw-status');
            const firstDraw = new Date('2026-08-24T00:00:00+07:00');
            const secondDraw = new Date('2026-09-24T00:00:00+07:00');

            const formatDistance = (target, label) => {
                const now = new Date();
                const diff = target.getTime() - now.getTime();
                if (diff <= 0) return null;
                const days = Math.ceil(diff / 86400000);
                return `${label} dalam ${days} hari lagi.`;
            };

            const first = formatDistance(firstDraw, 'Pengundian smartphone');
            const second = formatDistance(secondDraw, 'Pengundian tablet');

            if (first) {
                status.textContent = first;
            } else if (second) {
                status.textContent = second;
            } else {
                status.textContent = 'Periode pengundian telah selesai.';
            }
        })();
    </script>
</body>
</html>
