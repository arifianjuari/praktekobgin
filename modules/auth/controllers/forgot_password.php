<?php
require_once 'error_display.php';
session_start();
require_once 'config_auth.php';
require_once 'security_helpers.php';
require_once 'email_helper.php';

$error = '';
$success = '';
$base_url = "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request, silakan coba lagi';
    } else {
        $email = $_POST['email'] ?? '';

        // Check rate limiting
        $rate_limit = checkRateLimit($_SERVER['REMOTE_ADDR'], 'forgot_password');
        if (!$rate_limit['allowed']) {
            $error = $rate_limit['message'];
        } else {
            if (empty($email)) {
                $error = 'Silakan masukkan email Anda';
            } else {
                try {
                    // Check if email exists and user is active
                    $stmt = $auth_conn->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        // Generate reset token
                        $reset_token = bin2hex(random_bytes(32));
                        $token_hash = hash('sha256', $reset_token);
                        $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                        // Save reset token
                        $stmt = $auth_conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires) VALUES (?, ?, ?)");
                        $stmt->execute([$user['id'], $token_hash, $token_expires]);

                        // Send reset email
                        $reset_link = rtrim($base_url, '/') . "/reset_password.php?token=" . urlencode($reset_token);
                        $subject = "Reset Password - Sistem Antrian Pasien";
                        $message = "Hai {$user['username']},<br><br>";
                        $message .= "Kami menerima permintaan untuk mereset password akun Anda.<br><br>";
                        $message .= "Silakan klik link berikut untuk mereset password Anda:<br>";
                        $message .= "<a href='$reset_link'>$reset_link</a><br><br>";
                        $message .= "Link ini akan kadaluarsa dalam 1 jam.<br>";
                        $message .= "Jika Anda tidak meminta reset password, abaikan email ini.";

                        $result = sendEmail($email, $subject, $message);

                        if ($result['success']) {
                            // Log reset request
                            logActivity($user['id'], 'password_reset_requested', 'Permintaan reset password');
                            $success = 'Instruksi reset password telah dikirim ke email Anda.';
                        } else {
                            $error = $result['message'];
                        }
                    } else {
                        // Return same message even if email doesn't exist (security through obscurity)
                        $success = 'Jika email terdaftar, instruksi reset password akan dikirim.';
                    }
                } catch (PDOException $e) {
                    $error = 'Terjadi kesalahan. Silakan coba lagi nanti.';
                    error_log("Forgot password error: " . $e->getMessage());
                }
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
    <title>Lupa Password - Sistem Antrian Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .forgot-password-form {
            max-width: 400px;
            padding: 15px;
            margin: auto;
            margin-top: 100px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="forgot-password-form">
            <h2 class="text-center mb-4">Lupa Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-3">
                        <a href="<?= $base_url ?>/login.php" class="btn btn-primary">Kembali ke Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kirim Link Reset Password</button>
                    <div class="text-center mt-3">
                        <a href="<?= $base_url ?>/login.php">Kembali ke Login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>