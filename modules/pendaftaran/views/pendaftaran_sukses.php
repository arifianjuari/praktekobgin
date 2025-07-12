<?php
// File: pendaftaran_sukses.php
// Deskripsi: Tampilan sukses setelah pendaftaran berhasil

// Periksa apakah session sudah dimulai
if (function_exists('session_status')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} else {
    if (!headers_sent()) {
        @session_start();
    }
}

// Aktifkan log error
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Load database configuration
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/config.php';

// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = $protocol . $host . $basePath;

// Ambil parameter dari URL
$id = isset($_GET['id']) ? $_GET['id'] : '';
error_log("Pendaftaran sukses page loaded with ID: $id");

// Dapatkan data pendaftaran jika ID tersedia
$pendaftaran_data = null;
if (!empty($id) && isset($conn)) {
    try {
        $stmt = $conn->prepare("SELECT p.*, 
                                     tp.Nama_Tempat, 
                                     d.Nama_Dokter,
                                     jr.Hari, 
                                     jr.Jam_Mulai, 
                                     jr.Jam_Selesai
                               FROM pendaftaran p 
                               LEFT JOIN tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
                               LEFT JOIN dokter d ON p.ID_Dokter = d.ID_Dokter
                               LEFT JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
                               WHERE p.ID_Pendaftaran = ?");
        $stmt->execute([$id]);
        $pendaftaran_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pendaftaran_data) {
            error_log("Pendaftaran data found for ID: $id");
        } else {
            error_log("No pendaftaran data found for ID: $id");
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
    }
}

// Page title
$page_title = "Pendaftaran Berhasil";

// CSS styles will be included directly in this file
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Praktik Obstetri Ginekologi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #198754;
            --primary-dark: #0d6a3f;
            --secondary: #6c757d;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .text-teal {
            color: var(--primary);
        }
        .btn-teal {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .btn-teal:hover {
            background-color: var(--primary-dark);
            color: white;
            border-color: var(--primary-dark);
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
        }
        .success-animation {
            text-align: center;
            margin: 30px 0;
        }
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            box-sizing: content-box;
            border: 4px solid #4CAF50;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scale-up 0.5s ease-in-out;
        }
        .success-checkmark i {
            font-size: 40px;
            color: #4CAF50;
            animation: fade-in 0.5s ease-in-out 0.3s forwards;
            opacity: 0;
        }
        @keyframes scale-up {
            0% { transform: scale(0); }
            60% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        @keyframes fade-in {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        .info-row {
            display: flex;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 10px 0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 40%;
            font-weight: 500;
            color: var(--secondary);
        }
        .info-value {
            width: 60%;
            font-weight: 500;
        }
        .qr-section {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header py-3 text-center">
                        <h4 class="text-teal mb-0">✓ Pendaftaran Berhasil</h4>
                    </div>
                    <div class="card-body">
                        <div class="success-animation">
                            <div class="success-checkmark">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <h5 class="mt-3 text-success">Data Pendaftaran Berhasil Disimpan</h5>
                        </div>
                        
                        <?php if ($pendaftaran_data): ?>
                        <div class="mt-4">
                            <h5 class="card-title text-center mb-4">Informasi Pendaftaran</h5>
                            
                            <div class="info-row">
                                <div class="info-label">ID Pendaftaran</div>
                                <div class="info-value"><?= htmlspecialchars($pendaftaran_data['ID_Pendaftaran']) ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Nama Pasien</div>
                                <div class="info-value"><?= htmlspecialchars($pendaftaran_data['nm_pasien']) ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Tempat Praktik</div>
                                <div class="info-value"><?= htmlspecialchars($pendaftaran_data['Nama_Tempat'] ?? '-') ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Dokter</div>
                                <div class="info-value"><?= htmlspecialchars($pendaftaran_data['Nama_Dokter'] ?? '-') ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Jadwal</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($pendaftaran_data['Hari'] ?? '-') ?>, 
                                    <?= htmlspecialchars($pendaftaran_data['Jam_Mulai'] ?? '') ?> - 
                                    <?= htmlspecialchars($pendaftaran_data['Jam_Selesai'] ?? '') ?>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Waktu Pendaftaran</div>
                                <div class="info-value"><?= htmlspecialchars(date('d M Y, H:i', strtotime($pendaftaran_data['Waktu_Pendaftaran']))) ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="badge bg-warning"><?= htmlspecialchars($pendaftaran_data['Status_Pendaftaran']) ?></span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Perkiraan Waktu Periksa</div>
                                <div class="info-value"><?= htmlspecialchars(date('d M Y, H:i', strtotime($pendaftaran_data['Waktu_Perkiraan']))) ?></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Data pendaftaran tidak ditemukan atau ID pendaftaran tidak valid.
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <p>Silahkan datang sesuai jadwal yang telah ditentukan.<br>Mohon simpan ID pendaftaran Anda.</p>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-center">
                        <a href="<?= $base_url ?>/modules/pendaftaran/controllers/antrian.php" class="btn btn-primary">
                            <i class="bi bi-list-check me-1"></i> Lihat Antrian
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
