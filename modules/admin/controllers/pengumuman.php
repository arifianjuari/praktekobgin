<?php
session_start();

// Kredensial database
require_once __DIR__ . '/../../../config/database.php';
$db_host = $db2_host;
$db_username = $db2_username;
$db_password = $db2_password;
$db_database = $db2_database;

// Buat koneksi
// Gunakan koneksi global PDO
require_once __DIR__ . '/../../../config/database.php';
global $conn; // $conn sudah merupakan instance PDO dari config/database.php

// Cek koneksi database (untuk PDO, error sudah dilempar sebagai exception jika gagal)
// Tidak perlu cek $conn->connect_error untuk PDO.

// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
if ($host === 'localhost' || strpos($host, 'localhost:') === 0) {
    $base_url = $protocol . $host; // gunakan BASE_URL dari config.env jika ada
} else if ($host === 'www.praktekobgin.com' || $host === 'praktekobgin.com') {
    // Untuk domain produksi, selalu gunakan HTTPS
    $base_url = 'https://' . $host;
} else {
    $base_url = $protocol . $host;
}
$base_url = rtrim($base_url, '/');

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
        // Tidak perlu close() pada PDOStatement
    } catch (Exception $e) {
        error_log("Error mengupdate status pengumuman: " . $e->getMessage());
    }

    // Tandai bahwa update sudah dilakukan hari ini
    $_SESSION[$update_key] = true;
}

// Judul halaman
$page_title = "Pengumuman";

// Ambil pengumuman aktif
$pengumuman = [];
$current_date = date('Y-m-d');

$query = "SELECT p.*, u.username 
          FROM pengumuman p 
          LEFT JOIN users u ON p.dibuat_oleh = u.id 
          WHERE p.status_aktif = 1 
          AND p.tanggal_mulai <= ? 
          AND (p.tanggal_berakhir IS NULL OR p.tanggal_berakhir >= ?)
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$current_date, $current_date]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($result && count($result) > 0) {
    foreach ($result as $row) {
        $pengumuman[] = $row;
    }
}

// ------------------ MVC Rendering (Refactored) ------------------
ob_start();
include __DIR__ . '/../views/daftar_pengumuman.php';
$content = ob_get_clean();

$additional_css = <<<CSS
.pengumuman-card{transition:transform .3s;height:100%}.pengumuman-card:hover{transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,.1)}.pengumuman-date{font-size:.85rem;color:#6c757d}.pengumuman-content{overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;line-clamp:3;-webkit-box-orient:vertical}.pengumuman-content-full{line-height:1.6}
CSS;

$additional_js = <<<JS
$(function(){
  $('.view-pengumuman').on('click',function(){
      const judul=$(this).data('judul'),
            isi=$(this).data('isi'),
            mulai=$(this).data('mulai'),
            berakhir=$(this).data('berakhir'),
            penulis=$(this).data('penulis');
      let tanggalText=mulai;
      if(berakhir!=='-'){tanggalText+=' s/d '+berakhir;}
      $('#modal-judul').text(judul);
      $('#modal-isi').html(isi);
      $('#modal-tanggal').text(tanggalText);
      $('#modal-penulis').text(penulis);
  });
});
JS;

include_once __DIR__ . '/../../../template/layout.php';
exit;
?>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Praktekobgin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.min.css">
    <style>
        .pengumuman-card {
            transition: transform 0.3s;
            height: 100%;
        }

        .pengumuman-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .pengumuman-date {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .pengumuman-content {
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .pengumuman-content-full {
            line-height: 1.6;
        }

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
        
        @media (max-width: 991.98px) {
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }

        .card-header h5 {
            font-weight: 600;
            color: #333;
        }

        .btn-outline-primary {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>

<body>
    <?php include_once 'template/sidebar.php'; ?>

    <div class="content-wrapper p-3">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo $page_title; ?></h5>
                                <a href="javascript:history.back()" class="btn btn-sm btn-light">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (count($pengumuman) > 0): ?>
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                    <?php foreach ($pengumuman as $item): ?>
                                        <div class="col">
                                            <div class="card h-100 pengumuman-card">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($item['judul']); ?></h5>
                                                    <p class="pengumuman-date mb-2">
                                                        <i class="bi bi-calendar-event"></i>
                                                        <?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>
                                                        <?php if (!empty($item['tanggal_berakhir'])): ?>
                                                            s/d <?php echo date('d-m-Y', strtotime($item['tanggal_berakhir'])); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <div class="pengumuman-content mb-3">
                                                        <?php echo $item['isi_pengumuman']; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary view-pengumuman"
                                                        data-bs-toggle="modal" data-bs-target="#pengumumanModal"
                                                        data-id="<?php echo $item['id_pengumuman']; ?>"
                                                        data-judul="<?php echo htmlspecialchars($item['judul']); ?>"
                                                        data-isi="<?php echo htmlspecialchars($item['isi_pengumuman']); ?>"
                                                        data-mulai="<?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>"
                                                        data-berakhir="<?php echo !empty($item['tanggal_berakhir']) ? date('d-m-Y', strtotime($item['tanggal_berakhir'])) : '-'; ?>"
                                                        data-penulis="<?php echo htmlspecialchars($item['username'] ?? 'Admin'); ?>">
                                                        <i class="bi bi-eye"></i> Baca Selengkapnya
                                                    </button>
                                                </div>
                                                <div class="card-footer text-muted">
                                                    <small>Oleh: <?php echo htmlspecialchars($item['username'] ?? 'Admin'); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Tidak ada pengumuman aktif saat ini.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pengumuman -->
    <div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="pengumumanModalLabel">Detail Pengumuman</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 id="modal-judul" class="mb-3 text-primary"></h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-calendar-event"></i> Tanggal:</strong> <span id="modal-tanggal"></span></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong><i class="bi bi-person"></i> Oleh:</strong> <span id="modal-penulis"></span></p>
                        </div>
                    </div>
                    <div class="card border">
                        <div class="card-body">
                            <div id="modal-isi" class="pengumuman-content-full"></div>
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

    <script>
        $(document).ready(function() {
            // Tampilkan detail pengumuman pada modal
            $('.view-pengumuman').click(function() {
                const judul = $(this).data('judul');
                const isi = $(this).data('isi');
                const mulai = $(this).data('mulai');
                const berakhir = $(this).data('berakhir');
                const penulis = $(this).data('penulis');

                let tanggalText = mulai;
                if (berakhir !== '-') {
                    tanggalText += ' s/d ' + berakhir;
                }

                $('#modal-judul').text(judul);
                $('#modal-isi').html(isi);
                $('#modal-tanggal').text(tanggalText);
                $('#modal-penulis').text(penulis);
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

            // Tambahkan ini untuk mengatasi masalah modal
            if (window.location.hash === '#pengumumanModal') {
                $('#pengumumanModal').modal('show');
            }
        });
    </script>
</body>

</html>