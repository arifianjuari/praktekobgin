<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Pastikan file config_auth.php ada dan berisi koneksi database
if (!file_exists('config_auth.php')) {
    die("Error: File config_auth.php tidak ditemukan. Silakan hubungi administrator.");
}

require_once 'config_auth.php';
require_once 'security_helpers.php';
require_once 'config/config.php';

// Gunakan koneksi yang benar dari config_auth.php
$auth_conn = $conn_db2;

// Periksa koneksi database
if (!isset($auth_conn) || !($auth_conn instanceof PDO)) {
    die("Error: Koneksi database tidak tersedia. Silakan periksa file config_auth.php.");
}

$error = '';
$success = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debugging
        $debug_info .= "POST data diterima\n";

        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request, silakan coba lagi');
        }
        $debug_info .= "CSRF token valid\n";

        // Sanitasi input - gunakan htmlspecialchars sebagai pengganti FILTER_SANITIZE_STRING
        $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $debug_info .= "Input disanitasi: username=$username, email=$email\n";

        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            throw new Exception('Semua field harus diisi');
        }

        // Validasi panjang username
        if (strlen($username) < 4 || strlen($username) > 20) {
            throw new Exception('Username harus antara 4-20 karakter');
        }

        // Validasi format username
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('Username hanya boleh mengandung huruf, angka, dan underscore');
        }
        $debug_info .= "Username valid\n";

        // Validate password strength
        $password_check = isPasswordStrong($password);
        if (!$password_check['valid']) {
            throw new Exception($password_check['message']);
        }
        $debug_info .= "Password kuat\n";

        // Validate email
        $email_check = isEmailValid($email);
        if (!$email_check['valid']) {
            throw new Exception($email_check['message']);
        }
        $debug_info .= "Email valid\n";

        // Check password match
        if ($password !== $confirm_password) {
            throw new Exception('Password tidak cocok');
        }
        $debug_info .= "Password cocok\n";

        // Check if username or email already exists
        $stmt = $auth_conn->prepare("SELECT id, email FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug_info .= "Cek user existing selesai\n";

        if ($existing_user) {
            if ($existing_user['email'] === $email) {
                throw new Exception('Email sudah terdaftar');
            } else {
                throw new Exception('Username sudah digunakan');
            }
        }
        $debug_info .= "User belum terdaftar\n";

        // Generate email verification token
        $verification_token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $verification_token);
        $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $debug_info .= "Token verifikasi dibuat\n";

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $debug_info .= "Password di-hash\n";

        // Begin transaction
        $auth_conn->beginTransaction();
        $debug_info .= "Transaksi dimulai\n";

        try {
            // Insert new user - Gunakan query yang lebih sederhana
            $query = "INSERT INTO users (username, email, password, role, status, email_verified, email_verification_token, email_verification_expires) 
                     VALUES (?, ?, ?, 'user', 'pending', 0, ?, ?)";
            $debug_info .= "Query: $query\n";

            $stmt = $auth_conn->prepare($query);
            $debug_info .= "Statement prepared\n";

            $result = $stmt->execute([
                $username,
                $email,
                $hashed_password,
                $token_hash,
                $token_expires
            ]);

            if (!$result) {
                $error_info = $stmt->errorInfo();
                $debug_info .= "Execute error: " . print_r($error_info, true) . "\n";
                throw new Exception("Database error: " . $error_info[2]);
            }

            $debug_info .= "User berhasil diinsert\n";

            $user_id = $auth_conn->lastInsertId();
            $debug_info .= "User ID: $user_id\n";

            // Log registration
            if (function_exists('logActivity')) {
                logActivity($user_id, 'register', 'Pendaftaran berhasil');
                $debug_info .= "Activity logged\n";
            } else {
                $debug_info .= "Warning: fungsi logActivity tidak ditemukan\n";
            }

            // Send verification email
            $verification_link = "http://{$_SERVER['HTTP_HOST']}/verify_email.php?token=" . urlencode($verification_token);
            $to = $email;
            $subject = "Verifikasi Email - Sistem Antrian Pasien";
            $message = "Terima kasih telah mendaftar di Sistem Antrian Pasien.\n\n";
            $message .= "Silakan klik link berikut untuk memverifikasi email Anda:\n";
            $message .= $verification_link . "\n\n";
            $message .= "Link ini akan kadaluarsa dalam 24 jam.\n";
            $headers = "From: noreply@example.com";
            $debug_info .= "Email siap dikirim\n";

            // Coba kirim email, tapi jangan gagalkan registrasi jika email gagal
            $mail_sent = mail($to, $subject, $message, $headers);
            if (!$mail_sent) {
                $debug_info .= "Email gagal dikirim, tapi registrasi tetap dilanjutkan\n";
                error_log("Failed to send verification email to $email");
            } else {
                $debug_info .= "Email berhasil dikirim\n";
            }

            // Commit transaction
            $auth_conn->commit();
            $debug_info .= "Transaksi di-commit\n";

            $success = 'Pendaftaran berhasil! Silakan cek email Anda untuk verifikasi.';
            $debug_info .= "Proses registrasi selesai\n";

            // Redirect ke halaman sukses
            $_SESSION['success_message'] = $success;
            $_SESSION['debug_info'] = $debug_info;
            header("Location: register_success.php");
            exit;
        } catch (Exception $e) {
            $auth_conn->rollBack();
            $debug_info .= "Exception: " . $e->getMessage() . "\n";
            error_log("Registration error: " . $e->getMessage());
            throw new Exception('Pendaftaran gagal. Silakan coba lagi nanti. Error: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $debug_info .= "Caught exception: " . $error . "\n";
    }
}

