const CACHE_NAME = 'bll-v1';
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

    // API: network only for availability
    if (url.pathname.includes('/api/locations') && url.pathname.includes('/availability')) {
        event.respondWith(fetch(request));
        return;
    }

    // API: network first for booking details
    if (url.pathname.includes('/api/bookings') || url.pathname.includes('/booking/')) {
        event.respondWith(
            fetch(request).then(response => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                return response;
            }).catch(() => caches.match(request))
        );
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

    // HTML: stale while revalidate
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
