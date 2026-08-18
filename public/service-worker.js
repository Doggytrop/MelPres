const STATIC_CACHE = 'melpres-static-v1';
const STATIC_ASSETS = [
    '/manifest.webmanifest',
    '/pwa-register.js',
    '/favicon.svg',
    '/favicon-16x16.png',
    '/favicon-32x32.png',
    '/favicon.ico',
    '/images/logo_mel.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(STATIC_CACHE).then(cache => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys
                .filter(key => key.startsWith('melpres-static-') && key !== STATIC_CACHE)
                .map(key => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const isSafeStaticAsset = url.origin === self.location.origin && (
        STATIC_ASSETS.includes(url.pathname)
        || (url.pathname.startsWith('/build/assets/') && /\.(?:css|js|woff2?|png|svg)$/i.test(url.pathname))
    );

    if (!isSafeStaticAsset) {
        return;
    }

    event.respondWith(
        caches.open(STATIC_CACHE).then(cache => cache.match(request).then(cached => {
            if (cached) {
                return cached;
            }

            return fetch(request).then(response => {
                if (response.ok && response.type === 'basic') {
                    cache.put(request, response.clone());
                }

                return response;
            });
        }))
    );
});
