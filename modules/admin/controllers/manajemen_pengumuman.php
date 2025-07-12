<?php
session_start();

// Gunakan koneksi global PDO dari config/database.php
require_once __DIR__ . '/../../../config/database.php';
global $conn; // $conn sudah merupakan instance PDO dari config/database.php
// Tidak perlu cek $conn->connect_error, PDO akan throw exception jika gagal

// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host;

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $base_url . "/login.php");
    exit;
}

// Update status pengumuman yang sudah melewati tanggal_berakhir
// Hanya jalankan update ini sekali per hari menggunakan session
$update_key = 'pengumuman_status_updated_' . date('Y-m-d');
if (!isset($_SESSION[$update_key])) {
    $current_date = date('Y-m-d');

    // Update status_aktif menjadi 0 untuk pengumuman yang sudah melewati tanggal_berakhir
    $update_query = "UPDATE pengumuman 
                    SET status_aktif = 0 
                    WHERE status_aktif = 1 
                    AND tanggal_berakhir IS NOT NULL 
                    AND tanggal_berakhir < ?";

    try {
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$current_date]);
        $affected_rows = $update_stmt->rowCount();

        if ($affected_rows > 0) {
            error_log(date('Y-m-d H:i:s') . " - " . $affected_rows . " pengumuman dinonaktifkan karena sudah melewati tanggal_berakhir.");
        }
    } catch (Exception $e) {
        error_log("Error mengupdate status pengumuman: " . $e->getMessage());
    }

    // Tandai bahwa update sudah dilakukan hari ini
    $_SESSION[$update_key] = true;
}

// Fungsi untuk generate UUID
function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

// Fungsi untuk sanitasi input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Inisialisasi variabel pesan
$success_message = '';
$error_message = '';

// Cek apakah ada parameter success di URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Pengumuman berhasil disimpan";
}

// Proses form tambah/edit pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input
    $judul = sanitize_input($_POST['judul']);

    // Bersihkan isi_pengumuman dari tag <p> yang tidak diinginkan
    $isi_pengumuman = $_POST['isi_pengumuman'];
    $isi_pengumuman = preg_replace('/<p[^>]*>/', '', $isi_pengumuman); // Hapus tag pembuka <p>
    $isi_pengumuman = str_replace('</p>', '', $isi_pengumuman); // Hapus tag penutup </p>
    $isi_pengumuman = trim($isi_pengumuman); // Hapus whitespace di awal dan akhir

    // Sanitasi tag HTML yang diizinkan
    $isi_pengumuman = strip_tags($isi_pengumuman, '<br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img><table><thead><tbody><tr><td><th>');

    $tanggal_mulai = sanitize_input($_POST['tanggal_mulai']);
    $tanggal_berakhir = !empty($_POST['tanggal_berakhir']) ? sanitize_input($_POST['tanggal_berakhir']) : null;
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $dibuat_oleh = $_SESSION['user_id'];

    // Validasi input
    if (empty($judul) || empty($isi_pengumuman) || empty($tanggal_mulai)) {
        $error_message = "Semua field wajib diisi kecuali tanggal berakhir";
    } else {
        // Cek apakah ini update atau tambah baru
        if (isset($_POST['id_pengumuman']) && !empty($_POST['id_pengumuman'])) {
            // Update pengumuman
            $id_pengumuman = sanitize_input($_POST['id_pengumuman']);

            $query = "UPDATE pengumuman SET 
                      judul = ?, 
                      isi_pengumuman = ?, 
                      tanggal_mulai = ?, 
                      tanggal_berakhir = ?, 
                      status_aktif = ? 
                      WHERE id_pengumuman = ?";

            $stmt = $conn->prepare($query);
            if ($stmt->execute([$judul, $isi_pengumuman, $tanggal_mulai, $tanggal_berakhir, $status_aktif, $id_pengumuman])) {
                $success_message = "Pengumuman berhasil diperbarui";
                // Redirect ke halaman yang sama tanpa parameter edit untuk menutup modal
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit;
            } else {
                $error_message = "Gagal memperbarui pengumuman: " . $stmt->errorInfo()[2];
            }
        } else {
            // Tambah pengumuman baru
            $id_pengumuman = generateUUID();

            $query = "INSERT INTO pengumuman (id_pengumuman, judul, isi_pengumuman, tanggal_mulai, tanggal_berakhir, status_aktif, dibuat_oleh) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            if ($stmt->execute([$id_pengumuman, $judul, $isi_pengumuman, $tanggal_mulai, $tanggal_berakhir, $status_aktif, $dibuat_oleh])) {
                $success_message = "Pengumuman berhasil ditambahkan";
                // Redirect ke halaman yang sama untuk menutup modal
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit;
            } else {
                $error_message = "Gagal menambahkan pengumuman: " . $stmt->errorInfo()[2];
            }
        }
    }
}

