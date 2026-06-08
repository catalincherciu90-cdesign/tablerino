<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$limba = $rest['limba'];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino - <?= t('nav_menu') ?></title>
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
.layout { display: grid; grid-template-columns: 260px 1fr; gap: 24px; }
.panel { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
.panel h3 { font-size: 15px; margin-bottom: 16px; color: #333; }
.cat-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 14px; transition: background .15s; margin-bottom: 4px; }
.cat-item:hover { background: #f5f5f5; }
.cat-item.activ { background: #ede9ff; color: #6c47ff; font-weight: 600; }
.cat-item .del { color: #ccc; font-size: 16px; }
.cat-item .del:hover { color: #e53e3e; }
.form-inline { display: flex; gap: 8px; margin-top: 12px; }
.form-inline input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; }
.form-inline input:focus { border-color: #6c47ff; }
.btn-add { padding: 8px 14px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.produs-item { display: flex; align-items: center; gap: 14px; padding: 12px; border-radius: 10px; border: 1px solid #eee; margin-bottom: 10px; transition: opacity .2s; background: #fff; }
.produs-item.indisponibil { opacity: .5; }
.drag-handle { color: #ccc; cursor: grab; font-size: 18px; padding: 0 4px; user-select: none; flex-shrink: 0; }
.drag-handle:active { cursor: grabbing; color: #6c47ff; }
.sortable-ghost { opacity: .3; background: #ede9ff !important; border: 2px dashed #6c47ff !important; }
.cat-drag-handle { color: #555; cursor: grab; font-size: 13px; margin-right: 4px; flex-shrink: 0; }
.produs-poza-wrap { position: relative; width: 72px; height: 72px; flex-shrink: 0; border-radius: 10px; overflow: hidden; cursor: pointer; background: linear-gradient(135deg, #ede9ff, #dbeafe); }
.produs-poza-wrap img { width: 100%; height: 100%; object-fit: cover; }
.produs-poza-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 28px; }
.poza-hover { position: absolute; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s; color: #fff; font-size: 11px; font-weight: 600; text-align: center; padding: 4px; }
.produs-poza-wrap:hover .poza-hover { opacity: 1; }
.poza-input { display: none; }
.produs-info { flex: 1; min-width: 0; }
.produs-info strong { font-size: 14px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.produs-info p { font-size: 12px; color: #888; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.produs-pret { font-weight: 700; font-size: 15px; color: #333; white-space: nowrap; }
.produs-actiuni { display: flex; gap: 6px; flex-shrink: 0; }
.btn-sm { padding: 6px 12px; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-toggle { background: #f3f4f6; color: #555; }
.btn-toggle.activ { background: #d1fae5; color: #065f46; }
.btn-edit { background: #e0f2fe; color: #0369a1; }
.btn-edit:hover { background: #bae6fd; }
.upload-progress { font-size: 11px; color: #6c47ff; margin-top: 4px; display: none; }
.overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 100; align-items: center; justify-content: center; }
.overlay.open { display: flex; }
.modal { background: #fff; border-radius: 12px; padding: 28px; width: 100%; max-width: 440px; }
.modal h3 { margin-bottom: 20px; }
.modal label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #333; }
.modal input, .modal textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 16px; outline: none; }
.modal input:focus, .modal textarea:focus { border-color: #6c47ff; }
.modal-btns { display: flex; gap: 10px; justify-content: flex-end; }
.btn-anuleaza { padding: 10px 18px; border: 1px solid #ddd; background: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; }
.btn-salveaza { padding: 10px 18px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.toast { position: fixed; bottom: 24px; right: 24px; background: #333; color: #fff; padding: 12px 20px; border-radius: 8px; font-size: 13px; display: none; z-index: 999; }
.toast.ok { background: #10b981; }
.toast.err { background: #e53e3e; }
.gol { color: #aaa; text-align: center; padding: 40px; font-size: 14px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-header h2 { font-size: 20px; }
@media (max-width: 768px) {
    .header-mobil { display: flex !important; }
    .sidebar { position: fixed; left: 0; right: 0; top: auto; bottom: 0; width: 100%; height: 62px; flex-direction: row; padding: 0; justify-content: space-around; align-items: center; border-top: 1px solid #2a2a3e; z-index: 100; }
    .sidebar .logo { display: none; }
    .sidebar .logout { margin-top: 0; }
    .sidebar a { flex-direction: column; gap: 2px; font-size: 10px; padding: 6px 8px; border-radius: 6px; flex: 1; justify-content: center; text-align: center; font-weight: 600; }
    .main { margin-left: 0 !important; padding: 14px 12px 80px !important; }
    .layout { grid-template-columns: 1fr !important; }
    .produs-item { flex-wrap: wrap; }
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
</head>
<body>
<div class="sidebar">
    <div class="logo"><img src="/uploads/logo-tablerino.png" alt="Tablerino" style="height:190px;width:auto;object-fit:contain;display:block;"></div>
    <a href="/admin/index.php">📋 <?= t('nav_orders') ?></a>
    <a href="/admin/meniu.php" class="activ">🍕 <?= t('nav_menu') ?></a>
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

<div class="main">
    <div class="page-header">
        <h2>🍕 <?= t('nav_menu') ?></h2>
        <button class="btn-ai-import" onclick="deschideAI()">🤖 Import din poză</button>
    </div>
    <div class="layout">
        <div class="panel">
            <h3><?= t('categories') ?></h3>
            <div id="categoriiList"></div>
            <div class="form-inline">
                <input type="text" id="numeCategorie" placeholder="<?= t('category_name') ?>">
                <button class="btn-add" onclick="adaugaCategorie()">+ <?= t('add') ?></button>
            </div>
        </div>
        <div class="panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 id="titluCategorie"><?= t('select_category') ?></h3>
                <button class="btn-add" onclick="deschideModal()" id="btnAdaugaProdus" style="display:none">+ <?= t('product') ?></button>
            </div>
            <div id="produseList"><div class="gol"><?= t('select_category_hint') ?></div></div>
        </div>
    </div>
</div>

<!-- Modal editare produs -->
<div class="overlay" id="overlayEditare">
    <div class="modal" style="max-width:560px;max-height:90vh;overflow-y:auto">
        <h3>✏️ Editează produs</h3>
        <input type="hidden" id="eProdusId">
        <label><?= t('product_name') ?></label>
        <input type="text" id="eNume">
        <label><?= t('product_desc_opt') ?></label>
        <textarea id="eDesc" rows="2" style="resize:none"></textarea>
        <label><?= t('product_price') ?> (lei)</label>
        <input type="number" id="ePret" step="0.01" min="0">
        <label>Ingrediente</label>
        <textarea id="eIngrediente" rows="2" style="resize:none" placeholder="Ex: pui, usturoi, roșii, ulei de măsline"></textarea>
        <label>Alergeni</label>
        <input type="text" id="eAlergeni" placeholder="Ex: gluten, lactoză, ouă">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;margin-bottom:16px">
            <div>
                <label>Calorii (kcal)</label>
                <input type="number" id="eCalorii" min="0" style="margin-bottom:0">
            </div>
            <div>
                <label>Proteine (g)</label>
                <input type="number" id="eProteine" step="0.1" min="0" style="margin-bottom:0">
            </div>
            <div>
                <label>Carbohidrați (g)</label>
                <input type="number" id="eCarbohidrati" step="0.1" min="0" style="margin-bottom:0">
            </div>
            <div>
                <label>Grăsimi (g)</label>
                <input type="number" id="eGrasimi" step="0.1" min="0" style="margin-bottom:0">
            </div>
        </div>
        <div class="modal-btns">
            <button class="btn-anuleaza" onclick="inchideEditare()"><?= t('cancel') ?></button>
            <button class="btn-salveaza" onclick="salveazaEditare()"><?= t('save') ?></button>
        </div>
    </div>
</div>

<!-- Modal adauga produs -->
<div class="overlay" id="overlay">
    <div class="modal">
        <h3><?= t('new_product') ?></h3>
        <label><?= t('product_name') ?></label>
        <input type="text" id="mNume">
        <label><?= t('product_desc_opt') ?></label>
        <textarea id="mDesc" rows="2" style="resize:none"></textarea>
        <label><?= t('product_price') ?> (lei)</label>
        <input type="number" id="mPret" step="0.01" min="0">
        <div class="modal-btns">
            <button class="btn-anuleaza" onclick="inchideModal()"><?= t('cancel') ?></button>
            <button class="btn-salveaza" onclick="salveazaProdus()"><?= t('save') ?></button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script src="/admin/meniu.js"></script>

<!-- ═══════════════════════════════════════════════
     AI IMPORT MENIU - include în admin/meniu.php
     Adaugă înainte de </body>:

     
     Adaugă butonul în header-ul paginii lângă titlu:
═══════════════════════════════════════════════ -->

<?php include __DIR__ . '/ai_meniu_modal.php'; ?>
</body>
</html>
