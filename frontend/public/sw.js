/**
 * ORDERra PWA shell — installs quickly and skips aggressive fetch caching so catalog/checkout
 * payloads always resolve through the normal network stack (fresh menu/order data).
 */
self.addEventListener("install", () => {
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", (event) => {
  event.respondWith(fetch(event.request));
});