// Proses hapus pengumuman
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_pengumuman = sanitize_input($_GET['id']);

    $query = "DELETE FROM pengumuman WHERE id_pengumuman = ?";
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$id_pengumuman])) {
        $success_message = "Pengumuman berhasil dihapus";
    } else {
        $error_message = "Gagal menghapus pengumuman: " . $stmt->errorInfo()[2];
    }
}

// Ambil data pengumuman untuk ditampilkan
$pengumuman = [];
$query = "SELECT p.*, u.username 
          FROM pengumuman p 
          LEFT JOIN users u ON p.dibuat_oleh = u.id 
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($result && count($result) > 0) {
    foreach ($result as $row) {
        $pengumuman[] = $row;
    }
}

// Ambil data pengumuman untuk diedit jika ada
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_pengumuman = sanitize_input($_GET['id']);

    $query = "SELECT * FROM pengumuman WHERE id_pengumuman = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_pengumuman]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $edit_data = $result;
    }
}

// Judul halaman
$page_title = "Manajemen Pengumuman";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Antrian Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        /* Base Styles */
        body {
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }
        
        /* Content Wrapper Layout */
        .content-wrapper {
            margin-left: 240px;
            padding: 20px;
            transition: margin-left 0.3s ease, width 0.3s ease;
            width: calc(100% - 240px); /* Width minus sidebar width */
            box-sizing: border-box;
        }
        
        /* Adjust content when sidebar is minimized */
        .sidebar.minimized ~ .content-wrapper {
            margin-left: 60px;
            width: calc(100% - 60px); /* Width minus minimized sidebar width */
        }
        
        /* Mobile adjustments */
        @media (max-width: 991.98px) {
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }

        .card-header {
            background-color: #f8f9fa;
        }

        .note-editor {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="content-wrapper p-3">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo $page_title; ?></h5>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#pengumumanModal">
                                <i class="bi bi-plus-circle"></i> Tambah Pengumuman
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table id="pengumumanTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Tanggal Mulai</th>
                                            <th>Tanggal Berakhir</th>
                                            <th>Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($pengumuman) > 0): ?>
                                            <?php foreach ($pengumuman as $index => $item): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($item['judul']); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?></td>
                                                    <td>
                                                        <?php
                                                        echo !empty($item['tanggal_berakhir'])
                                                            ? date('d-m-Y', strtotime($item['tanggal_berakhir']))
                                                            : '<span class="text-muted">-</span>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($item['status_aktif'] == 1): ?>
                                                            <span class="badge bg-success">Aktif</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['username'] ?? 'Unknown'); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info view-btn"
                                                                data-bs-toggle="modal" data-bs-target="#viewModal"
                                                                data-id="<?php echo $item['id_pengumuman']; ?>"
                                                                data-judul="<?php echo htmlspecialchars($item['judul']); ?>"
                                                                data-isi="<?php echo $item['isi_pengumuman']; ?>"
                                                                data-mulai="<?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>"
                                                                data-berakhir="<?php echo !empty($item['tanggal_berakhir']) ? date('d-m-Y', strtotime($item['tanggal_berakhir'])) : '-'; ?>"
                                                                data-penulis="<?php echo htmlspecialchars($item['username'] ?? 'Admin'); ?>">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <a href="?action=edit&id=<?php echo $item['id_pengumuman']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="?action=delete&id=<?php echo $item['id_pengumuman']; ?>" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Pengumuman -->
    <div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pengumumanModalLabel">
                        <?php echo $edit_data ? 'Edit Pengumuman' : 'Tambah Pengumuman Baru'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="pengumumanForm">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id_pengumuman" value="<?php echo $edit_data['id_pengumuman']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Pengumuman <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" required
                                value="<?php echo $edit_data ? htmlspecialchars($edit_data['judul']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="isi_pengumuman" class="form-label">Isi Pengumuman <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="isi_pengumuman" name="isi_pengumuman" rows="5" required><?php echo $edit_data ? $edit_data['isi_pengumuman'] : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required
                                        value="<?php echo $edit_data ? $edit_data['tanggal_mulai'] : date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_berakhir" class="form-label">Tanggal Berakhir</label>
                                    <input type="date" class="form-control" id="tanggal_berakhir" name="tanggal_berakhir"
                                        value="<?php echo $edit_data && $edit_data['tanggal_berakhir'] ? $edit_data['tanggal_berakhir'] : ''; ?>">
                                    <small class="text-muted">Kosongkan jika tidak ada batas waktu</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="status_aktif" name="status_aktif"
                                <?php echo (!$edit_data || ($edit_data && $edit_data['status_aktif'] == 1)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status_aktif">Aktif</label>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btnSimpan">
                                <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status" aria-hidden="true"></span>
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal View Pengumuman -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Detail Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 id="view-judul" class="mb-3"></h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Tanggal Mulai:</strong> <span id="view-mulai"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Berakhir:</strong> <span id="view-berakhir"></span></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Isi Pengumuman</h6>
                        </div>
                        <div class="card-body">
                            <div id="view-isi"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            $('#pengumumanTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                }
            });

            // Inisialisasi Summernote
            $('#isi_pengumuman').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onPaste: function(e) {
                        // Bersihkan konten yang di-paste dari tag HTML yang tidak diinginkan
                        var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                        e.preventDefault();
                        document.execCommand('insertText', false, bufferText);
                    }
                },
                placeholder: 'Tulis isi pengumuman di sini...',
                disableDragAndDrop: true,
                codeviewFilter: true,
                codeviewIframeFilter: true
            });

            // Tampilkan modal edit jika ada parameter edit
            <?php if ($edit_data): ?>
                $('#pengumumanModal').modal('show');
            <?php endif; ?>

            // Tutup modal jika form berhasil disimpan
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                // Tampilkan pesan sukses
                setTimeout(function() {
                    $('.alert-success').fadeOut('slow');
                }, 5000); // Hilangkan pesan sukses setelah 5 detik
            <?php endif; ?>

            // Tambahkan event handler untuk form submit
            $('#pengumumanForm').submit(function() {
                // Tampilkan loading spinner
                $('#loadingSpinner').removeClass('d-none');
                $('#btnSimpan').attr('disabled', true);

                // Form akan di-submit secara normal
                return true;
            });

            // Tampilkan detail pengumuman pada modal view
            $('.view-btn').click(function() {
                const judul = $(this).data('judul');
                const isi = $(this).data('isi');
                const mulai = $(this).data('mulai');
                const berakhir = $(this).data('berakhir');
                const penulis = $(this).data('penulis');

                $('#view-judul').text(judul);
                $('#view-isi').html(isi);
                $('#view-mulai').text(mulai);
                $('#view-berakhir').text(berakhir);
            });

            // Toggle sidebar
            $('#toggleSidebar').click(function() {
                $('body').toggleClass('sidebar-collapsed');
                $('.sidebar').toggleClass('collapsed');

                if ($('.sidebar').hasClass('collapsed')) {
                    $('.sidebar').css('width', '70px');
                    $('.menu-text').hide();
                    $('.submenu-arrow').hide();
                } else {
                    $('.sidebar').css('width', '280px');
                    $('.menu-text').show();
                    $('.submenu-arrow').show();
                }
            });
        });
    </script>
</body>

</html>