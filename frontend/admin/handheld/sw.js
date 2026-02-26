const CACHE_NAME = 'qr-gate-handheld-v5';
const urlsToCache = [
  './',
  './index.html',
  './index.php',
  './manifest.json',
  './icon-72x72.png',
  './icon-96x96.png',
  './icon-128x128.png',
  './icon-144x144.png',
  './icon-152x152.png',
  './icon-192x192.png',
  './icon-384x384.png',
  './icon-512x512.png',
  'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js',
  'https://fonts.googleapis.com/css2?family=Quicksand:wght@400..700&display=swap',
  'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/basecoat.cdn.min.css',
  'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.10-beta.2/dist/js/all.min.js'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Cache geöffnet');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', function(event) {
  // API-Anfragen direkt vom Server laden
  if (event.request.url.includes('/api/ticket/validate')) {
    event.respondWith(fetch(event.request));
  } else {
    // Andere Anfragen zunächst aus dem Cache laden, sonst vom Netzwerk
    event.respondWith(
      caches.match(event.request)
        .then(function(response) {
          if (response) {
            return response;
          }
          return fetch(event.request);
        }
      )
    );
  }
});

// Push-Benachrichtigungen (optional)
self.addEventListener('push', function(event) {
  console.log('Push-Nachricht erhalten');
});