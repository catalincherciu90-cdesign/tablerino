<?php
require_once __DIR__ . '/../config.php';
authMaster();

$rid = (int)($_GET['id'] ?? 0);
if (!$rid) { header('Location: /master/index.php'); exit; }

$qr = db()->prepare('SELECT * FROM restaurante WHERE id = ?');
$qr->execute([$rid]);
$restaurant = $qr->fetch();
if (!$restaurant) { header('Location: /master/index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Comenzi — <?= htmlspecialchars($restaurant['nume']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #0f0f1a; color: #e0e0e0; min-height: 100vh; }
.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
.sidebar .logo { color: #fff; font-size: 18px; font-weight: 700; margin-bottom: 4px; padding: 0 8px; }
.sidebar .badge { display: inline-block; background: #6c47ff; color: #fff; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 20px; margin-left: 6px; }
.sidebar .sub { color: #555; font-size: 11px; padding: 0 8px; margin-bottom: 20px; }
.sidebar a { color: #aaa; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }
.main { margin-left: 220px; padding: 32px; }
.main h2 { font-size: 20px; color: #fff; margin-bottom: 4px; }
.main p.sub { color: #666; font-size: 13px; margin-bottom: 24px; }

.filtre-bar { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
.filtre-bar input[type=date] { padding: 9px 12px; border: 1px solid #2a2a3e; border-radius: 8px; font-size: 13px; outline: none; background: #1a1a2e; color: #fff; }
.btn-cauta { padding: 9px 18px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }

.sumar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.sumar-card { background: #1a1a2e; border-radius: 10px; padding: 14px 18px; border: 1px solid #2a2a3e; min-width: 130px; }
.sumar-card .val { font-size: 22px; font-weight: 800; color: #6c47ff; }
.sumar-card .val.verde { color: #10b981; }
.sumar-card .lbl { font-size: 12px; color: #666; margin-top: 2px; }

.tabel-wrap { background: #1a1a2e; border-radius: 12px; overflow: hidden; border: 1px solid #2a2a3e; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; border-bottom: 1px solid #2a2a3e; }
td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #1f1f2e; color: #ccc; vertical-align: top; }
tr:last-child td { border-bottom: none; }
.td-produse { font-size: 12px; color: #666; line-height: 1.7; }
.td-total { font-weight: 700; color: #fff; }
.gol { text-align: center; color: #444; padding: 40px; }
.status-badge { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
.s-noua { background: rgba(245,158,11,0.15); color: #f59e0b; }
.s-in_pregatire { background: rgba(59,130,246,0.15); color: #3b82f6; }
.s-servita { background: rgba(16,185,129,0.15); color: #10b981; }
.s-plata_aleasa { background: rgba(139,92,246,0.15); color: #8b5cf6; }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo">🍽️ Tablerino <span class="badge">MASTER</span></div>
    <div class="sub">Panou platformă</div>
    <a href="/master/index.php">🏢 Restaurante</a>
    <a href="/master/logout.php" class="logout">🚪 Ieșire</a>
</div>

<div class="main">
    <h2><?= htmlspecialchars($restaurant['nume']) ?></h2>
    <p class="sub"><?= htmlspecialchars($restaurant['email']) ?> · Comenzi per zi</p>

    <div class="filtre-bar">
        <input type="date" id="filtruData" value="<?= date('Y-m-d') ?>">
        <button class="btn-cauta" onclick="incarca()">Caută</button>
    </div>

    <div class="sumar" id="sumar"></div>

    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Masă</th>
                    <th>Produse</th>
                    <th>Total</th>
                    <th>Plată</th>
                    <th>Status</th>
                    <th>Ora</th>
                </tr>
            </thead>
            <tbody id="tabelBody">
                <tr><td colspan="7" class="gol">Se încarcă...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const restaurantId = <?= $rid ?>;

function formatOra(dt) {
    return new Date(dt).toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' });
}

async function incarca() {
    const data = document.getElementById('filtruData').value;
    const r = await fetch(`/master/api.php?actiune=comenzi_restaurant&restaurant_id=${restaurantId}&data=${data}`);
    const d = await r.json();

    const total = d.comenzi.reduce((s, c) => s + parseFloat(c.total || 0), 0);
    const cash = d.comenzi.filter(c => c.metoda_plata === 'cash').length;
    const card = d.comenzi.filter(c => c.metoda_plata === 'card').length;

    document.getElementById('sumar').innerHTML = `
        <div class="sumar-card"><div class="val">${d.comenzi.length}</div><div class="lbl">Comenzi</div></div>
        <div class="sumar-card"><div class="val verde">${total.toFixed(2)} lei</div><div class="lbl">Total vânzări</div></div>
        <div class="sumar-card"><div class="val">${cash}</div><div class="lbl">💵 Cash</div></div>
        <div class="sumar-card"><div class="val">${card}</div><div class="lbl">💳 Card</div></div>
    `;

    const body = document.getElementById('tabelBody');
    if (!d.comenzi.length) {
        body.innerHTML = '<tr><td colspan="7" class="gol">Nicio comandă în această zi.</td></tr>';
        return;
    }

    body.innerHTML = d.comenzi.map(c => {
        const produse = c.produse.map(p => `${p.cantitate}× ${p.nume_produs}`).join('<br>');
        const plata = c.metoda_plata === 'cash' ? '💵 Cash' : c.metoda_plata === 'card' ? '💳 Card' : '—';
        const cls = 's-' + c.status;
        const statusLabel = { noua: 'Nouă', in_pregatire: 'În pregătire', servita: 'Servită', plata_aleasa: 'Plată aleasă' };
        return `
        <tr>
            <td style="color:#555;font-size:12px">#${c.nr_ordine_zi || c.id}</td>
            <td style="font-weight:600;color:#fff">${c.masa_nume}</td>
            <td class="td-produse">${produse}</td>
            <td class="td-total">${parseFloat(c.total).toFixed(2)} lei</td>
            <td>${plata}</td>
            <td><span class="status-badge ${cls}">${statusLabel[c.status] || c.status}</span></td>
            <td style="color:#555">${formatOra(c.created_at)}</td>
        </tr>`;
    }).join('');
}

incarca();
</script>
</body>
</html>
