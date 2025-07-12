<?php
session_start();
require_once 'config_auth.php';
require_once 'security_helpers.php';
require_once 'config/config.php';

$error = '';
$success = '';
$valid_token = false;
$user_id = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash('sha256', $token);

    try {
        // Find valid reset token
        $stmt = $auth_conn->prepare("SELECT user_id FROM password_resets WHERE token_hash = ? AND expires > NOW() AND is_used = 0");
        $stmt->execute([$token_hash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $valid_token = true;
            $user_id = $result['user_id'];
        } else {
            $error = 'Token reset password tidak valid atau sudah kadaluarsa.';
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan. Silakan coba lagi nanti.';
        error_log("Reset password token check error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request, silakan coba lagi';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate password
        $password_check = isPasswordStrong($password);
        if (!$password_check['valid']) {
            $error = $password_check['message'];
        } elseif ($password !== $confirm_password) {
            $error = 'Password tidak cocok';
        } else {
            try {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $auth_conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);

                // Mark token as used
                $stmt = $auth_conn->prepare("UPDATE password_resets SET is_used = 1 WHERE token_hash = ?");
                $stmt->execute([$token_hash]);

                // Log password reset
                logActivity($user_id, 'password_reset', 'Password berhasil direset');

                $success = 'Password berhasil direset! Silakan login dengan password baru Anda.';
            } catch (PDOException $e) {
                $error = 'Gagal mereset password. Silakan coba lagi nanti.';
                error_log("Reset password error: " . $e->getMessage());
            }
        }
    }
}

// Generate new CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sistem Antrian Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .reset-form {
            max-width: 400px;
            padding: 15px;
            margin: auto;
            margin-top: 100px;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="reset-form">
            <h2 class="text-center mb-4">Reset Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if (!$valid_token): ?>
                        <div class="mt-3">
                            <a href="<?= $base_url ?>/login.php" class="btn btn-primary">Kembali ke Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-3">
                        <a href="<?= $base_url ?>/login.php" class="btn btn-primary">Login Sekarang</a>
                    </div>
                </div>
            <?php elseif ($valid_token): ?>
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="bi bi-eye password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($valid_token): ?>
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
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('confirm_password').value;

                if (password !== confirm) {
                    e.preventDefault();
                    alert('Password tidak cocok!');
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>