const CACHE = 'tablerino-v1';
const STATIC = [
    '/masa/',
    '/masa/index.php',
];

self.addEventListener('install', e => {
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Network first — datele comenzilor trebuie mereu fresh
self.addEventListener('fetch', e => {
    const url = new URL(e.request.url);

    // Imagini din uploads — cache first
    if (url.pathname.startsWith('/uploads/')) {
        e.respondWith(
            caches.match(e.request).then(cached => {
                if (cached) return cached;
                return fetch(e.request).then(res => {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(e.request, clone));
                    return res;
                });
            })
        );
        return;
    }

    // API calls — mereu network
    if (url.pathname.includes('/api.php')) {
        e.respondWith(fetch(e.request));
        return;
    }

    // Altele — network first, fallback cache
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});
