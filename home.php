<?php
require_once __DIR__ . '/config.php';

// Citeste setarile landing din DB
$ls = [];
try {
    $q = db()->prepare('SELECT cheie, valoare FROM landing_settings');
    $q->execute();
    foreach ($q->fetchAll() as $row) $ls[$row['cheie']] = $row['valoare'];
} catch (Exception $e) {}

// Functie helper - returneaza valoarea din DB sau default-ul
function ls(string $key, string $default = ''): string {
    global $ls;
    return htmlspecialchars($ls[$key] ?? $default);
}
function lsRaw(string $key, string $default = ''): string {
    global $ls;
    return $ls[$key] ?? $default;
}
// Culori din DB sau default
$gold  = $ls['culoare_gold']  ?? '#c9a84c';
$navy  = $ls['culoare_navy']  ?? '#0d1117';
$cream = $ls['culoare_cream'] ?? '#f5f0e8';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino — Tehnologie pentru experiențe culinare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --navy: <?= $navy ?>;
    --navy-mid: #161b22;
    --gold: <?= $gold ?>;
    --gold-light: #e8c97a;
    --cream: <?= $cream ?>;
    --cream-dark: #e8e0d0;
    --text: #0d1117;
    --text-sub: #4a4a5a;
    --white: #ffffff;
    --radius: 2px;
}

html { scroll-behavior: smooth; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--text);
    overflow-x: hidden;
}

/* ── CURSOR CUSTOM ── */
* { cursor: none !important; }
.cursor {
    width: 8px; height: 8px;
    background: var(--gold);
    border-radius: 50%;
    position: fixed;
    top: 0; left: 0;
    pointer-events: none;
    z-index: 9999;
    transition: transform 0.1s ease;
    mix-blend-mode: multiply;
}
.cursor-ring {
    width: 36px; height: 36px;
    border: 1px solid var(--gold);
    border-radius: 50%;
    position: fixed;
    top: 0; left: 0;
    pointer-events: none;
    z-index: 9998;
    transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), width 0.3s, height 0.3s, opacity 0.3s;
    opacity: 0.6;
}

/* ── NAVBAR ── */
nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    padding: 24px 60px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.4s, padding 0.4s;
}
nav.scrolled {
    background: rgba(245,240,232,0.95);
    backdrop-filter: blur(12px);
    padding: 16px 60px;
    border-bottom: 1px solid rgba(201,168,76,0.2);
}
.nav-logo {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    letter-spacing: 0.08em;
    color: var(--navy);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}
.nav-logo img { height: 56px; width: auto; }
.nav-links { display: flex; gap: 40px; align-items: center; }
.nav-links a {
    font-size: 13px;
    font-weight: 400;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-sub);
    text-decoration: none;
    transition: color 0.2s;
}
.nav-links a:hover { color: var(--gold); }
.lang-toggle {
    display: flex;
    gap: 4px;
    background: rgba(201,168,76,0.1);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 20px;
    padding: 4px;
}
.lang-toggle button {
    background: none;
    border: none;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.1em;
    color: var(--text-sub);
    padding: 4px 10px;
    border-radius: 16px;
    cursor: pointer !important;
    transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
}
.lang-toggle button.activ {
    background: var(--gold);
    color: var(--white);
}
.nav-mobile-controls { display: none; align-items: center; gap: 10px; }
.nav-hamburger { background: var(--gold); border: none; color: var(--navy); padding: 8px 14px; border-radius: 8px; font-size: 18px; cursor: pointer !important; font-weight: 700; }
.mobile-menu { display: none; position: fixed; top: 0; left: 0; right: 0; background: var(--navy); padding: 80px 24px 32px; z-index: 99; flex-direction: column; gap: 4px; border-bottom: 1px solid rgba(201,168,76,0.2); }
.mobile-menu.open { display: flex; }
.mobile-menu a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 14px 16px; font-size: 16px; font-weight: 400; border-radius: 8px; letter-spacing: 0.05em; transition: background 0.2s; }
.mobile-menu a:hover { background: rgba(201,168,76,0.1); color: var(--gold-light); }
.mobile-menu-btn { background: var(--gold) !important; color: var(--navy) !important; font-weight: 600 !important; text-align: center; margin-top: 8px; }

@media (max-width: 600px) {
    .nav-links { display: none !important; }
    .nav-mobile-controls { display: flex; }
}
    padding: 10px 24px !important;
    border-radius: 2px;
    font-size: 12px !important;
    font-weight: 500 !important;
    letter-spacing: 0.12em !important;
    text-transform: uppercase !important;
    transition: background 0.2s !important;
}
.btn-nav:hover { background: var(--gold) !important; color: var(--navy) !important; }

/* ── HERO ── */
.hero {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    position: relative;
    overflow: hidden;
}
.hero-left {
    background: var(--navy);
    background-image: url('/uploads/landing-hero-bg.jpg');
    background-size: cover;
    background-position: center;
    padding: 160px 80px 80px 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.hero-price { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; opacity: 0; animation: fadeUp 0.8s ease 0.7s forwards; }
.price-badge { font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold); border: 1px solid rgba(201,168,76,0.4); padding: 5px 14px; border-radius: 20px; font-weight: 400; }
.price-amount { font-family: 'Cormorant Garamond', serif; font-size: 48px; font-weight: 300; color: var(--white); line-height: 1; }
.price-per { font-size: 16px; color: rgba(255,255,255,0.45); font-family: 'DM Sans', sans-serif; font-weight: 300; }
.hero-left::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(13,17,23,0.93) 0%, rgba(13,17,23,0.78) 60%, rgba(13,17,23,0.60) 100%);
    pointer-events: none;
    z-index: 0;
}
.hero-left > * { position: relative; z-index: 1; }
.hero-eyebrow {
    font-size: 11px;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 28px;
    opacity: 0;
    animation: fadeUp 0.8s ease 0.2s forwards;
    font-weight: 400;
}
.hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(42px, 5vw, 68px);
    font-weight: 300;
    line-height: 1.1;
    color: var(--white);
    margin-bottom: 32px;
    opacity: 0;
    animation: fadeUp 0.8s ease 0.4s forwards;
}
.hero-title em {
    font-style: italic;
    color: var(--gold-light);
}
.hero-desc {
    font-size: 16px;
    line-height: 1.7;
    color: rgba(255,255,255,0.6);
    max-width: 420px;
    margin-bottom: 48px;
    font-weight: 300;
    opacity: 0;
    animation: fadeUp 0.8s ease 0.6s forwards;
}
.hero-actions {
    display: flex;
    gap: 16px;
    align-items: center;
    opacity: 0;
    animation: fadeUp 0.8s ease 0.8s forwards;
}
.btn-primary {
    background: var(--gold);
    color: var(--navy);
    padding: 14px 36px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: var(--radius);
    transition: all 0.3s;
    display: inline-block;
    font-family: 'DM Sans', sans-serif;
}
.btn-primary:hover {
    background: var(--gold-light);
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(201,168,76,0.3);
}
.btn-secondary {
    color: rgba(255,255,255,0.7);
    font-size: 12px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 400;
    transition: color 0.2s;
    font-family: 'DM Sans', sans-serif;
}
.btn-secondary:hover { color: var(--gold-light); }
.btn-secondary::after { content: '→'; transition: transform 0.2s; }
.btn-secondary:hover::after { transform: translateX(4px); }

