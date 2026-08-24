<?php
function svgTrophy(): string {
    return '<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8M12 17v4"/><path d="M7 4H4a2 2 0 0 0-2 2v1c0 2.76 2.24 5 5 5h.5"/><path d="M17 4h3a2 2 0 0 1 2 2v1c0 2.76-2.24 5-5 5h-.5"/><path d="M7 4h10v8a5 5 0 0 1-10 0V4z"/></svg>';
}
function svgCheck(): string {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
}

$eventEndTime = strtotime($batch['end_date'] ?? 'now');
$eventEndIso  = date('c', $eventEndTime);
$batchName    = htmlspecialchars($batch['name']);
$outletName   = 'Lumero Kalibunder';
$marqueeText  = "✨ Selamat Datang di Pengundian {$batchName} &nbsp;|&nbsp; {$outletName} &nbsp;|&nbsp; Terima kasih telah berpartisipasi! 🎉 &nbsp;|&nbsp; Semoga beruntung kepada seluruh peserta! 🍀 &nbsp;|&nbsp; Hadiah siap menanti Anda! 🎁 &nbsp;|&nbsp; ";
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Outfit:wght@900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('pos-template/bootstrap.min.css') ?>">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --gold: #f59e0b;
            --gold-light: #fcd34d;
            --green-neon: #4ade80;
            --blue-glow: #818cf8;
            --purple-glow: #a78bfa;
            --bg: #03040e;
        }

        html, body {
            margin: 0; padding: 0;
            background: var(--bg);
            color: #fff;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Canvas Layers ─────────────────────────────────── */
        #bgCanvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        #fxCanvas  { position: fixed; inset: 0; z-index: 1; pointer-events: none; }

        /* ── Spotlight Beams (CSS) ─────────────────────────── */
        .spotlights {
            position: fixed; inset: 0; z-index: 2; pointer-events: none; overflow: hidden;
        }
        .beam {
            position: absolute;
            top: -40%;
            width: 3px;
            height: 180%;
            border-radius: 50%;
            transform-origin: top center;
        }
        .beam-1 {
            left: 15%;
            background: linear-gradient(to bottom, rgba(129,140,248,0.18), transparent 70%);
            animation: swingBeam 8s ease-in-out infinite;
            box-shadow: 0 0 30px 8px rgba(129,140,248,0.07);
        }
        .beam-2 {
            left: 40%;
            background: linear-gradient(to bottom, rgba(245,158,11,0.12), transparent 65%);
            animation: swingBeam 11s ease-in-out infinite reverse;
            box-shadow: 0 0 30px 8px rgba(245,158,11,0.05);
        }
        .beam-3 {
            right: 20%;
            background: linear-gradient(to bottom, rgba(167,139,250,0.15), transparent 70%);
            animation: swingBeam 9.5s ease-in-out infinite 2s;
            box-shadow: 0 0 30px 8px rgba(167,139,250,0.06);
        }
        .beam-4 {
            right: 5%;
            background: linear-gradient(to bottom, rgba(74,222,128,0.10), transparent 60%);
            animation: swingBeam 13s ease-in-out infinite reverse 1s;
            box-shadow: 0 0 30px 8px rgba(74,222,128,0.04);
        }
        @keyframes swingBeam {
            0%, 100% { transform: rotate(-18deg); }
            50%       { transform: rotate(18deg);  }
        }

        /* ── Whole page layout ─────────────────────────────── */
        .page-content { position: relative; z-index: 10; padding-bottom: 80px; }

        /* ── Header ─────────────────────────────────────────── */
        .show-header {
            text-align: center;
            padding: 60px 20px 40px;
            position: relative;
        }
        .event-eyebrow {
            font-size: 11px; font-weight: 700; letter-spacing: 0.25em;
            text-transform: uppercase; color: var(--gold); margin-bottom: 16px;
            opacity: 0; animation: fadeSlideDown 0.8s 0.3s ease forwards;
        }
        .event-title {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            line-height: 1.05;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 20%, var(--gold-light) 50%, #fff 80%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeSlideDown 0.8s 0.5s ease forwards, shimmerText 4s 1.5s linear infinite;
            opacity: 0;
        }
        @keyframes shimmerText {
            0%   { background-position: 200% center; }
            100% { background-position: -200% center; }
        }
        .event-subtitle {
            font-size: 1.1rem; color: rgba(255,255,255,0.55); margin-bottom: 40px;
            opacity: 0; animation: fadeSlideDown 0.8s 0.7s ease forwards;
        }
        @keyframes fadeSlideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Countdown ─────────────────────────────────────── */
        .countdown-section {
            display: flex; gap: 16px; justify-content: center; align-items: center;
            flex-wrap: wrap; margin-bottom: 50px;
            opacity: 0; animation: fadeSlideDown 0.8s 0.9s ease forwards;
        }
        .countdown-label-top {
            width: 100%; text-align: center;
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.2em; color: rgba(255,255,255,0.4);
            margin-bottom: 8px;
        }
        .cd-block {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; padding: 20px 28px;
            min-width: 100px; text-align: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(129,140,248,0.08), inset 0 1px 0 rgba(255,255,255,0.07);
            position: relative; overflow: hidden;
        }
        .cd-block::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(129,140,248,0.06) 0%, transparent 100%);
        }
        .cd-block .cd-num {
            font-family: 'Outfit', monospace;
            font-size: 3.5rem; font-weight: 900; line-height: 1;
            color: #fff;
            text-shadow: 0 0 30px rgba(129,140,248,0.5);
            display: block;
            transition: transform 0.15s cubic-bezier(0.34,1.56,0.64,1);
        }
        .cd-block .cd-num.flip { transform: scale(1.15); }
        .cd-block .cd-unit {
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.15em; color: rgba(255,255,255,0.4);
            margin-top: 6px; display: block;
        }
        .cd-sep {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem; font-weight: 900; color: rgba(255,255,255,0.3);
            line-height: 1; padding-bottom: 24px;
            animation: blink 1s step-end infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

        .cd-done-msg {
            display: none;
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem; font-weight: 900;
            background: linear-gradient(135deg, var(--gold), var(--gold-light), var(--gold));
            background-size: 200% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            animation: shimmerText 2s linear infinite;
            text-align: center; padding: 20px;
        }

        /* ── Divider ──────────────────────────────────────── */
        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(129,140,248,0.3), rgba(245,158,11,0.3), rgba(129,140,248,0.3), transparent);
            margin: 10px auto 50px; max-width: 700px;
        }
        .section-label {
            text-align: center;
            font-size: 11px; font-weight: 800; letter-spacing: 0.25em; text-transform: uppercase;
            color: rgba(255,255,255,0.35); margin-bottom: 30px;
        }

        /* ── Prize Cards ───────────────────────────────────── */
        .prize-grid { padding: 0 20px; }

        .prize-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 24px; padding: 32px 20px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative; overflow: hidden;
            height: 100%;
        }
        .prize-card::before {
            content: '';
            position: absolute; inset: 0; border-radius: 24px;
            background: linear-gradient(145deg, rgba(129,140,248,0.05), transparent 60%);
            opacity: 0; transition: opacity 0.4s;
        }
        .prize-card.not-drawn {
            cursor: pointer;
            border-color: rgba(74,222,128,0.2);
            animation: cardPulse 3s ease-in-out infinite;
        }
        .prize-card.not-drawn::after {
            content: '';
            position: absolute; inset: -2px; border-radius: 26px;
            background: linear-gradient(135deg, rgba(74,222,128,0.25), rgba(129,140,248,0.1), rgba(74,222,128,0.25));
            z-index: -1; opacity: 0;
            transition: opacity 0.4s;
        }
        .prize-card.not-drawn:hover {
            transform: translateY(-12px) scale(1.02);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 20px 50px rgba(74,222,128,0.2), 0 0 0 1px rgba(74,222,128,0.4);
            border-color: rgba(74,222,128,0.5);
        }
        .prize-card.not-drawn:hover::before { opacity: 1; }
        .prize-card.not-drawn:hover::after  { opacity: 1; }
        .prize-card.not-drawn:hover .prize-img { transform: scale(1.08); border-color: rgba(74,222,128,0.6); box-shadow: 0 0 25px rgba(74,222,128,0.4); }

        @keyframes cardPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(74,222,128,0.0), 0 0 20px rgba(74,222,128,0.05); }
            50%       { box-shadow: 0 0 0 6px rgba(74,222,128,0.0), 0 0 30px rgba(74,222,128,0.12); }
        }

        .prize-card.drawn {
            background: linear-gradient(145deg, rgba(245,158,11,0.08), rgba(245,158,11,0.02));
            border-color: rgba(245,158,11,0.35);
            cursor: default;
            box-shadow: 0 0 25px rgba(245,158,11,0.08), inset 0 1px 0 rgba(245,158,11,0.1);
            animation: drawnCardShine 4s ease-in-out infinite;
        }
        @keyframes drawnCardShine {
            0%, 100% { box-shadow: 0 0 20px rgba(245,158,11,0.08); }
            50%       { box-shadow: 0 0 40px rgba(245,158,11,0.15); }
        }

        .prize-img-wrap {
            width: 110px; height: 110px;
            border-radius: 50%; margin: 0 auto 20px;
            position: relative; display: flex; align-items: center; justify-content: center;
        }
        .prize-img-wrap::before {
            content: '';
            position: absolute; inset: -4px; border-radius: 50%;
            background: conic-gradient(from 0deg, rgba(74,222,128,0.5), rgba(129,140,248,0.3), rgba(74,222,128,0.5));
            animation: rotateRing 3s linear infinite;
        }
        .prize-card.drawn .prize-img-wrap::before {
            background: conic-gradient(from 0deg, rgba(245,158,11,0.6), rgba(253,212,77,0.3), rgba(245,158,11,0.6));
        }
        @keyframes rotateRing { 100% { transform: rotate(360deg); } }
        .prize-img-inner {
            position: absolute; inset: 3px; border-radius: 50%;
            background: var(--bg); display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .prize-img {
            width: 100%; height: 100%; object-fit: cover;
            border-radius: 50%;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .prize-name {
            font-weight: 800; font-size: 1rem; line-height: 1.4;
            margin-bottom: 20px; color: #fff;
        }

        .prize-status-ready {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3);
            color: var(--green-neon); font-size: 12px; font-weight: 800;
            padding: 8px 20px; border-radius: 999px;
            text-transform: uppercase; letter-spacing: 0.12em;
        }
        .ready-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--green-neon);
            animation: pulseDot 1.5s ease-in-out infinite;
            box-shadow: 0 0 6px var(--green-neon);
        }
        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%       { transform: scale(1.5); opacity: 0.7; }
        }

        .prize-winner-wrap { text-align: center; }
        .prize-winner-label {
            font-size: 10px; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--gold); margin-bottom: 8px;
        }
        .prize-winner-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem; font-weight: 900; color: #fff;
            margin-bottom: 8px;
        }
        .prize-ticket-badge {
            background: rgba(0,0,0,0.3); border: 1px solid rgba(245,158,11,0.3);
            color: var(--gold); font-size: 12px; font-weight: 700;
            font-family: 'Courier New', monospace;
            padding: 6px 14px; border-radius: 8px;
            display: inline-block; margin-bottom: 6px;
        }
        .prize-winner-phone {
            font-size: 12px; color: rgba(255,255,255,0.45);
        }

        /* ── Click hint ────────────────────────────────────── */
        .click-hint {
            text-align: center; padding: 30px 20px;
            font-size: 13px; color: rgba(255,255,255,0.3);
            font-weight: 600; letter-spacing: 0.05em;
            animation: hintPulse 3s ease-in-out infinite;
        }
        @keyframes hintPulse {
            0%, 100% { opacity: 0.5; }
            50%       { opacity: 1; }
        }

        /* ── Mute Button ───────────────────────────────────── */
        #muteBtn {
            position: fixed; bottom: 80px; right: 20px; z-index: 1000;
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.6); font-size: 18px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            backdrop-filter: blur(10px);
        }
        #muteBtn:hover { background: rgba(255,255,255,0.15); }

        /* ── Marquee ───────────────────────────────────────── */
        .marquee-wrap {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;
            background: rgba(0,0,0,0.7); border-top: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(10px); overflow: hidden; height: 40px;
            display: flex; align-items: center;
        }
        .marquee-inner {
            white-space: nowrap;
            animation: marqueeScroll 40s linear infinite;
            font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.6);
            padding-left: 100%;
        }
        @keyframes marqueeScroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        /* ══════════════════════════════════════════════════
           ROLL OVERLAY
        ══════════════════════════════════════════════════ */
        .roll-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: radial-gradient(ellipse at center, #06071a 0%, #020308 100%);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .roll-panel {
            width: 90%; max-width: 640px; text-align: center;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(129,140,248,0.15);
            border-radius: 32px; padding: 48px 32px;
            box-shadow: 0 0 60px rgba(129,140,248,0.1), 0 30px 60px rgba(0,0,0,0.6),
                        inset 0 1px 0 rgba(255,255,255,0.06);
            backdrop-filter: blur(30px);
            position: relative; overflow: hidden;
        }
        .roll-panel::before {
            content: '';
            position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg at 50% 50%, transparent 0%, rgba(129,140,248,0.04) 10%, transparent 20%);
            animation: rotateSpotlight 12s linear infinite;
            pointer-events: none;
        }
        .roll-panel::after {
            content: '';
            position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 120deg at 50% 50%, transparent 0%, rgba(245,158,11,0.03) 10%, transparent 20%);
            animation: rotateSpotlight 18s linear infinite reverse;
            pointer-events: none;
        }
        .giant-countdown {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-size: 220px; font-weight: 900; color: #fff; z-index: 9999;
            text-shadow: 0 0 100px rgba(245,158,11,0.8); opacity: 0; pointer-events: none;
        }
        @keyframes popIn {
            0%   { transform: scale(0.3); opacity: 0; }
            30%  { transform: scale(1.1); opacity: 1; }
            80%  { transform: scale(1);   opacity: 1; }
            100% { transform: scale(0.9); opacity: 0; }
        }
        .roll-eyebrow {
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.15em; color: var(--gold); margin-bottom: 16px;
        }
        .roll-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 26px; font-weight: 900; color: #fff;
            margin-bottom: 28px; letter-spacing: -0.02em;
        }
        .roll-machine {
            position: relative; height: 120px; border-radius: 20px;
            overflow: hidden; margin: 0 auto 12px;
            background: rgba(255,255,255,0.04);
            border: 2px solid rgba(129,140,248,0.15);
            box-shadow: 0 0 20px rgba(129,140,248,0.08) inset;
        }
        .roll-strip { display: flex; flex-direction: column; will-change: transform; }
        .roll-strip.spinning { animation: spinSlot 0.25s linear infinite; filter: blur(1px); }
        @keyframes spinSlot {
            0%   { transform: translateY(0); }
            100% { transform: translateY(calc(-100% + 120px)); }
        }
        .roll-strip.stopping { transition: transform 3.8s cubic-bezier(0.12, 0.85, 0.2, 1); }
        .roll-item {
            height: 120px; display: flex; align-items: center; justify-content: center;
            font-family: 'Courier New', monospace; font-size: 40px; font-weight: 900;
            color: #fff; letter-spacing: 0.05em; flex-shrink: 0;
        }
        .roll-item.winner-item {
            color: var(--gold); font-size: 46px;
            text-shadow: 0 0 30px rgba(245,158,11,0.7);
        }
        .roll-mask { position: absolute; left: 0; right: 0; height: 40px; pointer-events: none; z-index: 2; }
        .roll-mask-top    { top: 0;    background: linear-gradient(to bottom, rgba(6,7,26,0.95), transparent); }
        .roll-mask-bottom { bottom: 0; background: linear-gradient(to top,   rgba(6,7,26,0.95), transparent); }
        @keyframes rotateSpotlight { 100% { transform: rotate(360deg); } }

        .roll-status {
            font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.4);
            margin-top: 12px; margin-bottom: 32px; min-height: 20px;
        }
        .roll-participant-count {
            color: var(--green-neon); font-weight: 800; margin-bottom: 20px;
            font-size: 14px; letter-spacing: 0.08em; text-transform: uppercase;
            text-shadow: 0 0 12px rgba(74,222,128,0.4);
        }

        .btn-start-roll {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 16px 52px; border-radius: 16px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff; font-size: 16px; font-weight: 900; letter-spacing: 0.08em;
            border: none; cursor: pointer;
            box-shadow: 0 8px 32px rgba(22,163,74,0.35);
            transition: transform 0.2s, box-shadow 0.2s;
            text-transform: uppercase;
        }
        .btn-start-roll:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(22,163,74,0.5); }

        .winner-reveal { display: none; position: relative; z-index: 2; }
        .winner-reveal.visible {
            display: block;
            animation: revealIn 0.65s cubic-bezier(0.16,1,0.3,1) forwards;
        }
        @keyframes revealIn {
            0%   { opacity: 0; transform: scale(0.9) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .winner-trophy-wrap {
            display: flex; justify-content: center; margin-bottom: 16px;
            animation: trophyBounce 2s infinite;
        }
        @keyframes trophyBounce {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25%       { transform: translateY(-12px) rotate(-3deg); }
            75%       { transform: translateY(-12px) rotate(3deg); }
        }
        .winner-tag {
            font-size: 12px; font-weight: 800; color: var(--gold);
            text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 10px;
        }
        .winner-big-name {
            font-family: 'Outfit', sans-serif;
            font-size: 3.2rem; font-weight: 900; color: #fff;
            letter-spacing: -0.02em; line-height: 1.1; margin-bottom: 16px;
            text-shadow: 0 0 30px rgba(255,255,255,0.2);
        }
        .winner-ticket-num {
            font-size: 22px; font-weight: 900; color: var(--green-neon);
            font-family: 'Courier New', monospace;
            letter-spacing: 0.08em; margin-bottom: 8px;
        }
        .winner-phone-num {
            font-size: 16px; font-weight: 600;
            color: rgba(255,255,255,0.55); margin-bottom: 36px;
        }
        .btn-close-roll {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 36px; border-radius: 14px;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            color: #fff; font-size: 15px; font-weight: 800;
            cursor: pointer; transition: background 0.2s, border-color 0.2s;
        }
        .btn-close-roll:hover {
            background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.25);
        }

        /* ── Confetti ──────────────────────────────────────── */
        #confettiCanvas { position: fixed; inset: 0; pointer-events: none; z-index: 9998; display: none; }

        /* ── Footer ────────────────────────────────────────── */
        .footer-link {
            position: fixed; bottom: 50px; left: 50%; transform: translateX(-50%);
            z-index: 999; opacity: 0.4; font-size: 12px;
            color: rgba(255,255,255,0.5); text-decoration: none;
            transition: opacity 0.2s;
        }
        .footer-link:hover { opacity: 1; color: #fff; }

        /* Card animation delay */
        .col:nth-child(1) .prize-card { animation: cardAppear 0.6s 0.2s ease backwards; }
        .col:nth-child(2) .prize-card { animation: cardAppear 0.6s 0.35s ease backwards; }
        .col:nth-child(3) .prize-card { animation: cardAppear 0.6s 0.5s ease backwards; }
        .col:nth-child(4) .prize-card { animation: cardAppear 0.6s 0.65s ease backwards; }
        .col:nth-child(5) .prize-card { animation: cardAppear 0.6s 0.8s ease backwards; }
        .col:nth-child(6) .prize-card { animation: cardAppear 0.6s 0.95s ease backwards; }
        .col:nth-child(n+7) .prize-card { animation: cardAppear 0.6s 1.1s ease backwards; }
        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>

<!-- Canvas Layers -->
<canvas id="bgCanvas"></canvas>
<canvas id="fxCanvas"></canvas>

<!-- Spotlight Beams -->
<div class="spotlights">
    <div class="beam beam-1"></div>
    <div class="beam beam-2"></div>
    <div class="beam beam-3"></div>
    <div class="beam beam-4"></div>
</div>

<!-- Main Content -->
<div class="page-content">

    <!-- Header -->
    <div class="show-header">
        <div class="event-eyebrow">✦ Pengundian Hadiah Spesial ✦</div>
        <h1 class="event-title"><?= $batchName ?></h1>
        <p class="event-subtitle">Pilih kartu hadiah di bawah ini untuk memulai pengundian</p>

        <!-- Countdown -->
        <div class="countdown-section" id="countdownSection">
            <div class="countdown-label-top" id="cdLabelTop">Pengundian Dimulai Dalam</div>
            <div id="cdDoneMsg" class="cd-done-msg">🎉 Saatnya Mengundi! Selamat! 🎉</div>
            <div id="cdBlocks" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;justify-content:center;">
                <div class="cd-block">
                    <span class="cd-num" id="cdH">00</span>
                    <span class="cd-unit">Jam</span>
                </div>
                <div class="cd-sep">:</div>
                <div class="cd-block">
                    <span class="cd-num" id="cdM">00</span>
                    <span class="cd-unit">Menit</span>
                </div>
                <div class="cd-sep">:</div>
                <div class="cd-block">
                    <span class="cd-num" id="cdS">00</span>
                    <span class="cd-unit">Detik</span>
                </div>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>
    <div class="section-label">🎁 Daftar Hadiah &mdash; Klik untuk Mengundi</div>

    <!-- Prize Grid -->
    <div class="container prize-grid">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4 justify-content-center">
            <?php foreach ($prizes as $p): ?>
                <div class="col">
                    <div class="prize-card <?= $p['winner_ticket_id'] ? 'drawn' : 'not-drawn' ?>"
                         <?php if (!$p['winner_ticket_id']): ?>
                         onclick="prepareRoll(<?= (int)$p['id'] ?>, <?= (int)$batch['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= $p['image_url'] ? asset($p['image_url']) : '' ?>')"
                         <?php endif; ?>>

                        <div class="prize-img-wrap">
                            <div class="prize-img-inner">
                                <?php if ($p['image_url']): ?>
                                    <img src="<?= asset($p['image_url']) ?>" class="prize-img" alt="<?= htmlspecialchars($p['name']) ?>">
                                <?php else: ?>
                                    <span style="font-size:3rem;">🎁</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="prize-name"><?= htmlspecialchars($p['name']) ?></div>

                        <div>
                            <?php if ($p['winner_ticket_id']): ?>
                                <div class="prize-winner-wrap">
                                    <div class="prize-winner-label">🏆 Pemenang</div>
                                    <div class="prize-winner-name"><?= htmlspecialchars($p['winner_name'] ?? '-') ?></div>
                                    <div class="prize-ticket-badge"><?= htmlspecialchars($p['ticket_code']) ?></div>
                                    <?php if (!empty($p['winner_phone'])): ?>
                                        <div class="prize-winner-phone"><?= htmlspecialchars($p['winner_phone']) ?></div>
                                    <?php endif ?>
                                </div>
                            <?php else: ?>
                                <div class="prize-status-ready">
                                    <span class="ready-dot"></span>
                                    Siap Diundi
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="click-hint" id="clickHint">
            👆 Klik kartu hadiah di atas untuk mulai mengundi
        </div>
    </div>

</div><!-- end page-content -->

<!-- Mute Button -->
<button id="muteBtn" title="Mute/Unmute">🔊</button>

<!-- Marquee -->
<div class="marquee-wrap">
    <div class="marquee-inner">
        <?= $marqueeText . $marqueeText ?>
    </div>
</div>

<!-- Tutup Layar -->
<a href="javascript:window.close()" class="footer-link">✕ Tutup Layar</a>

<!-- ─── Rolling Overlay ─────────────────────────────────────── -->
<div id="rollOverlay" class="roll-overlay" hidden>
    <div id="giantCountdown" class="giant-countdown"></div>
    <div class="roll-panel">
        <div id="rollPhase" style="position:relative;z-index:2;transition:opacity 0.5s;">
            <div class="roll-eyebrow">✦ Pengundian Berlangsung ✦</div>

            <div id="rollPrizeImgWrap" style="text-align:center;margin-bottom:20px;display:none;">
                <img id="rollPrizeImg" src="" alt="Prize"
                     style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:3px solid rgba(245,158,11,0.5);box-shadow:0 0 30px rgba(245,158,11,0.3);">
            </div>

            <div class="roll-heading" id="rollPrizeName"></div>

            <div class="roll-machine">
                <div class="roll-mask roll-mask-top"></div>
                <div class="roll-strip" id="rollStrip">
                    <div class="roll-item" style="color:rgba(255,255,255,0.3)">[ SIAP DIUNDI ]</div>
                </div>
                <div class="roll-mask roll-mask-bottom"></div>
            </div>

            <div class="roll-status" id="rollStatus">Tekan tombol di bawah atau tombol Spasi</div>

            <div class="roll-participant-count">
                <?= number_format($stats['total_tickets'] ?? 0, 0, ',', '.') ?> Tiket Aktif
            </div>

            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                <button id="btnStartRoll" class="btn-start-roll">▶ Mulai Pengundian</button>
                <button id="btnCancelRoll"
                        onclick="cancelRoll()"
                        style="background:none;border:1px solid rgba(255,255,255,0.15);border-radius:999px;color:rgba(255,255,255,0.5);padding:8px 28px;cursor:pointer;font-size:13px;font-weight:700;transition:all 0.2s;">
                    Batal &amp; Pilih Ulang
                </button>
            </div>
        </div>

        <!-- Winner reveal -->
        <div class="winner-reveal" id="winnerReveal">
            <div class="winner-trophy-wrap"><?= svgTrophy() ?></div>
            <div class="winner-tag">🎉 Selamat Kepada Pemenang! 🎉</div>
            <div class="winner-big-name" id="winnerName"></div>
            <div class="winner-ticket-num" id="winnerTicketCode"></div>
            <div class="winner-phone-num" id="winnerPhone"></div>
            <button class="btn-close-roll" onclick="closeRoll()">
                <?= svgCheck() ?> Tutup &amp; Lihat Hadiah Lainnya
            </button>
        </div>
    </div>
</div>

<canvas id="confettiCanvas"></canvas>

<script>
/* ═══════════════════════════════════════════════════════════
   CONFIGURATION
═══════════════════════════════════════════════════════════ */
const EVENT_END_TIME  = new Date('<?= $eventEndIso ?>').getTime();
const TICKET_POOL     = ['UND-982133','UND-458129','UND-102934','UND-847192','UND-382910','UND-582914','UND-784192'];

/* ═══════════════════════════════════════════════════════════
   1. STAR FIELD (bgCanvas)
═══════════════════════════════════════════════════════════ */
(function initStarField() {
    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');
    let W, H, stars = [];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
        buildStars();
    }

    function buildStars() {
        stars = [];
        const count = Math.floor((W * H) / 3000);
        for (let i = 0; i < count; i++) {
            const size = Math.random() * 2 + 0.3;
            stars.push({
                x:     Math.random() * W,
                y:     Math.random() * H,
                size,
                baseAlpha: Math.random() * 0.7 + 0.3,
                alpha:     0,
                speed:     Math.random() * 0.5 + 0.3,
                twinkleSpeed: Math.random() * 0.02 + 0.005,
                twinkleOffset: Math.random() * Math.PI * 2,
                hue:  Math.random() > 0.85 ? (Math.random() > 0.5 ? 230 : 280) : 0,
                sat:  Math.random() > 0.85 ? 80 : 0,
            });
        }
    }

    let t = 0;
    function draw() {
        ctx.clearRect(0, 0, W, H);

        // Deep space bg
        const grad = ctx.createRadialGradient(W/2, H*0.3, 0, W/2, H/2, W*0.8);
        grad.addColorStop(0,   'rgba(10,12,40,1)');
        grad.addColorStop(0.5, 'rgba(5,6,20,1)');
        grad.addColorStop(1,   'rgba(3,4,14,1)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        // Nebula clusters
        const nebulae = [
            { x: W*0.2, y: H*0.3, r: W*0.25, c: '80,60,180' },
            { x: W*0.8, y: H*0.5, r: W*0.2,  c: '60,100,200' },
            { x: W*0.5, y: H*0.8, r: W*0.3,  c: '120,40,160' },
        ];
        nebulae.forEach(n => {
            const g = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, n.r);
            g.addColorStop(0, `rgba(${n.c},0.06)`);
            g.addColorStop(1, `rgba(${n.c},0)`);
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, W, H);
        });

        // Stars
        t += 0.016;
        stars.forEach(s => {
            s.alpha = s.baseAlpha * (0.5 + 0.5 * Math.sin(t * s.twinkleSpeed * 60 + s.twinkleOffset));
            ctx.save();
            if (s.hue > 0) {
                ctx.shadowColor = `hsl(${s.hue},${s.sat}%,70%)`;
                ctx.shadowBlur  = s.size * 4;
            }
            ctx.globalAlpha = s.alpha;
            ctx.fillStyle   = s.hue > 0 ? `hsl(${s.hue},${s.sat}%,85%)` : '#fff';
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.size, 0, Math.PI*2);
            ctx.fill();
            ctx.restore();
        });

        requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    resize();
    draw();
})();

