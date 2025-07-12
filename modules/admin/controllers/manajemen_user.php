<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/email_helper.php';

// Cek apakah user sudah login dan memiliki role admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $_POST['username'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $_POST['role'];
                $email = $_POST['email'];
                $verificationToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                try {
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, email_verified, email_verification_token, email_verification_expires, created_at) 
                                          VALUES (?, ?, ?, ?, 'pending', 0, ?, ?, CURRENT_TIMESTAMP)");
                    $stmt->execute([$username, $email, $password, $role, $verificationToken, $expires]);

                    // Kirim email verifikasi
                    if (sendVerificationEmail($email, $username, $verificationToken)) {
                        $_SESSION['success'] = "User berhasil ditambahkan dan email verifikasi telah dikirim";
                    } else {
                        $_SESSION['warning'] = "User berhasil ditambahkan tetapi gagal mengirim email verifikasi";
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Gagal menambahkan user: " . $e->getMessage();
                }
                break;

            case 'edit':
                $user_id = $_POST['user_id'];
                $username = $_POST['username'];
                $role = $_POST['role'];
                $email = $_POST['email'];

                try {
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$username, $password, $role, $email, $user_id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$username, $role, $email, $user_id]);
                    }
                    $_SESSION['success'] = "Data user berhasil diperbarui";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Gagal memperbarui data user: " . $e->getMessage();
                }
                break;

            case 'delete':
                $user_id = $_POST['user_id'];
                try {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['success'] = "User berhasil dihapus";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
                }
                break;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Ambil daftar user
try {
    $stmt = $conn->query("SELECT id, username, role, email, status, email_verified, created_at, updated_at FROM users ORDER BY username");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal mengambil data user: " . $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body>

    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid px-4">
            <h2 class="mt-4">Manajemen User</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Tombol Tambah User -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Tambah User
                </button>
            </div>

            <!-- Tabel User -->
            <div class="card mb-4">
                <div class="card-body">
                    <table id="usersTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Email Verified</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role']) ?></td>
                                    <td>
                                        <span class="badge <?= $user['status'] === 'active' ? 'bg-success' : ($user['status'] === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                            <?= htmlspecialchars($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $user['email_verified'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $user['email_verified'] ? 'Terverifikasi' : 'Belum Verifikasi' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-user"
                                            data-id="<?= $user['id'] ?>"
                                            data-username="<?= htmlspecialchars($user['username']) ?>"
                                            data-email="<?= htmlspecialchars($user['email']) ?>"
                                            data-role="<?= htmlspecialchars($user['role']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-user"
                                            data-id="<?= $user['id'] ?>"
                                            data-username="<?= htmlspecialchars($user['username']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteUserModal">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Hapus User -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus user <strong id="delete_username"></strong>?</p>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="delete_user_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                }
            });

            // Handler untuk tombol edit
            $('.edit-user').click(function() {
                const id = $(this).data('id');
                const username = $(this).data('username');
                const email = $(this).data('email');
                const role = $(this).data('role');

                $('#edit_user_id').val(id);
                $('#edit_username').val(username);
                $('#edit_email').val(email);
                $('#edit_role').val(role);
                $('#edit_password').val(''); // Reset password field
            });

            // Handler untuk tombol delete
            $('.delete-user').click(function() {
                const id = $(this).data('id');
                const username = $(this).data('username');

                $('#delete_user_id').val(id);
                $('#delete_username').text(username);
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>

</body>

</html>