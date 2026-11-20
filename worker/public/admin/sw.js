// Tablerino admin service worker — owner notifications only (no caching).
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

self.addEventListener('push', e => {
    let data = {};
    try { data = e.data ? e.data.json() : {}; }
    catch (_) { data = { title: 'Tablerino', body: e.data ? e.data.text() : '' }; }
    const title = data.title || 'Tablerino';
    const options = {
        body: data.body || '',
        icon: '/uploads/icon-192.png',
        badge: '/uploads/icon-192.png',
        tag: data.tag || 'tablerino-admin',
        renotify: true,
        vibrate: [120, 60, 120],
        data: { url: data.url || '/admin/rezervari.html' },
    };
    e.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', e => {
    e.notification.close();
    const url = (e.notification.data && e.notification.data.url) || '/admin/rezervari.html';
    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const c of list) { if (c.url.includes('/admin') && 'focus' in c) return c.focus(); }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});
