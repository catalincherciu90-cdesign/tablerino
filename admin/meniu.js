let catActiva = null;
const baseUrl = '<?= BASE_URL ?>';
const _mn = {
    noCategory:        '<?= t('no_category') ?>',
    selectCatHint:     '<?= t('select_category_hint') ?>',
    noProducts:        '<?= t('no_products') ?>',
    available:         '<?= t('available') ?>',
    unavailable:       '<?= t('unavailable') ?>',
    changePic:         '<?= t('change_photo') ?>',
    photoSaved:        '<?= t('photo_saved') ?>',
    productAdded:      '<?= t('product_added') ?>',
    deleteCatConf:     '<?= t('delete_cat_conf') ?>',
    deleteProductConf: '<?= t('delete_product_conf') ?>',
    fillNamePrice:     '<?= t('fill_name_price') ?>',
    error:             '<?= t('error') ?>',
    uploading:         '<?= t('uploading_photo') ?>',
};

function showToast(msg, tip = 'ok') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + tip;
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 2500);
}

let sortableProduse = null;
let sortableCateg = null;

async function incarcaCategorii() {
    const r = await fetch('/admin/api.php?actiune=categorii');
    const d = await r.json();
    const list = document.getElementById('categoriiList');
    if (!d.categorii.length) { list.innerHTML = `<div class="gol">${_mn.noCategory}</div>`; return; }
    list.innerHTML = d.categorii.map(c => `
        <div class="cat-item ${catActiva == c.id ? 'activ' : ''}" data-id="${c.id}" onclick="selecteazaCategorie(${c.id}, '${c.nume.replace(/'/g,"\\'")}')">
            <span style="display:flex;align-items:center;gap:4px;min-width:0">
                <span class="cat-drag-handle" onclick="event.stopPropagation()">⠿</span>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${c.nume}</span>
            </span>
            <span class="del" onclick="event.stopPropagation();stergeCategorie(${c.id})">×</span>
        </div>
    `).join('');

    // Sortable categorii
    if (sortableCateg) sortableCateg.destroy();
    sortableCateg = Sortable.create(list, {
        handle: '.cat-drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: async () => {
            const ordine = [...list.querySelectorAll('.cat-item')].map(el => el.dataset.id);
            await fetch('/admin/api.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ actiune: 'reordoneaza_categorii', ordine })
            });
        }
    });
}

async function selecteazaCategorie(id, nume) {
    catActiva = id;
    document.getElementById('titluCategorie').textContent = nume;
    document.getElementById('btnAdaugaProdus').style.display = 'block';
    incarcaCategorii();
    incarcaProduse();
}

async function incarcaProduse() {
    if (!catActiva) return;
    const r = await fetch('/admin/api.php?actiune=produse&categorie_id=' + catActiva);
    const d = await r.json();
    const list = document.getElementById('produseList');
    if (!d.produse.length) { list.innerHTML = `<div class="gol">${_mn.noProducts}</div>`; return; }
    list.innerHTML = d.produse.map(p => {
        const pozaHtml = p.poza
            ? `<img src="/${p.poza}" alt="${p.nume}">`
            : `<div class="produs-poza-placeholder">🍽️</div>`;
        return `
        <div class="produs-item ${p.disponibil == 0 ? 'indisponibil' : ''}" id="produs-${p.id}" data-id="${p.id}">
            <span class="drag-handle" title="Trage pentru a reordona">⠿</span>
            <div class="produs-poza-wrap" onclick="document.getElementById('poza-input-${p.id}').click()" title="${_mn.changePic}">
                ${pozaHtml}
                <div class="poza-hover">📷 ${_mn.changePic}</div>
            </div>
            <input type="file" class="poza-input" id="poza-input-${p.id}" accept="image/jpeg,image/png,image/webp" onchange="uploadPoza(${p.id}, this)">
            <div class="produs-info">
                <strong>${p.nume}</strong>
                ${p.descriere ? `<p>${p.descriere}</p>` : ''}
                ${p.ingrediente ? `<p style="color:#6c47ff;font-size:11px;margin-top:3px">🧄 ${p.ingrediente}</p>` : ''}
                ${p.alergeni ? `<p style="color:#e53e3e;font-size:11px;margin-top:2px">⚠️ ${p.alergeni}</p>` : ''}
                ${p.calorii ? `<p style="color:#888;font-size:11px;margin-top:2px">🔥 ${p.calorii} kcal${p.proteine ? ` · P: ${p.proteine}g` : ''}${p.carbohidrati ? ` · C: ${p.carbohidrati}g` : ''}${p.grasimi ? ` · G: ${p.grasimi}g` : ''}</p>` : ''}
                <div class="upload-progress" id="progress-${p.id}">${_mn.uploading}</div>
            </div>
            <div class="produs-pret">${parseFloat(p.pret).toFixed(2)} lei</div>
            <div class="produs-actiuni">
                <button class="btn-sm btn-edit" onclick="deschideEditare(${p.id}, '${p.nume.replace(/'/g,"\\'")}', '${(p.descriere||'').replace(/'/g,"\\'")}', ${p.pret}, '${(p.ingrediente||'').replace(/'/g,"\\'")}', '${(p.alergeni||'').replace(/'/g,"\\'")}', ${p.calorii||0}, ${p.proteine||0}, ${p.carbohidrati||0}, ${p.grasimi||0})">✏️</button>
                <button class="btn-sm btn-toggle ${p.disponibil ? 'activ' : ''}" onclick="toggleDisponibil(${p.id})">
                    ${p.disponibil ? '✓ ' + _mn.available : '✗ ' + _mn.unavailable}
                </button>
                <button class="btn-sm btn-del" onclick="stergeProdus(${p.id})"><?= t('delete') ?></button>
            </div>
        </div>`;
    }).join('');

    // Sortable produse
    if (sortableProduse) sortableProduse.destroy();
    sortableProduse = Sortable.create(list, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: async () => {
            const ordine = [...list.querySelectorAll('.produs-item')].map(el => el.dataset.id);
            await fetch('/admin/api.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ actiune: 'reordoneaza_produse', ordine })
            });
            showToast('Ordine salvată!', 'ok');
        }
    });

