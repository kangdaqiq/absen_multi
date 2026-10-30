const CACHE_NAME = 'scanner-usb-v4';
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

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

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

    // Bypass API, auth, and dynamic scanning endpoints (Always Network Only)
    if (
        url.pathname.includes('/absensi/scan-usb') ||
        url.pathname.includes('/absensi/sync-offline') ||
        url.pathname.includes('/absensi/scanner-cache') ||
        url.pathname.includes('/login') ||
        url.pathname.includes('/logout') ||
        url.pathname.includes('/api/')
    ) {
        return;
    }

    // 1. Navigation requests specifically for /absensi/scanner-usb (PWA launch):
    // Fast response: If offline, serve cache instantly. If online, race network with 1500ms fallback.
    if (url.pathname === '/absensi/scanner-usb') {
        event.respondWith(
            (async () => {
                if (!navigator.onLine) {
                    const cached = await caches.match('/absensi/scanner-usb') || await caches.match(event.request);
                    if (cached) return cached;
                }

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 1500);

                    const networkResponse = await fetch(event.request, {
                        signal: controller.signal,
                        credentials: 'same-origin'
                    });
                    clearTimeout(timeoutId);

                    // Only cache and serve directly if it's a valid 200 scanner page (not redirected to /login)
                    const isRedirectedToLogin = networkResponse.redirected || (networkResponse.url && networkResponse.url.includes('/login'));
                    if (networkResponse && networkResponse.status === 200 && !isRedirectedToLogin) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((c) => {
                            c.put('/absensi/scanner-usb', copy.clone());
                            c.put(event.request, copy);
                        });
                        return networkResponse;
                    }

                    // If session expired (redirected to /login), prefer cached scanner UI so PWA can run in offline/re-login mode
                    if (isRedirectedToLogin) {
                        const cachedScanner = await caches.match('/absensi/scanner-usb') || await caches.match(event.request);
                        if (cachedScanner) {
                            return cachedScanner;
                        }
                        return networkResponse;
                    }
                } catch (e) {
                    // Network failed or timed out, fallback to cache
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