// Generate new CSRF token
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : md5(uniqid(mt_rand(), true));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistem Antrian Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .register-form {
            max-width: 450px;
            padding: 2rem;
            margin: auto;
            margin-top: 50px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .password-strength {
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .form-floating {
            position: relative;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="register-form">
            <h2 class="text-center mb-4">Register</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off" id="registerForm" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required
                        pattern="^[a-zA-Z0-9_]{4,20}$" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <label for="username">Username</label>
                    <div class="invalid-feedback">
                        Username harus 4-20 karakter dan hanya boleh mengandung huruf, angka, dan underscore
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <label for="email">Email</label>
                    <div class="invalid-feedback">
                        Masukkan alamat email yang valid
                    </div>
                </div>

                <div class="form-floating mb-3 position-relative">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password" required>
                    <label for="confirm_password">Konfirmasi Password</label>
                    <i class="bi bi-eye password-toggle" id="toggleConfirmPassword"></i>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">Register</button>

                <div class="text-center mt-3">
                    <p class="mb-0">Sudah punya akun? <a href="<?= $base_url ?>/login.php" class="text-decoration-none">Login di sini</a></p>
                </div>
            </form>

            <?php if (!empty($debug_info) && (isset($_GET['debug']) || defined('DEBUG_MODE'))): ?>
                <div class="debug-info mt-4">
                    <h5>Debug Info:</h5>
                    <pre><?php echo htmlspecialchars($debug_info); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);

            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                toggle.classList.toggle('bi-eye');
                toggle.classList.toggle('bi-eye-slash');
            });
        }

        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

        // Password strength checker
        const password = document.getElementById('password');
        const strengthMeter = document.getElementById('passwordStrength');

        password.addEventListener('input', function() {
            const val = password.value;
            let strength = 0;
            let message = '';

            if (val.length >= 8) strength++;
            if (val.match(/[A-Z]/)) strength++;
            if (val.match(/[a-z]/)) strength++;
            if (val.match(/[0-9]/)) strength++;
            if (val.match(/[^A-Za-z0-9]/)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    message = '<span class="text-danger">Sangat Lemah</span>';
                    break;
                case 2:
                    message = '<span class="text-warning">Lemah</span>';
                    break;
                case 3:
                    message = '<span class="text-info">Sedang</span>';
                    break;
                case 4:
                    message = '<span class="text-primary">Kuat</span>';
                    break;
                case 5:
                    message = '<span class="text-success">Sangat Kuat</span>';
                    break;
            }

            strengthMeter.innerHTML = 'Kekuatan Password: ' + message;
        });

        // Form validation
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }

            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Password tidak cocok!');
            }

            form.classList.add('was-validated');
        });

        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            const alerts = document.getElementsByClassName('alert');
            for (let alert of alerts) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, 5000);
    </script>
</body>

</html>