// PWA has been disabled (the previous cache-first strategy caused stale
// dashboard/login content). This service worker is a kill-switch: it wipes
// all caches, unregisters itself, and takes over any existing clients so
// they stop being controlled by the old caching worker on their next load.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(cacheNames.map((name) => caches.delete(name))))
            .then(() => self.registration.unregister())
            .then(() => self.clients.matchAll())
            .then((clients) => clients.forEach((client) => client.navigate(client.url)))
    );
});
