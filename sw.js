const CACHE_NAME = 'lumero-pwa-v1';
const urlsToCache = [
  'public/assets/images/icon-512x512.png',
  'public/favicon.ico'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Cache falling back to network strategy for specific assets, but mostly network-first for a dynamic PHP app
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});
