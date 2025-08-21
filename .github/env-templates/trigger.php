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

// Kumpulkan variabel dari POST (form urlencoded atau JSON)
$env_vars = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (is_array($payload)) {
        $env_vars = $payload;
    }
} else {
    // application/x-www-form-urlencoded atau multipart/form-data
    $env_vars = $_POST ?? [];
}

// Jika POST kosong, fallback baca dari .env.secret bila tersedia
if (empty($env_vars) && file_exists($envSecretFile)) {
    $secret_content = file_get_contents($envSecretFile);
    foreach (explode("\n", $secret_content) as $line) {
        if (empty($line) || strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env_vars[trim($key)] = trim($value);
    }
}

// Verifikasi file template ada
if (!file_exists($productionEnvFile)) {
    http_response_code(500);
    die('Production environment template tidak ditemukan');
}

// Baca production.env template
$production_template = file_get_contents($productionEnvFile);

// Substitusi variabel berdasarkan placeholder ${VAR}
foreach ($env_vars as $key => $value) {
    $production_template = str_replace('${' . $key . '}', $value, $production_template);
}

// Update timestamp
$production_template = str_replace('<?php echo date(\'Y-m-d H:i:s\'); ?>', date('Y-m-d H:i:s'), $production_template);

// Tulis ke file config.env dengan permission aman
if (file_put_contents($targetEnvFile, $production_template) !== false) {
    chmod($targetEnvFile, 0600);
    echo "Environment setup berhasil. Config file dibuat dengan permission 0600.";
} else {
    http_response_code(500);
    die('Gagal menulis config.env file. Periksa permission.');
}

// Hapus file sensitif jika ada (best practice)
@unlink($envSecretFile);
