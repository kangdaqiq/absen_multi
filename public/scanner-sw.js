const CACHE_NAME = 'scanner-usb-v3';
const PRECACHE_ASSETS = [
    '/absensi/scanner-usb',
    '/build/assets/app-sLRerkS1.css',
    '/build/assets/app-CLVGvVgY.js',
    '/build/assets/app-CksuuEqD.css',
    '/vendor/fontawesome-free/css/all.min.css',
    '/images/logo/logo-icon.svg',
    '/images/logo/logo-icon.ico',
    '/favicon.ico',
    '/scanner-manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return Promise.allSettled(
                PRECACHE_ASSETS.map((url) =>
                    cache.add(url).catch((err) => console.warn('[SW] Precache failed for:', url, err))
                )
            );
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Bypass API and dynamic scanning endpoints (Always Network Only)
    if (
        url.pathname.includes('/absensi/scan-usb') ||
        url.pathname.includes('/absensi/sync-offline') ||
        url.pathname.includes('/absensi/scanner-cache') ||
        url.pathname.includes('/api/')
    ) {
        return;
    }

    // 1. Navigation requests (/absensi/scanner-usb or PWA launch):
    // Fast response: If offline, serve cache instantly. If online, race network with 800ms fallback.
    if (event.request.mode === 'navigate' || url.pathname === '/absensi/scanner-usb') {
        event.respondWith(
            (async () => {
                if (!navigator.onLine) {
                    const cached = await caches.match('/absensi/scanner-usb');
                    if (cached) return cached;
                }

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 800);

                    const networkResponse = await fetch(event.request, { signal: controller.signal });
                    clearTimeout(timeoutId);

                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((c) => c.put(event.request, copy));
                        return networkResponse;
                    }
                } catch (e) {
                    // Network failed or timed out after 800ms, use cache
                }

                const cachedResponse = await caches.match('/absensi/scanner-usb') || await caches.match(event.request);
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetch(event.request);
            })()
        );
        return;
    }

    // 2. Static Assets (CSS, JS, Fonts, Images) -> Cache First for 0ms Instant Load!
    const isStaticAsset =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/vendor/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.ttf') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) {
                    return cached;
                }

                // If not yet in cache, fetch from network and save a copy
                return fetch(event.request)
                    .then((networkRes) => {
                        if (networkRes && networkRes.status === 200) {
                            const copy = networkRes.clone();
                            caches.open(CACHE_NAME).then((c) => c.put(event.request, copy));
                        }
                        return networkRes;
                    })
                    .catch(() => {
                        // Silent catch if asset unavailable offline
                        return new Response('', { status: 408, statusText: 'Offline Asset Unavailable' });
                    });
            })
        );
        return;
    }
});
