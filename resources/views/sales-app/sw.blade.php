// Service worker for the sales app: the app opens with no internet. Orders and data are kept
// by the app itself (localStorage); here we only keep the screen and its files.
const CACHE = 'msk-sales-{{ $version }}';
const SHELL = @json($shell);
const FILES = @json($files);
const API = @json($api);

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll([SHELL, ...FILES])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k.startsWith('msk-sales-') && k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = req.url.split('#')[0];
    if (url.startsWith(API)) return; // the API is always live
    if (req.mode === 'navigate' && url.split('?')[0] === SHELL) {
        // The newest screen when online, the saved one when not.
        event.respondWith(fetch(req).then((res) => { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(SHELL, copy)); return res; }).catch(() => caches.match(SHELL)));
        return;
    }
    if (FILES.includes(url)) {
        event.respondWith(caches.match(url).then((hit) => hit || fetch(req)));
    }
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(self.clients.matchAll({ type: 'window' }).then((list) => (list[0] ? list[0].focus() : self.clients.openWindow(SHELL))));
});
