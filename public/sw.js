const CACHE_NAME = 'meshbeacon-cache-v1';
const OFFLINE_URL = '/offline.html';

const urlsToCache = [
    '/',
    '/dashboard',
    '/status',
    '/gps',
    '/offline.html',
    '/images/logo.png',
    '/vendor/tailwindplus-elements/elements.min.js',
    '/vendor/jquery/jquery-3.0.0.min.js',
    '/vendor/datatables/dataTables.min.js',
    '/vendor/flowbite/flowbite.min.js',
    '/vendor/apexcharts/apexcharts.min.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                // Ensure offline fallback is cached
                cache.add(new Request(OFFLINE_URL, { cache: 'reload' }));
                return cache.addAll(urlsToCache);
            })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Only cache GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                if (response) {
                    return response; // Return from cache if found
                }
                
                return fetch(event.request).then((fetchResponse) => {
                    return fetchResponse;
                }).catch(() => {
                    // If network fails and it's a navigation request, show offline page
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});
