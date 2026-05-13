/**
 * Finance AI — Service Worker
 * Handles caching for offline support and PWA functionality
 */

const CACHE_NAME = 'finance-ai-v1';
const OFFLINE_URL = '/offline';

// Assets to cache on install (app shell)
const PRECACHE_URLS = [
    '/',
    '/offline',
    '/manifest.json',
];

// ── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS).catch(() => {
                // Silently fail if some precache URLs don't exist yet
            });
        }).then(() => self.skipWaiting())
    );
});

// ── Activate ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// ── Fetch ─────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip cross-origin requests (Telegram API, Groq API, etc.)
    if (url.origin !== self.location.origin) return;

    // Skip Laravel API routes and webhooks
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/webhook/')) return;

    // Skip admin routes (always fresh)
    if (url.pathname.startsWith('/admin/')) return;

    // Strategy: Network first for HTML pages (always fresh data)
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful responses
                    if (response.ok) {
                        const cloned = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned));
                    }
                    return response;
                })
                .catch(() => {
                    // Offline fallback: try cache, then offline page
                    return caches.match(request)
                        .then((cached) => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }

    // Strategy: Cache first for static assets (CSS, JS, images, fonts)
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/) ||
        url.pathname.startsWith('/build/')
    ) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const cloned = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned));
                    }
                    return response;
                });
            })
        );
        return;
    }
});

// ── Push Notifications ────────────────────────────────────────────────────────
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data = {};
    try { data = event.data.json(); } catch (e) { data = { title: 'Finance AI', body: event.data.text() }; }

    const options = {
        body:    data.body || 'Ada notifikasi baru',
        icon:    '/icons/icon-192.png',
        badge:   '/icons/icon-72.png',
        vibrate: [200, 100, 200],
        data:    { url: data.url || '/' },
        actions: [
            { action: 'open',    title: 'Buka App' },
            { action: 'dismiss', title: 'Tutup' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Finance AI', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.action === 'dismiss') return;

    const url = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) return client.focus();
            }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});
