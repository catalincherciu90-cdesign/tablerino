// Role-based access gating for the admin panel.
// Hides sidebar links the current role may not use, and redirects away from
// disallowed pages. The backend enforces the real authorization; this is UX.
(function () {
  // Which sidebar sections each staff role may open (owner sees everything).
  var ACCESS = {
    sef_sala:  ['index', 'bucatarie', 'istoric', 'mese', 'rezervari', 'recenzii', 'ospatari'],
    bucatarie: ['bucatarie', 'meniu', 'depozit'],
    ospatar:   ['index', 'istoric'],
  };
  var HOME = {
    sef_sala:  '/admin/index.html',
    bucatarie: '/admin/bucatarie.html',
    ospatar:   '/admin/index.html',
  };
  function slugOf(href) {
    // Matches both "/admin/depozit.html" (sidebar links) and the extensionless
    // "/admin/depozit" that the host serves as the live URL.
    var m = (href || '').match(/\/admin\/([a-z-]+)(?:\.html)?(?:[?#].*)?$/);
    return m ? m[1] : null;
  }
  fetch('/api/admin/me').then(function (r) { return r.ok ? r.json() : null; }).then(function (me) {
    if (!me || !me.ok) return;
    window.__role = me.role;
    if (me.role === 'owner') return; // full access
    var allowed = ACCESS[me.role];
    if (!allowed) return;
    // Hide sidebar links not in the allowed set.
    document.querySelectorAll('.sidebar a[href^="/admin/"]').forEach(function (a) {
      var slug = slugOf(a.getAttribute('href'));
      if (!slug || slug === 'login') return;
      if (allowed.indexOf(slug) === -1) a.style.display = 'none';
    });
    // Redirect off a disallowed page.
    var cur = slugOf(location.pathname) || 'index';
    if (allowed.indexOf(cur) === -1) {
      location.replace(HOME[me.role] || '/admin/index.html');
    }
  }).catch(function () {});
})();