.hero-right {
    background: var(--cream);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hero-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9a84c' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-mockup {
    position: relative;
    width: 320px;
    opacity: 0;
    animation: fadeIn 1.2s ease 1s forwards;
}
.mockup-tablet {
    background: var(--navy);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 40px 120px rgba(13,17,23,0.25), 0 0 0 1px rgba(201,168,76,0.2);
}
.mockup-screen {
    background: #161b22;
    border-radius: 12px;
    overflow: hidden;
}
.mockup-header {
    background: #1a2535;
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.mockup-logo { font-size: 10px; color: var(--gold-light); font-family: 'Cormorant Garamond', serif; }
.mockup-btns { display: flex; gap: 6px; }
.mockup-btn { background: rgba(201,168,76,0.2); border: 1px solid rgba(201,168,76,0.3); border-radius: 10px; padding: 3px 8px; font-size: 7px; color: var(--gold-light); font-family: 'DM Sans', sans-serif; }
.mockup-ticker { background: #c9a84c; padding: 3px 14px; font-size: 7px; color: #0d1117; font-weight: 500; }
.mockup-body { padding: 12px; }
.mockup-status { background: #d1fae5; border-radius: 6px; padding: 6px 10px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.mockup-status span { font-size: 8px; color: #065f46; font-weight: 500; }
.mockup-item { background: rgba(255,255,255,0.05); border-radius: 6px; padding: 6px 10px; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.mockup-item span { font-size: 7px; color: rgba(255,255,255,0.7); }
.mockup-item .price { color: var(--gold-light); font-weight: 500; }
.mockup-total { padding: 8px 10px; display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 6px; }
.mockup-total span { font-size: 8px; color: rgba(255,255,255,0.5); }
.mockup-total strong { font-size: 10px; color: var(--gold-light); }
.mockup-cta { background: linear-gradient(135deg, #1a1a2e, #2d1f6e); border-radius: 8px; padding: 8px; margin: 8px 12px 12px; text-align: center; font-size: 8px; color: #fff; font-weight: 500; }

/* Floating badge */
.float-badge {
    position: absolute;
    background: var(--white);
    border-radius: 12px;
    padding: 10px 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    display: flex;
    align-items: center;
    gap: 8px;
    animation: float 3s ease-in-out infinite;
}
.float-badge-1 { bottom: 80px; left: -30px; animation-delay: 0s; }
.float-badge-2 { top: 100px; right: -20px; animation-delay: 1.5s; }
.float-badge .badge-icon { font-size: 20px; }
.float-badge .badge-text { font-size: 11px; }
.float-badge .badge-text strong { display: block; color: var(--navy); font-weight: 500; }
.float-badge .badge-text span { color: var(--text-sub); font-size: 10px; }

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* ── DEMO WRAPPER ── */
.hero-right {
    background: var(--cream);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 120px 40px 80px;
}
.hero-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9a84c' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.demo-wrapper {
    position: relative;
    width: 100%;
    max-width: 820px;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.demo-tablet {
    position: relative;
    z-index: 10;
    animation: fadeIn 1s ease 0.8s both;
    width: 420px;
}
.demo-tablet .mockup-tablet {
    box-shadow: 0 40px 100px rgba(13,17,23,0.2), 0 0 0 1px rgba(201,168,76,0.2);
    width: 100%;
    padding: 28px;
    border-radius: 28px;
}
.mockup-screen { border-radius: 16px; }
.mockup-header {
    background: #1a2535;
    padding: 18px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.mockup-logo { font-size: 15px; color: var(--gold-light); font-family: 'Cormorant Garamond', serif; }
.mockup-btns { display: flex; gap: 8px; }
.mockup-btn { background: rgba(201,168,76,0.2); border: 1px solid rgba(201,168,76,0.3); border-radius: 12px; padding: 6px 14px; font-size: 12px; color: var(--gold-light); font-family: 'DM Sans', sans-serif; }
.mockup-ticker { background: #c9a84c; padding: 8px 22px; font-size: 12px; color: #0d1117; font-weight: 600; }
.mockup-body { padding: 20px; }
.mockup-status { background: #d1fae5; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.mockup-status span { font-size: 13px; color: #065f46; font-weight: 500; }
.mockup-item { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
.mockup-item span { font-size: 13px; color: rgba(255,255,255,0.75); }
.mockup-item .price { color: var(--gold-light); font-weight: 600; font-size: 14px; }
.mockup-total { padding: 14px 16px; display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 10px; }
.mockup-total span { font-size: 13px; color: rgba(255,255,255,0.5); }
.mockup-total strong { font-size: 18px; color: var(--gold-light); }
.mockup-cta { background: linear-gradient(135deg, #1a1a2e, #2d1f6e); border-radius: 12px; padding: 16px; margin: 14px 0 6px; text-align: center; font-size: 14px; color: #fff; font-weight: 600; letter-spacing: 0.05em; }

/* Callouts */
.callout {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: fadeIn 0.8s ease both;
    z-index: 5;
}
.callout-box {
    background: var(--white);
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 10px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    white-space: nowrap;
}
.callout-icon { font-size: 18px; flex-shrink: 0; }
.callout-box strong { display: block; font-size: 12px; font-weight: 500; color: var(--navy); }
.callout-box span { font-size: 10px; color: var(--text-sub); font-weight: 300; }
.callout-arrow { flex-shrink: 0; }
.callout-tl { top: 6%; left: 0; flex-direction: column; align-items: flex-start; animation-delay: 1.2s; }
.callout-tl .callout-arrow { width: 80px; height: 50px; align-self: flex-end; margin-right: 20px; }
.callout-tr { top: 6%; right: 0; flex-direction: column; align-items: flex-end; animation-delay: 1.4s; }
.callout-tr .callout-arrow { width: 80px; height: 50px; align-self: flex-start; margin-left: 20px; }
.callout-ml { left: -20px; top: 50%; transform: translateY(-80px); animation-delay: 1.6s; }
.callout-ml .callout-arrow { width: 60px; height: 20px; }
.callout-mr { right: -20px; top: 50%; transform: translateY(-80px); animation-delay: 1.8s; }
.callout-mr .callout-arrow { width: 60px; height: 20px; }
.callout-bl { bottom: 12%; left: 0; flex-direction: column; align-items: flex-start; animation-delay: 2s; }
.callout-bl .callout-arrow { width: 80px; height: 50px; align-self: flex-end; margin-right: 20px; }
.callout-br { bottom: 12%; right: 0; flex-direction: column; align-items: flex-end; animation-delay: 2.2s; }
.callout-br .callout-arrow { width: 80px; height: 50px; align-self: flex-start; margin-left: 20px; }

/* Preț jos */
.demo-price {
    position: absolute;
    bottom: -60px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--navy);
    padding: 16px 36px;
    border-radius: 40px;
    box-shadow: 0 12px 48px rgba(13,17,23,0.25), 0 0 0 1px rgba(201,168,76,0.3);
    white-space: nowrap;
    animation: fadeIn 0.8s ease 2.4s both;
    border: 1px solid rgba(201,168,76,0.2);
}
.demo-price-badge {
    font-size: 10px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
    font-weight: 400;
    border-right: 1px solid rgba(255,255,255,0.1);
    padding-right: 16px;
}
.demo-price-amount {
    font-family: 'Cormorant Garamond', serif;
    font-size: 42px;
    font-weight: 300;
    color: var(--gold-light);
    line-height: 1;
}
.demo-price-amount span {
    font-size: 16px;
    color: rgba(255,255,255,0.4);
    font-family: 'DM Sans', sans-serif;
    font-weight: 300;
    margin-left: 3px;
}
.stats-bar {
    background: var(--navy);
    padding: 32px 80px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
    border-top: 1px solid rgba(201,168,76,0.2);
}
.stat-item { text-align: center; }
.stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 42px;
    font-weight: 300;
    color: var(--gold-light);
    line-height: 1;
    margin-bottom: 6px;
}
.stat-lbl {
    font-size: 11px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.4);
    font-weight: 300;
}

/* ── FEATURES ── */
.features {
    padding: 120px 80px;
    max-width: 1400px;
    margin: 0 auto;
}
.section-header {
    text-align: center;
    margin-bottom: 80px;
}
.section-eyebrow {
    font-size: 11px;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 16px;
    font-weight: 400;
}
.section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 4vw, 52px);
    font-weight: 300;
    line-height: 1.2;
    color: var(--navy);
}
.section-title em { font-style: italic; color: var(--gold); }
.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
    background: rgba(201,168,76,0.1);
    border: 1px solid rgba(201,168,76,0.15);
}
.feature-card {
    background: var(--cream);
    padding: 48px 40px;
    transition: background 0.3s;
    position: relative;
    overflow: hidden;
}
.feature-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: var(--gold);
    transform: scaleX(0);
    transition: transform 0.3s;
    transform-origin: left;
}
.feature-card:hover { background: var(--white); }
.feature-card:hover::after { transform: scaleX(1); }
.feature-icon {
    font-size: 32px;
    margin-bottom: 24px;
    display: block;
}
.feature-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 400;
    color: var(--navy);
    margin-bottom: 12px;
}
.feature-desc {
    font-size: 14px;
    line-height: 1.7;
    color: var(--text-sub);
    font-weight: 300;
}

/* ── GALERIE ── */
.galerie {
    padding: 0;
    width: 100%;
}
.galerie-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 1fr;
    gap: 3px;
    height: 500px;
}
.galerie-item {
    position: relative;
    overflow: hidden;
}
.galerie-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
    display: block;
}
.galerie-item:hover img {
    transform: scale(1.04);
}
.galerie-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(13,17,23,0.75) 0%, transparent 55%);
    display: flex;
    align-items: flex-end;
    padding: 28px;
    opacity: 0;
    transition: opacity 0.3s;
}
.galerie-item:hover .galerie-overlay {
    opacity: 1;
}
.galerie-overlay span {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 300;
    font-style: italic;
    color: var(--white);
    letter-spacing: 0.05em;
}

