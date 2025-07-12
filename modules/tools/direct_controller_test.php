<?php
// File untuk menguji controller secara langsung tanpa melalui index.php

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Aktifkan logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/direct_controller_error.log');
error_log("=== Start direct_controller_test.php execution ===");

// Define base path
define('BASE_PATH', __DIR__);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Direct Controller Test</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

// Load dependencies
try {
    echo "<h2>1. Loading dependencies</h2>";
    
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        echo "<p class='success'>✓ Database config loaded</p>";
        error_log("Database config loaded");
    } else {
        echo "<p class='error'>✗ Database config not found</p>";
        error_log("ERROR: Database config not found");
    }
    
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
        echo "<p class='success'>✓ App config loaded</p>";
        error_log("App config loaded");
    } else {
        echo "<p class='error'>✗ App config not found</p>";
        error_log("WARNING: App config not found");
    }
    
    if (file_exists('modules/rekam_medis/controllers/RekamMedisController.php')) {
        require_once 'modules/rekam_medis/controllers/RekamMedisController.php';
        echo "<p class='success'>✓ RekamMedisController loaded</p>";
        error_log("RekamMedisController loaded");
    } else {
        echo "<p class='error'>✗ RekamMedisController not found</p>";
        error_log("ERROR: RekamMedisController not found");
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>Error loading dependencies: " . $e->getMessage() . "</p>";
    error_log("Error loading dependencies: " . $e->getMessage());
    exit;
}

// Check database connection
echo "<h2>2. Checking database connection</h2>";
if (!isset($conn) || !($conn instanceof PDO)) {
    echo "<p class='error'>✗ Database connection not available</p>";
    error_log("ERROR: Database connection not available");
    exit;
} else {
    try {
        $stmt = $conn->query("SELECT 1");
        echo "<p class='success'>✓ Database connection successful</p>";
        error_log("Database connection successful");
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Database connection error: " . $e->getMessage() . "</p>";
        error_log("ERROR: Database connection error: " . $e->getMessage());
        exit;
    }
}

// Initialize controller
echo "<h2>3. Initializing controller</h2>";
try {
    $rekamMedisController = new RekamMedisController($conn);
    echo "<p class='success'>✓ Controller initialized successfully</p>";
    error_log("Controller initialized successfully");
} catch (Exception $e) {
    echo "<p class='error'>✗ Error initializing controller: " . $e->getMessage() . "</p>";
    error_log("ERROR: Error initializing controller: " . $e->getMessage());
    exit;
}

// Execute controller method with output buffering
echo "<h2>4. Executing controller method (dataPasien)</h2>";
try {
    // Start output buffering
    ob_start();
    error_log("Starting controller->dataPasien() with ob_start");
    
    // Execute controller method
    $rekamMedisController->dataPasien();
    
    // Get buffered content
    $content = ob_get_clean();
    error_log("Controller execution finished, content length: " . strlen($content));
    
    // Display stats about captured content
    echo "<p>Content captured length: " . strlen($content) . " bytes</p>";
    
    if (empty($content)) {
        echo "<p class='error'>✗ Controller produced no output</p>";
        error_log("ERROR: Controller produced no output");
    } else {
        echo "<p class='success'>✓ Controller produced output</p>";
        // Display truncated content for debugging
        $preview = substr($content, 0, 200) . (strlen($content) > 200 ? '...' : '');
        echo "<pre style='background:#eee; padding:10px; max-height:150px; overflow:auto;'>" . 
             htmlspecialchars($preview) . 
             "</pre>";
    }
    
    // Display full content in an iframe
    echo "<h3>Content preview:</h3>";
    echo "<iframe id='content-preview' style='width:100%; height:500px; border:1px solid #ccc;'></iframe>";
    echo "<script>
        document.getElementById('content-preview').contentDocument.open();
        document.getElementById('content-preview').contentDocument.write(" . json_encode($content) . ");
        document.getElementById('content-preview').contentDocument.close();
    </script>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error executing controller method: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    error_log("ERROR: Error executing controller method: " . $e->getMessage());
    error_log($e->getTraceAsString());
}

echo "<style>
.success { color: green; }
.error { color: red; font-weight: bold; }
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; }
</style>";

error_log("=== End direct_controller_test.php execution ===");
?>
