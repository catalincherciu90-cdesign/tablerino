// Shows a floating "managing as Master" indicator when a platform admin opened
// this restaurant's panel via the Master "Gestionează" button.
(function () {
  fetch('/api/admin/me').then(function (r) { return r.ok ? r.json() : null; }).then(function (me) {
    if (!me || !me.ok || !me.via_master) return;
    var nume = me.restaurant_nume || me.nume || '';
    var bar = document.createElement('div');
    bar.id = 'masterBanner';
    bar.style.cssText = 'position:fixed;top:10px;left:50%;transform:translateX(-50%);z-index:99999;' +
      'background:#6c47ff;color:#fff;font:600 13px system-ui,-apple-system,sans-serif;display:flex;align-items:center;' +
      'gap:12px;padding:8px 10px 8px 16px;border-radius:999px;box-shadow:0 8px 24px rgba(108,71,255,.45);max-width:94vw';
    var txt = document.createElement('span');
    txt.style.cssText = 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis';
    txt.innerHTML = '⚙️ Gestionezi ca <b>Master</b>: ' + nume.replace(/[&<>"]/g, function (c) { return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]); });
    var btn = document.createElement('button');
    btn.textContent = 'Ieși';
    btn.style.cssText = 'background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.45);color:#fff;' +
      'border-radius:999px;padding:5px 15px;font:700 12px system-ui,sans-serif;cursor:pointer;flex:0 0 auto';
    btn.addEventListener('click', function () {
      btn.disabled = true; btn.textContent = '...';
      fetch('/api/admin/logout', { method: 'POST' }).catch(function () {}).finally(function () {
        window.close();
        setTimeout(function () { location.href = '/master/index.html'; }, 200);
      });
    });
    bar.appendChild(txt); bar.appendChild(btn);
    document.body.appendChild(bar);
  }).catch(function () {});
})();
