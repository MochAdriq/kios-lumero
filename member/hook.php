<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';
loyalty_ensure_tables($pdo);

$claimCode = strtoupper(trim((string)($_GET['claim'] ?? '')));

// Ambil maksimal 4 katalog hadiah untuk showcase Hook
$stmt = $pdo->query("SELECT * FROM point_reward_products WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 4");
$highlights = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loyalty Club - Lumero POS</title>
    <link rel="icon" type="image/png" href="../assets/img/icon-192.png">
    <style>
        :root {
            --red: #c41230;
            --red-dark: #7a001b;
            --gold: #ffc72c;
            --cream: #fffaf0;
            --ink: #1e1b18;
            --muted: #6b635b;
            --line: #e8dcc8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: radial-gradient(circle at 15% 10%, rgba(255,199,44,0.35), transparent 40%),
                        linear-gradient(180deg, var(--cream), #ffffff);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            min-height: 100vh;
            padding: 24px 16px 64px;
        }
        .container {
            width: min(840px, 100%);
            margin: auto;
        }
        .hero-banner {
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            border-radius: 32px;
            padding: 36px 28px;
            color: #fff;
            box-shadow: 0 20px 50px rgba(196,18,48,0.25);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-banner h1 {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.03em;
            margin-bottom: 12px;
        }
        .hero-banner p {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            line-height: 1.5;
            max-width: 580px;
            margin: 0 auto 28px;
        }
        .claim-alert {
            background: #ffffff;
            color: var(--ink);
            border: 2px solid var(--gold);
            border-radius: 20px;
            padding: 18px 22px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            text-align: left;
        }
        .claim-alert-icon {
            font-size: 32px;
            line-height: 1;
        }
        .claim-alert-text b {
            color: var(--red);
            font-size: 16px;
            display: block;
        }
        .claim-alert-text span {
            font-size: 13px;
            color: var(--muted);
        }
        .cta-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 420px;
            margin: 0 auto;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 24px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-gold {
            background: var(--gold);
            color: #2b1f00;
            box-shadow: 0 8px 20px rgba(255,199,44,0.4);
        }
        .btn-gold:hover { transform: translateY(-2px); }
        .btn-outline {
            background: rgba(255,255,255,0.15);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .section-title {
            margin: 44px 0 20px;
            font-size: 22px;
            font-weight: 900;
            text-align: center;
        }
        .reward-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }
        .reward-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0,0,0,0.04);
            transition: transform 0.2s ease;
        }
        .reward-card:hover { transform: translateY(-3px); }
        .reward-tag {
            align-self: flex-start;
            background: #fef3c7;
            color: #92400e;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        .reward-title {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .reward-desc {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .perks {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 36px;
        }
        .perk-item {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
        }
        .perk-emoji { font-size: 28px; margin-bottom: 8px; }
        .perk-title { font-size: 15px; font-weight: 800; margin-bottom: 4px; }
        .perk-desc { font-size: 13px; color: var(--muted); }
    </style>
</head>
<body>
<div class="container">
    <?php if ($claimCode !== ''): ?>
    <div class="claim-alert">
        <div class="d-flex align-items-center gap-3">
            <div class="claim-alert-icon">🎉</div>
            <div class="claim-alert-text">
                <b>Klaim Poin Tertunda Terdeteksi!</b>
                <span>Struk transaksi dengan kode <strong><?= htmlspecialchars($claimCode) ?></strong> siap dimasukkan ke saldo Anda.</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="hero-banner">
        <h1>Loyalty Club Lumero POS</h1>
        <p>Nikmati setiap hidangan favoritmu, kumpulkan poin dari setiap pesanan, dan tukarkan dengan sajian lezat secara gratis!</p>

        <div class="cta-group">
            <a href="index.php?login=1<?= $claimCode !== '' ? '&claim=' . urlencode($claimCode) : '' ?>" class="btn btn-gold">
                🚀 Daftar & Klaim Poin Sekarang (Gratis)
            </a>
            <a href="index.php?login=1" class="btn btn-outline">
                🔑 Sudah Punya Akun? Masuk Di Sini
            </a>
        </div>
    </div>

    <div class="perks">
        <div class="perk-item">
            <div class="perk-emoji">⚡</div>
            <div class="perk-title">Tanpa Install Aplikasi</div>
            <div class="perk-desc">Cukup gunakan nomor WhatsApp Anda. Cepat, aman, dan tanpa repot.</div>
        </div>
        <div class="perk-item">
            <div class="perk-emoji">💎</div>
            <div class="perk-title">Poin Otomatis</div>
            <div class="perk-desc">Setiap kelipatan belanja otomatis menghasilkan poin yang bisa ditukar.</div>
        </div>
        <div class="perk-item">
            <div class="perk-emoji">🎁</div>
            <div class="perk-title">Hadiah Eksklusif</div>
            <div class="perk-desc">Pilih hadiah makanan atau minuman gratis langsung dari katalog.</div>
        </div>
    </div>

    <h2 class="section-title">✨ Katalog Hadiah Pilihan</h2>
    <div class="reward-grid">
        <?php if (empty($highlights)): ?>
            <div class="reward-card" style="grid-column: 1/-1; text-align: center; color: var(--muted);">
                Katalog hadiah spesial sedang dipersiapkan.
            </div>
        <?php else: ?>
            <?php foreach ($highlights as $rw): ?>
            <div class="reward-card">
                <div>
                    <span class="reward-tag">Butuh <?= number_format((int)$rw['required_points'],0,',','.') ?> Poin</span>
                    <div class="reward-title"><?= htmlspecialchars($rw['name']) ?></div>
                    <div class="reward-desc"><?= htmlspecialchars($rw['description'] ?? 'Hadiah hidangan lezat Lumero') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
