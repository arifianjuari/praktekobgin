<?php
session_start();

// Define ROOT_PATH if not already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../../../'));
}

// Simpan base_url sebelum menghapus session
require_once ROOT_PATH . '/config/config.php';

// Hapus semua data session
$_SESSION = array();

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hapus cookie lain yang mungkin diset saat login
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Hancurkan session
session_destroy();

// Start session baru khusus untuk pesan
session_start();
$_SESSION['message'] = array(
    'type' => 'success',
    'text' => 'Anda telah berhasil keluar dari sistem.'
);

// Redirect ke halaman pendaftaran pasien (landing page) menggunakan base_url dari config.php
header("Location: " . $base_url . "/modules/pendaftaran/views/form_pendaftaran_pasien.php");
exit;
