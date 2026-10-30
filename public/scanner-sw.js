const CACHE_NAME = 'scanner-usb-v5';
const GOOGLE_FONT_CSS = 'https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap';

const PRECACHE_ASSETS = [
    '/absensi/scanner-usb',
    '/build/assets/app-sLRerkS1.css',
    '/build/assets/app-CLVGvVgY.js',
    '/build/assets/app-CksuuEqD.css',
    '/vendor/fontawesome-free/css/all.min.css',
    '/vendor/fontawesome-free/webfonts/fa-solid-900.woff2',
    '/vendor/fontawesome-free/webfonts/fa-regular-400.woff2',
    '/vendor/fontawesome-free/webfonts/fa-brands-400.woff2',
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
        caches.open(CACHE_NAME).then(async (cache) => {
            // 1. Precache aset lokal utama & FontAwesome webfonts
            await Promise.allSettled(
                PRECACHE_ASSETS.map((url) =>
                    cache.add(url).catch((err) => console.warn('[SW] Precache failed for:', url, err))
                )
            );

            // 2. Precache Google Font (Outfit CSS + file .woff2 dari fonts.gstatic.com) agar font tidak berubah saat offline
            try {
                const fontCssRes = await fetch(GOOGLE_FONT_CSS, { mode: 'cors', credentials: 'omit' });
                if (fontCssRes && fontCssRes.ok) {
                    await cache.put(GOOGLE_FONT_CSS, fontCssRes.clone());
                    const cssText = await fontCssRes.text();
                    const woffUrls = [...cssText.matchAll(/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/g)]
                        .map((m) => m[1].replace(/['"]/g, ''));
                    await Promise.allSettled(
                        woffUrls.map((fontUrl) =>
                            fetch(fontUrl, { mode: 'cors', credentials: 'omit' }).then((res) => {
                                if (res && (res.ok || res.type === 'opaque')) {
                                    return cache.put(fontUrl, res);
                                }
                            })
                        )
                    );
                }
            } catch (err) {
                console.warn('[SW] Google Fonts precache skipped (offline):', err);
            }
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

    // 1. Google Fonts (fonts.googleapis.com & fonts.gstatic.com) -> Cache First (0ms saat offline!)
    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(
            (async () => {
                const cachedFont = await caches.match(event.request) ||
                    (url.hostname === 'fonts.googleapis.com' ? await caches.match(GOOGLE_FONT_CSS) : null);
                if (cachedFont) {
                    return cachedFont;
                }

                // Jika sedang offline dan belum ter-cache, langsung kembalikan CSS kosong agar tidak memblokir render halaman
                if (!navigator.onLine) {
                    return new Response('', {
                        status: 200,
                        headers: { 'Content-Type': url.hostname === 'fonts.googleapis.com' ? 'text/css' : 'font/woff2' }
                    });
                }

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 1200);
                    const networkRes = await fetch(event.request, { signal: controller.signal });
                    clearTimeout(timeoutId);

                    if (networkRes && (networkRes.status === 200 || networkRes.type === 'opaque')) {
                        const copy = networkRes.clone();
                        caches.open(CACHE_NAME).then((c) => c.put(event.request, copy));
                    }
                    return networkRes;
                } catch (e) {
                    return new Response('', {
                        status: 200,
                        headers: { 'Content-Type': url.hostname === 'fonts.googleapis.com' ? 'text/css' : 'font/woff2' }
                    });
                }
            })()
        );
        return;
    }

    // 2. Navigation requests specifically for /absensi/scanner-usb (PWA launch):
    // Jika offline: 0ms instan dari cache. Jika online & sudah ada cache: batas tunggu 600ms agar tidak lemot saat jaringan mati/lambat.
    if (url.pathname === '/absensi/scanner-usb') {
        event.respondWith(
            (async () => {
                const cachedScanner = await caches.match('/absensi/scanner-usb') || await caches.match(event.request);

                if (!navigator.onLine && cachedScanner) {
                    return cachedScanner;
                }

                try {
                    const controller = new AbortController();
                    const timeoutMs = cachedScanner ? 600 : 2000;
                    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

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
                    if (isRedirectedToLogin && cachedScanner) {
                        return cachedScanner;
                    }
                    return networkResponse;
                } catch (e) {
                    // Network failed or timed out after 600ms, immediately serve cached scanner UI
                    if (cachedScanner) {
                        return cachedScanner;
                    }
                }

                return fetch(event.request);
            })()
        );
        return;
    }

    // 3. Static Assets (CSS, JS, Fonts, Images, Student Photos) -> Cache First for 0ms Instant Load!
    const isStaticAsset =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/vendor/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/storage/') ||
        url.pathname.startsWith('/siswa/photo/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.ttf') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.jpeg') ||
        url.pathname.endsWith('.webp');

    if (isStaticAsset) {
        event.respondWith(
            (async () => {
                const cached = await caches.match(event.request) || await caches.match(url.pathname);
                if (cached) {
                    return cached;
                }

                if (!navigator.onLine) {
                    return new Response('', { status: 408, statusText: 'Offline Asset Unavailable' });
                }

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 1500);
                    const networkRes = await fetch(event.request, { signal: controller.signal });
                    clearTimeout(timeoutId);

                    if (networkRes && networkRes.status === 200) {
                        const copy = networkRes.clone();
                        caches.open(CACHE_NAME).then((c) => c.put(event.request, copy));
                    }
                    return networkRes;
                } catch (e) {
                    return new Response('', { status: 408, statusText: 'Offline Asset Unavailable' });
                }
            })()
        );
        return;
    }
});
