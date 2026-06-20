<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$rid = $rest['id'];
$limba = $rest['limba'];

// Incarca comenzile problematice:
// 1. Comenzi active (noua, in_pregatire, plata_aleasa) - nefinalizate
// 2. Comenzi servite fara metoda_plata - inchise gresit
$qc = db()->prepare("
    SELECT
        c.id, c.nr_ordine_zi, c.status, c.created_at, c.updated_at, c.metoda_plata,
        m.nume AS masa_nume,
        TIMESTAMPDIFF(MINUTE, c.created_at, NOW()) AS minute_vechi,
        COALESCE(SUM(cp.total), 0) AS total,
        CASE
            WHEN c.status NOT IN ('servita') THEN 'activa'
            WHEN c.status = 'servita' AND c.metoda_plata IS NULL THEN 'fara_plata'
        END AS tip_problema
    FROM comenzi c
    JOIN mese m ON m.id = c.masa_id
    LEFT JOIN comanda_produse cp ON cp.comanda_id = c.id
    WHERE c.restaurant_id = ?
      AND (
        c.status NOT IN ('servita')
        OR (c.status = 'servita' AND c.metoda_plata IS NULL)
      )
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$qc->execute([$rid]);
$comenzi = $qc->fetchAll();

// Verifica daca are parola de reset setata
$qp = db()->prepare("SELECT parola_reset FROM restaurante WHERE id = ?");
$qp->execute([$rid]);
$row = $qp->fetch();
$areParola = !empty($row['parola_reset']);
?>
<!DOCTYPE html>
<html lang="<?= $limba ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<title>Tablerino - <?= t('audit_title') ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f0f0f5; color: #111; }
.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; z-index: 50; }
.sidebar .logo { margin-bottom: 20px; padding: 4px; display: flex; align-items: center; justify-content: center; }
.sidebar .logo img { height: 190px; width: auto; object-fit: contain; }
.sidebar a { color: #aaa; text-decoration: none; padding: 11px 12px; border-radius: 10px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s, color .15s; font-weight: 500; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }
.lang-switch { margin-top: auto; padding: 8px 4px; display: flex; gap: 6px; justify-content: center; }
.lang-switch a { padding: 5px 10px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; flex: none; }

.main { margin-left: 220px; padding: 28px; }
.main h2 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
.main p.sub { color: #888; font-size: 14px; margin-bottom: 24px; }

/* Alerta parola lipsa */
.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 12px; }
.alert-warn { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.alert-warn a { color: #92400e; font-weight: 700; }

/* Carduri statistici */
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 28px; }
.stat-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.stat-card .val { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 6px; }
.stat-card .lbl { font-size: 12px; color: #888; font-weight: 500; }
.val-rosu { color: #e53e3e; }
.val-portocaliu { color: #f59e0b; }
.val-verde { color: #10b981; }

/* Tabel comenzi */
.tabel-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 24px; }
.tabel-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
.tabel-header h3 { font-size: 15px; font-weight: 700; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { text-align: left; padding: 10px 16px; background: #f8f8fc; color: #888; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; border-bottom: 2px solid #f0f0f0; }
td { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
.badge-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.s-noua { background: #fef3c7; color: #92400e; }
.s-in_pregatire { background: #dbeafe; color: #1e40af; }
.s-plata_aleasa { background: #ede9fe; color: #5b21b6; }
.badge-vechi { padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; }
.vechi-ok { background: #d1fae5; color: #065f46; }
.vechi-warn { background: #fef3c7; color: #92400e; }
.vechi-danger { background: #fee2e2; color: #c62828; }
.btn-inchide { padding: 7px 14px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-inchide:hover { background: #5535e0; }
.gol { text-align: center; padding: 48px; color: #aaa; font-size: 15px; }
.gol .icon { font-size: 40px; margin-bottom: 12px; }

/* Modal confirmare */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.open { display: flex; }
.modal { background: #fff; border-radius: 16px; padding: 32px 28px; width: 100%; max-width: 420px; }
.modal h3 { font-size: 18px; font-weight: 800; margin-bottom: 8px; }
.modal p { color: #888; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
.modal label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.modal input { width: 100%; padding: 11px 14px; border: 2px solid #ddd; border-radius: 10px; font-size: 14px; outline: none; margin-bottom: 20px; }
.modal input:focus { border-color: #6c47ff; }
.modal-btns { display: flex; gap: 10px; }
.btn-cancel { padding: 11px 20px; border: 1px solid #ddd; background: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; flex: 1; }
.btn-confirm { padding: 11px 20px; background: #e53e3e; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; flex: 1; }
.btn-confirm-all { background: #6c47ff; }

.toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; display: none; z-index: 999; color: #fff; }

/* Responsive */
@media (max-width: 768px) {
    .sidebar { position: fixed; left: 0; right: 0; top: auto; bottom: 0; width: 100%; height: 62px; flex-direction: row; padding: 0; justify-content: space-around; align-items: center; border-top: 1px solid #2a2a3e; z-index: 100; }
    .sidebar .logo { display: none; }
    .sidebar .logout { margin-top: 0; }
    .sidebar a { flex-direction: column; gap: 2px; font-size: 10px; padding: 6px 8px; border-radius: 6px; flex: 1; justify-content: center; text-align: center; }
    .main { margin-left: 0; padding: 14px 12px 80px; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    table { font-size: 11px; }
    th, td { padding: 8px 10px; }
    .lang-switch { display: none; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><img src="/uploads/logo-tablerino.png" alt="Tablerino" style="height:190px;width:auto;object-fit:contain;display:block;"></div>
    <a href="/admin/index.php">📋 <?= t('nav_orders') ?></a>
    <a href="/admin/meniu.php">🍕 <?= t('nav_menu') ?></a>
    <a href="/admin/mese.php">🪑 <?= t('nav_tables') ?></a>
    <a href="/admin/istoric.php">📂 <?= t('nav_history') ?></a>
    <a href="/admin/raport.php">📊 <?= t('nav_reports') ?></a>
    <a href="/admin/audit.php" class="activ">🔍 <?= t('nav_audit') ?></a>
    <a href="/admin/setari.php">🎨 <?= t('nav_design') ?></a>
    <a href="/admin/reclame.php">📢 <?= t('nav_ads') ?></a>
    <div class="lang-switch">
        <a href="?lang=ro" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='ro' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇷🇴 RO</a>
        <a href="?lang=en" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='en' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇬🇧 EN</a>
    </div>
    <a href="/admin/logout.php" class="logout">🚪 <?= t('logout') ?></a>
</div>

<div class="main">
    <h2>🔍 <?= t('audit_title') ?></h2>
    <p class="sub"><?= t('audit_desc') ?></p>

    <?php if (!$areParola): ?>
    <div class="alert alert-warn">
        ⚠️ <?= t('audit_no_password') ?> <a href="/admin/setari.php"><?= t('audit_set_password') ?></a>
    </div>
    <?php endif; ?>

    <?php
    $nrActive   = count(array_filter($comenzi, fn($c) => $c['tip_problema'] === 'activa'));
    $nrFaraPlata= count(array_filter($comenzi, fn($c) => $c['tip_problema'] === 'fara_plata'));
    $nrVechi    = count(array_filter($comenzi, fn($c) => $c['minute_vechi'] > 120 && $c['tip_problema'] === 'activa'));
    $totalBlocat= array_sum(array_column($comenzi, 'total'));
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="val <?= $nrActive > 0 ? 'val-portocaliu' : 'val-verde' ?>"><?= $nrActive ?></div>
            <div class="lbl"><?= t('audit_active_orders') ?></div>
        </div>
        <div class="stat-card">
            <div class="val <?= $nrFaraPlata > 0 ? 'val-rosu' : 'val-verde' ?>"><?= $nrFaraPlata ?></div>
            <div class="lbl">Fără metodă de plată</div>
        </div>
        <div class="stat-card">
            <div class="val <?= $totalBlocat > 0 ? 'val-portocaliu' : 'val-verde' ?>"><?= number_format($totalBlocat, 2) ?> lei</div>
            <div class="lbl"><?= t('audit_blocked_value') ?></div>
        </div>
    </div>

    <!-- Tabel comenzi active -->
    <div class="tabel-wrap">
        <div class="tabel-header">
            <h3><?= t('audit_unclosed_orders') ?></h3>
            <?php if ($nrActive > 0): ?>
            <button class="btn-inchide" onclick="deschideModalTot()" <?= !$areParola ? 'disabled title="Setează parola mai întâi"' : '' ?>>
                ⚠️ <?= t('audit_close_all') ?>
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($comenzi)): ?>
        <div class="gol">
            <div class="icon">✅</div>
            <p><?= t('audit_all_ok') ?></p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= t('table') ?></th>
                    <th><?= t('status') ?></th>
                    <th>Problemă</th>
                    <th><?= t('total') ?></th>
                    <th><?= t('audit_open_since') ?></th>
                    <th><?= t('audit_age') ?></th>
                    <th><?= t('action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comenzi as $c):
                    $ore = floor($c['minute_vechi'] / 60);
                    $min = $c['minute_vechi'] % 60;
                    $vecheClasa = $c['minute_vechi'] < 60 ? 'vechi-ok' : ($c['minute_vechi'] < 120 ? 'vechi-warn' : 'vechi-danger');
                    $vecheText = $ore > 0 ? "{$ore}h {$min}m" : "{$min}m";
                    $eActiva = $c['tip_problema'] === 'activa';
                    $eFaraPlata = $c['tip_problema'] === 'fara_plata';
                ?>
                <tr>
                    <td style="color:#aaa;font-size:12px">#<?= $c['id'] ?> <span style="color:#ccc">·</span> <span style="font-weight:700">#<?= $c['nr_ordine_zi'] ?></span></td>
                    <td><strong><?= htmlspecialchars($c['masa_nume']) ?></strong></td>
                    <td><span class="badge-status s-<?= $c['status'] ?>"><?= $c['status'] ?></span></td>
                    <td>
                        <?php if ($eFaraPlata): ?>
                            <span style="background:#fee2e2;color:#c62828;padding:3px 8px;border-radius:8px;font-size:11px;font-weight:700">⚠️ Fără plată</span>
                        <?php else: ?>
                            <span style="background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:8px;font-size:11px;font-weight:700">⏳ Nefinalizată</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= number_format($c['total'], 2) ?> lei</strong></td>
                    <td style="color:#888;font-size:12px"><?= date('d.m H:i', strtotime($c['created_at'])) ?></td>
                    <td><span class="badge-vechi <?= $vecheClasa ?>"><?= $vecheText ?></span></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <?php if ($eFaraPlata): ?>
                            <button class="btn-inchide" style="background:#10b981" onclick="deschideModalPlata(<?= $c['id'] ?>, '<?= htmlspecialchars($c['masa_nume']) ?>')"
                                <?= !$areParola ? 'disabled' : '' ?>>
                                💳 Setează plata
                            </button>
                        <?php endif; ?>
                        <button class="btn-inchide" onclick="deschideModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['masa_nume']) ?>', '<?= $c['status'] ?>')"
                            <?= !$areParola ? 'disabled' : '' ?>>
                            <?= t('audit_close') ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal setare plata -->
<div class="modal-overlay" id="modalOverlayPlata">
    <div class="modal">
        <h3>💳 Setează metoda de plată</h3>
        <p id="modalDescPlata"></p>
        <label>Metodă de plată</label>
        <div style="display:flex;gap:10px;margin-bottom:20px">
            <button id="btnCash" onclick="selecteazaPlata('cash')" style="flex:1;padding:12px;border:2px solid #ddd;border-radius:10px;font-size:14px;cursor:pointer;font-weight:600;background:#fff">💵 Cash</button>
            <button id="btnCard" onclick="selecteazaPlata('card')" style="flex:1;padding:12px;border:2px solid #ddd;border-radius:10px;font-size:14px;cursor:pointer;font-weight:600;background:#fff">💳 Card</button>
        </div>
        <label><?= t('audit_password_label') ?></label>
        <input type="password" id="modalParolaPlata" placeholder="••••••••" onkeydown="if(event.key==='Enter') confirmaPlata()">
        <div class="modal-btns" style="margin-top:20px">
            <button class="btn-cancel" onclick="inchideModalPlata()"><?= t('cancel') ?></button>
            <button class="btn-confirm btn-confirm-all" onclick="confirmaPlata()">Salvează</button>
        </div>
    </div>
</div>

<!-- Modal inchidere individuala -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h3>🔒 <?= t('audit_close_order') ?></h3>
        <p id="modalDesc"></p>
        <label><?= t('audit_password_label') ?></label>
        <input type="password" id="modalParola" placeholder="••••••••" onkeydown="if(event.key==='Enter') confirmaInchidere()">
        <div class="modal-btns">
            <button class="btn-cancel" onclick="inchideModal()"><?= t('cancel') ?></button>
            <button class="btn-confirm" onclick="confirmaInchidere()"><?= t('audit_confirm_close') ?></button>
        </div>
    </div>
</div>

<!-- Modal inchidere toate -->
<div class="modal-overlay" id="modalOverlayTot">
    <div class="modal">
        <h3>⚠️ <?= t('audit_close_all_title') ?></h3>
        <p><?= t('audit_close_all_desc') ?></p>
        <label><?= t('audit_password_label') ?></label>
        <input type="password" id="modalParolaTot" placeholder="••••••••" onkeydown="if(event.key==='Enter') confirmaInchidereTot()">
        <div class="modal-btns">
            <button class="btn-cancel" onclick="inchideModalTot()"><?= t('cancel') ?></button>
            <button class="btn-confirm btn-confirm-all" onclick="confirmaInchidereTot()"><?= t('audit_confirm_close_all') ?></button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
let comandaActivaId = null;
let metodaSelectata = null;

function selecteazaPlata(metoda) {
    metodaSelectata = metoda;
    document.getElementById('btnCash').style.borderColor = metoda === 'cash' ? '#10b981' : '#ddd';
    document.getElementById('btnCash').style.background  = metoda === 'cash' ? '#d1fae5' : '#fff';
    document.getElementById('btnCard').style.borderColor = metoda === 'card' ? '#6c47ff' : '#ddd';
    document.getElementById('btnCard').style.background  = metoda === 'card' ? '#ede9fe' : '#fff';
}

function deschideModalPlata(id, masa) {
    comandaActivaId = id;
    metodaSelectata = null;
    document.getElementById('modalDescPlata').innerHTML = `Comanda de la masa <strong>${masa}</strong> — alege cum a plătit clientul.`;
    document.getElementById('modalParolaPlata').value = '';
    document.getElementById('btnCash').style.cssText = 'flex:1;padding:12px;border:2px solid #ddd;border-radius:10px;font-size:14px;cursor:pointer;font-weight:600;background:#fff';
    document.getElementById('btnCard').style.cssText = 'flex:1;padding:12px;border:2px solid #ddd;border-radius:10px;font-size:14px;cursor:pointer;font-weight:600;background:#fff';
    document.getElementById('modalOverlayPlata').classList.add('open');
    setTimeout(() => document.getElementById('modalParolaPlata').focus(), 100);
}

function inchideModalPlata() {
    document.getElementById('modalOverlayPlata').classList.remove('open');
    comandaActivaId = null;
    metodaSelectata = null;
}

async function confirmaPlata() {
    if (!metodaSelectata) { showToast('Alege metoda de plată', false); return; }
    const parola = document.getElementById('modalParolaPlata').value;
    if (!parola) { showToast('Introdu parola', false); return; }
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'audit_seteaza_plata', comanda_id: comandaActivaId, metoda_plata: metodaSelectata, parola })
    });
    const d = await r.json();
    if (d.ok) {
        inchideModalPlata();
        showToast('Plată salvată!');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(d.msg || '<?= t('audit_wrong_password') ?>', false);
        document.getElementById('modalParolaPlata').value = '';
    }
}

function showToast(msg, ok = true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = ok ? '#10b981' : '#e53e3e';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

function deschideModal(id, masa, status) {
    comandaActivaId = id;
    document.getElementById('modalDesc').innerHTML =
        `<?= t('audit_close_desc') ?> <strong>${masa}</strong> (${status}).`;
    document.getElementById('modalParola').value = '';
    document.getElementById('modalOverlay').classList.add('open');
    setTimeout(() => document.getElementById('modalParola').focus(), 100);
}

function inchideModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    comandaActivaId = null;
}

async function confirmaInchidere() {
    const parola = document.getElementById('modalParola').value;
    if (!parola) return;
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'audit_inchide', comanda_id: comandaActivaId, parola })
    });
    const d = await r.json();
    if (d.ok) {
        inchideModal();
        showToast('<?= t('audit_closed_ok') ?>');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(d.msg || '<?= t('audit_wrong_password') ?>', false);
        document.getElementById('modalParola').value = '';
        document.getElementById('modalParola').focus();
    }
}

function deschideModalTot() {
    document.getElementById('modalParolaTot').value = '';
    document.getElementById('modalOverlayTot').classList.add('open');
    setTimeout(() => document.getElementById('modalParolaTot').focus(), 100);
}

function inchideModalTot() {
    document.getElementById('modalOverlayTot').classList.remove('open');
}

async function confirmaInchidereTot() {
    const parola = document.getElementById('modalParolaTot').value;
    if (!parola) return;
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'audit_inchide_tot', parola })
    });
    const d = await r.json();
    if (d.ok) {
        inchideModalTot();
        showToast(`<?= t('audit_all_closed_ok') ?>`);
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(d.msg || '<?= t('audit_wrong_password') ?>', false);
        document.getElementById('modalParolaTot').value = '';
        document.getElementById('modalParolaTot').focus();
    }
}
</script>
</body>
</html>
