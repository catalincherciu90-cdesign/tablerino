<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$limba = $rest['limba'];

// Incarcare mese pentru filtru
$qm = db()->prepare('SELECT id, nume FROM mese WHERE restaurant_id = ? ORDER BY id');
$qm->execute([$rest['id']]);
$mese = $qm->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino - <?= t('history_title') ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f0f0f5; color: #111; }
.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
.sidebar .logo { margin-bottom: 20px; padding: 4px; display: flex; align-items: center; justify-content: center; }
.sidebar .logo img { height: 64px; width: auto; object-fit: contain; background: #fff; border-radius: 10px; padding: 6px; }
.sidebar a { color: #aaa; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s, color .15s; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }
.main { margin-left: 220px; padding: 32px; }
.main h2 { font-size: 20px; margin-bottom: 24px; }

/* Filtre */
.filtre-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end; }
.filtru-group { display: flex; flex-direction: column; gap: 6px; }
.filtru-group label { font-size: 12px; font-weight: 600; color: #666; }
.filtru-group select, .filtru-group input { padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background: #fff; min-width: 160px; }
.filtru-group select:focus, .filtru-group input:focus { border-color: #6c47ff; }
.btn-filtreaza { padding: 9px 20px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; align-self: flex-end; }
.btn-reset { padding: 9px 16px; background: #f3f4f6; color: #555; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; align-self: flex-end; }

/* Sumar */
.sumar { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.sumar-card { background: #fff; border-radius: 10px; padding: 16px 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); min-width: 160px; }
.sumar-card .val { font-size: 24px; font-weight: 700; color: #6c47ff; }
.sumar-card .lbl { font-size: 12px; color: #888; margin-top: 4px; }

/* Tabel */
.tabel-wrap { background: #fff; border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: .5px; border-bottom: 2px solid #f0f0f0; background: #fafafa; }
td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid #f5f5f5; vertical-align: top; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #fafafa; }

.td-produse { font-size: 12px; color: #666; line-height: 1.6; }
.td-total { font-weight: 700; color: #333; white-space: nowrap; }
.td-masa { font-weight: 600; }
.td-ora { color: #888; font-size: 13px; white-space: nowrap; }

/* Paginare */
.paginare { display: flex; gap: 6px; justify-content: center; padding: 20px; }
.pg-btn { padding: 8px 14px; border-radius: 8px; border: 1px solid #ddd; background: #fff; font-size: 13px; cursor: pointer; transition: all .15s; }
.pg-btn:hover { border-color: #6c47ff; color: #6c47ff; }
.pg-btn.activ { background: #6c47ff; color: #fff; border-color: #6c47ff; }
.pg-btn:disabled { opacity: .4; cursor: default; }

.gol { text-align: center; color: #aaa; padding: 60px; font-size: 15px; }


/* ── RESPONSIVE SIDEBAR ── */
@media (max-width: 768px) {
    .header-mobil { display: flex !important; }
    .sidebar { position: fixed; left: 0; right: 0; top: auto; bottom: 0; width: 100%; height: 62px; flex-direction: row; padding: 0; justify-content: space-around; align-items: center; border-top: 1px solid #2a2a3e; z-index: 100; }
    .sidebar .logo { display: none; }
    .sidebar .logout { margin-top: 0; }
    .sidebar a { flex-direction: column; gap: 2px; font-size: 10px; padding: 6px 8px; border-radius: 6px; flex: 1; justify-content: center; text-align: center; font-weight: 600; }
    .main { margin-left: 0 !important; padding: 14px 12px 80px !important; }
    .main h2 { font-size: 18px !important; }
    .layout { grid-template-columns: 1fr !important; }
    .teme-grid { grid-template-columns: repeat(2, 1fr) !important; }
    .mese-grid { grid-template-columns: 1fr !important; }
    .adauga-form { flex-direction: column; }
    table { font-size: 12px; }
    th, td { padding: 8px 10px; }
    .filtre-bar { flex-wrap: wrap; }
    .sumar { gap: 8px; }
    .sumar-card { padding: 12px 14px; min-width: 0; flex: 1; }
    .sectiune { padding: 18px; }
    .produs-item { flex-wrap: wrap; }
}

</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><img src="/uploads/logo-tablerino.png" alt="Tablerino" style="height:190px;width:auto;object-fit:contain;display:block;"></div>
    <a href="/admin/index.php">📋 <?= t('nav_orders') ?></a>
    <a href="/admin/meniu.php">🍕 <?= t('nav_menu') ?></a>
    <a href="/admin/mese.php">🪑 <?= t('nav_tables') ?></a>
    <a href="/admin/istoric.php" class="activ">📂 <?= t('nav_history') ?></a>
    <a href="/admin/raport.php">📊 <?= t('nav_reports') ?></a>
    <a href="/admin/setari.php">🎨 <?= t('nav_design') ?></a>
    <a href="/admin/reclame.php">📢 <?= t('nav_ads') ?></a>
        <div style="margin-top:auto;padding:8px 4px;display:flex;gap:6px;justify-content:center">
        <a href="?lang=ro" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='ro' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇷🇴 RO</a>
        <a href="?lang=en" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='en' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇬🇧 EN</a>
    </div>
    <a href="/admin/logout.php" class="logout">🚪 <?= t('logout') ?></a>
</div>

<div class="main">
    <h2><?= t('history_title') ?></h2>

    <div class="filtre-bar">
        <div class="filtru-group">
            <label><?= t('table') ?></label>
            <select id="filtruMasa">
                <option value=""><?= t('all_tables') ?></option>
                <?php foreach ($mese as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtru-group">
            <label><?= t('date') ?></label>
            <input type="date" id="filtruData" value="<?= date('Y-m-d') ?>">
        </div>
        <button class="btn-filtreaza" onclick="cauta(1)"><?= t('search') ?></button>
        <button class="btn-reset" onclick="reseteaza()"><?= t('reset') ?></button>
    </div>

    <div class="sumar" id="sumar"></div>

    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= t('table') ?></th>
                    <th><?= t('products') ?></th>
                    <th><?= t('total') ?></th>
                    <th><?= t('payment') ?></th>
                    <th><?= t('date_time') ?></th>
                </tr>
            </thead>
            <tbody id="tabelBody">
                <tr><td colspan="6" class="gol"><?= t('loading') ?></td></tr>
            </tbody>
        </table>
        <div class="paginare" id="paginare"></div>
    </div>
</div>

<script>
let pageActiva = 1;
const _ht = {
    ordersFound:  '<?= t('orders_found') ?>',
    totalSales:   '<?= t('total_sales_page') ?>',
    productsSold: '<?= t('products_sold') ?>',
    noOrders:     '<?= t('no_history') ?>',
    cash:         '<?= t('cash') ?>',
    card:         '<?= t('card') ?>',
    loading:      '<?= t('loading') ?>',
    lei:          '<?= t('lei') ?>',
};

function formatData(dt) {
    return new Date(dt).toLocaleString('<?= $limba === 'en' ? 'en-GB' : 'ro-RO' ?>', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function cauta(page = 1) {
    pageActiva = page;
    const masa = document.getElementById('filtruMasa').value;
    const data = document.getElementById('filtruData').value;

    let url = `/admin/api.php?actiune=istoric&page=${page}`;
    if (masa) url += `&masa_id=${masa}`;
    if (data) url += `&data=${data}`;

    const r = await fetch(url);
    const d = await r.json();

    renderSumar(d.comenzi, d.total);
    renderTabel(d.comenzi);
    renderPaginare(d.pagini, page);
}

function renderSumar(comenzi, totalCount) {
    const totalVanzari = comenzi.reduce((s, c) => s + parseFloat(c.total || 0), 0);
    const totalProduse = comenzi.reduce((s, c) => s + c.produse.reduce((sp, p) => sp + parseInt(p.cantitate), 0), 0);

    document.getElementById('sumar').innerHTML = `
        <div class="sumar-card"><div class="val">${totalCount}</div><div class="lbl">${_ht.ordersFound}</div></div>
        <div class="sumar-card"><div class="val">${totalVanzari.toFixed(2)} ${_ht.lei}</div><div class="lbl">${_ht.totalSales}</div></div>
        <div class="sumar-card"><div class="val">${totalProduse}</div><div class="lbl">${_ht.productsSold}</div></div>
    `;
}

function renderTabel(comenzi) {
    const body = document.getElementById('tabelBody');
    if (!comenzi.length) {
        body.innerHTML = `<tr><td colspan="6" class="gol">${_ht.noOrders}</td></tr>`;
        return;
    }
    body.innerHTML = comenzi.map(c => {
        const produse = c.produse.map(p => `${p.cantitate}x ${p.nume_produs} (${parseFloat(p.total).toFixed(2)} ${_ht.lei})`).join('<br>');
        const plata = c.metoda_plata === 'cash' ? `💵 ${_ht.cash}` : c.metoda_plata === 'card' ? `💳 ${_ht.card}` : '—';
        return `
        <tr>
            <td style="color:#aaa;font-size:12px">#${c.id}</td>
            <td class="td-masa">${c.masa_nume}</td>
            <td class="td-produse">${produse}</td>
            <td class="td-total">${parseFloat(c.total).toFixed(2)} ${_ht.lei}</td>
            <td>${plata}</td>
            <td class="td-ora">${formatData(c.created_at)}</td>
        </tr>`;
    }).join('');
}

function renderPaginare(pagini, page) {
    const div = document.getElementById('paginare');
    if (pagini <= 1) { div.innerHTML = ''; return; }
    let html = '';
    for (let i = 1; i <= pagini; i++) {
        html += `<button class="pg-btn ${i === page ? 'activ' : ''}" onclick="cauta(${i})">${i}</button>`;
    }
    div.innerHTML = html;
}

function reseteaza() {
    document.getElementById('filtruMasa').value = '';
    document.getElementById('filtruData').value = '';
    cauta(1);
}

cauta(1);
</script>
</body>
</html>
