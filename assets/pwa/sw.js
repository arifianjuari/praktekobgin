const CACHE_NAME = 'praktek-obgin-v4'; // Meningkatkan versi untuk memaksa update
const isLocalhost = self.location.hostname === 'localhost' || self.location.hostname === '127.0.0.1';
const urlsToCache = [
    '/',
    '/index.php',
    '/login.php',
    '/register.php',
    '/dashboard.php',
    '/pendaftaran/form_pendaftaran_pasien.php',
    '/offline.html',
    '/assets/pwa/manifest.json',
    '/assets/pwa/icons/praktekobgin_icon72x72.png',
    '/assets/pwa/icons/praktekobgin_icon96x96.png',
    '/assets/pwa/icons/praktekobgin_icon128.png',
    '/assets/pwa/icons/praktekobgin_icon144.png',
    '/assets/pwa/icons/praktekobgin_icon192.png',
    '/assets/pwa/icons/praktekobgin_icon384.png',
    '/assets/pwa/icons/praktekobgin_icon512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://code.jquery.com/jquery-3.6.0.min.js'
];

console.log('Service Worker dimuat');

// Install Service Worker
self.addEventListener('install', event => {
    console.log('Service Worker: Event install terdeteksi');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache dibuka:', CACHE_NAME);
                return cache.addAll(urlsToCache)
                    .then(() => {
                        console.log('Semua URL berhasil di-cache');
                    })
                    .catch(error => {
                        console.error('Error during service worker installation:', error);
                    });
            })
    );
    // Force the waiting service worker to become the active service worker
    self.skipWaiting();
});

// Activate Service Worker
self.addEventListener('activate', event => {
    console.log('Service Worker: Event activate terdeteksi');
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            console.log('Cache yang ada:', cacheNames);
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        console.log('Menghapus cache lama:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Mengambil kontrol dari semua klien tanpa reload
            return self.clients.claim();
        })
    );
});

// Fetch Event
self.addEventListener('fetch', event => {
    console.log('Service Worker: Event fetch terdeteksi untuk:', event.request.url);

    // Jangan mengintervensi request di lingkungan development/localhost
    if (isLocalhost) {
        return;
    }

    // Jangan mencoba cache untuk request yang bukan GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Jangan mencoba cache untuk URL yang mengandung API, admin, atau halaman edit/form
    if (event.request.url.includes('/api/') || 
        event.request.url.includes('/admin/') ||
        event.request.url.includes('edit_pemeriksaan') ||
        event.request.url.includes('form_edit_pemeriksaan') ||
        event.request.url.includes('formEditPemeriksaan') ||
        event.request.url.includes('form_penilaian_medis_ralan_kandungan') ||
        event.request.url.includes('detail_pemeriksaan')) {
        console.log('Service Worker: Tidak mengintervensi halaman dinamis:', event.request.url);
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    console.log('Cache hit untuk:', event.request.url);
                    return response;
                }

                console.log('Cache miss untuk:', event.request.url);
                // Clone the request
                const fetchRequest = event.request.clone();

                return fetch(fetchRequest)
                    .then(response => {
                        // Periksa apakah response valid
                        if (!response || response.status !== 200) {
                            console.log('Response tidak valid untuk:', event.request.url, 'Status:', response.status);

                            // Jika status 500 atau error lainnya dan request adalah navigasi
                            if (!isLocalhost && (response.status === 500 || !response.ok) && event.request.mode === 'navigate') {
                                console.log('Server error 500, menampilkan halaman offline');
                                return caches.match('/offline.html');
                            }

                            return response;
                        }

                        // Clone the response
                        const responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(cache => {
                                console.log('Menyimpan response ke cache:', event.request.url);
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(error => {
                        console.log('Fetch error:', error);
                        // Jika request gagal (offline atau error network lainnya)
                        if (!isLocalhost && event.request.mode === 'navigate') {
                            console.log('Menampilkan halaman offline');
                            return caches.match('/offline.html');
                        }

                        // Untuk request gambar yang gagal
                        if (event.request.destination === 'image') {
                            return new Response(
                                '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f0f0f0"/><text x="50%" y="50%" font-family="Arial" font-size="20" text-anchor="middle" fill="#999">Image Offline</text></svg>',
                                { headers: { 'Content-Type': 'image/svg+xml' } }
                            );
                        }

                        throw error;
                    });
            })
    );
});

// Push Notification Event
self.addEventListener('push', event => {
    console.log('Push notification diterima:', event);

    const title = 'Praktek Obgin';
    const options = {
        body: event.data ? event.data.text() : 'Notifikasi baru',
        icon: '/assets/pwa/icons/praktekobgin_icon192.png',
        badge: '/assets/pwa/icons/praktekobgin_icon72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Lihat Aplikasi',
                icon: '/assets/pwa/icons/praktekobgin_icon72x72.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification Click Event
self.addEventListener('notificationclick', event => {
    console.log('Notifikasi diklik:', event);

    event.notification.close();

    event.waitUntil(
        clients.openWindow('/pendaftaran/form_pendaftaran_pasien.php')
    );
}); 