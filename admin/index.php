<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$limba = $rest['limba'];
?>
<!DOCTYPE html>
<html lang="<?= $limba ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<title>Tablerino - <?= t('nav_orders') ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f0f0f5; color: #111; }

.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; z-index: 50; }
.sidebar .logo { margin-bottom: 20px; display: flex; align-items: center; justify-content: center; }
.sidebar .logo img { height: 190px; width: auto; object-fit: contain; display: block; }
.sidebar a { color: #aaa; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s, color .15s; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }

.header-mobil { display: none; background: #1a1a2e; color: #fff; padding: 12px 16px; position: sticky; top: 0; z-index: 50; justify-content: space-between; align-items: center; }
.btn-fullscreen { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 7px 14px; border-radius: 8px; font-size: 12px; cursor: pointer; }

.main { margin-left: 220px; padding: 28px; }
.main h2 { font-size: 20px; margin-bottom: 8px; font-weight: 800; }

.live-indicator { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #888; margin-bottom: 16px; }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 2s infinite; flex-shrink: 0; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

.sumar-zi { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.sumar-card { background: #fff; border-radius: 10px; padding: 16px 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); min-width: 130px; }
.sumar-card .val { font-size: 26px; font-weight: 800; color: #6c47ff; line-height: 1; margin-bottom: 4px; }
.sumar-card .val.verde { color: #10b981; }
.sumar-card .val.albastru { color: #3b82f6; }
.sumar-card .lbl { font-size: 12px; color: #888; }

.filtre { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.filtru-btn { padding: 8px 18px; border-radius: 20px; border: 2px solid #ddd; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s; }
.filtru-btn.activ { border-color: #6c47ff; background: #6c47ff; color: #fff; }

.comenzi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.comanda-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); border-left: 4px solid #ddd; }
.comanda-card.noua { border-left-color: #f59e0b; }
.comanda-card.in_pregatire { border-left-color: #3b82f6; }
.comanda-card.plata_aleasa { border-left-color: #8b5cf6; background: #faf5ff; }

.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.card-masa { font-weight: 700; font-size: 16px; }
.card-nr { font-size: 11px; font-weight: 700; background: #f3f4f6; color: #666; padding: 2px 8px; border-radius: 20px; margin-left: 6px; }
.card-ora { font-size: 12px; color: #888; margin-top: 2px; }
.card-status { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }
.status-noua { background: #fef3c7; color: #92400e; }
.status-in_pregatire { background: #dbeafe; color: #1e40af; }
.status-plata_aleasa { background: #ede9fe; color: #5b21b6; }

.card-produse { font-size: 13px; color: #444; margin-bottom: 14px; }
.card-produse li { list-style: none; padding: 6px 0; border-bottom: 1px solid #f5f5f5; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.card-produse li:last-child { border-bottom: none; }
.tag-nou { font-size: 10px; font-weight: 700; background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 8px; margin-left: 4px; }
.tag-livrat { font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46; padding: 1px 6px; border-radius: 8px; margin-left: 4px; }
li.livrat { opacity: 0.5; }
.btn-livreaza-produs { padding: 3px 8px; background: #3b82f6; color: #fff; border: none; border-radius: 6px; font-size: 10px; font-weight: 600; cursor: pointer; white-space: nowrap; }

.card-total { font-weight: 700; font-size: 14px; text-align: right; margin-bottom: 14px; color: #333; }
.card-actiuni { display: flex; gap: 8px; flex-wrap: wrap; }
.btn { padding: 8px 14px; border-radius: 8px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; transition: opacity .15s; }
.btn:hover { opacity: .85; }
.btn-pregatire { background: #3b82f6; color: #fff; flex: 1; }
.btn-servita { background: #10b981; color: #fff; flex: 1; }
.btn-elibereaza { background: #8b5cf6; color: #fff; flex: 1; }

.banner-plata { border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; font-size: 13px; font-weight: 700; text-align: center; }
.banner-plata.cash { background: #d1fae5; color: #065f46; }
.banner-plata.card { background: #dbeafe; color: #1e40af; }

.toate-servite-msg { font-size: 12px; color: #10b981; font-weight: 700; text-align: center; padding: 8px 0; }

.gol { text-align: center; color: #aaa; padding: 60px 0; font-size: 15px; }
.notif { position: fixed; top: 20px; right: 20px; background: #6c47ff; color: #fff; padding: 14px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; display: none; z-index: 999; box-shadow: 0 4px 20px rgba(108,71,255,.4); }

@media (max-width: 768px) {
    .header-mobil { display: flex !important; }
    .sidebar { position: fixed; left: 0; right: 0; top: auto; bottom: 0; width: 100%; height: 62px; flex-direction: row; padding: 0; justify-content: space-around; align-items: center; border-top: 1px solid #2a2a3e; }
    .sidebar .logo { display: none; }
    .sidebar .logout { margin-top: 0; }
    .sidebar a { flex-direction: column; gap: 2px; font-size: 10px; padding: 6px 8px; border-radius: 6px; flex: 1; justify-content: center; text-align: center; }
    .main { margin-left: 0; padding: 14px 12px 80px; }
    .sumar-zi { gap: 8px; }
    .sumar-card { padding: 12px 14px; min-width: 0; flex: 1; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><img src="/uploads/logo-tablerino.png" alt="Tablerino"></div>
    <a href="/admin/index.php" class="activ">📋 <?= t('nav_orders') ?></a>
    <a href="/admin/meniu.php">🍕 <?= t('nav_menu') ?></a>
    <a href="/admin/mese.php">🪑 <?= t('nav_tables') ?></a>
    <a href="/admin/istoric.php">📂 <?= t('nav_history') ?></a>
    <a href="/admin/raport.php">📊 <?= t('nav_reports') ?></a>
    <a href="/admin/setari.php">🎨 <?= t('nav_design') ?></a>
    <a href="/admin/reclame.php">📢 <?= t('nav_ads') ?></a>
    <a href="/admin/audit.php">🔍 <?= t('nav_audit') ?></a>
    <div style="margin-top:auto;padding:8px 4px;display:flex;gap:6px;justify-content:center">
        <a href="?lang=ro" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='ro' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇷🇴 RO</a>
        <a href="?lang=en" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='en' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇬🇧 EN</a>
    </div>
    <a href="/admin/logout.php" class="logout">🚪 <?= t('logout') ?></a>
</div>

<div class="header-mobil">
    <span>🍽️ <strong>Tablerino</strong></span>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="?lang=ro" style="color:<?= $limba==='ro' ? '#fff' : '#aaa' ?>;font-size:12px;font-weight:700;text-decoration:none">🇷🇴</a>
        <a href="?lang=en" style="color:<?= $limba==='en' ? '#fff' : '#aaa' ?>;font-size:12px;font-weight:700;text-decoration:none">🇬🇧</a>
        <button class="btn-fullscreen" onclick="toggleFullscreen()" id="btnFS">⛶ <?= t('fullscreen') ?></button>
    </div>
</div>

<div class="main">
    <h2><?= t('orders_title') ?> — <?= htmlspecialchars($rest['nume']) ?></h2>
    <div class="live-indicator">
        <span class="live-dot"></span>
        <span id="liveText"><?= t('last_update') ?>...</span>
    </div>

    <div class="sumar-zi" id="sumarZi"></div>

    <div class="filtre">
        <button class="filtru-btn activ" data-status="toate"><?= t('filter_all') ?></button>
        <button class="filtru-btn" data-status="noua"><?= t('filter_new') ?></button>
        <button class="filtru-btn" data-status="in_pregatire"><?= t('filter_preparing') ?></button>
        <button class="filtru-btn" data-status="plata_aleasa"><?= t('filter_payment') ?></button>
    </div>

    <div class="comenzi-grid" id="comenziGrid">
        <div class="gol"><?= t('loading') ?></div>
    </div>
</div>

<div class="notif" id="notif">🔔 <?= t('filter_new') ?>!</div>

<script>
const restaurantId = <?= $rest['id'] ?>;
const _t = {
    noOrders:     '<?= t('no_orders') ?>',
    loading:      '<?= t('loading') ?>',
    tagNew:       '<?= t('tag_new') ?>',
    tagServed:    '<?= t('tag_served') ?>',
    deliver:      '<?= t('deliver') ?>',
    btnPreparing: '<?= t('btn_preparing') ?>',
    btnServeAll:  '<?= t('btn_serve_all') ?>',
    btnRelease:   '<?= t('btn_release') ?>',
    allServed:    '<?= t('all_served_wait') ?>',
    payCash:      '<?= t('pay_cash') ?>',
    payCard:      '<?= t('pay_card') ?>',
    statusNew:    '<?= t('status_new') ?>',
    statusPrep:   '<?= t('status_preparing') ?>',
    statusPay:    '<?= t('status_payment') ?>',
    lastUpdate:   '<?= t('last_update') ?>',
    ordersToday:  '<?= t('orders_today') ?>',
    salesToday:   '<?= t('sales_today') ?>',
    cash:         '<?= t('cash') ?>',
    card:         '<?= t('card') ?>',
    total:        '<?= t('total') ?>',
    lei:          '<?= t('lei') ?>',
    releaseConf:  '<?= t('release_table_conf') ?>',
    connError:    '<?= t('connection_error') ?>',
    fullscreen:   '<?= t('fullscreen') ?>',
    exitFS:       '<?= t('exit_fullscreen') ?>',
};

let filtruActiv = 'toate';
let comenziCunoscute = new Set();

function statusLabel(s, metoda) {
    if (s === 'plata_aleasa') {
        const icon = metoda === 'cash' ? '💵' : '💳';
        const label = metoda === 'cash' ? _t.cash : _t.card;
        return `${icon} ${label}`;
    }
    const m = { noua: _t.statusNew, in_pregatire: _t.statusPrep };
    return m[s] || s;
}

function formatOra(dt) {
    return new Date(dt).toLocaleTimeString('<?= $limba === 'en' ? 'en-GB' : 'ro-RO' ?>', { hour: '2-digit', minute: '2-digit' });
}

function renderComenzi(comenzi) {
    const grid = document.getElementById('comenziGrid');
    const filtrate = filtruActiv === 'toate' ? comenzi : comenzi.filter(c => c.status === filtruActiv);

    if (!filtrate.length) {
        grid.innerHTML = `<div class="gol">${_t.noOrders}</div>`;
        return;
    }

    grid.innerHTML = filtrate.map(c => {
        const produse = c.produse.map(p => {
            const eNou = p.status === 'nou' || !p.status;
            return `<li class="${eNou ? '' : 'livrat'}">
                <span>${p.cantitate}x ${p.nume_produs}
                    ${eNou ? `<span class="tag-nou">${_t.tagNew}</span>` : `<span class="tag-livrat">${_t.tagServed}</span>`}
                </span>
                <span style="display:flex;align-items:center;gap:6px">
                    <span>${parseFloat(p.total).toFixed(2)} ${_t.lei}</span>
                    ${eNou ? `<button class="btn-livreaza-produs" onclick="livreazaProdus(${p.id},event)">${_t.deliver}</button>` : ''}
                </span>
            </li>`;
        }).join('');

        const total = c.produse.reduce((s, p) => s + parseFloat(p.total), 0);
        const areNoi = c.produse.some(p => p.status === 'nou' || !p.status);
        const toateLivrate = c.produse.length > 0 && c.produse.every(p => p.status === 'livrat');

        // Banner plata
        const bannerPlata = c.status === 'plata_aleasa' ? `
            <div class="banner-plata ${c.metoda_plata}">
                ${c.metoda_plata === 'cash' ? `💵 ${_t.payCash}` : `💳 ${_t.payCard}`}
            </div>` : '';

        // Butoane actiuni - FLUX CORECT:
        // noua + are produse noi -> In pregatire / Serveste toate
        // in_pregatire + are produse noi -> Serveste toate
        // toateLivrate (orice status) + nu e plata_aleasa -> asteapta nota clientului
        // plata_aleasa -> Elibereaza masa
        let actiuni = '';
        if (c.status === 'plata_aleasa') {
            actiuni = `<button class="btn btn-elibereaza" onclick="elibereazaMasa(${c.masa_id})">${_t.btnRelease}</button>`;
        } else if (toateLivrate) {
            actiuni = `<div class="toate-servite-msg">${_t.allServed}</div>`;
        } else if (c.status === 'noua' && areNoi) {
            actiuni = `
                <button class="btn btn-pregatire" onclick="schimbaStatus(${c.id},'in_pregatire')">${_t.btnPreparing}</button>
                <button class="btn btn-servita" onclick="livreazaToare(${c.id})">${_t.btnServeAll}</button>`;
        } else if (c.status === 'in_pregatire' && areNoi) {
            actiuni = `<button class="btn btn-servita" onclick="livreazaToare(${c.id})">${_t.btnServeAll}</button>`;
        }

        return `
        <div class="comanda-card ${c.status}">
            <div class="card-header">
                <div>
                    <div class="card-masa">${c.masa_nume} <span class="card-nr">#${c.nr_ordine_zi || '—'}</span></div>
                    <div class="card-ora">${formatOra(c.created_at)}</div>
                </div>
                <span class="card-status status-${c.status}">${statusLabel(c.status, c.metoda_plata)}</span>
            </div>
            ${bannerPlata}
            <ul class="card-produse">${produse}</ul>
            <div class="card-total">${_t.total}: ${total.toFixed(2)} ${_t.lei}</div>
            <div class="card-actiuni">${actiuni}</div>
        </div>`;
    }).join('');
}

async function incarcaSumarZi() {
    try {
        const r = await fetch('/admin/api.php?actiune=sumar_zi');
        const d = await r.json();
        if (!d.ok) return;
        document.getElementById('sumarZi').innerHTML = `
            <div class="sumar-card"><div class="val">${d.total_comenzi || 0}</div><div class="lbl">${_t.ordersToday}</div></div>
            <div class="sumar-card"><div class="val verde">${parseFloat(d.total_vanzari || 0).toFixed(2)} ${_t.lei}</div><div class="lbl">${_t.salesToday}</div></div>
            <div class="sumar-card"><div class="val albastru">${d.comenzi_cash || 0}</div><div class="lbl">💵 ${_t.cash}</div></div>
            <div class="sumar-card"><div class="val albastru">${d.comenzi_card || 0}</div><div class="lbl">💳 ${_t.card}</div></div>
        `;
    } catch(e) {}
}

async function incarcaComenzi() {
    try {
        const r = await fetch('/admin/api.php?actiune=comenzi');
        const data = await r.json();
        if (!data.ok) return;

        const nouaGasita = data.comenzi.some(c => c.status === 'noua' && !comenziCunoscute.has(c.id));
        if (nouaGasita && comenziCunoscute.size > 0) {
            const notif = document.getElementById('notif');
            notif.style.display = 'block';
            setTimeout(() => notif.style.display = 'none', 3000);
        }
        data.comenzi.forEach(c => comenziCunoscute.add(c.id));
        renderComenzi(data.comenzi);
        incarcaSumarZi();

        const acum = new Date().toLocaleTimeString('<?= $limba === 'en' ? 'en-GB' : 'ro-RO' ?>', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('liveText').textContent = `${_t.lastUpdate}: ${acum}`;
    } catch(e) {
        document.getElementById('liveText').textContent = _t.connError;
    }
}

async function schimbaStatus(id, status) {
    await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'schimba_status', comanda_id: id, status })
    });
    incarcaComenzi();
}

async function livreazaProdus(prodId, event) {
    event.stopPropagation();
    await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'livreaza_produs', comanda_produs_id: prodId })
    });
    incarcaComenzi();
}

async function livreazaToare(comandaId) {
    // Marcheaza toate produsele ca livrate - comanda NU devine servita
    // Ramane vizibila pana clientul solicita nota si se elibereaza masa
    await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'livreaza_toate', comanda_id: comandaId })
    });
    incarcaComenzi();
}

async function elibereazaMasa(masaId) {
    if (!confirm(_t.releaseConf)) return;
    await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'elibereaza_masa', masa_id: masaId })
    });
    incarcaComenzi();
}

document.querySelectorAll('.filtru-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filtru-btn').forEach(b => b.classList.remove('activ'));
        btn.classList.add('activ');
        filtruActiv = btn.dataset.status;
        incarcaComenzi();
    });
});

function toggleFullscreen() {
    const btn = document.getElementById('btnFS');
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            btn.textContent = '✕ ' + _t.exitFS;
        }).catch(() => window.scrollTo(0, 1));
    } else {
        document.exitFullscreen().then(() => {
            btn.textContent = '⛶ ' + _t.fullscreen;
        });
    }
}
document.addEventListener('fullscreenchange', () => {
    const btn = document.getElementById('btnFS');
    if (btn) btn.textContent = document.fullscreenElement ? '✕ ' + _t.exitFS : '⛶ ' + _t.fullscreen;
});

incarcaComenzi();
setInterval(incarcaComenzi, 5000);
</script>
</body>
</html>
