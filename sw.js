const CACHE_NAME = 'school-portal-cache-v2'; // Bump version
const STATIC_ASSETS = [
  '/school_app/src/output.css',
  '/school_app/src/boxicons.css',
  '/school_app/src/jquery.js',
  '/school_app/src/scripts.js',
  '/school_app/src/office-workplace.svg'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Service Worker: Caching Static Assets');
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate & Cleanup old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch Strategy
self.addEventListener('fetch', event => {
  // 1. Skip non-GET requests and external URLs
  if (event.request.method !== 'GET') return;
  
  const url = new URL(event.request.url);
  
  // 2. NETWORK FIRST for PHP/HTML (Dynamic Content)
  // This ensures logout/session redirects work properly
  if (url.pathname.endsWith('.php') || url.pathname.endsWith('/') || !url.pathname.includes('.')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => caches.match(event.request)) // Fallback to cache ONLY if network fails
    );
    return;
  }

  // 3. CACHING STRATEGY for Static Assets (CSS, JS, Images)
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request).then(fetchRes => {
        return caches.open(CACHE_NAME).then(cache => {
          // Only cache successful GET responses
          if (fetchRes.status === 200) {
            cache.put(event.request.url, fetchRes.clone());
          }
          return fetchRes;
        });
      });
    }).catch(() => {
        // Handle fetch errors for assets
        return new Response('Asset not found', { status: 404 });
    })
  );
});
