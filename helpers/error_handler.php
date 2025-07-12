<?php

/**
 * Custom Error Handler
 * 
 * Menangani error PHP dan mengarahkan ke halaman offline.html jika terjadi error fatal
 */

// Set error reporting level
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Buat direktori log jika belum ada
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Fungsi untuk menangani error
function customErrorHandler($errno, $errstr, $errfile, $errline)
{
    // Log error
    error_log("Error [$errno] $errstr in $errfile on line $errline");

    // Jangan tampilkan error untuk user
    return true;
}

// Fungsi untuk menangani fatal error
function fatalErrorHandler()
{
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Log fatal error
        error_log("FATAL ERROR [{$error['type']}] {$error['message']} in {$error['file']} on line {$error['line']}");

        // Redirect ke halaman offline untuk PWA
        if (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
            header('HTTP/1.1 500 Internal Server Error');

            // Cek apakah request dari PWA
            $isPWA = isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'navigate';

            if ($isPWA) {
                // Jika PWA, arahkan ke offline.html
                header('Location: /offline.html');
            } else {
                // Tampilkan pesan error yang lebih user-friendly
                echo '<html><head><title>Error</title>';
                echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                echo '<style>body{font-family:Arial,sans-serif;margin:0;padding:20px;background:#f8f9fa;}';
                echo '.error-container{max-width:600px;margin:50px auto;background:white;border-radius:10px;padding:20px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}';
                echo 'h1{color:#dc3545;}p{color:#6c757d;}</style></head>';
                echo '<body><div class="error-container">';
                echo '<h1>Terjadi Kesalahan</h1>';
                echo '<p>Maaf, terjadi kesalahan pada server. Tim kami sedang mengatasi masalah ini.</p>';
                echo '<p>Silakan coba lagi nanti atau hubungi administrator jika masalah berlanjut.</p>';
                echo '</div></body></html>';
            }
            exit;
        }
    }
}

// Set custom error handler
set_error_handler('customErrorHandler');

// Register shutdown function untuk menangani fatal error
register_shutdown_function('fatalErrorHandler');