/* ═══════════════════════════════════════════════════════════
   2. FIREWORKS ENGINE (fxCanvas)
═══════════════════════════════════════════════════════════ */
(function initFireworks() {
    const canvas = document.getElementById('fxCanvas');
    const ctx    = canvas.getContext('2d');
    let W, H;
    const particles = [];
    const FW_COLORS = ['#f59e0b','#818cf8','#4ade80','#fb7185','#38bdf8','#fcd34d','#c084fc'];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    function explode(x, y) {
        const count  = 55 + Math.floor(Math.random() * 30);
        const color  = FW_COLORS[Math.floor(Math.random() * FW_COLORS.length)];
        const speed  = 2.5 + Math.random() * 3;
        for (let i = 0; i < count; i++) {
            const angle = (Math.PI * 2 * i) / count + Math.random() * 0.3;
            const v     = speed * (0.5 + Math.random() * 0.5);
            particles.push({
                x, y,
                vx: Math.cos(angle) * v,
                vy: Math.sin(angle) * v,
                alpha: 1, size: Math.random() * 2.5 + 1,
                color, decay: 0.014 + Math.random() * 0.01,
                trail: [],
            });
        }
    }

    function launchFirework() {
        if (document.getElementById('rollOverlay').hasAttribute('hidden')) {
            const x = W * (0.1 + Math.random() * 0.8);
            const y = H * (0.05 + Math.random() * 0.55);
            explode(x, y);
        }
        const nextDelay = 2500 + Math.random() * 4000;
        setTimeout(launchFirework, nextDelay);
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.trail.push({ x: p.x, y: p.y, alpha: p.alpha });
            if (p.trail.length > 6) p.trail.shift();

            p.x  += p.vx;
            p.y  += p.vy;
            p.vy += 0.04;
            p.vx *= 0.98;
            p.alpha -= p.decay;

            p.trail.forEach((pt, ti) => {
                const ta = pt.alpha * (ti / p.trail.length) * 0.4;
                ctx.save();
                ctx.globalAlpha = ta;
                ctx.fillStyle   = p.color;
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, p.size * 0.5, 0, Math.PI*2);
                ctx.fill();
                ctx.restore();
            });

            if (p.alpha > 0) {
                ctx.save();
                ctx.globalAlpha = p.alpha;
                ctx.shadowColor = p.color;
                ctx.shadowBlur  = 8;
                ctx.fillStyle   = p.color;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI*2);
                ctx.fill();
                ctx.restore();
            } else {
                particles.splice(i, 1);
            }
        }
        requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    resize();
    draw();
    setTimeout(launchFirework, 1500);
    window.triggerFirework = explode;
})();

