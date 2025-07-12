# Aplikasi Praktekobgin

[![Deploy to VPS](https://github.com/arifianjuari/praktekobgin/actions/workflows/deploy.yml/badge.svg)](https://github.com/arifianjuari/praktekobgin/actions/workflows/deploy.yml)

## Panduan Migrasi dari Localhost ke Server Hosting
(Terakhir diperbarui: 21 Juni 2024)

### Langkah-langkah Migrasi

1. **Persiapan File**
   - Backup seluruh database lokal
   - Backup seluruh file aplikasi

2. **Upload File ke Hosting**
   - Upload semua file ke direktori public_html atau htdocs di server hosting Anda

3. **Konfigurasi Database**
   - Buat database baru di server hosting
   - Import file SQL dari backup database lokal
   - Update konfigurasi database di file `config.php` dan `config_auth.php` dengan kredensial database hosting

4. **Konfigurasi Base URL**
   - Edit file `config/config.php` dan ubah nilai variabel `$base_url` menjadi URL domain Anda:
   ```php
   // Untuk server produksi
   $base_url = 'https://www.domain-anda.com';
   
   // Komentar atau hapus konfigurasi localhost
   // $base_url = 'http://localhost/antrian%20pasien';
   ```

5. **Pengaturan Folder dan Hak Akses**
   - Pastikan folder `uploads` memiliki izin tulis (chmod 755 atau 775)
   - Pastikan file konfigurasi memiliki izin yang tepat (chmod 644)

6. **Pengujian**
   - Uji semua fitur aplikasi untuk memastikan semuanya berfungsi dengan baik
   - Periksa apakah semua link dan redirect berfungsi dengan benar

### Catatan Penting

- Aplikasi ini telah direfaktor untuk menggunakan variabel `$base_url` yang terpusat di file `config/config.php`
- Jika Anda menemukan URL hardcoded yang masih menggunakan localhost, silakan perbaiki dengan menggunakan variabel `$base_url`
- Pastikan semua file PHP yang memerlukan akses ke `$base_url` telah meng-include file `config/config.php`

### Troubleshooting

Jika Anda mengalami masalah setelah migrasi:

1. **Masalah Database**
   - Periksa kredensial database di file konfigurasi
   - Pastikan database telah diimport dengan benar

2. **Masalah URL**
   - Periksa nilai `$base_url` di file `config/config.php`
   - Pastikan tidak ada URL hardcoded yang masih menggunakan localhost

3. **Masalah Hak Akses**
   - Pastikan folder dan file memiliki izin yang tepat
   - Periksa log error server untuk informasi lebih lanjut 