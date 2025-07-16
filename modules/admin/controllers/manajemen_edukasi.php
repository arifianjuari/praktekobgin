<?php
// Aktifkan error reporting untuk debugging di localhost
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Di server produksi, log error tapi jangan tampilkan
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/php-errors.log');
}

// Set memory limit yang cukup untuk manipulasi gambar
ini_set('memory_limit', '128M');

// Load file konfigurasi
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/auth.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Pengecekan ekstensi yang dibutuhkan
$required_extensions = ['gd', 'fileinfo', 'pdo_mysql'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('Ekstensi PHP berikut diperlukan tapi tidak tersedia: ' . implode(', ', $missing_extensions));
}

// Cek keberadaan dan permission folder upload
// Tentukan direktori upload relatif dari document root agar URL gambar konsisten
$upload_dir_relative = '/uploads/edukasi/';
$upload_dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $upload_dir_relative;
if (!file_exists($upload_dir)) {
    if (!@mkdir($upload_dir, 0755, true)) {
        die("Gagal membuat direktori upload: $upload_dir");
    }
}

if (!is_writable($upload_dir)) {
    die("Direktori upload tidak writable: $upload_dir. Silakan periksa permission folder atau sesuaikan path.");
}

// Konfigurasi upload dan optimasi gambar
$image_config = [
    'max_size' => 5 * 1024 * 1024, // 5MB
    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
    'max_dimension' => 1920, // Mendukung resolusi Full HD
    'quality' => 95, // Kualitas kompresi yang lebih tinggi
    'output_format' => 'jpg'
];

// Fungsi untuk menangani upload dan proses gambar tanpa Intervention/Image
// Menggunakan GD secara langsung
function processImageUpload($image_file, $image_config, $upload_dir)
{
    if ($image_file['error'] !== 0) {
        return [false, "Error kode: " . $image_file['error']];
    }

    // Validasi file
    $file_type = $image_file['type'];
    $file_size = $image_file['size'];

    if (!in_array($file_type, $image_config['allowed_types'])) {
        return [false, "Hanya file JPG, PNG, dan GIF yang diperbolehkan."];
    }

    if ($file_size > $image_config['max_size']) {
        return [false, "Ukuran file tidak boleh lebih dari 5MB."];
    }

    try {
        // Generate nama file unik
        $file_extension = strtolower(pathinfo($image_file["name"], PATHINFO_EXTENSION));
        $new_filename = 'edukasi-' . time() . '-' . substr(md5(uniqid()), 0, 8) . '.jpg';
        $target_file = $upload_dir . $new_filename;

        // Buat sumber gambar berdasarkan tipe file
        $src_image = null;

        switch ($file_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $src_image = imagecreatefromjpeg($image_file['tmp_name']);
                break;
            case 'image/png':
                $src_image = imagecreatefrompng($image_file['tmp_name']);
                break;
            case 'image/gif':
                $src_image = imagecreatefromgif($image_file['tmp_name']);
                break;
            default:
                throw new Exception("Format gambar tidak didukung.");
        }

        if (!$src_image) {
            throw new Exception("Gagal membaca gambar.");
        }

        // Dapatkan dimensi gambar asli
        $src_width = imagesx($src_image);
        $src_height = imagesy($src_image);

        // Hitung dimensi baru dengan mempertahankan rasio aspek
        $max_dimension = $image_config['max_dimension'];

        if ($src_width > $max_dimension || $src_height > $max_dimension) {
            if ($src_width > $src_height) {
                $dst_width = $max_dimension;
                $dst_height = floor($src_height * ($max_dimension / $src_width));
            } else {
                $dst_height = $max_dimension;
                $dst_width = floor($src_width * ($max_dimension / $src_height));
            }
        } else {
            // Tidak perlu resize jika gambar sudah lebih kecil
            $dst_width = $src_width;
            $dst_height = $src_height;
        }

        // Buat gambar baru
        $dst_image = imagecreatetruecolor($dst_width, $dst_height);

        // Pertahankan alpha channel jika PNG
        if ($file_type == 'image/png') {
            imagealphablending($dst_image, false);
            imagesavealpha($dst_image, true);
        }

        // Resize gambar
        imagecopyresampled(
            $dst_image,
            $src_image,
            0,
            0,
            0,
            0,
            $dst_width,
            $dst_height,
            $src_width,
            $src_height
        );

        // Simpan gambar
        imagejpeg($dst_image, $target_file, $image_config['quality']);

        // Bersihkan memori
        imagedestroy($src_image);
        imagedestroy($dst_image);

        // Set permission
        chmod($target_file, 0644);

        return [true, $new_filename];
    } catch (Exception $e) {
        return [false, "Gagal mengoptimasi gambar: " . $e->getMessage()];
    }
}

