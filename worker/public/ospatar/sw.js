const CACHE = 'tablerino-ospatar-v1';
const STATIC = [
    '/ospatar/',
    '/ospatar/index.html',
    '/ospatar/login.html',
    '/ospatar/manifest.json',
];

self.addEventListener('install', e => {
    self.skipWaiting();
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(STATIC)).catch(() => {}));
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    const url = new URL(e.request.url);

    // Images: cache first.
    if (url.pathname.startsWith('/uploads/')) {
        e.respondWith(
            caches.match(e.request).then(cached =>
                cached || fetch(e.request).then(res => {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(e.request, clone));
                    return res;
                })
            )
        );
        return;
    }

    // API: always network (order data must be fresh); never cache.
    if (url.pathname.startsWith('/api/')) {
        e.respondWith(fetch(e.request));
        return;
    }

    // App shell: network first, fall back to cache when offline.
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request).then(c => c || caches.match('/ospatar/')))
    );
});
