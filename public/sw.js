const CACHE_NAME = 'cash-coffee-cache-v2';
const ASSETS_TO_CACHE = [
    '/',
    '/favicon.ico',
    '/logo/logo.png',
    '/logo/logow.png',
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            // Gagal loading salah satu cache opsional tidak menggagalkan worker
            cache.addAll(ASSETS_TO_CACHE).catch(() => {});
        }).then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event (Network First, falling back to cache for pages/assets)
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Bypassing chrome-extension or other schemes
    if (!event.request.url.startsWith(self.location.origin)) return;

    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                // If network request is successful, clone and put it in cache for offline use
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                // Fallback to cache if network fails
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                });
            })
    );
});