/* ═══════════════════════════════════════════════════════════
   3. COUNTDOWN TIMER
═══════════════════════════════════════════════════════════ */
(function initCountdown() {
    const elH   = document.getElementById('cdH');
    const elM   = document.getElementById('cdM');
    const elS   = document.getElementById('cdS');
    const done  = document.getElementById('cdDoneMsg');
    const blocks = document.getElementById('cdBlocks');
    const label  = document.getElementById('cdLabelTop');

    function pad(n) { return String(n).padStart(2, '0'); }

    function flip(el, val) {
        const str = pad(val);
        if (el.textContent !== str) {
            el.textContent = str;
            el.classList.remove('flip');
            void el.offsetWidth;
            el.classList.add('flip');
            setTimeout(() => el.classList.remove('flip'), 200);
        }
    }

    function tick() {
        const now  = Date.now();
        const diff = EVENT_END_TIME - now;

        if (diff <= 0) {
            blocks.style.display = 'none';
            label.style.display  = 'none';
            done.style.display   = 'block';
            return;
        }

        const totalSec = Math.floor(diff / 1000);
        const h = Math.floor(totalSec / 3600);
        const m = Math.floor((totalSec % 3600) / 60);
        const s = totalSec % 60;

        flip(elH, h);
        flip(elM, m);
        flip(elS, s);

        setTimeout(tick, 1000);
    }
    tick();
})();