@media (max-width: 1024px) {
    .galerie-grid { height: auto; grid-template-columns: 1fr; grid-template-rows: 260px 260px 260px; }
}
.how {
    background: var(--navy);
    padding: 120px 80px;
    position: relative;
    overflow: hidden;
}
.how::before {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 800px; height: 800px;
    background: radial-gradient(circle, rgba(201,168,76,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.how .section-title { color: var(--white); }
.how-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
    margin-top: 64px;
    position: relative;
}
.how-grid::before {
    content: '';
    position: absolute;
    top: 28px; left: 60px; right: 60px;
    height: 1px;
    background: linear-gradient(to right, transparent, rgba(201,168,76,0.3), rgba(201,168,76,0.3), transparent);
}
.how-step { text-align: center; position: relative; }
.step-num {
    width: 56px; height: 56px;
    border: 1px solid rgba(201,168,76,0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    background: var(--navy);
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    color: var(--gold-light);
    position: relative;
    z-index: 1;
}
.step-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    color: var(--white);
    margin-bottom: 10px;
    font-weight: 400;
}
.step-desc {
    font-size: 13px;
    color: rgba(255,255,255,0.45);
    line-height: 1.6;
    font-weight: 300;
}

/* ── TESTIMONIAL ── */
.testimonial {
    padding: 120px 80px;
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
}
.quote-mark {
    font-family: 'Cormorant Garamond', serif;
    font-size: 120px;
    line-height: 0.6;
    color: var(--gold);
    opacity: 0.3;
    margin-bottom: 20px;
    display: block;
}
.quote-text {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(22px, 3vw, 34px);
    font-weight: 300;
    font-style: italic;
    line-height: 1.5;
    color: var(--navy);
    margin-bottom: 40px;
}
.quote-author {
    font-size: 12px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--text-sub);
}
.quote-author strong {
    color: var(--gold);
    display: block;
    font-size: 14px;
    letter-spacing: 0.1em;
    margin-bottom: 4px;
}

/* ── CTA ── */
.cta-section {
    background: var(--navy);
    padding: 120px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.cta-section::before {
    content: 'TABLERINO';
    position: absolute;
    font-family: 'Cormorant Garamond', serif;
    font-size: 200px;
    font-weight: 300;
    color: rgba(201,168,76,0.03);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: 0.1em;
}
.cta-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(36px, 5vw, 64px);
    font-weight: 300;
    color: var(--white);
    margin-bottom: 20px;
    line-height: 1.1;
}
.cta-title em { font-style: italic; color: var(--gold-light); }
.cta-desc {
    font-size: 16px;
    color: rgba(255,255,255,0.5);
    margin-bottom: 48px;
    font-weight: 300;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}
.cta-btns { display: flex; gap: 16px; justify-content: center; align-items: center; flex-wrap: wrap; }
.btn-gold {
    background: var(--gold);
    color: var(--navy);
    padding: 16px 48px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: var(--radius);
    transition: all 0.3s;
    font-family: 'DM Sans', sans-serif;
    display: inline-block;
}
.btn-gold:hover {
    background: var(--gold-light);
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(201,168,76,0.25);
}
.btn-outline {
    border: 1px solid rgba(201,168,76,0.4);
    color: rgba(255,255,255,0.7);
    padding: 16px 48px;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: var(--radius);
    transition: all 0.3s;
    font-family: 'DM Sans', sans-serif;
    display: inline-block;
}
.btn-outline:hover { border-color: var(--gold); color: var(--gold-light); }

/* ── FOOTER ── */
footer {
    background: #080d12;
    padding: 60px 80px 32px;
    border-top: 1px solid rgba(201,168,76,0.1);
}
.footer-top {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 60px;
    margin-bottom: 48px;
}
.footer-brand {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 300;
    color: var(--gold-light);
    margin-bottom: 16px;
    letter-spacing: 0.05em;
}
.footer-tagline {
    font-size: 13px;
    color: rgba(255,255,255,0.3);
    font-weight: 300;
    line-height: 1.6;
}
.footer-col h4 {
    font-size: 10px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 20px;
    font-weight: 400;
}
.footer-col a {
    display: block;
    font-size: 13px;
    color: rgba(255,255,255,0.4);
    text-decoration: none;
    margin-bottom: 10px;
    transition: color 0.2s;
    font-weight: 300;
}
.footer-col a:hover { color: var(--gold-light); }
.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.06);
    padding-top: 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.footer-copy {
    font-size: 12px;
    color: rgba(255,255,255,0.2);
    font-weight: 300;
}
.footer-line {
    width: 40px;
    height: 1px;
    background: var(--gold);
    opacity: 0.4;
}

