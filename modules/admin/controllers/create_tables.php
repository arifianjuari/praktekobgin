<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    // Read and execute SQL file for pendaftaran table
    $sql = file_get_contents(__DIR__ . '/create_table_pendaftaran.sql');
    $conn->exec($sql);
    echo "Table 'pendaftaran' created successfully\n";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
