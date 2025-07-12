<?php
/**
 * Script untuk memicu setup environment
 * File ini ditempatkan di public_html/.env-setup/ dan diakses melalui HTTP request
 * dari GitHub Actions workflow
 */

// Validasi token keamanan
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$expected_token = ''; // Token akan diisi oleh env_setup.php

// Verifikasi request
if (empty($auth_header) || $auth_header !== $expected_token) {
    http_response_code(403);
    die('Unauthorized');
}

// Path ke file
$envSecretFile = __DIR__ . '/.env.secret';
$productionEnvFile = __DIR__ . '/production.env';
$targetEnvFile = __DIR__ . '/../config.env';
$setupScriptFile = __DIR__ . '/env_setup.php';

// Verifikasi bahwa semua file diperlukan ada
if (!file_exists($envSecretFile)) {
    http_response_code(500);
    die('Secret environment file tidak ditemukan');
}

if (!file_exists($productionEnvFile)) {
    http_response_code(500);
    die('Production environment template tidak ditemukan');
}

// Baca variabel dari .env.secret
$env_vars = [];
$secret_content = file_get_contents($envSecretFile);
foreach (explode("\n", $secret_content) as $line) {
    if (empty($line) || strpos($line, '#') === 0) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $env_vars[trim($key)] = trim($value);
}

// Baca production.env template
$production_template = file_get_contents($productionEnvFile);

// Substitusi variabel
foreach ($env_vars as $key => $value) {
    $production_template = str_replace('${' . $key . '}', $value, $production_template);
}

// Update timestamp
$production_template = str_replace('<?php echo date(\'Y-m-d H:i:s\'); ?>', date('Y-m-d H:i:s'), $production_template);

// Tulis ke file config.env
if (file_put_contents($targetEnvFile, $production_template) !== false) {
    // Set permission ke 600 (hanya owner bisa baca/tulis)
    chmod($targetEnvFile, 0600);
    echo "Environment setup berhasil. Config file dibuat dengan permission 0600.";
} else {
    http_response_code(500);
    die('Gagal menulis config.env file. Periksa permission.');
}

// Hapus file sensitif (best practice)
@unlink($envSecretFile);