/* ── ÎNREGISTRARE ── */
.inreg-section { background: var(--cream); padding: 100px 80px; border-top: 1px solid rgba(201,168,76,0.15); }
.inreg-wrap { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
.inreg-title { font-family: 'Cormorant Garamond', serif; font-size: clamp(32px,4vw,48px); font-weight: 300; line-height: 1.15; color: var(--navy); margin-bottom: 16px; }
.inreg-title em { font-style: italic; color: var(--gold); }
.inreg-desc { font-size: 15px; color: var(--text-sub); line-height: 1.7; font-weight: 300; }
.inreg-form-wrap { background: var(--white); border-radius: 16px; padding: 36px; box-shadow: 0 8px 40px rgba(13,17,23,0.08); border: 1px solid rgba(201,168,76,0.15); }
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-sub); margin-bottom: 6px; }
.field-group input { width: 100%; padding: 12px 16px; border: 1px solid rgba(13,17,23,0.12); border-radius: var(--radius); font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color 0.2s; background: var(--cream); color: var(--text); }
.field-group input:focus { border-color: var(--gold); background: var(--white); }
.inreg-btn { width: 100%; padding: 14px; background: var(--navy); color: var(--white); border: none; border-radius: var(--radius); font-size: 12px; font-weight: 500; letter-spacing: 0.15em; text-transform: uppercase; cursor: pointer; margin-top: 8px; transition: all 0.3s; font-family: 'DM Sans', sans-serif; }
.inreg-btn:hover { background: var(--gold); color: var(--navy); }
.inreg-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.inreg-note { font-size: 11px; color: var(--text-sub); text-align: center; margin-top: 12px; opacity: 0.7; }
.inreg-success { text-align: center; padding: 48px 24px; }
.inreg-success .success-icon { font-size: 48px; margin-bottom: 16px; }
.inreg-success h3 { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 300; color: var(--navy); margin-bottom: 10px; }
.inreg-success p { color: var(--text-sub); font-size: 14px; }
@media (max-width: 1024px) {
    .inreg-section { padding: 60px 32px; }
    .inreg-wrap { grid-template-columns: 1fr; gap: 40px; }
}

