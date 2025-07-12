<?php
session_start();

// Ambil pesan sukses dari session
$success_message = $_SESSION['success_message'] ?? 'Pendaftaran berhasil!';
$debug_info = $_SESSION['debug_info'] ?? '';

// Hapus pesan dari session setelah diambil
unset($_SESSION['success_message']);
unset($_SESSION['debug_info']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Berhasil - Sistem Antrian Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }

        .success-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
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
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h2 class="mb-4">Registrasi Berhasil!</h2>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <p>Silakan cek email Anda untuk melakukan verifikasi akun.</p>
            <p>Jika Anda tidak menerima email dalam beberapa menit, silakan cek folder spam atau hubungi administrator.</p>

            <div class="mt-4">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="index.php" class="btn btn-outline-secondary ms-2">Kembali ke Beranda</a>
            </div>

            <?php if (!empty($debug_info) && (isset($_GET['debug']) || defined('DEBUG_MODE'))): ?>
                <div class="debug-info mt-4">
                    <h5>Debug Info:</h5>
                    <pre><?php echo htmlspecialchars($debug_info); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>