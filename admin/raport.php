<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$limba = $rest['limba'];
$rid = $rest['id'];

// Data selectata (default azi)
$data = $_GET['data'] ?? date('Y-m-d');
$dataAfisata = date('d.m.Y', strtotime($data));

// === DATE RAPORT ===

// Sumar general
$qsumar = db()->prepare("
    SELECT
        COUNT(*) as total_comenzi,
        COALESCE(SUM(cp.total_comanda), 0) as total_vanzari,
        COALESCE(SUM(CASE WHEN c.metoda_plata = 'cash' THEN cp.total_comanda ELSE 0 END), 0) as total_cash,
        COALESCE(SUM(CASE WHEN c.metoda_plata = 'card' THEN cp.total_comanda ELSE 0 END), 0) as total_card,
        COUNT(CASE WHEN c.metoda_plata = 'cash' THEN 1 END) as nr_cash,
        COUNT(CASE WHEN c.metoda_plata = 'card' THEN 1 END) as nr_card,
        AVG(cp.total_comanda) as medie_comanda
    FROM comenzi c
    JOIN (
        SELECT comanda_id, SUM(total) as total_comanda
        FROM comanda_produse
        GROUP BY comanda_id
    ) cp ON cp.comanda_id = c.id
    WHERE c.restaurant_id = ? AND DATE(c.created_at) = ? AND c.status = 'servita'
");
$qsumar->execute([$rid, $data]);
$sumar = $qsumar->fetch();

// Top produse vandute
$qtop = db()->prepare("
    SELECT
        cp.nume_produs,
        SUM(cp.cantitate) as cantitate_totala,
        SUM(cp.total) as total_produs,
        COUNT(DISTINCT cp.comanda_id) as nr_comenzi,
        cp.pret_unitar
    FROM comanda_produse cp
    JOIN comenzi c ON c.id = cp.comanda_id
    WHERE c.restaurant_id = ? AND DATE(c.created_at) = ? AND c.status = 'servita'
    GROUP BY cp.nume_produs, cp.pret_unitar
    ORDER BY cantitate_totala DESC
    LIMIT 20
");
$qtop->execute([$rid, $data]);
$topProduse = $qtop->fetchAll();

// Detaliu per masa
$qmese = db()->prepare("
    SELECT
        m.nume as masa_nume,
        COUNT(c.id) as nr_comenzi,
        SUM(cp.total_comanda) as total_masa,
        c.metoda_plata,
        MIN(c.created_at) as prima_comanda,
        MAX(c.created_at) as ultima_comanda
    FROM comenzi c
    JOIN mese m ON m.id = c.masa_id
    JOIN (
        SELECT comanda_id, SUM(total) as total_comanda
        FROM comanda_produse GROUP BY comanda_id
    ) cp ON cp.comanda_id = c.id
    WHERE c.restaurant_id = ? AND DATE(c.created_at) = ? AND c.status = 'servita'
    GROUP BY m.id, m.nume, c.metoda_plata
    ORDER BY total_masa DESC
");
$qmese->execute([$rid, $data]);
$perMasa = $qmese->fetchAll();

// Comenzi pe ore (distributie)
$qore = db()->prepare("
    SELECT
        HOUR(c.created_at) as ora,
        COUNT(*) as nr_comenzi,
        SUM(cp.total_comanda) as total_ora
    FROM comenzi c
    JOIN (
        SELECT comanda_id, SUM(total) as total_comanda
        FROM comanda_produse GROUP BY comanda_id
    ) cp ON cp.comanda_id = c.id
    WHERE c.restaurant_id = ? AND DATE(c.created_at) = ? AND c.status = 'servita'
    GROUP BY HOUR(c.created_at)
    ORDER BY ora
");
$qore->execute([$rid, $data]);
$perOra = $qore->fetchAll();

// Numar mese active azi
$qmaseActive = db()->prepare("
    SELECT COUNT(DISTINCT masa_id) as mese_active
    FROM comenzi
    WHERE restaurant_id = ? AND DATE(created_at) = ? AND status = 'servita'
");
$qmaseActive->execute([$rid, $data]);
$maseActive = (int)$qmaseActive->fetchColumn();

$numeRest = $rest['nume'];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Raport <?= $dataAfisata ?> — <?= htmlspecialchars($numeRest) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
:root { --primar: #6c47ff; --sidebar-w: 220px; }
body { font-family: system-ui, sans-serif; background: #f0f0f5; color: #111; }

.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-w); background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; z-index: 50; }
.sidebar .logo { margin-bottom: 20px; padding: 4px; display: flex; align-items: center; justify-content: center; }
.sidebar .logo img { height: 64px; width: auto; object-fit: contain; background: #fff; border-radius: 10px; padding: 6px; }
.sidebar a { color: #aaa; text-decoration: none; padding: 11px 12px; border-radius: 10px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s, color .15s; font-weight: 500; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }

.main { margin-left: var(--sidebar-w); padding: 28px; }

/* Header raport */
.raport-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px; }
.raport-header h2 { font-size: 22px; font-weight: 800; }
.raport-header p { color: #888; font-size: 14px; margin-top: 2px; }
.header-actiuni { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.input-data { padding: 9px 14px; border: 2px solid #ddd; border-radius: 10px; font-size: 14px; outline: none; }
.input-data:focus { border-color: var(--primar); }
.btn-cauta { padding: 9px 18px; background: var(--primar); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-pdf { padding: 9px 18px; background: #e53e3e; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }

/* Carduri sumar */
.sumar-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
.sumar-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-bottom: 3px solid #eee; }
.sumar-card .icon { font-size: 24px; margin-bottom: 10px; }
.sumar-card .val { font-size: 26px; font-weight: 800; color: var(--primar); line-height: 1; }
.sumar-card .val.verde { color: #10b981; }
.sumar-card .val.albastru { color: #3b82f6; }
.sumar-card .val.portocaliu { color: #f59e0b; }
.sumar-card .val.rosu { color: #e53e3e; }
.sumar-card .lbl { font-size: 12px; color: #888; margin-top: 6px; font-weight: 500; }

/* Sectiuni */
.sectiune { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px; }
.sectiune h3 { font-size: 16px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }

/* Tabel */
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th { text-align: left; padding: 10px 14px; background: #f8f8fc; color: #888; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; border-bottom: 2px solid #f0f0f0; }
td { padding: 12px 14px; border-bottom: 1px solid #f5f5f5; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #fafafa; }
.rank { font-weight: 800; color: #ccc; font-size: 13px; width: 30px; }
.rank.gold { color: #f59e0b; }
.rank.silver { color: #888; }
.rank.bronze { color: #cd7f32; }
.badge-metoda { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-cash { background: #fef3c7; color: #92400e; }
.badge-card { background: #dbeafe; color: #1e40af; }

/* Grafic ore */
.grafic-ore { display: flex; align-items: flex-end; gap: 8px; height: 120px; padding: 0 4px; }
.bara-ora { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; height: 100%; justify-content: flex-end; }
.bara-fill { background: var(--primar); border-radius: 6px 6px 0 0; width: 100%; min-height: 4px; transition: all .3s; opacity: .85; }
.bara-ora:hover .bara-fill { opacity: 1; }
.bara-label { font-size: 10px; color: #aaa; white-space: nowrap; }
.bara-val { font-size: 10px; font-weight: 700; color: #555; }

/* Gol */
.gol { text-align: center; padding: 40px; color: #bbb; font-size: 15px; }

/* Print */
@media print {
    .sidebar, .header-actiuni, .no-print { display: none !important; }
    .main { margin-left: 0; padding: 20px; }
    body { background: #fff; }
    .sectiune, .sumar-card { box-shadow: none; border: 1px solid #eee; }
    .sumar-grid { grid-template-columns: repeat(4, 1fr); }
    .raport-header { margin-bottom: 16px; }
    table { font-size: 12px; }
    th, td { padding: 8px 10px; }
    .bara-fill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { margin: 1.5cm; }
}

/* Responsive */
@media (max-width: 768px) {
    :root { --sidebar-w: 0px; }
    .sidebar { display: none; }
    .main { margin-left: 0; padding: 16px 12px; }
    .sumar-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .raport-header { flex-direction: column; align-items: flex-start; }
    table { font-size: 12px; }
    th, td { padding: 8px 10px; }
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
    <a href="/admin/raport.php" class="activ">📊 <?= t('nav_reports') ?></a>
    <a href="/admin/setari.php">🎨 <?= t('nav_design') ?></a>
    <a href="/admin/reclame.php">📢 <?= t('nav_ads') ?></a>
        <div style="margin-top:auto;padding:8px 4px;display:flex;gap:6px;justify-content:center">
        <a href="?lang=ro" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='ro' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇷🇴 RO</a>
        <a href="?lang=en" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='en' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇬🇧 EN</a>
    </div>
    <a href="/admin/logout.php" class="logout">🚪 <?= t('logout') ?></a>
</div>

<div class="main">
    <div class="raport-header">
        <div>
            <h2>📊 <?= t('report_title') ?> — <?= $dataAfisata ?></h2>
            <p><?= htmlspecialchars($numeRest) ?></p>
        </div>
        <div class="header-actiuni">
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <input type="date" name="data" class="input-data" value="<?= htmlspecialchars($data) ?>" max="<?= date('Y-m-d') ?>">
                <button type="submit" class="btn-cauta"><?= t('search') ?></button>
            </form>
            <button class="btn-pdf" onclick="window.print()"><?= t('export_pdf') ?></button>
        </div>
    </div>

    <?php if ($sumar['total_comenzi'] == 0): ?>
    <div class="sectiune">
        <div class="gol">📭 <?= t('no_report_data') ?> în data de <?= $dataAfisata ?></div>
    </div>
    <?php else: ?>

    <!-- Sumar carduri -->
    <div class="sumar-grid">
        <div class="sumar-card">
            <div class="icon">🧾</div>
            <div class="val"><?= $sumar['total_comenzi'] ?></div>
            <div class="lbl"><?= t('orders_finished') ?></div>
        </div>
        <div class="sumar-card">
            <div class="icon">💰</div>
            <div class="val verde"><?= number_format($sumar['total_vanzari'], 2, '.', ',') ?> lei</div>
            <div class="lbl"><?= t('total_sales') ?></div>
        </div>
        <div class="sumar-card">
            <div class="icon">💵</div>
            <div class="val portocaliu"><?= number_format($sumar['total_cash'], 2, '.', ',') ?> lei</div>
            <div class="lbl">Cash (<?= $sumar['nr_cash'] ?> comenzi)</div>
        </div>
        <div class="sumar-card">
            <div class="icon">💳</div>
            <div class="val albastru"><?= number_format($sumar['total_card'], 2, '.', ',') ?> lei</div>
            <div class="lbl">Card (<?= $sumar['nr_card'] ?> comenzi)</div>
        </div>
        <div class="sumar-card">
            <div class="icon">📈</div>
            <div class="val"><?= number_format($sumar['medie_comanda'], 2, '.', ',') ?> lei</div>
            <div class="lbl"><?= t('average_order') ?></div>
        </div>
        <div class="sumar-card">
            <div class="icon">🪑</div>
            <div class="val rosu"><?= $maseActive ?></div>
            <div class="lbl"><?= t('active_tables') ?></div>
        </div>
    </div>

    <!-- Grafic pe ore -->
    <?php if (!empty($perOra)): ?>
    <div class="sectiune">
        <h3>🕐 <?= t('orders_per_hour') ?></h3>
        <?php
        $maxOra = max(array_column($perOra, 'nr_comenzi'));
        $oreIndex = [];
        foreach ($perOra as $o) $oreIndex[$o['ora']] = $o;
        ?>
        <div class="grafic-ore">
            <?php for ($h = 8; $h <= 23; $h++): ?>
            <?php $o = $oreIndex[$h] ?? null; $h_display = sprintf('%02d', $h); ?>
            <div class="bara-ora">
                <?php if ($o): ?>
                <div class="bara-val"><?= $o['nr_comenzi'] ?></div>
                <div class="bara-fill" style="height:<?= round(($o['nr_comenzi'] / $maxOra) * 90) ?>px" title="<?= $h_display ?>:00 — <?= $o['nr_comenzi'] ?> comenzi, <?= number_format($o['total_ora'], 2) ?> lei"></div>
                <?php else: ?>
                <div class="bara-val" style="opacity:0">0</div>
                <div class="bara-fill" style="height:4px;opacity:.15"></div>
                <?php endif; ?>
                <div class="bara-label"><?= $h_display ?></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top produse -->
    <?php if (!empty($topProduse)): ?>
    <div class="sectiune">
        <h3>🏆 <?= t('top_products') ?></h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produs</th>
                    <th>Preț unitar</th>
                    <th>Cantitate</th>
                    <th>Comenzi</th>
                    <th style="text-align:right"><?= t('total') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProduse as $i => $p): ?>
                <tr>
                    <td class="rank <?= $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')) ?>">
                        <?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ($i + 1))) ?>
                    </td>
                    <td><strong><?= htmlspecialchars($p['nume_produs']) ?></strong></td>
                    <td><?= number_format($p['pret_unitar'], 2, '.', ',') ?> lei</td>
                    <td><strong><?= $p['cantitate_totala'] ?></strong> buc</td>
                    <td><?= $p['nr_comenzi'] ?> comenzi</td>
                    <td style="text-align:right;font-weight:700;color:#6c47ff"><?= number_format($p['total_produs'], 2, '.', ',') ?> lei</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Detaliu per masa -->
    <?php if (!empty($perMasa)): ?>
    <div class="sectiune">
        <h3>🪑 <?= t('per_table') ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Masă</th>
                    <th>Comenzi</th>
                    <th>Metodă plată</th>
                    <th><?= t('first_order') ?></th>
                    <th><?= t('last_order') ?></th>
                    <th style="text-align:right"><?= t('total') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($perMasa as $m): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['masa_nume']) ?></strong></td>
                    <td><?= $m['nr_comenzi'] ?></td>
                    <td>
                        <?php if ($m['metoda_plata']): ?>
                        <span class="badge-metoda badge-<?= $m['metoda_plata'] ?>">
                            <?= $m['metoda_plata'] === 'cash' ? '💵 Cash' : '💳 Card' ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#ccc">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('H:i', strtotime($m['prima_comanda'])) ?></td>
                    <td><?= date('H:i', strtotime($m['ultima_comanda'])) ?></td>
                    <td style="text-align:right;font-weight:700;color:#6c47ff"><?= number_format($m['total_masa'], 2, '.', ',') ?> lei</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8f8fc">
                    <td colspan="5" style="font-weight:700;padding:12px 14px"><?= t('daily_total') ?></td>
                    <td style="text-align:right;font-weight:800;font-size:16px;color:#10b981;padding:12px 14px"><?= number_format($sumar['total_vanzari'], 2, '.', ',') ?> lei</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <div class="no-print" style="text-align:center;color:#ccc;font-size:12px;padding:20px">
        Raport generat la <?= date('H:i:s') ?> · <?= htmlspecialchars($numeRest) ?>
    </div>
</div>

</body>
</html>
