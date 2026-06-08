<style>
/* ── AI IMPORT MODAL ── */
.ai-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 500; align-items: center; justify-content: center; padding: 20px; }
.ai-overlay.open { display: flex; }
.ai-modal { background: #fff; border-radius: 20px; width: 100%; max-width: 760px; max-height: 90vh; overflow-y: auto; display: flex; flex-direction: column; }
.ai-modal-header { padding: 24px 28px 0; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.ai-modal-header h3 { font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.ai-modal-header h3 span { font-size: 22px; }
.btn-ai-close { background: #f3f4f6; border: none; border-radius: 50%; width: 32px; height: 32px; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.ai-modal-body { padding: 24px 28px 28px; flex: 1; }

/* Step 1: Upload */
.ai-step { display: none; }
.ai-step.activ { display: block; }

.ai-upload-zone { border: 2px dashed #6c47ff; border-radius: 16px; padding: 40px; text-align: center; cursor: pointer; transition: all .2s; position: relative; background: #faf9ff; }
.ai-upload-zone:hover { background: #f0eeff; }
.ai-upload-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.ai-upload-icon { font-size: 48px; margin-bottom: 12px; }
.ai-upload-zone h4 { font-size: 16px; font-weight: 700; margin-bottom: 6px; color: #111; }
.ai-upload-zone p { font-size: 13px; color: #888; }
.ai-preview-img { max-width: 100%; max-height: 300px; border-radius: 12px; margin-top: 16px; display: none; object-fit: contain; }
.btn-analizeaza { width: 100%; padding: 14px; background: linear-gradient(135deg, #6c47ff, #a855f7); color: #fff; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 16px; display: none; transition: opacity .2s; }
.btn-analizeaza:hover { opacity: .9; }
.btn-analizeaza:disabled { opacity: .5; cursor: not-allowed; }

/* Step 2: Loading */
.ai-loading { text-align: center; padding: 40px 20px; }
.ai-loading .spinner { width: 48px; height: 48px; border: 4px solid #f0f0f0; border-top-color: #6c47ff; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 20px; }
@keyframes spin { to { transform: rotate(360deg); } }
.ai-loading h4 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
.ai-loading p { color: #888; font-size: 14px; }
.ai-loading-steps { display: flex; flex-direction: column; gap: 8px; margin-top: 20px; text-align: left; max-width: 300px; margin-left: auto; margin-right: auto; }
.ai-loading-step { font-size: 13px; color: #aaa; display: flex; align-items: center; gap: 8px; }
.ai-loading-step.done { color: #10b981; }
.ai-loading-step.activ { color: #6c47ff; font-weight: 600; }

/* Step 3: Rezultate */
.ai-rezultate-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.ai-rezultate-header h4 { font-size: 16px; font-weight: 700; }
.ai-rezultate-header span { font-size: 13px; color: #888; }
.ai-cat { background: #f8f8fc; border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid #eee; }
.ai-cat-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.ai-cat-header input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-weight: 600; outline: none; }
.ai-cat-header input:focus { border-color: #6c47ff; }
.btn-sterge-cat { background: #fee2e2; color: #c62828; border: none; border-radius: 6px; padding: 5px 10px; font-size: 12px; cursor: pointer; flex-shrink: 0; }
.ai-produs { background: #fff; border-radius: 8px; padding: 12px; margin-bottom: 8px; display: grid; grid-template-columns: 1fr 1fr 80px auto; gap: 8px; align-items: center; border: 1px solid #f0f0f0; }
.ai-produs input { padding: 7px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; width: 100%; }
.ai-produs input:focus { border-color: #6c47ff; }
.ai-produs input.pret { text-align: right; }
.btn-sterge-produs { background: #fee2e2; color: #c62828; border: none; border-radius: 6px; padding: 6px 8px; font-size: 12px; cursor: pointer; }
.ai-produs-desc { grid-column: 1 / -1; }
.ai-produs-desc input { font-size: 12px; color: #666; }

.ai-actiuni { display: flex; gap: 10px; margin-top: 20px; }
.btn-ai-back { padding: 12px 20px; border: 1px solid #ddd; background: #fff; border-radius: 10px; font-size: 14px; cursor: pointer; }
.btn-ai-salva { flex: 1; padding: 12px; background: #10b981; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn-ai-salva:hover { background: #059669; }
.btn-ai-salva:disabled { opacity: .5; cursor: not-allowed; }

/* Step 4: Success */
.ai-success { text-align: center; padding: 40px 20px; }
.ai-success .icon { font-size: 56px; margin-bottom: 16px; }
.ai-success h4 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
.ai-success p { color: #888; font-size: 14px; margin-bottom: 24px; }
.btn-ai-done { padding: 12px 32px; background: #6c47ff; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; }

/* Buton trigger */
.btn-ai-import { padding: 9px 18px; background: linear-gradient(135deg, #6c47ff, #a855f7); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-ai-import:hover { opacity: .9; }

/* Error */
.ai-error { background: #fee2e2; color: #c62828; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-top: 12px; display: none; }
</style>

<!-- Modal AI -->
<div class="ai-overlay" id="aiOverlay">
    <div class="ai-modal">
        <div class="ai-modal-header">
            <h3><span>🤖</span> Import meniu din poză</h3>
            <button class="btn-ai-close" onclick="inchideAI()">×</button>
        </div>
        <div class="ai-modal-body">

            <!-- Step 1: Upload -->
            <div class="ai-step activ" id="aiStep1">
                <p style="color:#888;font-size:14px;margin-bottom:20px">Uploadează o poză clară cu meniul restaurantului. AI-ul va extrage automat categoriile și produsele.</p>
                <div class="ai-upload-zone" id="aiUploadZone">
                    <input type="file" id="aiFileInput" accept="image/jpeg,image/png,image/webp" onchange="aiFileSelected(this)">
                    <div class="ai-upload-icon">📷</div>
                    <h4>Click sau trage poza aici</h4>
                    <p>JPG, PNG, WEBP · max 10MB</p>
                </div>
                <img id="aiPreview" class="ai-preview-img" alt="Preview">
                <div class="ai-error" id="aiError"></div>
                <button class="btn-analizeaza" id="btnAnalizeaza" onclick="analizeaza()">
                    🤖 Analizează meniul
                </button>
            </div>

            <!-- Step 2: Loading -->
            <div class="ai-step" id="aiStep2">
                <div class="ai-loading">
                    <div class="spinner"></div>
                    <h4>AI analizează meniul...</h4>
                    <p>Acest proces durează 10-30 secunde</p>
                    <div class="ai-loading-steps">
                        <div class="ai-loading-step activ" id="ls1">📷 Se procesează imaginea...</div>
                        <div class="ai-loading-step" id="ls2">🔍 Se identifică produsele...</div>
                        <div class="ai-loading-step" id="ls3">📂 Se organizează în categorii...</div>
                        <div class="ai-loading-step" id="ls4">✅ Se pregătesc rezultatele...</div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Rezultate -->
            <div class="ai-step" id="aiStep3">
                <div class="ai-rezultate-header">
                    <h4>✅ Produse identificate — verifică și editează</h4>
                    <span id="aiCount"></span>
                </div>
                <div id="aiRezultate"></div>
                <div class="ai-actiuni">
                    <button class="btn-ai-back" onclick="mergeBack()">← Încearcă altă poză</button>
                    <button class="btn-ai-salva" id="btnSalva" onclick="salveaza()">💾 Salvează în meniu</button>
                </div>
            </div>

            <!-- Step 4: Success -->
            <div class="ai-step" id="aiStep4">
                <div class="ai-success">
                    <div class="icon">🎉</div>
                    <h4 id="aiSuccessMsg">Produse adăugate!</h4>
                    <p>Toate produsele au fost salvate în meniu. Le poți edita individual din pagina de meniu.</p>
                    <button class="btn-ai-done" onclick="inchideAI(); location.reload()">✓ Gata, du-mă la meniu</button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let aiMeniuData = null;
let aiFile = null;

function deschideAI() {
    document.getElementById('aiOverlay').classList.add('open');
    resetAI();
}

function inchideAI() {
    document.getElementById('aiOverlay').classList.remove('open');
}

function resetAI() {
    setStep(1);
    document.getElementById('aiFileInput').value = '';
    document.getElementById('aiPreview').style.display = 'none';
    document.getElementById('btnAnalizeaza').style.display = 'none';
    document.getElementById('aiError').style.display = 'none';
    aiFile = null;
    aiMeniuData = null;
}

function setStep(n) {
    document.querySelectorAll('.ai-step').forEach(s => s.classList.remove('activ'));
    document.getElementById('aiStep' + n).classList.add('activ');
}

function aiFileSelected(input) {
    if (!input.files.length) return;
    aiFile = input.files[0];
    const url = URL.createObjectURL(aiFile);
    const preview = document.getElementById('aiPreview');
    preview.src = url;
    preview.style.display = 'block';
    document.getElementById('btnAnalizeaza').style.display = 'block';
    document.getElementById('aiError').style.display = 'none';
}

function mergeBack() {
    resetAI();
}

// Animatie loading steps
function animaLoading() {
    const steps = ['ls1','ls2','ls3','ls4'];
    let i = 0;
    const interval = setInterval(() => {
        if (i > 0) {
            document.getElementById(steps[i-1]).classList.remove('activ');
            document.getElementById(steps[i-1]).classList.add('done');
        }
        if (i < steps.length) {
            document.getElementById(steps[i]).classList.add('activ');
        }
        i++;
        if (i >= steps.length) clearInterval(interval);
    }, 5000);
    return interval;
}

async function analizeaza() {
    if (!aiFile) return;
    const btn = document.getElementById('btnAnalizeaza');
    btn.disabled = true;
    setStep(2);

    // Reset loading steps
    ['ls1','ls2','ls3','ls4'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.remove('activ','done');
    });
    document.getElementById('ls1').classList.add('activ');
    const animInterval = animaLoading();

    const formData = new FormData();
    formData.append('poza', aiFile);

    try {
        const r = await fetch('/admin/ai_meniu.php', { method: 'POST', body: formData });
        const d = await r.json();
        clearInterval(animInterval);

        if (!d.ok) {
            setStep(1);
            const err = document.getElementById('aiError');
            err.textContent = d.msg || 'Eroare necunoscută.';
            err.style.display = 'block';
            btn.disabled = false;
            return;
        }

        aiMeniuData = d.meniu;
        renderRezultate(d.meniu);
        setStep(3);
    } catch(e) {
        clearInterval(animInterval);
        setStep(1);
        const err = document.getElementById('aiError');
        err.textContent = 'Eroare de conexiune. Încearcă din nou.';
        err.style.display = 'block';
        btn.disabled = false;
    }
}

function renderRezultate(meniu) {
    const container = document.getElementById('aiRezultate');
    let totalProduse = 0;
    meniu.categorii.forEach(c => totalProduse += (c.produse || []).length);
    document.getElementById('aiCount').textContent = `${meniu.categorii.length} categorii · ${totalProduse} produse`;

    container.innerHTML = meniu.categorii.map((cat, ci) => `
        <div class="ai-cat" id="aiCat${ci}">
            <div class="ai-cat-header">
                <input type="text" value="${escHtml(cat.nume)}" id="catNume${ci}" placeholder="Nume categorie">
                <button class="btn-sterge-cat" onclick="stergeCat(${ci})">🗑 Șterge categoria</button>
            </div>
            <div id="catProduse${ci}">
                ${(cat.produse || []).map((p, pi) => renderProdusBucata(ci, pi, p)).join('')}
            </div>
            <button onclick="adaugaProdusGol(${ci})" style="background:none;border:1px dashed #ddd;border-radius:6px;padding:6px 12px;font-size:12px;color:#888;cursor:pointer;width:100%;margin-top:6px">+ Adaugă produs</button>
        </div>
    `).join('');
}

function renderProdusBucata(ci, pi, p) {
    return `
    <div class="ai-produs" id="aiProd${ci}_${pi}">
        <input type="text" value="${escHtml(p.nume)}" id="pNume${ci}_${pi}" placeholder="Nume produs">
        <input type="text" value="${escHtml(p.descriere||'')}" id="pDesc${ci}_${pi}" placeholder="Descriere (opțional)" class="">
        <input type="number" value="${p.pret||0}" id="pPret${ci}_${pi}" placeholder="Preț" class="pret" step="0.01" min="0">
        <button class="btn-sterge-produs" onclick="stergeProdusAI(${ci},${pi})">🗑</button>
    </div>`;
}

function adaugaProdusGol(ci) {
    const container = document.getElementById('catProduse' + ci);
    const pi = container.children.length;
    container.insertAdjacentHTML('beforeend', renderProdusBucata(ci, pi, {nume:'',descriere:'',pret:0}));
}

function stergeCat(ci) {
    document.getElementById('aiCat' + ci)?.remove();
}

function stergeProdusAI(ci, pi) {
    document.getElementById('aiProd' + ci + '_' + pi)?.remove();
}

function escHtml(str) {
    return (str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function salveaza() {
    const btn = document.getElementById('btnSalva');
    btn.disabled = true;
    btn.textContent = 'Se salvează...';

    // Colectează datele din form
    const categorii = [];
    document.querySelectorAll('.ai-cat').forEach((catEl, ci) => {
        const numeInput = catEl.querySelector(`#catNume${ci}`);
        if (!numeInput) return;
        const cat = { nume: numeInput.value.trim(), produse: [] };

        catEl.querySelectorAll('.ai-produs').forEach(prodEl => {
            const idParts = prodEl.id.replace('aiProd','').split('_');
            const ci2 = idParts[0], pi2 = idParts[1];
            const numeP = document.getElementById(`pNume${ci2}_${pi2}`)?.value?.trim();
            const desc  = document.getElementById(`pDesc${ci2}_${pi2}`)?.value?.trim();
            const pret  = parseFloat(document.getElementById(`pPret${ci2}_${pi2}`)?.value) || 0;
            if (numeP) cat.produse.push({ nume: numeP, descriere: desc || '', pret });
        });

        if (cat.produse.length > 0) categorii.push(cat);
    });

    if (!categorii.length) {
        alert('Nu există produse de salvat.');
        btn.disabled = false;
        btn.textContent = '💾 Salvează în meniu';
        return;
    }

    const r = await fetch('/admin/ai_meniu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actiune: 'salveaza', categorii })
    });
    const d = await r.json();

    if (d.ok) {
        document.getElementById('aiSuccessMsg').textContent = `${d.salvate} produse adăugate în meniu!`;
        setStep(4);
    } else {
        alert('Eroare la salvare. Încearcă din nou.');
        btn.disabled = false;
        btn.textContent = '💾 Salvează în meniu';
    }
}

// Închide la click în afară
document.getElementById('aiOverlay').addEventListener('click', function(e) {
    if (e.target === this) inchideAI();
});
</script>
