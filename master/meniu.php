<?php
require_once __DIR__ . '/../config.php';
authMaster();

$rid = (int)($_GET['id'] ?? 0);
if (!$rid) { header('Location: /master/index.php'); exit; }

$qr = db()->prepare('SELECT * FROM restaurante WHERE id = ?');
$qr->execute([$rid]);
$restaurant = $qr->fetch();
if (!$restaurant) { header('Location: /master/index.php'); exit; }

// Incarcare categorii si produse
$qc = db()->prepare('SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id');
$qc->execute([$rid]);
$categorii = $qc->fetchAll();

foreach ($categorii as &$cat) {
    $qp = db()->prepare('SELECT * FROM meniu_produse WHERE categorie_id = ? ORDER BY ordine, id');
    $qp->execute([$cat['id']]);
    $cat['produse'] = $qp->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meniu — <?= htmlspecialchars($restaurant['nume']) ?></title>
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
.main p.sub { color: #666; font-size: 13px; margin-bottom: 28px; }

/* Stats rapide */
.stats { display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap; }
.stat { background: #1a1a2e; border-radius: 10px; padding: 14px 18px; border: 1px solid #2a2a3e; }
.stat .val { font-size: 22px; font-weight: 800; color: #6c47ff; }
.stat .lbl { font-size: 12px; color: #666; margin-top: 2px; }

/* Categorii */
.categorie { margin-bottom: 32px; }
.cat-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #2a2a3e; }
.cat-header h3 { font-size: 15px; color: #fff; font-weight: 700; }
.cat-count { background: #2a2a3e; color: #666; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; }

/* Grid produse */
.produse-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.produs-card { background: #1a1a2e; border-radius: 12px; overflow: hidden; border: 1px solid #2a2a3e; }
.produs-poza { height: 120px; background: linear-gradient(135deg, #1f1f3e, #2a1a3e); display: flex; align-items: center; justify-content: center; font-size: 36px; overflow: hidden; }
.produs-poza img { width: 100%; height: 100%; object-fit: cover; }
.produs-body { padding: 12px 14px; }
.produs-body h4 { font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px; }
.produs-body p { font-size: 12px; color: #555; margin-bottom: 8px; min-height: 16px; }
.produs-footer { display: flex; justify-content: space-between; align-items: center; }
.produs-pret { font-weight: 700; font-size: 15px; color: #6c47ff; }
.badge-disponibil { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.badge-disponibil.da { background: rgba(16,185,129,0.15); color: #10b981; }
.badge-disponibil.nu { background: rgba(239,68,68,0.15); color: #ef4444; }

.gol { text-align: center; color: #444; padding: 40px; font-size: 14px; }
.gol-cat { color: #444; font-size: 13px; padding: 20px 0; }
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
    <p class="sub">Meniu complet · <?= htmlspecialchars($restaurant['email']) ?></p>

    <?php
    $totalProduse = array_sum(array_map(fn($c) => count($c['produse']), $categorii));
    $disponibile = 0;
    foreach ($categorii as $cat) {
        foreach ($cat['produse'] as $p) {
            if ($p['disponibil']) $disponibile++;
        }
    }
    ?>

    <div class="stats">
        <div class="stat"><div class="val"><?= count($categorii) ?></div><div class="lbl">Categorii</div></div>
        <div class="stat"><div class="val"><?= $totalProduse ?></div><div class="lbl">Total produse</div></div>
        <div class="stat"><div class="val" style="color:#10b981"><?= $disponibile ?></div><div class="lbl">Disponibile</div></div>
        <div class="stat"><div class="val" style="color:#ef4444"><?= $totalProduse - $disponibile ?></div><div class="lbl">Indisponibile</div></div>
    </div>

    <?php if (empty($categorii)): ?>
        <div class="gol">Nicio categorie adăugată în meniu.</div>
    <?php else: ?>
        <?php foreach ($categorii as $cat): ?>
        <div class="categorie">
            <div class="cat-header">
                <h3><?= htmlspecialchars($cat['nume']) ?></h3>
                <span class="cat-count"><?= count($cat['produse']) ?> produse</span>
            </div>
            <?php if (empty($cat['produse'])): ?>
                <p class="gol-cat">Niciun produs în această categorie.</p>
            <?php else: ?>
            <div class="produse-grid">
                <?php foreach ($cat['produse'] as $p): ?>
                <div class="produs-card">
                    <div class="produs-poza">
                        <?php if ($p['poza']): ?>
                            <img src="/<?= htmlspecialchars($p['poza']) ?>" alt="<?= htmlspecialchars($p['nume']) ?>">
                        <?php else: ?>
                            🍽️
                        <?php endif; ?>
                    </div>
                    <div class="produs-body">
                        <h4><?= htmlspecialchars($p['nume']) ?></h4>
                        <p><?= htmlspecialchars($p['descriere'] ?? '') ?></p>
                        <div class="produs-footer">
                            <span class="produs-pret"><?= number_format($p['pret'], 2) ?> lei</span>
                            <span class="badge-disponibil <?= $p['disponibil'] ? 'da' : 'nu' ?>">
                                <?= $p['disponibil'] ? '✓ Disponibil' : '✗ Indisponibil' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
