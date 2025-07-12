<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/security_helpers.php';
require_once __DIR__ . '/../config/config.php';

class LoginController {
    public function handle() {
        if (function_exists('session_status')) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        } else {
            if (!headers_sent()) @session_start();
        }
        $error = '';
        $success = '';
        // Ambil pesan dari session jika ada
        if (isset($_SESSION['message'])) {
            if ($_SESSION['message']['type'] === 'success') {
                $success = $_SESSION['message']['text'];
            } else {
                $error = $_SESSION['message']['text'];
            }
            unset($_SESSION['message']);
        }
        // Check for remember me cookie
        global $pdo, $base_url;
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
            $remember_token = $_COOKIE['remember_token'];
            $user_id = $_COOKIE['remember_user'];
            if ($token_data = validateRememberToken($user_id, $remember_token)) {
                $stmt = $pdo->prepare("SELECT id, username, role, status FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && $user['status'] === 'active') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    setRememberMeCookie($user['id'], generateRememberToken());
                    header('Location: ' . $base_url . '/router.php?module=pendaftaran');
                    exit();
                }
            }
        }
        // Jika sudah login, redirect ke halaman utama
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . $base_url . '/router.php?module=pendaftaran');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin($error, $success);
        } else {
            $this->showLoginForm($error, $success);
        }
    }
    private function showLoginForm($error = '', $success = '') {
        require_once __DIR__ . '/../views/login_form.php';
    }
    private function processLogin(&$error, &$success) {
        global $pdo, $base_url;
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request, silakan coba lagi';
            $this->showLoginForm($error, $success);
            return;
        }
        // Check rate limiting
        $rate_limit = checkRateLimit($_SERVER['REMOTE_ADDR']);
        if (!$rate_limit['allowed']) {
            $error = $rate_limit['message'];
            $this->showLoginForm($error, $success);
            return;
        }
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role, status, email_verified FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    if ($user['email_verified']) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        if ($remember_me) {
                            setRememberMeCookie($user['id'], generateRememberToken());
                        }
                        logActivity($user['id'], 'Login', 'Login berhasil');
                        header('Location: ' . $base_url . '/router.php?module=pendaftaran');
                        exit();
                    } else {
                        $error = 'Silakan verifikasi email Anda terlebih dahulu';
                    }
                } else {
                    $error = 'Akun Anda tidak aktif';
                }
            } else {
                $error = 'Username atau password salah';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan saat login';
        }
        $this->showLoginForm($error, $success);
    }
}