/* ═══════════════════════════════════════════════════════════
   4. AMBIENT AUDIO
═══════════════════════════════════════════════════════════ */
let ambientNodes  = [];
let isMuted       = false;
let audioStarted  = false;
const audioCtx    = new (window.AudioContext || window.webkitAudioContext)();

function startAmbient() {
    if (audioStarted) return;
    audioStarted = true;

    const notes = [130.81, 164.81, 196.00, 261.63]; // C3 E3 G3 C4
    notes.forEach((freq, i) => {
        const osc  = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        const filter = audioCtx.createBiquadFilter();

        osc.type = 'sine';
        osc.frequency.value = freq;
        filter.type = 'lowpass';
        filter.frequency.value = 600;
        gain.gain.value = isMuted ? 0 : 0.018;

        osc.connect(filter);
        filter.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();

        ambientNodes.push({ osc, gain });
    });
}

function setAmbientVolume(v) {
    ambientNodes.forEach(n => {
        n.gain.gain.setTargetAtTime(v, audioCtx.currentTime, 0.3);
    });
}

document.addEventListener('click', () => {
    if (audioCtx.state === 'suspended') audioCtx.resume();
    startAmbient();
}, { once: true });

document.getElementById('muteBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    isMuted = !isMuted;
    this.textContent = isMuted ? '🔇' : '🔊';
    if (audioStarted) setAmbientVolume(isMuted ? 0 : 0.018);
});

