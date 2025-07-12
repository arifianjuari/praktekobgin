<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Global database connection variable
global $conn;

// Get root directory
$root_dir = dirname(dirname(__FILE__));

// Include database configuration if not already included
if (!isset($conn) || !($conn instanceof PDO)) {
    require_once $root_dir . '/config/database.php';
}

// Include base URL configuration if not already included
if (!isset($base_url)) {
    require_once $root_dir . '/config/config.php';
}

/**
 * Get database connection
 * @return PDO Database connection
 */
function get_db_connection()
{
    global $conn;

    if (!isset($conn) || !($conn instanceof PDO)) {
        require dirname(dirname(__FILE__)) . '/config/database.php';
    }

    return $conn;
}

/**
 * Check if database connection is working
 * @return bool True if connection is working, false otherwise
 */
function check_db_connection()
{
    try {
        $conn = get_db_connection();
        $test = $conn->query("SELECT 1");
        return ($test !== false);
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a database query
 * @param string $query SQL query to execute
 * @param array $params Parameters for prepared statement
 * @return PDOStatement|false Query result or false on failure
 */
function db_query($query, $params = [])
{
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a single row from database
 * @param string $query SQL query to execute
 * @param array $params Parameters for prepared statement
 * @return array|false Single row or false on failure
 */
function db_get_row($query, $params = [])
{
    $stmt = db_query($query, $params);
    if ($stmt) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

/**
 * Get multiple rows from database
 * @param string $query SQL query to execute
 * @param array $params Parameters for prepared statement
 * @return array|false Array of rows or false on failure
 */
function db_get_all($query, $params = [])
{
    $stmt = db_query($query, $params);
    if ($stmt) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return false;
}

// Initialize database connection
if (!check_db_connection()) {
    error_log("Database connection failed in functions.php");
    die("Database connection failed. Please check your configuration.");
}