// Fungsi untuk membuat slug dari judul
function createSlug($string)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Fungsi untuk membuat UUID v4
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

// Daftar kategori
$kategori_list = [
    'fetomaternal',
    'ginekologi umum',
    'onkogin',
    'fertilitas',
    'uroginekologi'
];

// Proses tambah data
if (isset($_POST['tambah'])) {
    $id_edukasi = generateUUID();
    $judul = $_POST['judul'];
    $kategori = $_POST['kategori'];
    $isi_edukasi = $_POST['isi_edukasi'];
    $sumber = isset($_POST['sumber']) ? $_POST['sumber'] : '';
    $tag = isset($_POST['tag']) ? $_POST['tag'] : '';
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $ditampilkan_beranda = isset($_POST['ditampilkan_beranda']) ? 1 : 0;
    $urutan_tampil = isset($_POST['urutan_tampil']) ? $_POST['urutan_tampil'] : null;
    $dibuat_oleh = $_SESSION['user_id'];
    $link_video = isset($_POST['link_video']) ? $_POST['link_video'] : '';

    // Upload gambar jika ada
    $link_gambar = '';
    if (isset($_FILES['link_gambar']) && $_FILES['link_gambar']['error'] == 0) {
        list($success, $result) = processImageUpload(
            $_FILES['link_gambar'],
            $image_config,
            $upload_dir
        );

        if ($success) {
            $link_gambar = $result;
        } else {
            $error_message = "Error: " . $result;
        }
    }

    if (!isset($error_message)) {
        try {
            $stmt = $conn->prepare("INSERT INTO edukasi (id_edukasi, judul, kategori, isi_edukasi, link_gambar, link_video, sumber, tag, status_aktif, ditampilkan_beranda, urutan_tampil, dibuat_oleh, created_at) 
                    VALUES (:id_edukasi, :judul, :kategori, :isi_edukasi, :link_gambar, :link_video, :sumber, :tag, :status_aktif, :ditampilkan_beranda, :urutan_tampil, :dibuat_oleh, NOW())");

            $stmt->bindParam(':id_edukasi', $id_edukasi);
            $stmt->bindParam(':judul', $judul);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':isi_edukasi', $isi_edukasi);
            $stmt->bindParam(':link_gambar', $link_gambar);
            $stmt->bindParam(':link_video', $link_video);
            $stmt->bindParam(':sumber', $sumber);
            $stmt->bindParam(':tag', $tag);
            $stmt->bindParam(':status_aktif', $status_aktif);
            $stmt->bindParam(':ditampilkan_beranda', $ditampilkan_beranda);
            $stmt->bindParam(':urutan_tampil', $urutan_tampil);
            $stmt->bindParam(':dibuat_oleh', $dibuat_oleh);

            $stmt->execute();
            $success_message = "Artikel edukasi berhasil ditambahkan";
        } catch (PDOException $e) {
            $error_message = "Error database: " . $e->getMessage();
        }
    }
}