/* ═══════════════════════════════════════════════════════════
   5. SPIN SOUND HELPERS
═══════════════════════════════════════════════════════════ */
function playBeep(freq, type, duration, vol = 0.1) {
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const osc  = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = type;
    osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
    gain.gain.setValueAtTime(vol, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + duration);
}
function playWinnerSound() {
    playBeep(440, 'triangle', 0.2, 0.2);
    setTimeout(() => playBeep(554, 'triangle', 0.2, 0.2), 150);
    setTimeout(() => playBeep(659, 'triangle', 0.8, 0.2), 300);
}

/* ═══════════════════════════════════════════════════════════
   6. ROLL ENGINE
═══════════════════════════════════════════════════════════ */
let currentPrizeId, currentBatchId;
let isRollingStarted = false;
let teaserInterval, heartbeatInterval, rollMachineSound;

function startTeaser() {
    clearInterval(teaserInterval);
    const strip = document.getElementById('rollStrip');
    teaserInterval = setInterval(() => {
        if (!isRollingStarted && Math.random() > 0.6) {
            strip.innerHTML = `<div class="roll-item" style="color:rgba(255,255,255,0.12);font-size:30px;">${TICKET_POOL[Math.floor(Math.random()*TICKET_POOL.length)]}</div>`;
            setTimeout(() => {
                if (!isRollingStarted) strip.innerHTML = '<div class="roll-item" style="color:rgba(255,255,255,0.3)">[ SIAP DIUNDI ]</div>';
            }, 80);
        }
    }, 1500);
}

