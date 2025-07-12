<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Check if the pasien table exists
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'pasien'");
    $stmt->execute();
    $result = $stmt->fetchAll();

    echo "Table check result: <pre>";
    print_r($result);
    echo "</pre>";

    if (count($result) > 0) {
        echo "Table 'pasien' exists.<br>";

        // Check the structure of the pasien table
        $stmt = $conn->prepare("DESCRIBE pasien");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Table structure: <pre>";
        print_r($columns);
        echo "</pre>";

        // Count records in the pasien table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pasien");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        echo "Total records in 'pasien' table: " . $count . "<br>";

        // Get a sample of records
        if ($count > 0) {
            $stmt = $conn->prepare("SELECT * FROM pasien LIMIT 5");
            $stmt->execute();
            $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "Sample records: <pre>";
            print_r($sample);
            echo "</pre>";
        }
    } else {
        echo "Table 'pasien' does not exist.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
