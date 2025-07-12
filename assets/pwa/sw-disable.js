// Script untuk menonaktifkan Service Worker
if ('serviceWorker' in navigator) {
    console.log('Menonaktifkan semua Service Worker...');
    
    // Unregister semua service worker yang terdaftar
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for(let registration of registrations) {
            registration.unregister();
            console.log('Service Worker berhasil dihapus:', registration.scope);
        }
    });
    
    // Hapus semua cache yang dibuat oleh service worker
    if ('caches' in window) {
        caches.keys().then(function(cacheNames) {
            cacheNames.forEach(function(cacheName) {
                caches.delete(cacheName);
                console.log('Cache berhasil dihapus:', cacheName);
            });
        });
    }
}

// Tambahkan alert untuk debugging
console.log('sw-disable.js telah dijalankan');