function startHeartbeat() {
    clearInterval(heartbeatInterval);
    heartbeatInterval = setInterval(() => {
        if (!isRollingStarted) {
            playBeep(60, 'sine', 0.4, 0.15);
            setTimeout(() => playBeep(60, 'sine', 0.4, 0.08), 250);
        }
    }, 1200);
}

function prepareRoll(prizeId, batchId, prizeName, prizeImgUrl) {
    currentPrizeId   = prizeId;
    currentBatchId   = batchId;
    isRollingStarted = false;

    setAmbientVolume(0); // dim ambient

    document.getElementById('rollPhase').style.display  = '';
    document.getElementById('rollPhase').style.opacity  = '1';
    document.getElementById('winnerReveal').classList.remove('visible');
    document.getElementById('rollPrizeName').textContent = prizeName;
    document.getElementById('rollStatus').textContent    = 'Tekan tombol di bawah atau SPASI';

    if (prizeImgUrl) {
        document.getElementById('rollPrizeImg').src = prizeImgUrl;
        document.getElementById('rollPrizeImgWrap').style.display = 'block';
    } else {
        document.getElementById('rollPrizeImgWrap').style.display = 'none';
    }

    const strip = document.getElementById('rollStrip');
    strip.className = 'roll-strip';
    strip.style.transform = '';
    strip.innerHTML = '<div class="roll-item" style="color:rgba(255,255,255,0.3)">[ SIAP DIUNDI ]</div>';

    document.getElementById('btnStartRoll').style.display  = 'inline-flex';
    document.getElementById('btnCancelRoll').style.display = 'inline-block';

    document.getElementById('rollOverlay').removeAttribute('hidden');
    document.body.style.overflow = 'hidden';

    if (audioCtx.state === 'suspended') audioCtx.resume();
    startTeaser();
    startHeartbeat();
}

