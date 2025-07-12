<?php
require_once '../error_display.php';
require_once '../session_check.php';
require_once '../config_auth.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$action_filter = $_GET['action'] ?? 'all';
$user_filter = $_GET['user_id'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT al.*, u.username 
          FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($action_filter !== 'all') {
    $query .= " AND al.action = ?";
    $params[] = $action_filter;
}

if ($user_filter !== 'all') {
    $query .= " AND al.user_id = ?";
    $params[] = $user_filter;
}

if ($date_from) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $query .= " AND (al.details LIKE ? OR al.ip_address LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY al.created_at DESC LIMIT 1000"; // Limit to prevent memory issues

// Get logs
try {
    $stmt = $auth_conn->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique actions for filter
    $stmt = $auth_conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get users for filter
    $stmt = $auth_conn->query("SELECT id, username FROM users ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Terjadi kesalahan saat mengambil data log';
    $logs = [];
    $actions = [];
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .table-responsive {
            margin-top: 20px;
        }

        .filters {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manajemen User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="activity_logs.php">Log Aktivitas</a>
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
        <h2>Log Aktivitas</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <select name="action" class="form-select">
                        <option value="all">Semua Aksi</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?php echo htmlspecialchars($action); ?>"
                                <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $action)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="user_id" class="form-select">
                        <option value="all">Semua User</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"
                                <?php echo $user_filter === $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" placeholder="Dari Tanggal">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" placeholder="Sampai Tanggal">
                </div>
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Cari..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Username</th>
                        <th>Aksi</th>
                        <th>Detail</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                <?php echo htmlspecialchars($log['user_agent']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data log</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>