// Proses edit data
if (isset($_POST['edit'])) {
    $id_edukasi = $_POST['id_edukasi'];
    $judul = $_POST['judul'];
    $kategori = $_POST['kategori'];
    $isi_edukasi = $_POST['isi_edukasi'];
    $sumber = isset($_POST['sumber']) ? $_POST['sumber'] : '';
    $tag = isset($_POST['tag']) ? $_POST['tag'] : '';
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $ditampilkan_beranda = isset($_POST['ditampilkan_beranda']) ? 1 : 0;
    $urutan_tampil = isset($_POST['urutan_tampil']) ? $_POST['urutan_tampil'] : null;
    $link_video = isset($_POST['link_video']) ? $_POST['link_video'] : '';

    try {
        // Cek apakah ada upload gambar baru
        if (isset($_FILES['link_gambar']) && $_FILES['link_gambar']['error'] == 0) {
            list($success, $result) = processImageUpload(
                $_FILES['link_gambar'],
                $image_config,
                $upload_dir
            );

            if ($success) {
                $new_filename = $result;

                // Hapus gambar lama jika ada
                $stmt = $conn->prepare("SELECT link_gambar FROM edukasi WHERE id_edukasi = :id_edukasi");
                $stmt->bindParam(':id_edukasi', $id_edukasi);
                $stmt->execute();
                $old_image = $stmt->fetchColumn();

                if (!empty($old_image)) {
                    $old_image_path = $upload_dir . $old_image;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }

                // Update dengan gambar baru
                $stmt = $conn->prepare("UPDATE edukasi SET 
                        judul = :judul,
                        kategori = :kategori,
                        isi_edukasi = :isi_edukasi,
                        link_gambar = :link_gambar,
                        link_video = :link_video,
                        sumber = :sumber,
                        tag = :tag,
                        status_aktif = :status_aktif,
                        ditampilkan_beranda = :ditampilkan_beranda,
                        urutan_tampil = :urutan_tampil
                        WHERE id_edukasi = :id_edukasi");
                $stmt->bindParam(':link_gambar', $new_filename);
            } else {
                $error_message = "Error: " . $result;
                // Update tanpa mengubah gambar jika terjadi error
                $stmt = $conn->prepare("UPDATE edukasi SET 
                        judul = :judul,
                        kategori = :kategori,
                        isi_edukasi = :isi_edukasi,
                        link_video = :link_video,
                        sumber = :sumber,
                        tag = :tag,
                        status_aktif = :status_aktif,
                        ditampilkan_beranda = :ditampilkan_beranda,
                        urutan_tampil = :urutan_tampil
                        WHERE id_edukasi = :id_edukasi");
            }
        } else {
            // Update tanpa mengubah gambar
            $stmt = $conn->prepare("UPDATE edukasi SET 
                    judul = :judul,
                    kategori = :kategori,
                    isi_edukasi = :isi_edukasi,
                    link_video = :link_video,
                    sumber = :sumber,
                    tag = :tag,
                    status_aktif = :status_aktif,
                    ditampilkan_beranda = :ditampilkan_beranda,
                    urutan_tampil = :urutan_tampil,
                    created_at = COALESCE(created_at, NOW())
                    WHERE id_edukasi = :id_edukasi");
        }

        $stmt->bindParam(':id_edukasi', $id_edukasi);
        $stmt->bindParam(':judul', $judul);
        $stmt->bindParam(':kategori', $kategori);
        $stmt->bindParam(':isi_edukasi', $isi_edukasi);
        $stmt->bindParam(':link_video', $link_video);
        $stmt->bindParam(':sumber', $sumber);
        $stmt->bindParam(':tag', $tag);
        $stmt->bindParam(':status_aktif', $status_aktif);
        $stmt->bindParam(':ditampilkan_beranda', $ditampilkan_beranda);
        $stmt->bindParam(':urutan_tampil', $urutan_tampil);

        $stmt->execute();
        $success_message = "Artikel edukasi berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Error database: " . $e->getMessage();
    } catch (Exception $e) {
        $error_message = "Error umum: " . $e->getMessage();
    }
}

// Proses hapus gambar dari form edit
if (isset($_POST['hapus_gambar'])) {
    $id_edukasi = $_POST['id_edukasi'];

    try {
        // Ambil informasi gambar
        $stmt = $conn->prepare("SELECT link_gambar FROM edukasi WHERE id_edukasi = :id_edukasi");
        $stmt->bindParam(':id_edukasi', $id_edukasi);
        $stmt->execute();
        $link_gambar = $stmt->fetchColumn();

        if (!empty($link_gambar)) {
            // Hapus file gambar
            $image_path = $upload_dir . $link_gambar;
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            // Update database, set link_gambar menjadi kosong
            $stmt = $conn->prepare("UPDATE edukasi SET link_gambar = '' WHERE id_edukasi = :id_edukasi");
            $stmt->bindParam(':id_edukasi', $id_edukasi);
            $stmt->execute();

            $success_message = "Gambar berhasil dihapus";
        } else {
            $error_message = "Tidak ada gambar untuk dihapus";
        }
    } catch (PDOException $e) {
        $error_message = "Error database: " . $e->getMessage();
    } catch (Exception $e) {
        $error_message = "Error umum: " . $e->getMessage();
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id_edukasi = $_GET['hapus'];

    try {
        // Hapus gambar terlebih dahulu
        $stmt = $conn->prepare("SELECT link_gambar FROM edukasi WHERE id_edukasi = :id_edukasi");
        $stmt->bindParam(':id_edukasi', $id_edukasi);
        $stmt->execute();
        $link_gambar = $stmt->fetchColumn();

        if (!empty($link_gambar)) {
            $image_path = $upload_dir . $link_gambar;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Hapus data dari database
        $stmt = $conn->prepare("DELETE FROM edukasi WHERE id_edukasi = :id_edukasi");
        $stmt->bindParam(':id_edukasi', $id_edukasi);
        $stmt->execute();
        $success_message = "Artikel edukasi berhasil dihapus";
    } catch (PDOException $e) {
        $error_message = "Error database: " . $e->getMessage();
    } catch (Exception $e) {
        $error_message = "Error umum: " . $e->getMessage();
    }
}

// Ambil data untuk ditampilkan
try {
    $stmt = $conn->query("SELECT * FROM edukasi ORDER BY created_at DESC");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $result = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Edukasi - Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= $base_url ?>/assets/css/styles.css" rel="stylesheet">

    <style>
        /* Base Styles */
        body {
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }
        
        /* Main Content Layout */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            transition: margin-left 0.3s ease, width 0.3s ease;
            width: calc(100% - 240px); /* Width minus sidebar width */
            box-sizing: border-box;
        }
        
        /* Adjust main content when sidebar is minimized */
        .sidebar.minimized ~ .main-content {
            margin-left: 60px;
            width: calc(100% - 60px); /* Width minus minimized sidebar width */
        }
        
        /* Mobile adjustments */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        
        .article-image-preview {
            max-width: 200px;
            height: auto;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h2 class="page-title">Manajemen Edukasi</h2>
                    <p class="text-muted">Kelola artikel edukasi kesehatan</p>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Daftar Artikel</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                <i class="bi bi-plus-circle"></i> Tambah Artikel
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabelEdukasi" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Kategori</th>
                                            <th>Status</th>
                                            <th>Tanggal Dibuat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach ($result as $row):
                                        ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($row['judul']) ?></td>
                                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                                <td>
                                                    <span class="badge <?= $row['status_aktif'] ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $row['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                    </span>
                                                </td>
                                                <td><?= !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEdit"
                                                        data-id="<?= $row['id_edukasi'] ?>"
                                                        data-judul="<?= htmlspecialchars($row['judul']) ?>"
                                                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                                                        data-konten="<?= htmlspecialchars($row['isi_edukasi']) ?>"
                                                        data-sumber="<?= htmlspecialchars($row['sumber']) ?>"
                                                        data-tag="<?= htmlspecialchars($row['tag']) ?>"
                                                        data-link_video="<?= htmlspecialchars($row['link_video']) ?>"
                                                        data-status="<?= $row['status_aktif'] ?>"
                                                        data-ditampilkan_beranda="<?= $row['ditampilkan_beranda'] ?>"
                                                        data-urutan_tampil="<?= $row['urutan_tampil'] ?>"
                                                        data-gambar="<?= htmlspecialchars($row['link_gambar']) ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <a href="#" class="btn btn-sm btn-danger btn-hapus"
                                                        data-id="<?= $row['id_edukasi'] ?>"
                                                        data-judul="<?= htmlspecialchars($row['judul']) ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahLabel">Tambah Artikel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $kategori): ?>
                                    <option value="<?= $kategori ?>"><?= $kategori ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="gambar" class="form-label">Gambar</label>
                            <input type="file" class="form-control" id="link_gambar" name="link_gambar" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                        </div>
                        <div class="mb-3">
                            <label for="sumber" class="form-label">Sumber</label>
                            <textarea class="form-control" id="sumber" name="sumber" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="tag" class="form-label">Tag</label>
                            <input type="text" class="form-control" id="tag" name="tag" placeholder="Contoh: kehamilan, kesehatan, tips">
                        </div>
                        <div class="mb-3">
                            <label for="link_video" class="form-label">Link Video</label>
                            <input type="text" class="form-control" id="link_video" name="link_video" placeholder="URL video YouTube, Vimeo, dll">
                        </div>
                        <div class="mb-3">
                            <label for="isi_edukasi" class="form-label">Konten <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" id="isi_edukasi" name="isi_edukasi" required></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif" checked>
                                <label class="form-check-label" for="status_aktif">Status Aktif</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ditampilkan_beranda" name="ditampilkan_beranda">
                                <label class="form-check-label" for="ditampilkan_beranda">Tampilkan di Beranda</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="urutan_tampil" class="form-label">Urutan Tampil</label>
                            <input type="number" class="form-control" id="urutan_tampil" name="urutan_tampil" min="1">
                            <small class="text-muted">Urutan tampil di beranda (opsional)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditLabel">Edit Artikel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_edukasi" name="id_edukasi">
                        <div class="mb-3">
                            <label for="edit_judul" class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_judul" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $kategori): ?>
                                    <option value="<?= $kategori ?>"><?= $kategori ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_gambar" class="form-label">Gambar</label>
                            <input type="file" class="form-control" id="edit_link_gambar" name="link_gambar" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                            <div id="preview_gambar" class="mt-2"></div>
                            <div id="hapus_gambar_container" class="mt-2" style="display:none;">
                                <button type="submit" class="btn btn-sm btn-danger" name="hapus_gambar"
                                    onclick="return confirm('Yakin ingin menghapus gambar?')">
                                    <i class="bi bi-trash"></i> Hapus Gambar
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sumber" class="form-label">Sumber</label>
                            <textarea class="form-control" id="edit_sumber" name="sumber" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tag" class="form-label">Tag</label>
                            <input type="text" class="form-control" id="edit_tag" name="tag" placeholder="Contoh: kehamilan, kesehatan, tips">
                        </div>
                        <div class="mb-3">
                            <label for="edit_link_video" class="form-label">Link Video</label>
                            <input type="text" class="form-control" id="edit_link_video" name="link_video" placeholder="URL video YouTube, Vimeo, dll">
                        </div>
                        <div class="mb-3">
                            <label for="edit_isi_edukasi" class="form-label">Konten <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote" id="edit_isi_edukasi" name="isi_edukasi" required></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_status_aktif" name="status_aktif">
                                <label class="form-check-label" for="edit_status_aktif">Status Aktif</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_ditampilkan_beranda" name="ditampilkan_beranda">
                                <label class="form-check-label" for="edit_ditampilkan_beranda">Tampilkan di Beranda</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_urutan_tampil" class="form-label">Urutan Tampil</label>
                            <input type="number" class="form-control" id="edit_urutan_tampil" name="urutan_tampil" min="1">
                            <small class="text-muted">Urutan tampil di beranda (opsional)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            $('#tabelEdukasi').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                }
            });

            // Inisialisasi Summernote
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onInit: function() {
                        // Menghapus spasi kosong saat inisialisasi
                        $(this).summernote('code', $(this).summernote('code').trim());
                    },
                    onChange: function(contents) {
                        // Menghapus spasi kosong saat konten berubah
                        if (contents.startsWith('<p><br></p>')) {
                            $(this).summernote('code', contents.replace(/^<p><br><\/p>/, ''));
                        }
                    }
                }
            });

            // Mengisi data ke modal edit
            $('#modalEdit').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var judul = button.data('judul');
                var kategori = button.data('kategori');
                var isi_edukasi = button.data('konten');
                var sumber = button.data('sumber');
                var tag = button.data('tag');
                var link_video = button.data('link_video');
                var status = button.data('status');
                var ditampilkan_beranda = button.data('ditampilkan_beranda');
                var urutan_tampil = button.data('urutan_tampil');
                var link_gambar = button.data('gambar');

                var modal = $(this);
                modal.find('#edit_id_edukasi').val(id);
                modal.find('#edit_judul').val(judul);
                modal.find('#edit_kategori').val(kategori);
                modal.find('#edit_isi_edukasi').summernote('code', isi_edukasi);
                modal.find('#edit_sumber').val(sumber);
                modal.find('#edit_tag').val(tag);
                modal.find('#edit_link_video').val(link_video);
                modal.find('#edit_status_aktif').prop('checked', status == 1);
                modal.find('#edit_ditampilkan_beranda').prop('checked', ditampilkan_beranda == 1);
                modal.find('#edit_urutan_tampil').val(urutan_tampil);

                // Tampilkan preview gambar jika ada dan tombol hapus
                if (link_gambar) {
                    var imgPreview = '<img src="<?= $base_url ?>/uploads/edukasi/' + link_gambar + '" class="article-image-preview">';
                    modal.find('#preview_gambar').html(imgPreview);
                    modal.find('#hapus_gambar_container').show();
                } else {
                    modal.find('#preview_gambar').empty();
                    modal.find('#hapus_gambar_container').hide();
                }
            });

            // Konfirmasi hapus dengan SweetAlert2
            $('.btn-hapus').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var judul = $(this).data('judul');

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Anda yakin ingin menghapus artikel "${judul}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `?hapus=${id}`;
                    }
                });
            });

            // Auto-hide alert setelah 5 detik
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>

</html>