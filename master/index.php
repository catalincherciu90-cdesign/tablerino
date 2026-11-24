<?php
require_once __DIR__ . '/../config.php';
authMaster();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino Master</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #0f0f1a; color: #e0e0e0; min-height: 100vh; }

.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
.sidebar .logo { color: #fff; font-size: 18px; font-weight: 700; margin-bottom: 4px; padding: 0 8px; }
.sidebar .badge { display: inline-block; background: #6c47ff; color: #fff; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 20px; margin-left: 6px; letter-spacing: .5px; }
.sidebar .sub { color: #555; font-size: 11px; padding: 0 8px; margin-bottom: 20px; }
.sidebar a { color: #aaa; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s, color .15s; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }

.main { margin-left: 220px; padding: 32px; }
.main h2 { font-size: 20px; margin-bottom: 6px; color: #fff; }
.main p.sub { color: #666; font-size: 13px; margin-bottom: 28px; }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; margin-bottom: 32px; }
.stat-card { background: #1a1a2e; border-radius: 12px; padding: 18px 20px; border: 1px solid #2a2a3e; }
.stat-card .val { font-size: 28px; font-weight: 800; color: #6c47ff; }
.stat-card .val.verde { color: #10b981; }
.stat-card .val.alb { color: #fff; }
.stat-card .lbl { font-size: 12px; color: #666; margin-top: 4px; }

.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.toolbar h3 { font-size: 16px; color: #fff; }
.btn-add { padding: 9px 18px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-add:hover { background: #5a38e0; }

.tabel-wrap { background: #1a1a2e; border-radius: 12px; overflow: hidden; border: 1px solid #2a2a3e; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #2a2a3e; }
td { padding: 14px 16px; font-size: 14px; border-bottom: 1px solid #1f1f2e; color: #ccc; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(108,71,255,0.05); }

.badge-activ { background: rgba(16,185,129,0.15); color: #10b981; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-inactiv { background: rgba(239,68,68,0.15); color: #ef4444; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

.btn-sm { padding: 5px 12px; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-edit { background: #2a2a3e; color: #aaa; }
.btn-edit:hover { background: #6c47ff; color: #fff; }
.btn-toggle-on { background: rgba(239,68,68,0.15); color: #ef4444; }
.btn-toggle-on:hover { background: rgba(239,68,68,0.3); }
.btn-toggle-off { background: rgba(16,185,129,0.15); color: #10b981; }
.btn-toggle-off:hover { background: rgba(16,185,129,0.3); }
.btn-vezi { background: rgba(108,71,255,0.15); color: #a78bfa; }
.btn-vezi:hover { background: rgba(108,71,255,0.3); }

.td-actiuni { display: flex; gap: 6px; }
.td-num { font-weight: 700; color: #fff; }
.td-muted { color: #555; font-size: 12px; }

.overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 200; align-items: center; justify-content: center; }
.overlay.open { display: flex; }
.modal { background: #1a1a2e; border-radius: 14px; padding: 28px; width: 100%; max-width: 440px; border: 1px solid #2a2a3e; }
.modal h3 { font-size: 17px; color: #fff; margin-bottom: 20px; }
.modal label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px; }
.modal input { width: 100%; padding: 10px 12px; border: 1px solid #2a2a3e; border-radius: 8px; font-size: 14px; margin-bottom: 14px; outline: none; background: #0f0f1a; color: #fff; }
.modal input:focus { border-color: #6c47ff; }
.modal-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 4px; }
.btn-cancel { padding: 10px 18px; border: 1px solid #2a2a3e; background: transparent; color: #aaa; border-radius: 8px; cursor: pointer; font-size: 14px; }
.btn-save { padding: 10px 18px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.modal-note { font-size: 11px; color: #555; margin-bottom: 14px; margin-top: -8px; }
.gol { text-align: center; color: #444; padding: 40px; font-size: 14px; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🍽️ Tablerino <span class="badge">MASTER</span></div>
    <div class="sub">Panou platformă</div>
    <a href="/master/index.php" class="activ">🏢 Restaurante</a>
    <a href="/master/cereri.php" id="linkCereri">📋 Cereri înregistrare</a>
    <a href="/master/landing.php">🌐 Landing Page</a>
    <a href="/master/logout.php" class="logout">🚪 Ieșire</a>
</div>

<div class="main">
    <h2>Panou Master</h2>
    <p class="sub">Vizualizare și administrare toate restaurantele din platformă.</p>

    <div class="stats-grid" id="statsGrid">
        <div class="stat-card"><div class="val alb">—</div><div class="lbl">Total restaurante</div></div>
        <div class="stat-card"><div class="val verde">—</div><div class="lbl">Active</div></div>
        <div class="stat-card"><div class="val">—</div><div class="lbl">Comenzi azi (toate)</div></div>
        <div class="stat-card"><div class="val verde">—</div><div class="lbl">Vânzări azi (toate)</div></div>
    </div>

    <div class="toolbar">
        <h3>Restaurante</h3>
        <button class="btn-add" onclick="deschideModalAdauga()">+ Restaurant nou</button>
    </div>

    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Email</th>
                    <th>Mese</th>
                    <th>Comenzi azi</th>
                    <th>Vânzări azi</th>
                    <th>Status</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody id="tabelBody">
                <tr><td colspan="7" class="gol">Se încarcă...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal adaugă/editează -->
<div class="overlay" id="overlay">
    <div class="modal">
        <h3 id="modalTitlu">Restaurant nou</h3>
        <input type="hidden" id="mId">
        <label>Nume restaurant</label>
        <input type="text" id="mNume" placeholder="Ex: La Mama">
        <label>Email (folosit la login)</label>
        <input type="email" id="mEmail">
        <label>Telefon (opțional)</label>
        <input type="text" id="mTel">
        <label>Parolă</label>
        <input type="password" id="mParola" placeholder="Minim 6 caractere">
        <p class="modal-note" id="modalNote"></p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="inchideModal()">Anulează</button>
            <button class="btn-save" onclick="salveaza()">Salvează</button>
        </div>
    </div>
</div>

<script>
async function incarcaStats() {
    const r = await fetch('/master/api.php?actiune=statistici');
    const d = await r.json();
    if (!d.ok) return;
    const s = d.stats;
    document.getElementById('statsGrid').innerHTML = `
        <div class="stat-card"><div class="val alb">${s.total_restaurante}</div><div class="lbl">Total restaurante</div></div>
        <div class="stat-card"><div class="val verde">${s.restaurante_active}</div><div class="lbl">Active</div></div>
        <div class="stat-card"><div class="val">${s.comenzi_azi}</div><div class="lbl">Comenzi azi (toate)</div></div>
        <div class="stat-card"><div class="val verde">${parseFloat(s.vanzari_azi).toFixed(2)} lei</div><div class="lbl">Vânzări azi (toate)</div></div>
        <div class="stat-card"><div class="val alb">${s.total_comenzi_all}</div><div class="lbl">Total comenzi (all time)</div></div>
        ${s.cereri_pending > 0 ? `<div class="stat-card" style="border-color:#e53e3e"><div class="val" style="color:#e53e3e">${s.cereri_pending}</div><div class="lbl">⏳ Cereri în așteptare</div></div>` : ''}
    `;
    // Badge pe link cereri
    const linkCereri = document.getElementById('linkCereri');
    if (linkCereri && s.cereri_pending > 0) {
        linkCereri.innerHTML = `📋 Cereri înregistrare <span style="background:#e53e3e;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-left:4px">${s.cereri_pending}</span>`;
    }
}

async function incarcaRestaurante() {
    const r = await fetch('/master/api.php?actiune=restaurante');
    const d = await r.json();
    const body = document.getElementById('tabelBody');
    if (!d.restaurante.length) {
        body.innerHTML = '<tr><td colspan="7" class="gol">Niciun restaurant înregistrat.</td></tr>';
        return;
    }
    body.innerHTML = d.restaurante.map(r => `
        <tr>
            <td>
                <div class="td-num">${r.nume}</div>
                <div class="td-muted">ID #${r.id} · ${r.tema || 'italian'}</div>
            </td>
            <td>${r.email}</td>
            <td>${r.nr_mese}</td>
            <td>${r.comenzi_azi}</td>
            <td>${parseFloat(r.vanzari_azi).toFixed(2)} lei</td>
            <td><span class="${r.activ == 1 ? 'badge-activ' : 'badge-inactiv'}">${r.activ == 1 ? 'Activ' : 'Inactiv'}</span></td>
            <td>
                <div class="td-actiuni">
                    <button class="btn-sm btn-vezi" onclick="window.open('/master/comenzi.php?id=${r.id}','_blank')">Comenzi</button>
                    <button class="btn-sm btn-vezi" onclick="window.open('/master/meniu.php?id=${r.id}','_blank')" style="background:rgba(16,185,129,0.15);color:#10b981">Meniu</button>
                    <button class="btn-sm btn-edit" onclick="deschideModalEditare(${r.id},'${r.nume.replace(/'/g,"\\'")}','${r.email}','${r.telefon||''}')">Editează</button>
                    <button class="btn-sm ${r.activ == 1 ? 'btn-toggle-on' : 'btn-toggle-off'}" onclick="toggleActiv(${r.id})">${r.activ == 1 ? 'Dezactivează' : 'Activează'}</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function deschideModalAdauga() {
    document.getElementById('modalTitlu').textContent = 'Restaurant nou';
    document.getElementById('mId').value = '';
    document.getElementById('mNume').value = '';
    document.getElementById('mEmail').value = '';
    document.getElementById('mTel').value = '';
    document.getElementById('mParola').value = '';
    document.getElementById('mParola').placeholder = 'Minim 6 caractere';
    document.getElementById('modalNote').textContent = '';
    document.getElementById('overlay').classList.add('open');
}

function deschideModalEditare(id, nume, email, tel) {
    document.getElementById('modalTitlu').textContent = 'Editează restaurant';
    document.getElementById('mId').value = id;
    document.getElementById('mNume').value = nume;
    document.getElementById('mEmail').value = email;
    document.getElementById('mTel').value = tel;
    document.getElementById('mParola').value = '';
    document.getElementById('mParola').placeholder = 'Lasă gol pentru a păstra parola actuală';
    document.getElementById('modalNote').textContent = 'Parola se schimbă doar dacă completezi câmpul de mai sus.';
    document.getElementById('overlay').classList.add('open');
}

function inchideModal() {
    document.getElementById('overlay').classList.remove('open');
}

async function salveaza() {
    const id     = document.getElementById('mId').value;
    const nume   = document.getElementById('mNume').value.trim();
    const email  = document.getElementById('mEmail').value.trim();
    const tel    = document.getElementById('mTel').value.trim();
    const parola = document.getElementById('mParola').value;

    if (!nume || !email) { alert('Completează numele și emailul.'); return; }

    const actiune = id ? 'editeaza_restaurant' : 'adauga_restaurant';
    const body = { actiune, id: parseInt(id)||0, nume, email, telefon: tel, parola };

    const r = await fetch('/master/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.ok) {
        inchideModal();
        incarcaRestaurante();
        incarcaStats();
    } else {
        alert(d.msg || 'Eroare la salvare');
    }
}

async function toggleActiv(id) {
    await fetch('/master/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'toggle_activ', id })
    });
    incarcaRestaurante();
    incarcaStats();
}

incarcaStats();
incarcaRestaurante();
setInterval(() => { incarcaStats(); incarcaRestaurante(); }, 30000);
</script>
</body>
</html>
