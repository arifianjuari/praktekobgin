<?php
require_once '../error_display.php';
require_once '../session_check.php';
require_once '../config_auth.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle Actions (Approve, Reject, Delete, Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if ($action && $user_id) {
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $auth_conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['success'] = 'User berhasil diaktifkan';
                    break;

                case 'reject':
                    $stmt = $auth_conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['success'] = 'User berhasil ditolak';
                    break;

                case 'delete':
                    $stmt = $auth_conn->prepare("DELETE FROM users WHERE id = ? AND id != ?"); // Prevent admin self-delete
                    $stmt->execute([$user_id, $_SESSION['user_id']]);
                    $_SESSION['success'] = 'User berhasil dihapus';
                    break;

                case 'update_role':
                    $new_role = $_POST['role'] ?? '';
                    if ($new_role && in_array($new_role, ['admin', 'user'])) {
                        $stmt = $auth_conn->prepare("UPDATE users SET role = ? WHERE id = ? AND id != ?"); // Prevent admin self-update
                        $stmt->execute([$new_role, $user_id, $_SESSION['user_id']]);
                        $_SESSION['success'] = 'Role user berhasil diupdate';
                    }
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }

        // Redirect to prevent form resubmission
        header('Location: manage_users.php');
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$role_filter = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE id != ?"; // Exclude current admin
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($role_filter !== 'all') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if ($search) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY created_at DESC";

// Get users
try {
    $stmt = $auth_conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Terjadi kesalahan saat mengambil data user';
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .table-responsive {
            margin-top: 20px;
        }

        .action-buttons .btn {
            margin-right: 5px;
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
                        <a class="nav-link active" href="manage_users.php">Manajemen User</a>
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
        <h2>Manajemen User</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>Semua Role</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari username atau email..." value="<?php echo htmlspecialchars($search); ?>">
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
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Email Verified</th>
                        <th>Tanggal Registrasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <span class="badge bg-<?php
                                                        echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'pending' ? 'warning' : 'danger');
                                                        ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['email_verified'] ? 'success' : 'warning'; ?>">
                                    <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons">
                                <?php if ($user['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Yakin ingin mengaktifkan user ini?')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menolak user ini?')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data user</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>