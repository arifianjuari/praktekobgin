<?php
// File ini untuk menguji output buffering di server

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Log untuk debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_error.log');
error_log("=== Start debug_ob.php execution ===");

// Test 1: Output buffering sederhana
echo "<h2>Test 1: Output Buffering Dasar</h2>";
ob_start();
echo "Ini adalah konten di dalam buffer 1";
$content1 = ob_get_clean();
echo "Hasil buffer 1: <pre>" . htmlspecialchars($content1) . "</pre>";
error_log("Buffer 1 length: " . strlen($content1));

// Test 2: Output buffering bersarang
echo "<h2>Test 2: Output Buffering Bersarang</h2>";
ob_start(); // Outer buffer
echo "Awal outer buffer<br>";
ob_start(); // Inner buffer
echo "Ini adalah konten di dalam inner buffer";
$inner_content = ob_get_clean();
echo "Inner buffer berisi: " . htmlspecialchars($inner_content) . "<br>";
echo "Akhir outer buffer";
$outer_content = ob_get_clean();
echo "Hasil outer buffer: <pre>" . htmlspecialchars($outer_content) . "</pre>";
error_log("Inner buffer length: " . strlen($inner_content));
error_log("Outer buffer length: " . strlen($outer_content));

// Test 3: Simulasi layout dengan content
echo "<h2>Test 3: Simulasi Layout dengan Content</h2>";
ob_start();
echo "Ini adalah konten yang dihasilkan oleh controller dan akan ditampilkan di layout";
$view_content = ob_get_clean();
error_log("View content length: " . strlen($view_content));

// Simulasi layout
echo "<div style='border: 1px solid blue; padding: 15px;'>";
echo "<h3>Ini adalah layout template</h3>";
echo "<div style='border: 1px solid red; padding: 10px;'>";
if (!empty($view_content)) {
    echo $view_content;
} else {
    echo "<p style='color:red'>CONTENT KOSONG! Terjadi masalah dengan output buffering.</p>";
}
echo "</div>";
echo "</div>";

// Info PHP
echo "<h2>Informasi PHP</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>output_buffering: " . ini_get('output_buffering') . "</p>";
echo "<p>Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "</p>";

error_log("=== End debug_ob.php execution ===");
?>
