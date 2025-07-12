<?php
// Define base path if not already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Include dependencies with absolute paths
require_once dirname(__DIR__) . '/config/koneksi.php';

// Password validation
function isPasswordStrong($password)
{
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password harus minimal 8 karakter'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password harus mengandung huruf besar'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password harus mengandung huruf kecil'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password harus mengandung angka'];
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password harus mengandung karakter khusus'];
    }
    return ['valid' => true, 'message' => 'Password valid'];
}

// Rate limiting
function checkRateLimit($ip, $action = 'login', $max_attempts = 5, $timeframe = 300)
{
    global $pdo; // Menggunakan $pdo dari koneksi.php

    try {
        // Clear old attempts
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$timeframe]);

        // Count recent attempts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$ip, $timeframe]);
        $attempts = $stmt->fetchColumn();

        if ($attempts >= $max_attempts) {
            return ['allowed' => false, 'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam beberapa menit.'];
        }

        // Log new attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
        $stmt->execute([$ip]);

        return ['allowed' => true, 'message' => ''];
    } catch (PDOException $e) {
        error_log("Rate limit error: " . $e->getMessage());
        return ['allowed' => true, 'message' => '']; // Biarkan login berlanjut jika ada error
    }
}

// CSRF Protection
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Email validation
function isEmailValid($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Format email tidak valid'];
    }

    // Check email domain has valid MX record
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, 'MX')) {
        return ['valid' => false, 'message' => 'Domain email tidak valid'];
    }

    return ['valid' => true, 'message' => 'Email valid'];
}

// Activity logging
function logActivity($user_id, $action, $details = '')
{
    global $pdo;
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $timestamp = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, waktu, info, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $timestamp, $action . ': ' . $details, $ip, $user_agent]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false; // Gagal logging tidak harus menghentikan proses login
    }
}

// Remember Me functionality
function generateRememberToken()
{
    return bin2hex(random_bytes(32));
}

function setRememberMeCookie($user_id, $token)
{
    $token_hash = hash('sha256', $token);
    $expires = time() + (30 * 24 * 60 * 60); // 30 days

    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires) VALUES (?, ?, FROM_UNIXTIME(?))");
    $stmt->execute([$user_id, $token_hash, $expires]);

    setcookie('remember_token', $token, $expires, '/', '', true, true);
    setcookie('remember_user', $user_id, $expires, '/', '', true, true);
}

function validateRememberToken($user_id, $token)
{
    global $pdo; // Gunakan koneksi database global

    try {
        $stmt = $pdo->prepare("SELECT * FROM remember_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([$user_id, $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error validating remember token: " . $e->getMessage());
        return false;
    }
}
