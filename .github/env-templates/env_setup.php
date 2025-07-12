<?php
/**
 * PHP Script untuk memproses production.env ke config.env
 * File ini akan menjadi bagian dari deployment dan akan dijalankan di server
 * 
 * Cara kerja:
 * 1. Baca file production.env yang dikirim oleh GitHub Actions
 * 2. Replace placeholder variables dengan nilai yang benar
 * 3. Tulis ke file config.env yang digunakan aplikasi
 */

// Pastikan script hanya dijalankan dari CLI
if (php_sapi_name() !== 'cli') {
    die("Script ini hanya dapat dijalankan dari command line.");
}

// Path ke file
$sourceFile = __DIR__ . '/../../production.env';
$targetFile = __DIR__ . '/../../config.env';

// Pastikan file sumber ada
if (!file_exists($sourceFile)) {
    die("File production.env tidak ditemukan.");
}

// Baca konten file template
$content = file_get_contents($sourceFile);

// Variabel environment yang akan diambil dari GitHub Actions secrets
// melalui placeholder ${NAMA_VAR} di file production.env
$env_vars = [
    'DB_USER' => getenv('DB_USER') ?: 'praktekobgin_dev',
    'DB_PASS' => getenv('DB_PASS') ?: 'secure_password'
];

// Replace placeholder dengan nilai sebenarnya
foreach ($env_vars as $key => $value) {
    $content = str_replace('${' . $key . '}', $value, $content);
}

// Tambahkan timestamp update
$content = str_replace('<?php echo date(\'Y-m-d H:i:s\'); ?>', date('Y-m-d H:i:s'), $content);

// Tulis ke file config.env
if (file_put_contents($targetFile, $content) !== false) {
    echo "File config.env berhasil dibuat.\n";
    // Set permission aman untuk file sensitif
    chmod($targetFile, 0600);
    echo "Permission file diatur ke 0600.\n";
} else {
    die("Gagal menulis file config.env. Periksa permission folder.");
}
