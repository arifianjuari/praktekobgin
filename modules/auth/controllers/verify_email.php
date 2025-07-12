<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['token'])) {
    die('Token verifikasi tidak valid.');
}

$token = $_GET['token'];

try {
    // Cek token dan update status email_verified
    $stmt = $conn->prepare("UPDATE users 
                           SET email_verified = 1, 
                               status = 'active', 
                               email_verification_token = NULL,
                               email_verification_expires = NULL,
                               updated_at = CURRENT_TIMESTAMP 
                           WHERE email_verification_token = ? 
                           AND email_verified = 0 
                           AND email_verification_expires > CURRENT_TIMESTAMP");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        $message = "Email Anda berhasil diverifikasi! Sekarang Anda dapat login ke sistem.";
        $type = "success";
    } else {
        // Cek apakah token sudah kadaluarsa
        $stmt = $conn->prepare("SELECT email_verification_expires 
                              FROM users 
                              WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        $result = $stmt->fetch();

        if ($result && strtotime($result['email_verification_expires']) < time()) {
            $message = "Token verifikasi sudah kadaluarsa. Silakan hubungi admin untuk mendapatkan token baru.";
        } else {
            $message = "Token verifikasi tidak valid atau sudah digunakan.";
        }
        $type = "danger";
    }
} catch (PDOException $e) {
    $message = "Terjadi kesalahan saat memverifikasi email.";
    $type = "danger";
    error_log("Email verification error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title mb-4">Verifikasi Email</h3>
                        <div class="alert alert-<?= $type ?>" role="alert">
                            <?= $message ?>
                        </div>
                        <a href="<?= $base_url ?>/login.php" class="btn btn-primary">Kembali ke Halaman Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>