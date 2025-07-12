// Script untuk Service Worker
if ('serviceWorker' in navigator) {
    // Deteksi localhost
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    // Untuk lingkungan development (localhost) - nonaktifkan service worker
    if (isLocalhost) {
        console.log('Lingkungan development (localhost) terdeteksi');
        console.log('Menonaktifkan Service Worker untuk pengembangan...');
        
        // Unregister semua service worker yang terdaftar
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.unregister();
                console.log('Service Worker berhasil dihapus');
            }
        });
    } 
    // Untuk lingkungan produksi - aktifkan service worker
    else {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/assets/pwa/sw.js', { scope: '/' })
                .then(registration => {
                    console.log('ServiceWorker berhasil didaftarkan dengan scope:', registration.scope);

                    // Periksa apakah ada pembaruan service worker
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('Service worker baru sedang diinstal');

                        newWorker.addEventListener('statechange', () => {
                            console.log('Service worker state:', newWorker.state);
                        });
                    });

                    // Mendaftarkan untuk push notification jika didukung
                    if ('PushManager' in window) {
                        console.log('Push notification didukung');

                        // Meminta izin notifikasi
                        Notification.requestPermission().then(permission => {
                            if (permission === 'granted') {
                                console.log('Izin notifikasi diberikan');
                            } else {
                                console.log('Izin notifikasi ditolak');
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('ServiceWorker gagal didaftarkan:', error);
                });

            // Periksa apakah ada service worker yang perlu diperbarui
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('Service worker controller berubah');
            });
        });
    }
}

// Mendeteksi apakah aplikasi dijalankan dalam mode standalone (PWA)
window.addEventListener('DOMContentLoaded', () => {
    // Cek apakah aplikasi dijalankan sebagai PWA
    const isInStandaloneMode = () =>
        (window.matchMedia('(display-mode: standalone)').matches) ||
        (window.navigator.standalone) ||
        document.referrer.includes('android-app://');

    if (isInStandaloneMode()) {
        console.log('Aplikasi dijalankan dalam mode PWA');
        // Tambahkan kelas ke body untuk styling khusus PWA jika diperlukan
        document.body.classList.add('pwa-mode');
    }

    // Tambahkan event listener untuk beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('Prompt instalasi PWA tersedia');
        // Simpan event untuk digunakan nanti
        window.deferredPrompt = e;

        // Tampilkan tombol instalasi jika ada
        const installButton = document.getElementById('pwa-install-btn');
        if (installButton) {
            installButton.style.display = 'block';
            installButton.addEventListener('click', () => {
                // Tampilkan prompt instalasi
                window.deferredPrompt.prompt();
                // Tunggu pengguna merespons prompt
                window.deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Pengguna menerima instalasi PWA');
                    } else {
                        console.log('Pengguna menolak instalasi PWA');
                    }
                    // Clear the deferredPrompt
                    window.deferredPrompt = null;
                });
            });
        }
    });
});

// Mendeteksi perubahan status koneksi
window.addEventListener('online', () => {
    console.log('Aplikasi online');
    // Tampilkan notifikasi atau perbarui UI
    if (document.getElementById('offline-indicator')) {
        document.getElementById('offline-indicator').style.display = 'none';
    }
    // Reload halaman jika sebelumnya offline
    if (window.wasOffline) {
        window.wasOffline = false;
        window.location.reload();
    }
});

window.addEventListener('offline', () => {
    console.log('Aplikasi offline');
    // Tandai bahwa aplikasi sedang offline
    window.wasOffline = true;
    // Tampilkan notifikasi atau perbarui UI
    if (document.getElementById('offline-indicator')) {
        document.getElementById('offline-indicator').style.display = 'block';
    } else {
        // Buat indikator offline jika belum ada
        const offlineIndicator = document.createElement('div');
        offlineIndicator.id = 'offline-indicator';
        offlineIndicator.innerHTML = '<div style="position: fixed; bottom: 0; left: 0; right: 0; background-color: #dc3545; color: white; text-align: center; padding: 8px; z-index: 9999;">Anda sedang offline. Beberapa fitur mungkin tidak tersedia.</div>';
        document.body.appendChild(offlineIndicator);
    }
}); 