// KILL SWITCH — this service worker exists only to undo the previous one.
//
// The old SW cached scripts/styles "cache-first", which could leave a browser
// pinned to a stale admin bundle after a deploy → white screen for that user.
// We're removing service-worker caching entirely: on activation this purges
// EVERY cache, unregisters itself, and reloads open tabs so every client lands
// on fresh content. The page no longer registers a SW, so it won't come back.
//
// Assets are already fast without it: /build/ files are content-hashed and
// served immutable (1-year) by .htaccess, over HTTP/2. Reliability > a bit of
// SW caching.

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        // 1. Purge every cache this origin ever created.
        for (const key of await caches.keys()) {
            await caches.delete(key);
        }
        // 2. Remove this service worker registration.
        await self.registration.unregister();
        // 3. Reload any open tabs so they re-fetch everything from the network.
        for (const client of await self.clients.matchAll({ type: 'window' })) {
            try { client.navigate(client.url); } catch (e) { /* ignore */ }
        }
    })());
});

// While this SW is briefly alive, never serve from cache — straight to network.
self.addEventListener('fetch', event => {
    event.respondWith(fetch(event.request));
});
