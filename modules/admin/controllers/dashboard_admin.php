<?php
require_once '../error_display.php';
require_once '../session_check.php';
require_once '../config_auth.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Mengambil statistik user
try {
    // Total users
    $stmt = $auth_conn->query("SELECT COUNT(*) FROM users WHERE id != {$_SESSION['user_id']}");
    $total_users = $stmt->fetchColumn();

    // Users by status
    $stmt = $auth_conn->query("SELECT status, COUNT(*) as count FROM users WHERE id != {$_SESSION['user_id']} GROUP BY status");
    $users_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Users by role
    $stmt = $auth_conn->query("SELECT role, COUNT(*) as count FROM users WHERE id != {$_SESSION['user_id']} GROUP BY role");
    $users_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent registrations (last 7 days)
    $stmt = $auth_conn->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_registrations = $stmt->fetchColumn();

    // Unverified emails
    $stmt = $auth_conn->query("SELECT COUNT(*) FROM users WHERE email_verified = 0");
    $unverified_emails = $stmt->fetchColumn();

    // Recent activities
    $stmt = $auth_conn->prepare("
        SELECT al.*, u.username 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Login attempts (last 24 hours)
    $stmt = $auth_conn->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
        FROM login_attempts 
        WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $login_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Terjadi kesalahan saat mengambil data statistik';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .card {
            margin-bottom: 20px;
        }

        .stat-card {
            text-align: center;
            padding: 20px;
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manajemen User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="activity_logs.php">Log Aktivitas</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>/index.php?module=auth&action=logout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Dashboard Admin</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistik Utama -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-people text-primary"></i>
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="text-muted">Total User</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-person-plus text-success"></i>
                    <div class="number"><?php echo $recent_registrations; ?></div>
                    <div class="text-muted">Registrasi (7 hari terakhir)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-envelope text-warning"></i>
                    <div class="number"><?php echo $unverified_emails; ?></div>
                    <div class="text-muted">Email Belum Terverifikasi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-shield-lock text-danger"></i>
                    <div class="number"><?php echo $login_stats['failed'] ?? 0; ?></div>
                    <div class="text-muted">Login Gagal (24 jam)</div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Status User -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Status User</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users_by_status as $status): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php
                                                                        echo $status['status'] === 'active' ? 'success' : ($status['status'] === 'pending' ? 'warning' : 'danger');
                                                                        ?>">
                                                    <?php echo ucfirst($status['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $status['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Aktivitas Terbaru</h5>
                        <a href="activity_logs.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                                        <span class="text-muted">
                                            <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                    </small>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($activity['details']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Login -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statistik Login (24 jam terakhir)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col">
                                <h3><?php echo $login_stats['total'] ?? 0; ?></h3>
                                <div class="text-muted">Total Percobaan</div>
                            </div>
                            <div class="col">
                                <h3><?php echo ($login_stats['total'] ?? 0) - ($login_stats['failed'] ?? 0); ?></h3>
                                <div class="text-muted">Login Berhasil</div>
                            </div>
                            <div class="col">
                                <h3><?php echo $login_stats['failed'] ?? 0; ?></h3>
                                <div class="text-muted">Login Gagal</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>