document.getElementById('btnStartRoll').addEventListener('click', function() {
    this.style.display = 'none';
    document.getElementById('btnCancelRoll').style.display = 'none';
    isRollingStarted = true;
    clearInterval(teaserInterval);
    clearInterval(heartbeatInterval);
    setAmbientVolume(0);

    document.getElementById('rollPhase').style.opacity = '0.15';

    const cdEl  = document.getElementById('giantCountdown');
    let count   = 3;

    function tickCountdown() {
        if (count > 0) {
            cdEl.textContent = count;
            cdEl.style.animation = 'none';
            void cdEl.offsetWidth;
            cdEl.style.animation = 'popIn 1s ease-out forwards';
            playBeep(400, 'square', 0.2, 0.12);
            count--;
            setTimeout(tickCountdown, 1000);
        } else {
            cdEl.textContent = 'GO!';
            cdEl.style.animation = 'none';
            void cdEl.offsetWidth;
            cdEl.style.animation = 'popIn 1s ease-out forwards';
            playBeep(800, 'square', 0.4, 0.18);
            setTimeout(() => {
                document.getElementById('rollPhase').style.opacity = '1';
                document.getElementById('rollStatus').textContent  = 'Mengumpulkan seluruh tiket peserta...';
                setTimeout(() => { document.getElementById('rollStatus').textContent = 'Memasukkan tiket ke dalam mesin acak...'; }, 1800);
                setTimeout(() => { document.getElementById('rollStatus').textContent = 'Mengacak secara acak! Siap-siap...'; }, 3800);
                setTimeout(() => { document.getElementById('rollStatus').textContent = 'Mencari kandidat pemenang...'; }, 5500);
                startActualRoll();
            }, 600);
        }
    }
    tickCountdown();
});