async function uploadPoza(produsId, input) {
    if (!input.files.length) return;
    const file = input.files[0];
    const progress = document.getElementById('progress-' + produsId);
    progress.style.display = 'block';
    const formData = new FormData();
    formData.append('poza', file);
    formData.append('produs_id', produsId);
    try {
        const r = await fetch('/admin/upload_poza.php', { method: 'POST', body: formData });
        const d = await r.json();
        if (d.ok) { showToast(_mn.photoSaved, 'ok'); incarcaProduse(); }
        else showToast(d.msg || _mn.error, 'err');
    } catch(e) { showToast(_mn.error, 'err'); }
    progress.style.display = 'none';
}

async function adaugaCategorie() {
    const nume = document.getElementById('numeCategorie').value.trim();
    if (!nume) return;
    await fetch('/admin/api.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ actiune: 'adauga_categorie', nume }) });
    document.getElementById('numeCategorie').value = '';
    incarcaCategorii();
}

async function stergeCategorie(id) {
    if (!confirm(_mn.deleteCatConf)) return;
    await fetch('/admin/api.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ actiune: 'sterge_categorie', categorie_id: id }) });
    if (catActiva == id) catActiva = null;
    incarcaCategorii();
    document.getElementById('produseList').innerHTML = `<div class="gol">${_mn.selectCatHint}</div>`;
}

function deschideModal() { document.getElementById('overlay').classList.add('open'); }
function inchideModal() {
    document.getElementById('overlay').classList.remove('open');
    document.getElementById('mNume').value = '';
    document.getElementById('mDesc').value = '';
    document.getElementById('mPret').value = '';
}

async function salveazaProdus() {
    const nume = document.getElementById('mNume').value.trim();
    const desc = document.getElementById('mDesc').value.trim();
    const pret = parseFloat(document.getElementById('mPret').value);
    if (!nume || !pret) { alert(_mn.fillNamePrice); return; }
    await fetch('/admin/api.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ actiune: 'adauga_produs', categorie_id: catActiva, nume, descriere: desc, pret }) });
    inchideModal();
    incarcaProduse();
    showToast(_mn.productAdded, 'ok');
}

function deschideEditare(id, nume, desc, pret, ingrediente, alergeni, calorii, proteine, carbohidrati, grasimi) {
    document.getElementById('eProdusId').value = id;
    document.getElementById('eNume').value = nume;
    document.getElementById('eDesc').value = desc;
    document.getElementById('ePret').value = pret;
    document.getElementById('eIngrediente').value = ingrediente || '';
    document.getElementById('eAlergeni').value = alergeni || '';
    document.getElementById('eCalorii').value = calorii || '';
    document.getElementById('eProteine').value = proteine || '';
    document.getElementById('eCarbohidrati').value = carbohidrati || '';
    document.getElementById('eGrasimi').value = grasimi || '';
    document.getElementById('overlayEditare').classList.add('open');
}

function inchideEditare() {
    document.getElementById('overlayEditare').classList.remove('open');
}

async function salveazaEditare() {
    const id           = document.getElementById('eProdusId').value;
    const nume         = document.getElementById('eNume').value.trim();
    const desc         = document.getElementById('eDesc').value.trim();
    const pret         = parseFloat(document.getElementById('ePret').value);
    const ingrediente  = document.getElementById('eIngrediente').value.trim();
    const alergeni     = document.getElementById('eAlergeni').value.trim();
    const calorii      = parseInt(document.getElementById('eCalorii').value) || 0;
    const proteine     = parseFloat(document.getElementById('eProteine').value) || 0;
    const carbohidrati = parseFloat(document.getElementById('eCarbohidrati').value) || 0;
    const grasimi      = parseFloat(document.getElementById('eGrasimi').value) || 0;
    if (!nume || !pret) { alert(_mn.fillNamePrice); return; }
    const r = await fetch('/admin/api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ actiune: 'editeaza_produs', produs_id: parseInt(id), nume, descriere: desc, pret, ingrediente, alergeni, calorii, proteine, carbohidrati, grasimi })
    });
    const d = await r.json();
    if (d.ok) {
        inchideEditare();
        incarcaProduse();
        showToast('Produs actualizat!', 'ok');
    } else {
        showToast(_mn.error, 'err');
    }
}

async function toggleDisponibil(id) {
    await fetch('/admin/api.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ actiune: 'toggle_disponibil', produs_id: id }) });
    incarcaProduse();
}

async function stergeProdus(id) {
    if (!confirm(_mn.deleteProductConf)) return;
    await fetch('/admin/api.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ actiune: 'sterge_produs', produs_id: id }) });
    incarcaProduse();
}

incarcaCategorii();