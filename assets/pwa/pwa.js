// Mendaftarkan Service Worker
if ('serviceWorker' in navigator) {
    console.log('Service Worker didukung di browser ini');
    window.addEventListener('load', () => {
        console.log('Halaman dimuat, mencoba mendaftarkan Service Worker...');
        navigator.serviceWorker.register('/assets/pwa/sw.js')
            .then(registration => {
                console.log('Service Worker berhasil didaftarkan dengan scope:', registration.scope);
                console.log('Service Worker aktif:', registration.active);
                console.log('Service Worker waiting:', registration.waiting);
            })
            .catch(error => {
                console.error('Pendaftaran Service Worker gagal:', error);
                console.error('Detail error:', error.message);
            });
    });
} else {
    console.log('Service Worker tidak didukung di browser ini');
}

// Menampilkan prompt instalasi PWA
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    console.log('beforeinstallprompt event terdeteksi');
    // Mencegah Chrome 67 dan yang lebih baru untuk menampilkan prompt otomatis
    e.preventDefault();
    // Simpan event agar dapat dipanggil nanti
    deferredPrompt = e;
    console.log('deferredPrompt disimpan');

    // Tampilkan UI untuk menunjukkan bahwa aplikasi dapat diinstal
    const installButton = document.getElementById('install-button');
    if (installButton) {
        console.log('Tombol install ditemukan, menampilkan...');
        installButton.style.display = 'block';

        installButton.addEventListener('click', () => {
            console.log('Tombol install diklik');
            // Sembunyikan tombol install
            installButton.style.display = 'none';

            // Tampilkan prompt instalasi
            deferredPrompt.prompt();
            console.log('Prompt instalasi ditampilkan');

            // Tunggu pengguna merespons prompt
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('Pengguna menerima prompt instalasi');
                } else {
                    console.log('Pengguna menolak prompt instalasi');
                }
                deferredPrompt = null;
            });
        });
    } else {
        console.log('Tombol install tidak ditemukan di halaman');
    }
});

// Log ketika PWA diinstal
window.addEventListener('appinstalled', (evt) => {
    console.log('PWA berhasil diinstal');
    console.log('Detail instalasi:', evt);
}); 