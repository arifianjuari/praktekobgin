<?php
// Memulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memastikan variabel $base_url tersedia
require_once __DIR__ . '/config.php';

// Definisi role
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

/**
 * Fungsi untuk memeriksa apakah user sudah login
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Fungsi untuk memeriksa apakah user adalah admin
 * @return bool
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Fungsi untuk memeriksa apakah user adalah dokter
 * @return bool
 */
function isDokter()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dokter';
}

/**
 * Fungsi untuk memeriksa apakah user adalah staff
 * @return bool
 */
function isStaff()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

/**
 * Fungsi untuk memeriksa apakah user memiliki akses ke halaman tertentu
 * @param array $allowed_roles Array berisi role yang diizinkan
 * @return bool
 */
function hasAccess($allowed_roles = [])
{
    if (!isLoggedIn()) {
        return false;
    }

    if (empty($allowed_roles)) {
        return true; // Jika tidak ada role yang ditentukan, semua user yang login diizinkan
    }

    return in_array($_SESSION['role'], $allowed_roles);
}

/**
 * Fungsi untuk redirect ke halaman login jika user belum login
 * @param string $redirect_url URL untuk redirect setelah login
 * @return void
 */
function requireLogin($redirect_url = '')
{
    global $base_url;

    if (!isLoggedIn()) {
        $redirect = empty($redirect_url) ? '' : '?redirect=' . urlencode($redirect_url);
        header('Location: ' . $base_url . '/login.php' . $redirect);
        exit;
    }
}

/**
 * Fungsi untuk redirect ke halaman beranda jika user tidak memiliki akses
 * @param array $allowed_roles Array berisi role yang diizinkan
 * @return void
 */
function requireAccess($allowed_roles = [])
{
    global $base_url;

    if (!hasAccess($allowed_roles)) {
        header('Location: ' . $base_url . '/index.php?access_denied=1');
        exit;
    }
}

/**
 * Memeriksa apakah pengguna memiliki otorisasi untuk mengakses fitur
 * @param array $allowed_roles Array dari role yang diizinkan
 * @return bool True jika pengguna memiliki otorisasi, false jika tidak
 */
function isAuthorized($allowed_roles = [])
{
    // Periksa apakah session sudah dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Periksa apakah user sudah login
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    // Jika tidak ada role yang ditentukan, izinkan akses
    if (empty($allowed_roles)) {
        return true;
    }

    // Periksa apakah role user ada dalam daftar role yang diizinkan
    return in_array($_SESSION['user_role'], $allowed_roles);
}
