# Progressive Web App (PWA) - Praktek Obgin

Folder ini berisi file-file yang diperlukan untuk implementasi Progressive Web App (PWA) pada aplikasi Praktek Obgin.

## Komponen PWA

1. **manifest.json** - File manifest yang menentukan bagaimana aplikasi akan ditampilkan saat diinstal di perangkat pengguna.
2. **sw.js** - Service Worker yang menangani caching dan fungsionalitas offline.
3. **register-sw.js** - Script untuk mendaftarkan Service Worker.
4. **icons/** - Folder berisi ikon-ikon dengan berbagai ukuran untuk PWA.

## Cara Kerja PWA

1. **Installable** - Pengguna dapat menginstal aplikasi ke layar utama perangkat mereka.
2. **Offline Support** - Aplikasi dapat berfungsi bahkan ketika tidak ada koneksi internet.
3. **Push Notifications** - Aplikasi dapat mengirim notifikasi ke perangkat pengguna.
4. **App-like Experience** - Aplikasi berjalan dalam jendela terpisah, tanpa UI browser.

## Pengujian PWA

Untuk menguji PWA, Anda dapat menggunakan:

1. **Chrome DevTools** - Buka aplikasi di Chrome, klik kanan > Inspect > Application > Manifest/Service Workers.
2. **Lighthouse** - Alat audit yang tersedia di Chrome DevTools untuk menilai kualitas PWA.
3. **PWA Builder** - Layanan online untuk menguji dan meningkatkan PWA Anda (https://www.pwabuilder.com/).

## Troubleshooting

Jika PWA tidak berfungsi dengan baik, periksa:

1. **HTTPS** - PWA memerlukan HTTPS untuk berfungsi dengan baik.
2. **Service Worker** - Pastikan Service Worker terdaftar dan aktif.
3. **Manifest** - Pastikan manifest.json valid dan dapat diakses.
4. **Ikon** - Pastikan semua ikon yang diperlukan tersedia.

## Referensi

- [Web.dev PWA Guide](https://web.dev/progressive-web-apps/)
- [MDN PWA Documentation](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Google PWA Checklist](https://web.dev/pwa-checklist/) 