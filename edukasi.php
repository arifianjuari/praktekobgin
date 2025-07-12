<?php
/**
 * Edukasi Entry Point
 * Routes to the modular edukasi controller
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/config/database.php';

// Check if the modular controller exists
$controllerFile = __DIR__ . '/modules/edukasi/controllers/edukasi.php';

if (file_exists($controllerFile)) {
    // Include and execute the modular controller
    include $controllerFile;
} else {
    // Fallback error handling
    http_response_code(404);
    echo '<h1>404 Not Found</h1><p>Edukasi module tidak ditemukan.</p>';
    exit;
}
