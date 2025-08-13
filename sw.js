const CACHE_NAME = 'menhage-cache-v4';
const OFFLINE_PAGE = '/offline.html';
const ASSETS_TO_CACHE = [
  '/',
  '/offline.html',
  '/index.php',
  '/projects.json',
  '/stats.json',
  '/upload.php',
  '/login.php',
  '/project-details.php',
  '/mnsah1.png',
  '/favicon.ico',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// تثبيت Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('تم تخزين الموارد الأساسية في الذاكرة المؤقتة');
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );
});

// استراتيجية التخزين: Network Falling Back to Cache
self.addEventListener('fetch', event => {
  // تجاهل طلبات غير GET وطلبات API
  if (event.request.method !== 'GET' || 
      event.request.url.includes('upload.php') ||
      event.request.url.includes('projects.json') ||
      event.request.url.includes('stats.json')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // إذا نجح الطلب، نحدّث الذاكرة المؤقتة
        const responseToCache = response.clone();
        caches.open(CACHE_NAME)
          .then(cache => cache.put(event.request, responseToCache));
        return response;
      })
      .catch(() => {
        // إذا فشل الطلب، نعرض من الذاكرة المؤقتة
        return caches.match(event.request)
          .then(response => {
            // للصفحات، نعرض صفحة عدم الاتصال إذا لم تكن مخزنة
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match(OFFLINE_PAGE);
            }
            return response;
          });
      })
  );
});

// تحديث الذاكرة المؤقتة عند تغيير الإصدار
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('حذف الذاكرة المؤقتة القديمة:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});