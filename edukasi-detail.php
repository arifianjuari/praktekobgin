<?php
/**
 * Edukasi Detail Entry Point
 * Menjembatani akses ke controller modul Edukasi Detail.
 */

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sertakan koneksi database atau konfigurasi lain jika diperlukan secara global
require_once __DIR__ . '/config/database.php';

// Path controller detail pada modul Edukasi
$controllerFile = __DIR__ . '/modules/edukasi/controllers/edukasi-detail.php';

// Juga cek fallback path relatif jika diakses dari subfolder
if (!file_exists($controllerFile)) {
    $controllerFile = __DIR__ . '/modules/edukasi/controllers/edukasi-detail.php';
}

if (file_exists($controllerFile)) {
    // Jalankan controller
    include $controllerFile;
} else {
    http_response_code(404);
    echo '<h1>404 Not Found</h1><p>Module detail edukasi tidak ditemukan.</p>';
    exit;
}