document.addEventListener('keydown', function(e) {
    if (e.code === 'Space' && !document.getElementById('rollOverlay').hasAttribute('hidden')) {
        const btn = document.getElementById('btnStartRoll');
        if (btn.style.display !== 'none') { e.preventDefault(); btn.click(); }
    }
});

function startActualRoll() {
    rollMachineSound = setInterval(() => {
        playBeep(150 + Math.random()*200, 'sawtooth', 0.05, 0.025);
    }, 60);

    const strip = document.getElementById('rollStrip');
    const pool  = TICKET_POOL;
    let html    = '';
    for (let i = 0; i < 30; i++) {
        html += `<div class="roll-item">${pool[Math.floor(Math.random()*pool.length)]}</div>`;
    }
    strip.innerHTML = html;
    strip.className = 'roll-strip spinning';

    const startTime = Date.now();
    const fd        = new FormData();
    fd.append('prize_id', currentPrizeId);
    fd.append('batch_id', currentBatchId);
    fd.append('_ajax',    '1');

    fetch('<?= url('/raffle/draw-winner') ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            const elapsed = Date.now() - startTime;
            const delay   = Math.max(0, 7000 - elapsed);
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

    let si = 60;
    function slowDown() {
        playBeep(200, 'sine', 0.1, 0.04);
        si *= 1.35;
        if (si < 800) setTimeout(slowDown, si);
    }
    slowDown();

    const strip     = document.getElementById('rollStrip');
    const finalCode = data.ticket_code || pool[Math.floor(Math.random()*pool.length)];
    strip.className = 'roll-strip';
    let html        = '';
    const cnt       = 40;
    for (let i = 0; i < cnt; i++) {
        html += `<div class="roll-item" style="color:rgba(255,255,255,0.6)">${pool[Math.floor(Math.random()*pool.length)]}</div>`;
    }
    html += `<div class="roll-item winner-item">${finalCode}</div>`;
    strip.innerHTML = html;
    strip.style.transform = `translateY(0px)`;
    document.getElementById('rollStatus').textContent = 'Menemukan pemenang...';
    void strip.offsetWidth;
    strip.className = 'roll-strip stopping';
    strip.style.transform = `translateY(-${cnt * 120}px)`;

    setTimeout(() => { playWinnerSound(); showWinnerCard(data); }, 4100);
}

function showWinnerCard(data) {
    document.getElementById('rollPhase').style.display  = 'none';
    document.getElementById('winnerName').textContent        = data.winner_name  || 'Pemenang';
    document.getElementById('winnerTicketCode').textContent  = data.ticket_code  || '';
    document.getElementById('winnerPhone').textContent       = data.winner_phone || '';
    document.getElementById('winnerReveal').classList.add('visible');
    startConfetti();
    // Trigger fireworks too!
    if (window.triggerFirework) {
        for (let i = 0; i < 5; i++) {
            setTimeout(() => {
                triggerFirework(
                    window.innerWidth  * (0.2 + Math.random() * 0.6),
                    window.innerHeight * (0.1 + Math.random() * 0.5)
                );
            }, i * 400);
        }
    }
}

function cancelRoll() {
    if (isRollingStarted) return;
    clearInterval(teaserInterval);
    clearInterval(heartbeatInterval);
    document.getElementById('rollOverlay').setAttribute('hidden', '');
    document.body.style.overflow = '';
    if (!isMuted) setAmbientVolume(0.018);
}

function closeRoll() {
    clearInterval(teaserInterval);
    clearInterval(heartbeatInterval);
    clearInterval(rollMachineSound);
    document.getElementById('rollOverlay').setAttribute('hidden', '');
    document.body.style.overflow = '';
    stopConfetti();
    if (!isMuted) setAmbientVolume(0.018);
    location.reload();
}

/* ═══════════════════════════════════════════════════════════
   7. CONFETTI
═══════════════════════════════════════════════════════════ */
let confettiRaf = null;
const C_COLORS  = ['#c41230','#ffc72c','#16a34a','#3b82f6','#f59e0b','#fff','#a78bfa'];

function startConfetti() {
    const canvas = document.getElementById('confettiCanvas');
    canvas.style.display = 'block';
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    const ctx = canvas.getContext('2d');

    const parts = Array.from({ length: 200 }, () => ({
        x:     Math.random() * canvas.width,
        y:     -20 - Math.random() * 150,
        vx:    (Math.random() - 0.5) * 6,
        vy:    Math.random() * 4 + 2,
        w:     Math.random() * 12 + 4,
        h:     Math.random() * 7  + 3,
        color: C_COLORS[Math.floor(Math.random() * C_COLORS.length)],
        angle: Math.random() * 360,
        spin:  (Math.random() - 0.5) * 6,
        alpha: 1,
    }));

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = 0;
        parts.forEach(p => {
            if (p.y > canvas.height + 20) { p.alpha = 0; return; }
            p.x += p.vx; p.y += p.vy; p.vy += 0.06; p.angle += p.spin;
            if (p.y > canvas.height * 0.7) p.alpha = Math.max(0, p.alpha - 0.012);
            ctx.save();
            ctx.globalAlpha = p.alpha;
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
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
    document.getElementById('confettiCanvas').style.display = 'none';
}
</script>

</body>
</html>
