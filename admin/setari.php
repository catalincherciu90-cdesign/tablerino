<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../teme.php';
$rest = authRestaurant();
$limba = $rest['limba'];
$temaActiva = $rest['tema'];

// Incarca datele complete ale restaurantului
$qr = db()->prepare('SELECT logo, text_bun_venit, bg_imagine, facebook, instagram, tiktok, whatsapp FROM restaurante WHERE id = ?');
$qr->execute([$rest['id']]);
$profil = $qr->fetch();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino - Design</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f0f0f5; color: #111; }
.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
.sidebar .logo { margin-bottom: 20px; padding: 4px; display: flex; align-items: center; justify-content: center; }
.sidebar .logo img { height: 64px; width: auto; object-fit: contain; background: #fff; border-radius: 10px; padding: 6px; }
.sidebar a { color: #aaa; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: background .15s, color .15s; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }
.main { margin-left: 220px; padding: 32px; max-width: 960px; }
.main h2 { font-size: 20px; margin-bottom: 6px; }
.main p.sub { color: #888; font-size: 14px; margin-bottom: 32px; }
.sectiune { background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); margin-bottom: 24px; }
.sectiune h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
.sectiune p.desc { font-size: 13px; color: #888; margin-bottom: 20px; }
.upload-zone { border: 2px dashed #ddd; border-radius: 12px; padding: 28px; text-align: center; cursor: pointer; transition: all .2s; position: relative; }
.upload-zone:hover { border-color: #6c47ff; background: #f5f3ff; }
.upload-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
.upload-zone .icon { font-size: 32px; margin-bottom: 8px; }
.upload-zone p { font-size: 13px; color: #888; }
.upload-zone p strong { color: #6c47ff; }
.preview-wrap { position: relative; display: inline-block; margin-top: 16px; }
.preview-wrap img { max-height: 120px; max-width: 100%; border-radius: 10px; border: 1px solid #eee; display: block; }
.preview-wrap.bg img { max-height: 160px; max-width: 300px; object-fit: cover; }
.btn-sterge-img { position: absolute; top: -8px; right: -8px; background: #e53e3e; color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.field label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px; }
.field input { width: 100%; padding: 11px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; outline: none; transition: border-color .2s; }
.field input:focus { border-color: #6c47ff; }
.field small { color: #aaa; font-size: 12px; margin-top: 6px; display: block; }
.btn-salva { padding: 10px 22px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 16px; }
.btn-salva:hover { background: #5a38e0; }
.teme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
.tema-card { border-radius: 12px; overflow: hidden; cursor: pointer; border: 3px solid transparent; transition: all .2s; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
.tema-card:hover { transform: translateY(-2px); }
.tema-card.activa { border-color: #6c47ff; box-shadow: 0 0 0 4px rgba(108,71,255,0.2); }
.tema-preview { height: 100px; display: flex; flex-direction: column; }
.tema-header-prev { padding: 10px 12px; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.tema-body-prev { flex: 1; padding: 8px 12px; display: flex; flex-direction: column; gap: 5px; }
.prev-card { height: 16px; border-radius: 4px; opacity: .7; }
.prev-btn { height: 12px; border-radius: 20px; width: 55%; opacity: .9; }
.tema-info { background: #fff; padding: 10px 12px; }
.tema-info h4 { font-size: 13px; font-weight: 700; }
.tema-info .check { color: #6c47ff; font-size: 11px; font-weight: 600; display: none; }
.tema-card.activa .tema-info .check { display: block; }
.toast { position: fixed; bottom: 24px; right: 24px; background: #10b981; color: #fff; padding: 12px 20px; border-radius: 8px; font-size: 13px; display: none; z-index: 999; }
.toast.err { background: #e53e3e; }
@media (max-width: 768px) {
    .header-mobil { display: flex !important; }
    .sidebar { position: fixed; left: 0; right: 0; top: auto; bottom: 0; width: 100%; height: 62px; flex-direction: row; padding: 0; justify-content: space-around; align-items: center; border-top: 1px solid #2a2a3e; z-index: 100; }
    .sidebar .logo { display: none; }
    .sidebar .logout { margin-top: 0; }
    .sidebar a { flex-direction: column; gap: 2px; font-size: 10px; padding: 6px 8px; border-radius: 6px; flex: 1; justify-content: center; text-align: center; font-weight: 600; }
    .main { margin-left: 0 !important; padding: 14px 12px 80px !important; }
    .teme-grid { grid-template-columns: repeat(2, 1fr) !important; }
    .sectiune { padding: 18px; }
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
    <a href="/admin/setari.php" class="activ">🎨 <?= t('nav_design') ?></a>
    <a href="/admin/reclame.php">📢 <?= t('nav_ads') ?></a>
    <a href="/admin/audit.php">🔍 <?= t('nav_audit') ?></a>
    <div style="margin-top:auto;padding:8px 4px;display:flex;gap:6px;justify-content:center">
        <a href="?lang=ro" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='ro' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇷🇴 RO</a>
        <a href="?lang=en" style="padding:5px 10px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $limba==='en' ? 'background:#6c47ff;color:#fff' : 'color:#aaa' ?>">🇬🇧 EN</a>
    </div>
    <a href="/admin/logout.php" class="logout">🚪 <?= t('logout') ?></a>
</div>

<div class="main">
    <h2><?= t('design_title') ?></h2>
    <p class="sub"><?= t('design_desc') ?></p>

    <!-- Logo -->
    <div class="sectiune">
        <h3>🖼️ <?= t('logo') ?></h3>
        <p class="desc"><?= t('logo_desc') ?></p>
        <div class="upload-zone" onclick="document.getElementById('inputLogo').click()">
            <input type="file" id="inputLogo" accept="image/jpeg,image/png,image/webp,image/svg+xml" onchange="uploadImagine('logo', this)" style="opacity:0;position:absolute;inset:0;cursor:pointer">
            <div class="icon">📁</div>
            <p><?= t('click_select_logo') ?><br><strong>JPG, PNG, SVG, WEBP</strong> · max 3MB</p>
        </div>
        <?php if ($profil['logo']): ?>
        <div class="preview-wrap" id="previewLogo">
            <img src="/<?= htmlspecialchars($profil['logo']) ?>" alt="Logo">
            <button class="btn-sterge-img" onclick="stergeImagine('logo')">×</button>
        </div>
        <?php else: ?>
        <div id="previewLogo"></div>
        <?php endif; ?>
        <div id="progressLogo" style="font-size:12px;color:#6c47ff;margin-top:8px;display:none"><?= t('loading') ?></div>
    </div>

    <!-- Text bun venit -->
    <div class="sectiune">
        <h3>👋 <?= t('welcome_text') ?></h3>
        <p class="desc"><?= t('welcome_text_desc') ?></p>
        <div class="field">
            <label><?= t('welcome_label') ?></label>
            <input type="text" id="textBunVenit" value="<?= htmlspecialchars($profil['text_bun_venit'] ?? '') ?>" placeholder="<?= t('welcome_ph') ?>">
            <small><?= t('max_80_chars') ?></small>
        </div>
        <button class="btn-salva" onclick="salveazaText()"><?= t('save_text') ?></button>
    </div>

    <!-- Social media -->
    <div class="sectiune">
        <h3>📱 <?= t('social_media') ?></h3>
        <p class="desc"><?= t('social_desc') ?></p>
        <div class="field" style="margin-bottom:14px">
            <label>🔵 Facebook</label>
            <input type="url" id="smFacebook" value="<?= htmlspecialchars($profil['facebook'] ?? '') ?>" placeholder="https://facebook.com/your-restaurant">
        </div>
        <div class="field" style="margin-bottom:14px">
            <label>📸 Instagram</label>
            <input type="url" id="smInstagram" value="<?= htmlspecialchars($profil['instagram'] ?? '') ?>" placeholder="https://instagram.com/your-restaurant">
        </div>
        <div class="field" style="margin-bottom:14px">
            <label>🎵 TikTok</label>
            <input type="url" id="smTiktok" value="<?= htmlspecialchars($profil['tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@your-restaurant">
        </div>
        <div class="field" style="margin-bottom:14px">
            <label>💬 WhatsApp</label>
            <input type="tel" id="smWhatsapp" value="<?= htmlspecialchars($profil['whatsapp'] ?? '') ?>" placeholder="407XXXXXXXX">
            <small><?= t('whatsapp_example') ?></small>
        </div>
        <button class="btn-salva" onclick="salveazaSocial()"><?= t('save_social') ?></button>
    </div>

    <!-- Imagine fundal -->
    <div class="sectiune">
        <h3>🖼️ <?= t('bg_image') ?></h3>
        <p class="desc"><?= t('bg_image_desc') ?></p>
        <div class="upload-zone" onclick="document.getElementById('inputBg').click()">
            <input type="file" id="inputBg" accept="image/jpeg,image/png,image/webp" onchange="uploadImagine('bg', this)" style="opacity:0;position:absolute;inset:0;cursor:pointer">
            <div class="icon">🖼️</div>
            <p><?= t('click_select_bg') ?><br><strong>JPG, PNG, WEBP</strong> · max 3MB</p>
        </div>
        <?php if ($profil['bg_imagine']): ?>
        <div class="preview-wrap bg" id="previewBg">
            <img src="/<?= htmlspecialchars($profil['bg_imagine']) ?>" alt="Fundal">
            <button class="btn-sterge-img" onclick="stergeImagine('bg_imagine')">×</button>
        </div>
        <?php else: ?>
        <div id="previewBg"></div>
        <?php endif; ?>
        <div id="progressBg" style="font-size:12px;color:#6c47ff;margin-top:8px;display:none"><?= t('loading') ?></div>
    </div>

    <!-- Teme -->
    <div class="sectiune">
        <h3>🎨 <?= t('theme') ?></h3>
        <p class="desc"><?= t('theme_desc') ?></p>
        <div class="teme-grid">
            <?php foreach (TEME as $slug => $t): ?>
            <div class="tema-card <?= $slug === $temaActiva ? 'activa' : '' ?>" onclick="alegeaTema('<?= $slug ?>')">
                <div class="tema-preview" style="background: <?= $t['bg'] ?>; font-family: <?= $t['font'] ?>;">
                    <div class="tema-header-prev" style="background: <?= $t['header_bg'] ?>; color: <?= $t['header_text'] ?>;">
                        <?= $t['nume'] ?>
                    </div>
                    <div class="tema-body-prev" style="background: <?= $t['pattern'] !== 'none' ? $t['pattern'] . ', ' . $t['bg'] : $t['bg'] ?>">
                        <div class="prev-card" style="background: <?= $t['card_bg'] ?>; border-radius: <?= $t['border_r'] ?>; border: 1px solid rgba(0,0,0,0.08)"></div>
                        <div class="prev-card" style="background: <?= $t['card_bg'] ?>; border-radius: <?= $t['border_r'] ?>; width: 80%; border: 1px solid rgba(0,0,0,0.08)"></div>
                        <div class="prev-btn" style="background: <?= $t['primar'] ?>; border-radius: <?= $t['border_r'] ?>"></div>
                    </div>
                </div>
                <div class="tema-info">
                    <h4><?= $t['nume'] ?></h4>
                    <div class="check">✓ <?= t('active_theme') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Parolă audit comenzi -->
    <div class="sectiune">
        <h3>🔒 <?= t('audit_password_section') ?></h3>
        <p class="desc"><?= t('audit_password_desc') ?></p>
        <div class="field">
            <label><?= t('audit_password_new') ?></label>
            <input type="password" id="parolaAudit" placeholder="••••••••" autocomplete="new-password">
        </div>
        <button class="btn-salva" onclick="salveazaParolaAudit()"><?= t('audit_password_save') ?></button>
    <!-- Parolă audit comenzi -->
    <div class="sectiune">
        <h3>🔒 <?= t('audit_password_section') ?></h3>
        <p class="desc"><?= t('audit_password_desc') ?></p>
        <div class="field">
            <label><?= t('audit_password_new') ?></label>
            <input type="password" id="parolaAudit" placeholder="••••••••" autocomplete="new-password">
        </div>
        <button class="btn-salva" onclick="salveazaParolaAudit()"><?= t('audit_password_save') ?></button>
    </div>
</div>

<script>
function showToast(msg, tip = 'ok') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (tip === 'err' ? ' err' : '');
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 2500);
}

async function uploadImagine(tip, input) {
    if (!input.files.length) return;
    const progId = tip === 'logo' ? 'progressLogo' : 'progressBg';
    const prevId = tip === 'logo' ? 'previewLogo' : 'previewBg';
    document.getElementById(progId).style.display = 'block';
    const formData = new FormData();
    formData.append('imagine', input.files[0]);
    formData.append('tip', tip);
    try {
        const r = await fetch('/admin/upload_imagine.php', { method: 'POST', body: formData });
        const d = await r.json();
        if (d.ok) {
            const prev = document.getElementById(prevId);
            prev.className = 'preview-wrap' + (tip === 'bg' ? ' bg' : '');
            prev.innerHTML = `<img src="/${d.cale}?t=${Date.now()}" alt=""><button class="btn-sterge-img" onclick="stergeImagine('${tip === 'bg' ? 'bg_imagine' : 'logo'}')">×</button>`;
            showToast('<?= t('img_saved') ?>');
        } else { showToast(d.msg || '<?= t('error') ?>', 'err'); }
    } catch(e) { showToast('<?= t('error') ?>', 'err'); }
    document.getElementById(progId).style.display = 'none';
    input.value = '';
}

async function stergeImagine(tip) {
    if (!confirm('<?= t('delete_img_conf') ?>')) return;
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'sterge_imagine', tip })
    });
    const d = await r.json();
    if (d.ok) {
        document.getElementById(tip === 'logo' ? 'previewLogo' : 'previewBg').innerHTML = '';
        showToast('<?= t('img_deleted') ?>');
    }
}

async function salveazaText() {
    const text = document.getElementById('textBunVenit').value.trim();
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'salveaza_profil_vizual', text_bun_venit: text })
    });
    const d = await r.json();
    if (d.ok) showToast('<?= t('text_saved') ?>');
    else showToast('<?= t('error') ?>', 'err');
}

async function salveazaSocial() {
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            actiune: 'salveaza_social',
            facebook: document.getElementById('smFacebook').value.trim(),
            instagram: document.getElementById('smInstagram').value.trim(),
            tiktok: document.getElementById('smTiktok').value.trim(),
            whatsapp: document.getElementById('smWhatsapp').value.trim()
        })
    });
    const d = await r.json();
    if (d.ok) showToast('<?= t('social_saved') ?>');
    else showToast('<?= t('error') ?>', 'err');
}

async function alegeaTema(slug) {
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'salveaza_tema', tema: slug })
    });
    const d = await r.json();
    if (d.ok) {
        document.querySelectorAll('.tema-card').forEach(c => c.classList.remove('activa'));
        document.querySelector(`[onclick="alegeaTema('${slug}')"]`).classList.add('activa');
        showToast('<?= t('theme_saved') ?>');
    }
}

async function salveazaParolaAudit() {
    const parola = document.getElementById('parolaAudit').value.trim();
    if (!parola) { showToast('<?= t('audit_password_new') ?>', 'err'); return; }
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'salveaza_parola_reset', parola })
    });
    const d = await r.json();
    if (d.ok) {
        document.getElementById('parolaAudit').value = '';
        showToast('<?= t('audit_password_saved') ?>');
    } else {
        showToast(d.msg || '<?= t('error') ?>', 'err');
    }
}
</script>
</body>
</html>
