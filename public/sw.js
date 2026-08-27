const CACHE_NAME = 'spi-elms-v2';
const STATIC_ASSETS = [
    '/',
    '/dashboard',
    '/teacher/courses',
    '/student/dashboard',
    '/admin/dashboard',
    '/manifest.json',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/pwa-192.png',
    '/pwa-512.png',
    '/images/logo.png',
    '/images/og-cover.png'
];

// Install Event - Precache Core Shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('SW Precache non-critical asset warning:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate Event - Clean Old Caches & Claim Clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    return self.clients.claim();
});

// Fetch Event - Intercept Navigation & Asset Requests Offline
self.addEventListener('fetch', (event) => {
    // Only intercept GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Bypass API calls that shouldn't be cached or Cloudflare AI
    if (url.pathname.startsWith('/api/ai/') || url.pathname.startsWith('/api/ai-tutor/')) {
        return;
    }

    // 1. Navigation Requests (Page Reload / Routing)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    // Offline Mode: Match specific cached page or fallback to cached route
                    const cachedResponse = await caches.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    // Fallback to teacher courses if navigating in teacher area
                    if (url.pathname.startsWith('/teacher/')) {
                        const teacherFallback = await caches.match('/teacher/courses');
                        if (teacherFallback) return teacherFallback;
                    }

                    // Fallback to student dashboard or my-courses if in student area
                    if (url.pathname.startsWith('/student/')) {
                        const learnFallback = await caches.match(event.request);
                        if (learnFallback) return learnFallback;
                        const myCoursesFallback = await caches.match('/student/my-courses/enrolled');
                        if (myCoursesFallback) return myCoursesFallback;
                        const studentFallback = await caches.match('/student/dashboard');
                        if (studentFallback) return studentFallback;
                    }

                    // Generic root fallback
                    const rootFallback = await caches.match('/');
                    return rootFallback || new Response('<h1>Offline - SPI AI-ELMS</h1><p>You are currently offline. Please reconnect to access live data.</p>', {
                        headers: { 'Content-Type': 'text/html; charset=utf-8' }
                    });
                })
        );
        return;
    }

    // 2. Static Assets (CSS, JS, Fonts, Images, Build Chunks)
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                // Fetch in background to update cache (Stale-While-Revalidate)
                fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, networkResponse.clone());
                        });
                    }
                }).catch(() => {});

                return cachedResponse;
            }

            // Not in cache: fetch from network and cache
            return fetch(event.request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Return empty/fallback for images if offline
                if (event.request.destination === 'image') {
                    return caches.match('/images/logo.png');
                }
            });
        })
    );
});

// Message Event
self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
