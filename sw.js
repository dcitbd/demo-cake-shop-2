const CACHE_NAME = 'cake-shop-bd-v1.0';
const PRECACHE_URLS = [
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/images/logo.svg',
  './assets/images/icon-192.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});

// Handle Mobile / Desktop Push Notifications
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : { title: 'Cake Shop BD', body: 'নতুন অর্ডার আপডেট এসেছে!' };
  event.waitUntil(
    self.registration.showNotification(data.title || 'Cake Shop BD', {
      body: data.body || 'নতুন অর্ডার এসেছে!',
      icon: './assets/images/icon-192.png',
      badge: './assets/images/icon-192.png',
      vibrate: [200, 100, 200]
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(clients.openWindow('./admin/index.php?tab=orders'));
});