/* ── FOOTER SOCIAL ── */
.footer-social { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
.footer-social-btn { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s, opacity 0.2s; opacity: 0.7; color: rgba(255,255,255,0.6); }
.footer-social-btn:hover { opacity: 1; transform: translateY(-2px); }
.footer-social-btn svg { width: 18px; height: 18px; }
.footer-social-btn.facebook:hover { color: #1877f2; }
.footer-social-btn.instagram:hover { color: #e1306c; }
.footer-social-btn.tiktok:hover { color: #fff; }
.footer-social-btn.whatsapp:hover { color: #25d366; }
.footer-social-btn.youtube:hover { color: #ff0000; }
.reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.reveal.visible { opacity: 1; transform: translateY(0); }

/* ── ANIMATIONS ── */
@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
    from { opacity: 0; transform: translateY(20px); }
}
@keyframes fadeIn { to { opacity: 1; } from { opacity: 0; } }

/* ── RESPONSIVE TABLET (max 1024px) ── */
@media (max-width: 1024px) {
    nav { padding: 20px 32px; }
    nav.scrolled { padding: 14px 32px; }
    .nav-links { display: none; }
    .hero { grid-template-columns: 1fr; }
    .hero-left { padding: 100px 32px 60px; }
    .hero-right { min-height: auto; padding: 60px 0; }
    .pricing-panel { padding: 0 32px; }
    .stats-bar { grid-template-columns: repeat(2, 1fr); padding: 40px; }
    .features { padding: 80px 32px; }
    .features-grid { grid-template-columns: 1fr; }
    .how { padding: 80px 32px; }
    .how-grid { grid-template-columns: repeat(2, 1fr); }
    .how-grid::before { display: none; }
    .testimonial { padding: 80px 32px; }
    .cta-section { padding: 80px 32px; }
    footer { padding: 48px 32px 24px; }
    .footer-top { grid-template-columns: 1fr; gap: 32px; }
    .footer-bottom { flex-direction: column; gap: 16px; }
    * { cursor: auto !important; }
    .cursor, .cursor-ring { display: none; }
    /* Demo wrapper */
    .demo-wrapper { max-width: 500px; aspect-ratio: auto; padding: 40px 20px 80px; }
    .demo-tablet { width: 320px; }
    .callout { display: none; }
    .demo-price { bottom: 10px; }
}

/* ── RESPONSIVE MOBILE (max 600px) ── */
@media (max-width: 600px) {
    /* Navbar */
    nav { padding: 16px 20px; }
    nav.scrolled { padding: 12px 20px; }
    .nav-logo { font-size: 18px; }
    .nav-logo img { height: 40px; }

    /* Hero */
    .hero { grid-template-columns: 1fr; min-height: auto; }
    .hero-left { padding: 90px 24px 48px; }
    .hero-eyebrow { font-size: 10px; margin-bottom: 16px; }
    .hero-title { font-size: 36px; margin-bottom: 20px; }
    .hero-desc { font-size: 14px; margin-bottom: 28px; }
    .hero-actions { flex-direction: column; gap: 12px; }
    .hero-actions .btn-primary,
    .hero-actions .btn-secondary { text-align: center; width: 100%; }
    .hero-right { display: none; }

    /* Stats */
    .stats-bar { grid-template-columns: repeat(2, 1fr); padding: 28px 20px; gap: 16px; }
    .stat-num { font-size: 32px; }
    .stat-lbl { font-size: 10px; }

    /* Features */
    .features { padding: 60px 20px; }
    .section-title { font-size: 28px; }
    .features-grid { grid-template-columns: 1fr; gap: 2px; }
    .feature-card { padding: 28px 24px; }
    .feature-icon { font-size: 24px; margin-bottom: 14px; }
    .feature-title { font-size: 17px; }
    .feature-desc { font-size: 13px; }

    /* Galerie */
    .galerie-grid { grid-template-columns: 1fr; grid-template-rows: 220px 220px 220px; }

    /* How it works */
    .how { padding: 60px 20px; }
    .how-grid { grid-template-columns: 1fr; gap: 32px; }
    .step-num { width: 44px; height: 44px; font-size: 18px; }
    .step-title { font-size: 16px; }
    .step-desc { font-size: 13px; }

    /* Testimonial */
    .testimonial { padding: 60px 20px; }
    .quote-mark { font-size: 80px; }
    .quote-text { font-size: 20px; }

    /* CTA */
    .cta-section { padding: 60px 20px; }
    .cta-title { font-size: 28px; }
    .cta-desc { font-size: 14px; }
    .cta-btns { flex-direction: column; align-items: center; gap: 12px; width: 100%; }
    .btn-gold, .btn-outline { width: 100%; max-width: 320px; text-align: center; padding: 16px 24px; }

    /* Înregistrare */
    .inreg-section { padding: 60px 20px; }
    .inreg-wrap { grid-template-columns: 1fr; gap: 32px; }
    .inreg-title { font-size: 28px; }
    .inreg-form-wrap { padding: 24px 20px; }
    .inreg-btn { padding: 14px; font-size: 13px; }

    /* Footer */
    footer { padding: 48px 20px 24px; }
    .footer-top { grid-template-columns: 1fr; gap: 28px; }
    .footer-brand { font-size: 20px; }
    .footer-tagline { font-size: 13px; }
    .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
    .footer-line { display: none; }
}
</style>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- NAV -->
<nav id="navbar">
    <a href="#" class="nav-logo">
        <img src="/uploads/logo-tablerino.png" alt="Tablerino" onerror="this.style.display='none'">
        Tablerino
    </a>
    <div class="nav-links">
        <a href="#features" data-ro="Funcționalități" data-en="Features">Funcționalități</a>
        <a href="#how" data-ro="Cum funcționează" data-en="How it works">Cum funcționează</a>
        <a href="#inregistrare" data-ro="Înregistrare" data-en="Register">Înregistrare</a>
        <a href="#contact" data-ro="Contact" data-en="Contact">Contact</a>
        <div class="lang-toggle">
            <button onclick="setLang('ro')" id="btnRo" class="activ">RO</button>
            <button onclick="setLang('en')" id="btnEn">EN</button>
        </div>
        <a href="/admin/login.php" class="btn-nav" data-ro="Autentificare" data-en="Login">Autentificare</a>
    </div>
    <!-- Mobile controls -->
    <div class="nav-mobile-controls">
        <div class="lang-toggle">
            <button onclick="setLang('ro')" id="btnRoM" class="activ">RO</button>
            <button onclick="setLang('en')" id="btnEnM">EN</button>
        </div>
        <button class="nav-hamburger" onclick="toggleMobileMenu()" id="hamburger">☰</button>
    </div>
</nav>

<!-- Mobile menu -->
<div class="mobile-menu" id="mobileMenu">
    <a href="#features" onclick="toggleMobileMenu()" data-ro="Funcționalități" data-en="Features">Funcționalități</a>
    <a href="#how" onclick="toggleMobileMenu()" data-ro="Cum funcționează" data-en="How it works">Cum funcționează</a>
    <a href="#inregistrare" onclick="toggleMobileMenu()" data-ro="Înregistrare" data-en="Register">Înregistrare</a>
    <a href="#contact" onclick="toggleMobileMenu()" data-ro="Contact" data-en="Contact">Contact</a>
    <a href="/admin/login.php" class="mobile-menu-btn" data-ro="Autentificare" data-en="Login">Autentificare</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-left">
        <p class="hero-eyebrow" data-ro="<?= ls('hero_eyebrow_ro','Soluția digitală pentru restaurante') ?>" data-en="<?= ls('hero_eyebrow_en','The digital solution for restaurants') ?>"><?= ls('hero_eyebrow_ro','Soluția digitală pentru restaurante') ?></p>
        <h1 class="hero-title">
            <span data-ro="<?= ls('hero_title_ro','Comenzi la masă,<br><em>reimaginate.</em>') ?>" data-en="<?= ls('hero_title_en','Table ordering,<br><em>reimagined.</em>') ?>"><?= lsRaw('hero_title_ro','Comenzi la masă,<br><em>reimaginate.</em>') ?></span>
        </h1>
        <p class="hero-desc" data-ro="<?= ls('hero_desc_ro') ?>" data-en="<?= ls('hero_desc_en') ?>">
            <?= ls('hero_desc_ro','Tablerino transformă experiența de comandă în restaurantul tău.') ?>
        </p>
        <div class="hero-actions">
            <a href="#inregistrare" class="btn-primary" data-ro="Înregistrează-ți restaurantul" data-en="Register your restaurant">Înregistrează-ți restaurantul</a>
            <a href="#how" class="btn-secondary" data-ro="<?= ls('hero_btn2_ro','Cum funcționează') ?>" data-en="<?= ls('hero_btn2_en','How it works') ?>"><?= ls('hero_btn2_ro','Cum funcționează') ?></a>
        </div>
    </div>
    <div class="hero-right">
        <div class="demo-wrapper">

            <!-- Tableta centrală -->
            <div class="demo-tablet">
                <div class="mockup-tablet">
                    <div class="mockup-screen">
                        <div class="mockup-header">
                            <span class="mockup-logo">🍽 Afacerea Ta</span>
                            <div class="mockup-btns">
                                <span class="mockup-btn">📋 Comanda ta</span>
                                <span class="mockup-btn">🍕 Meniu</span>
                            </div>
                        </div>
                        <div class="mockup-ticker">Bine ai venit! Profită de oferta 2+1 GRATIS →</div>
                        <div class="mockup-body">
                            <div class="mockup-status">
                                <span>✅ Toate servite! Poți solicita nota.</span>
                            </div>
                            <div class="mockup-item">
                                <span>2× Souvlaki Pui cu Bacon</span>
                                <span class="price">90.00 lei</span>
                            </div>
                            <div class="mockup-item">
                                <span>1× Tzatziki</span>
                                <span class="price">18.00 lei</span>
                            </div>
                            <div class="mockup-item">
                                <span>2× Limonadă</span>
                                <span class="price">24.00 lei</span>
                            </div>
                            <div class="mockup-total">
                                <span>Total</span>
                                <strong>132.00 lei</strong>
                            </div>
                            <div class="mockup-cta">🧾 Solicită nota</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Săgeți + callout-uri -->
            <div class="callout callout-tl">
                <div class="callout-box">
                    <span class="callout-icon">📱</span>
                    <div>
                        <strong data-ro="QR / Tabletă / Telefon" data-en="QR / Tablet / Phone">QR / Tabletă / Telefon</strong>
                        <span data-ro="Fără aplicație instalată" data-en="No app needed">Fără aplicație instalată</span>
                    </div>
                </div>
                <svg class="callout-arrow arrow-tl" viewBox="0 0 80 50" fill="none">
                    <path d="M4 4 C20 4, 60 4, 76 46" stroke="#c9a84c" stroke-width="1.5" stroke-dasharray="4 3" fill="none"/>
                    <polygon points="70,44 76,46 72,38" fill="#c9a84c"/>
                </svg>
            </div>

            <div class="callout callout-tr">
                <svg class="callout-arrow arrow-tr" viewBox="0 0 80 50" fill="none">
                    <path d="M76 4 C60 4, 20 4, 4 46" stroke="#c9a84c" stroke-width="1.5" stroke-dasharray="4 3" fill="none"/>
                    <polygon points="10,44 4,46 8,38" fill="#c9a84c"/>
                </svg>
                <div class="callout-box">
                    <span class="callout-icon">⚡</span>
                    <div>
                        <strong data-ro="Comenzi în timp real" data-en="Real-time orders">Comenzi în timp real</strong>
                        <span data-ro="Refresh la 5 secunde" data-en="Refresh every 5 seconds">Refresh la 5 secunde</span>
                    </div>
                </div>
            </div>

            <div class="callout callout-ml">
                <div class="callout-box">
                    <span class="callout-icon">📢</span>
                    <div>
                        <strong data-ro="Reclame & Promoții" data-en="Ads & Promotions">Reclame & Promoții</strong>
                        <span data-ro="Ticker animat cu ofertele tale" data-en="Animated ticker with your offers">Ticker animat cu ofertele tale</span>
                    </div>
                </div>
                <svg class="callout-arrow arrow-ml" viewBox="0 0 60 20" fill="none">
                    <path d="M4 10 L56 10" stroke="#c9a84c" stroke-width="1.5" stroke-dasharray="4 3"/>
                    <polygon points="50,6 56,10 50,14" fill="#c9a84c"/>
                </svg>
            </div>

            <div class="callout callout-mr">
                <svg class="callout-arrow arrow-mr" viewBox="0 0 60 20" fill="none">
                    <path d="M56 10 L4 10" stroke="#c9a84c" stroke-width="1.5" stroke-dasharray="4 3"/>
                    <polygon points="10,6 4,10 10,14" fill="#c9a84c"/>
                </svg>
                <div class="callout-box">
                    <span class="callout-icon">📊</span>
                    <div>
                        <strong data-ro="Rapoarte zilnice" data-en="Daily reports">Rapoarte zilnice</strong>
                        <span data-ro="Export PDF cu un click" data-en="PDF export in one click">Export PDF cu un click</span>
                    </div>
                </div>
            </div>

            <div class="callout callout-bl">
                <div class="callout-box">
                    <span class="callout-icon">🎨</span>
                    <div>
                        <strong data-ro="Design personalizat" data-en="Custom design">Design personalizat</strong>
                        <span data-ro="Logo, temă, fundal propriu" data-en="Logo, theme, custom background">Logo, temă, fundal propriu</span>
                    </div>
                </div>
                <svg class="callout-arrow arrow-bl" viewBox="0 0 80 50" fill="none">
                    <path d="M4 46 C20 46, 60 46, 76 4" stroke="#c9a84c" stroke-width="1.5" stroke-dasharray="4 3" fill="none"/>
                    <polygon points="70,6 76,4 72,12" fill="#c9a84c"/>
                </svg>
            </div>

            <div class="callout callout-br">
                <svg class="callout-arrow arrow-br" viewBox="0 0 80 50" fill="none">
                    <path d="M76 46 C60 46, 20 46, 4 4" stroke="#c9a84c" stroke-width="1.5" stroke-dasharray="4 3" fill="none"/>
                    <polygon points="10,6 4,4 8,12" fill="#c9a84c"/>
                </svg>
                <div class="callout-box">
                    <span class="callout-icon">💳</span>
                    <div>
                        <strong data-ro="Cash sau Card" data-en="Cash or Card">Cash sau Card</strong>
                        <span data-ro="Clientul alege la masă" data-en="Customer chooses at the table">Clientul alege la masă</span>
                    </div>
                </div>
            </div>

            <!-- Preț jos -->
            <div class="demo-price">
                <span class="demo-price-badge" data-ro="Abonament lunar" data-en="Monthly subscription">Abonament lunar</span>
                <span class="demo-price-amount">€100<span data-ro="/lună" data-en="/month">/lună</span></span>
            </div>

        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <?php for ($i = 1; $i <= 4; $i++): $nums = ['∞','5s','2','PWA']; $lro = ['Mese configurabile','Timp de refresh comenzi','Limbi disponibile','Funcționează ca aplicație']; $len = ['Configurable tables','Order refresh time','Available languages','Works as an app']; ?>
    <div class="stat-item">
        <div class="stat-num"><?= ls("stat{$i}_num", $nums[$i-1]) ?></div>
        <div class="stat-lbl" data-ro="<?= ls("stat{$i}_ro", $lro[$i-1]) ?>" data-en="<?= ls("stat{$i}_en", $len[$i-1]) ?>"><?= ls("stat{$i}_ro", $lro[$i-1]) ?></div>
    </div>
    <?php endfor; ?>
</div>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="section-header reveal">
        <p class="section-eyebrow" data-ro="<?= ls('features_eyebrow_ro','Tot ce ai nevoie') ?>" data-en="<?= ls('features_eyebrow_en','Everything you need') ?>"><?= ls('features_eyebrow_ro','Tot ce ai nevoie') ?></p>
        <h2 class="section-title">
            <span data-ro="<?= ls('features_title_ro') ?>" data-en="<?= ls('features_title_en') ?>"><?= lsRaw('features_title_ro','Platformă completă,<br><em>gândită pentru restaurante</em>') ?></span>
        </h2>
    </div>
    <?php
    $fIcons = ['📱','⚡','📊','🎨','📢','🌍'];
    $fTitRo = ['Comandă de pe orice dispozitiv','Dashboard în timp real','Rapoarte zilnice','Design personalizabil','Reclame & Promoții','Bilingv RO / EN'];
    $fTitEn = ['Order from any device','Real-time dashboard','Daily reports','Customizable design','Ads & Promotions','Bilingual RO / EN'];
    $fDescRo = ['Clienții scanează codul QR de pe masă cu telefonul personal sau folosesc tableta restaurantului.','Restaurantul vede comenzile instant, le gestionează per produs și urmărește statusul fiecărei mese live.','Statistici complete: vânzări totale, top produse, distribuție pe ore, metodă de plată. Export PDF.','Logo, temă vizuală, imagine de fundal, mesaj de bun venit. Tableta arată exact ca brandul tău.','Banner ticker cu oferte speciale pe tableta clientului.','Interfața completă în română și engleză — atât pentru restaurant cât și pentru clienți.'];
    $fDescEn = ['Customers scan the QR code from the table with their phone or use the restaurant tablet.','The restaurant sees orders instantly, manages them per product, and tracks each table status live.','Complete statistics: total sales, top products, hourly distribution, payment method. PDF export.','Logo, visual theme, background image, welcome message. The tablet looks exactly like your brand.','Ticker banner with special offers on the customer tablet.','Complete interface in Romanian and English — both for the restaurant and customers.'];
    ?>
    <div class="features-grid">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <div class="feature-card reveal">
            <span class="feature-icon"><?= $fIcons[$i-1] ?></span>
            <h3 class="feature-title" data-ro="<?= ls("f{$i}_title_ro",$fTitRo[$i-1]) ?>" data-en="<?= ls("f{$i}_title_en",$fTitEn[$i-1]) ?>"><?= ls("f{$i}_title_ro",$fTitRo[$i-1]) ?></h3>
            <p class="feature-desc" data-ro="<?= ls("f{$i}_desc_ro",$fDescRo[$i-1]) ?>" data-en="<?= ls("f{$i}_desc_en",$fDescEn[$i-1]) ?>"><?= ls("f{$i}_desc_ro",$fDescRo[$i-1]) ?></p>
        </div>
        <?php endfor; ?>
    </div>
</section>

<!-- GALERIE FOTO -->
<section class="galerie reveal">
    <div class="galerie-grid">
        <div class="galerie-item">
            <img src="/uploads/landing-img2.jpg" alt="Restaurant cu clienți" loading="lazy">
            <div class="galerie-overlay">
                <span data-ro="Experiențe autentice" data-en="Authentic experiences">Experiențe autentice</span>
            </div>
        </div>
        <div class="galerie-item">
            <img src="/uploads/landing-img1.jpg" alt="Preparate servite" loading="lazy">
            <div class="galerie-overlay">
                <span data-ro="Servire rapidă și precisă" data-en="Fast and precise service">Servire rapidă și precisă</span>
            </div>
        </div>
        <div class="galerie-item">
            <img src="/uploads/landing-img3.jpg" alt="Atmosferă restaurant" loading="lazy">
            <div class="galerie-overlay">
                <span data-ro="Atmosferă memorabilă" data-en="Memorable atmosphere">Atmosferă memorabilă</span>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how" id="how">
    <div style="max-width:1200px;margin:0 auto">
        <div class="section-header reveal">
            <p class="section-eyebrow" data-ro="<?= ls('how_eyebrow_ro','Simplu de implementat') ?>" data-en="<?= ls('how_eyebrow_en','Easy to implement') ?>"><?= ls('how_eyebrow_ro','Simplu de implementat') ?></p>
            <h2 class="section-title">
                <span data-ro="<?= ls('how_title_ro') ?>" data-en="<?= ls('how_title_en') ?>"><?= lsRaw('how_title_ro','Pornești în <em>4 pași</em>') ?></span>
            </h2>
        </div>
        <?php
        $sTitRo = ['Primești accesul','Adaugi mesele','Clienții comandă','Gestionezi & încasezi'];
        $sTitEn = ['Get access','Add tables','Customers order','Manage & collect'];
        $sDescRo = ['În baza abonamentului, restaurantul primește acces la platformă și configurează meniul.','Fiecare masă primește un cod QR unic. Clientul îl scanează cu telefonul sau deschide linkul pe tabletă.','Clienții văd meniul pe tabletă și comandă. Tu primești instant în dashboard.','Marchezi produsele ca servite, clientul solicită nota, tu eliberezi masa.'];
        $sDescEn = ['Based on your subscription, the restaurant gets access and sets up the menu.','Each table gets a unique QR code. The customer scans it or opens the link on the tablet.','Customers see the menu on the tablet and order. You receive it instantly in the dashboard.','Mark products as served, the customer requests the bill, you release the table.'];
        ?>
        <div class="how-grid">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="how-step reveal">
                <div class="step-num"><?= $i ?></div>
                <h4 class="step-title" data-ro="<?= ls("step{$i}_title_ro",$sTitRo[$i-1]) ?>" data-en="<?= ls("step{$i}_title_en",$sTitEn[$i-1]) ?>"><?= ls("step{$i}_title_ro",$sTitRo[$i-1]) ?></h4>
                <p class="step-desc" data-ro="<?= ls("step{$i}_desc_ro",$sDescRo[$i-1]) ?>" data-en="<?= ls("step{$i}_desc_en",$sDescEn[$i-1]) ?>"><?= ls("step{$i}_desc_ro",$sDescRo[$i-1]) ?></p>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIAL -->
<section class="testimonial reveal">
    <span class="quote-mark">"</span>
    <p class="quote-text" data-ro="<?= ls('testimonial_text_ro') ?>" data-en="<?= ls('testimonial_text_en') ?>">"<?= ls('testimonial_text_ro','De când am implementat Tablerino, chelnerul nostru se ocupă de servire, nu de luat comenzi.') ?>"</p>
    <div class="quote-author">
        <strong data-ro="<?= ls('testimonial_author_ro','Restaurant partener') ?>" data-en="<?= ls('testimonial_author_en','Partner restaurant') ?>"><?= ls('testimonial_author_ro','Restaurant partener') ?></strong>
        <span data-ro="<?= ls('testimonial_sub_ro','Client Tablerino') ?>" data-en="<?= ls('testimonial_sub_en','Tablerino client') ?>"><?= ls('testimonial_sub_ro','Client Tablerino') ?></span>
    </div>
</section>

<!-- CTA -->
<section class="cta-section" id="contact">
    <h2 class="cta-title reveal">
        <span data-ro="<?= ls('cta_title_ro') ?>" data-en="<?= ls('cta_title_en') ?>"><?= lsRaw('cta_title_ro','Gata să <em>transformi</em><br>experiența din restaurant?') ?></span>
    </h2>
    <p class="cta-desc reveal" data-ro="<?= ls('cta_desc_ro') ?>" data-en="<?= ls('cta_desc_en') ?>"><?= ls('cta_desc_ro','Disponibil în baza unui abonament. Orice tabletă sau telefon cu browser funcționează.') ?></p>
    <div class="cta-btns reveal">
        <a href="#inregistrare" class="btn-gold" data-ro="Înregistrează-ți restaurantul" data-en="Register your restaurant">Înregistrează-ți restaurantul</a>
        <a href="mailto:<?= ls('contact_email','office@tablerino.ro') ?>" class="btn-outline" data-ro="<?= ls('cta_btn2_ro','Contactează-ne') ?>" data-en="<?= ls('cta_btn2_en','Contact us') ?>"><?= ls('cta_btn2_ro','Contactează-ne') ?></a>
    </div>
</section>

<!-- ÎNREGISTRARE -->
<section class="inreg-section" id="inregistrare">
    <div class="inreg-wrap">
        <div class="inreg-left reveal">
            <p class="section-eyebrow" data-ro="Începe acum" data-en="Get started">Începe acum</p>
            <h2 class="inreg-title" data-ro="Înregistrează-ți <em>restaurantul</em>" data-en="Register your <em>restaurant</em>">Înregistrează-ți <em>restaurantul</em></h2>
            <p class="inreg-desc" data-ro="Completează formularul și te contactăm în cel mai scurt timp pentru a activa contul." data-en="Fill in the form and we'll contact you as soon as possible to activate your account.">Completează formularul și te contactăm în cel mai scurt timp pentru a activa contul.</p>
        </div>
        <div class="inreg-right reveal">
            <div class="inreg-form-wrap" id="inregFormWrap">
                <div class="field-group">
                    <label data-ro="Nume restaurant *" data-en="Restaurant name *">Nume restaurant *</label>
                    <input type="text" id="irNume" placeholder="Ex: La Mama">
                </div>
                <div class="field-group">
                    <label data-ro="Email *" data-en="Email *">Email *</label>
                    <input type="email" id="irEmail" placeholder="contact@restaurant.ro">
                </div>
                <div class="field-group">
                    <label data-ro="Telefon" data-en="Phone">Telefon</label>
                    <input type="tel" id="irTelefon" placeholder="07XXXXXXXX">
                </div>
                <div class="field-group">
                    <label data-ro="Parolă *" data-en="Password *">Parolă *</label>
                    <input type="password" id="irParola" placeholder="Minim 6 caractere">
                </div>
                <button class="inreg-btn" onclick="trimiteInregistrare()" id="irBtn" data-ro="Trimite cererea" data-en="Submit request">Trimite cererea</button>
                <p class="inreg-note" data-ro="Contul va fi activat după confirmarea abonamentului." data-en="The account will be activated after subscription confirmation.">Contul va fi activat după confirmarea abonamentului.</p>
            </div>
            <div class="inreg-success" id="inregSuccess" style="display:none">
                <div class="success-icon">✅</div>
                <h3 data-ro="Cerere trimisă!" data-en="Request sent!">Cerere trimisă!</h3>
                <p data-ro="Te vom contacta în curând pentru activarea contului." data-en="We'll contact you soon to activate your account.">Te vom contacta în curând pentru activarea contului.</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-top">
        <div>
            <div class="footer-brand">Tablerino</div>
            <p class="footer-tagline" data-ro="Tehnologie pentru experiențe culinare. Simplificăm comanda la masă pentru restaurante moderne." data-en="Technology for culinary experiences. Simplifying table ordering for modern restaurants.">Tehnologie pentru experiențe culinare. Simplificăm comanda la masă pentru restaurante moderne.</p>
            <!-- Social media din setări -->
            <div class="footer-social" id="footerSocial">
                <?php
                $socials = [
                    'social_facebook'  => ['url' => $ls['social_facebook'] ?? '', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'],
                    'social_instagram' => ['url' => $ls['social_instagram'] ?? '', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>'],
                    'social_tiktok'    => ['url' => $ls['social_tiktok'] ?? '', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.27 8.27 0 004.84 1.55V6.79a4.85 4.85 0 01-1.07-.1z"/></svg>'],
                    'social_whatsapp'  => ['url' => $ls['social_whatsapp'] ?? '', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>'],
                    'social_youtube'   => ['url' => $ls['social_youtube'] ?? '', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>'],
                ];
                foreach ($socials as $key => $s):
                    if (empty($s['url'])) continue;
                    $href = $key === 'social_whatsapp' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $s['url']) : $s['url'];
                ?>
                <a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener" class="footer-social-btn <?= str_replace('social_','',$key) ?>">
                    <?= $s['icon'] ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="footer-col">
            <h4 data-ro="Platformă" data-en="Platform">Platformă</h4>
            <a href="/admin/login.php" data-ro="Autentificare" data-en="Login">Autentificare</a>
            <a href="#inregistrare" data-ro="Înregistrare" data-en="Register">Înregistrare</a>
            <a href="#features" data-ro="Funcționalități" data-en="Features">Funcționalități</a>
            <a href="#how" data-ro="Cum funcționează" data-en="How it works">Cum funcționează</a>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <a href="mailto:<?= ls('contact_email','office@tablerino.ro') ?>"><?= ls('contact_email','office@tablerino.ro') ?></a>
            <a href="https://tablerino.ro">tablerino.ro</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span class="footer-copy">© <?= date('Y') ?> Tablerino. <span data-ro="Toate drepturile rezervate." data-en="All rights reserved.">Toate drepturile rezervate.</span></span>
        <div class="footer-line"></div>
        <span class="footer-copy">Made with ♥ in Romania</span>
    </div>
</footer>

<script>
async function trimiteInregistrare() {
    const nume   = document.getElementById('irNume').value.trim();
    const email  = document.getElementById('irEmail').value.trim();
    const tel    = document.getElementById('irTelefon').value.trim();
    const parola = document.getElementById('irParola').value;
    const btn    = document.getElementById('irBtn');

    if (!nume || !email || !parola) {
        alert('Completează numele, emailul și parola.');
        return;
    }
    if (parola.length < 6) {
        alert('Parola trebuie să aibă minim 6 caractere.');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Se trimite...';

    const r = await fetch('/api/inregistrare.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nume, email, telefon: tel, parola })
    });
    const d = await r.json();
    btn.disabled = false;
    btn.textContent = btn.getAttribute('data-ro') || 'Trimite cererea';

    if (d.ok) {
        document.getElementById('inregFormWrap').style.display = 'none';
        document.getElementById('inregSuccess').style.display = 'block';
    } else {
        alert(d.msg || 'Eroare. Încearcă din nou.');
    }
}

function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
    document.getElementById('hamburger').textContent =
        document.getElementById('mobileMenu').classList.contains('open') ? '✕' : '☰';
}

// Inchide menu la click pe link
document.querySelectorAll('.mobile-menu a').forEach(a => {
    a.addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.remove('open');
        document.getElementById('hamburger').textContent = '☰';
    });
});

// === CURSOR ===
const cursor = document.getElementById('cursor');
const ring = document.getElementById('cursorRing');
let mx = 0, my = 0, rx = 0, ry = 0;
document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cursor.style.transform = `translate(${mx - 4}px, ${my - 4}px)`;
});
function animRing() {
    rx += (mx - rx) * 0.12;
    ry += (my - ry) * 0.12;
    ring.style.transform = `translate(${rx - 18}px, ${ry - 18}px)`;
    requestAnimationFrame(animRing);
}
animRing();
document.querySelectorAll('a, button').forEach(el => {
    el.addEventListener('mouseenter', () => { ring.style.width = '56px'; ring.style.height = '56px'; ring.style.opacity = '0.3'; });
    el.addEventListener('mouseleave', () => { ring.style.width = '36px'; ring.style.height = '36px'; ring.style.opacity = '0.6'; });
});

// === NAVBAR SCROLL ===
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
});

// === SCROLL REVEAL ===
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// === LIMBA ===
let limbaActiva = 'ro';

function setLang(lang) {
    limbaActiva = lang;
    // Sincronizeaza toate butoanele RO/EN (desktop + mobile)
    ['btnRo','btnRoM'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('activ', lang === 'ro');
    });
    ['btnEn','btnEnM'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('activ', lang === 'en');
    });
    document.documentElement.lang = lang;

    document.querySelectorAll('[data-ro]').forEach(el => {
        const txt = el.getAttribute('data-' + lang);
        if (!txt) return;
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            el.placeholder = txt;
        } else if (el.tagName === 'A' || el.tagName === 'BUTTON' || el.tagName === 'P' || el.tagName === 'SPAN' || el.tagName === 'DIV') {
            el.innerHTML = txt;
        } else {
            el.innerHTML = txt;
        }
    });
    localStorage.setItem('tablerino_lang', lang);
}

// Detecteaza preferinta salvata sau limba browserului
const saved = localStorage.getItem('tablerino_lang');
const browser = navigator.language.startsWith('en') ? 'en' : 'ro';
setLang(saved || browser);
</script>
</body>
</html>
