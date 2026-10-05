'use strict';
// Cache only the public offline screen. Never cache accounts, API responses,
// carts or checkout, and never queue/replay purchases while offline.
const OFFLINE = new URL('offline.html', self.registration.scope).href;
const PREFIX = 'nexora-offline-' + self.registration.scope + '-';
const CACHE = PREFIX + 'v3-purple';
self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE).then(cache => cache.add(OFFLINE)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(
    keys.filter(key => key.startsWith(PREFIX) && key !== CACHE).map(key => caches.delete(key))
  )).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET' || event.request.mode !== 'navigate') return;
  if (!event.request.url.startsWith(self.registration.scope)) return;
  event.respondWith(fetch(event.request).catch(async () => {
    const cache = await caches.open(CACHE);
    return (await cache.match(OFFLINE)) || new Response('NEXORA is offline. Reconnect and try again.', {
      status: 503, headers: {'Content-Type': 'text/plain; charset=utf-8'}
    });
  }));
});
