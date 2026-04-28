const CACHE_NAME = 'bll-v2';
const STATIC_ASSETS = ['/', '/faq', '/about', '/contact'];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Never cache anything that contains PII or auth state.
    // /booking/{uuid}/*  → contains customer name/email/PIN
    // /api/*             → all API responses are user-scoped
    // /admin/*           → admin SPA + auth
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/booking/') ||
        url.pathname.startsWith('/admin')
    ) {
        event.respondWith(fetch(request));
        return;
    }

    // Static assets: cache first
    if (request.destination === 'image' || request.destination === 'font' || request.destination === 'style' || request.destination === 'script') {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(response => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                return response;
            }))
        );
        return;
    }

    // HTML: stale while revalidate (only for non-PII, non-API pages above filters)
    event.respondWith(
        caches.match(request).then(cached => {
            const fetchPromise = fetch(request).then(response => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                return response;
            });
            return cached || fetchPromise;
        })
    );
});
