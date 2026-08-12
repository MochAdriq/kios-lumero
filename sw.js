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
    fetch(event.request).catch(async () => {
      const cachedResponse = await caches.match(event.request);
      if (cachedResponse) {
          return cachedResponse;
      }
      // Return a valid Response if both network and cache fail
      return new Response('Anda sedang offline atau server tidak merespon.', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: new Headers({ 'Content-Type': 'text/plain; charset=utf-8' })
      });
    })
  );
});
