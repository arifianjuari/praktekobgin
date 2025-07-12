<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get project root directory
$root_dir = dirname(dirname(dirname(__DIR__)));

// Include base URL configuration if not already included
if (!defined('BASE_URL')) {
    require_once $root_dir . '/config/config.php';
}

// Declare global connection variable
global $conn;

// Dapatkan root directory project
$root_dir = dirname(dirname(dirname(__DIR__)));

// Include database configuration if not already included
if (!isset($conn) || !($conn instanceof PDO)) {
    require_once $root_dir . '/config/database.php';
}

// Log status koneksi
error_log("Checking database connection in manajemen_antrian.php");

// Cek koneksi database
if (!isset($conn) || !($conn instanceof PDO)) {
    error_log("Database connection not available in manajemen_antrian.php");
    die("Koneksi database tidak tersedia. Silakan hubungi administrator.");
}

try {
    // Pastikan koneksi database tersedia dan valid
    ensureDBConnection();
    $test = $conn->query("SELECT 1");
    if (!$test) {
        throw new PDOException("Koneksi database tidak dapat melakukan query");
    }
    error_log("Database connection test successful in manajemen_antrian.php");
} catch (PDOException $e) {
    error_log("Database test failed in manajemen_antrian.php: " . $e->getMessage());
    die("Koneksi database bermasalah: " . $e->getMessage());
}

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Cek apakah ada pendaftaran baru yang sukses
$pendaftaran_sukses = isset($_GET['pendaftaran_sukses']) && $_GET['pendaftaran_sukses'] == 1;
$id_pendaftaran_baru = isset($_GET['id']) ? $_GET['id'] : '';

// Filter dan pengurutan
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'waktu_asc';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Filter default: tampilkan semua kecuali yang dibatalkan dan selesai
$default_filter = true;
if (isset($_GET['clear_filter']) && $_GET['clear_filter'] == '1') {
    $default_filter = false;
}

// Query untuk mengambil data pendaftaran dengan join ke tabel pasien
try {
    $query = "
        SELECT 
            p.ID_Pendaftaran,
            pas.no_rkm_medis,
            p.nm_pasien as Nama_Pasien,
            p.Keluhan,
            p.mohon_keringanan,
            p.Status_Pendaftaran,
            p.Waktu_Pendaftaran,
            p.Waktu_Perkiraan,
            p.voucher_code,
            p.updatedAt,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            ml.nama_layanan AS nama_layanan,
            tp.Nama_Tempat,
            d.Nama_Dokter,
            pas.no_tlp,
            pas.berikutnya_gratis
        FROM 
            pendaftaran p
        JOIN pasien pas ON p.no_ktp = pas.no_ktp
        JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        JOIN menu_layanan ml ON jr.ID_Layanan = ml.id_layanan
        JOIN tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        JOIN dokter d ON p.ID_Dokter = d.ID_Dokter
        WHERE 1=1
    ";

    if (!empty($status_filter)) {
        $query .= " AND p.Status_Pendaftaran = :status";
    } else if ($default_filter) {
        $query .= " AND (p.Status_Pendaftaran IN ('Dikonfirmasi', 'Menunggu Konfirmasi') 
                 OR (p.Status_Pendaftaran = 'Selesai' AND DATE(p.updatedAt) = CURRENT_DATE()))";
    }

    if (!empty($search)) {
        $query .= " AND (p.nm_pasien LIKE :search OR p.ID_Pendaftaran LIKE :search OR pas.no_rkm_medis LIKE :search)";
    }

    // Filter berdasarkan hari
    if (!empty($_GET['hari'])) {
        $query .= " AND jr.Hari = :hari";
    }

    // Filter berdasarkan dokter
    if (!empty($_GET['dokter'])) {
        $query .= " AND d.Nama_Dokter = :dokter";
    }

    // Filter berdasarkan tempat
    if (!empty($_GET['tempat'])) {
        $query .= " AND tp.Nama_Tempat = :tempat";
    }

    switch ($sort_by) {
        case 'waktu_desc':
            $query .= " ORDER BY p.Waktu_Pendaftaran DESC";
            break;
        case 'nama_asc':
            $query .= " ORDER BY pas.nm_pasien ASC";
            break;
        case 'nama_desc':
            $query .= " ORDER BY pas.nm_pasien DESC";
            break;
        case 'status_asc':
            $query .= " ORDER BY p.Status_Pendaftaran ASC";
            break;
        case 'status_desc':
            $query .= " ORDER BY p.Status_Pendaftaran DESC";
            break;
        case 'hari_asc':
            $query .= " ORDER BY CASE jr.Hari 
                        WHEN 'Senin' THEN 1 
                        WHEN 'Selasa' THEN 2 
                        WHEN 'Rabu' THEN 3 
                        WHEN 'Kamis' THEN 4 
                        WHEN 'Jumat' THEN 5 
                        WHEN 'Sabtu' THEN 6 
                        WHEN 'Minggu' THEN 7 
                        ELSE 8 END ASC, jr.Jam_Mulai ASC";
            break;
        case 'waktu_perkiraan_asc':
            $query .= " ORDER BY p.Waktu_Perkiraan ASC";
            break;
        case 'waktu_perkiraan_desc':
            $query .= " ORDER BY p.Waktu_Perkiraan DESC";
            break;
        case 'waktu_asc':
        default:
            $query .= " ORDER BY p.Waktu_Pendaftaran ASC";
            break;
    }

    $stmt = $conn->prepare($query);

    if (!empty($status_filter)) {
        $stmt->bindParam(':status', $status_filter);
    }

    if (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }

    // Bind parameter untuk filter hari
    if (!empty($_GET['hari'])) {
        $stmt->bindParam(':hari', $_GET['hari']);
    }

    // Bind parameter untuk filter dokter
    if (!empty($_GET['dokter'])) {
        $stmt->bindParam(':dokter', $_GET['dokter']);
    }

    // Bind parameter untuk filter tempat
    if (!empty($_GET['tempat'])) {
        $stmt->bindParam(':tempat', $_GET['tempat']);
    }

    $stmt->execute();
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $antrian = [];
}

// Hitung jumlah antrian berdasarkan status
try {
    $query_count = "
        SELECT 
            Status_Pendaftaran, 
            COUNT(*) as jumlah 
        FROM 
            pendaftaran 
        GROUP BY 
            Status_Pendaftaran
    ";
    $stmt_count = $conn->query($query_count);
    $status_counts = [];

    while ($row = $stmt_count->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['Status_Pendaftaran']] = $row['jumlah'];
    }

    $total_antrian = 0;
    foreach ($status_counts as $status => $count) {
        if ($status !== 'Dibatalkan' && $status !== 'Selesai') {
            $total_antrian += $count;
        }
    }

    $total_keseluruhan = array_sum($status_counts);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $status_counts = [];
    $total_antrian = 0;
    $total_keseluruhan = 0;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Manajemen Praktekobgin</h5>
                        <a href="<?= BASE_URL ?>/pendaftaran/form_pendaftaran_pasien.php?redirect=manajemen_antrian"
                            class="btn btn-primary btn-sm"
                            data-bs-toggle="tooltip"
                            title="Tambah Pendaftaran">
                            <i class="bi bi-plus-circle me-1"></i> Pendaftaran Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistik Antrian dan Filter -->
                    <div class="row mb-4">
                        <div class="col-md-4 col-5">
                            <!-- Statistik Antrian -->
                            <div class="d-flex gap-2">
                                <div class="bg-success bg-opacity-25 rounded stat-box-small">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="stat-value"><?= isset($status_counts['Dikonfirmasi']) ? $status_counts['Dikonfirmasi'] : 0 ?></div>
                                        <div class="stat-label">Dikonfirmasi</div>
                                    </div>
                                </div>
                                <div class="bg-warning bg-opacity-25 rounded stat-box-small">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="stat-value"><?= isset($status_counts['Menunggu Konfirmasi']) ? $status_counts['Menunggu Konfirmasi'] : 0 ?></div>
                                        <div class="stat-label">Menunggu</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8 col-7">
                            <!-- Filter dan Pencarian -->
                            <form method="GET" class="h-100">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="manajemen_antrian">

                                <div class="d-flex flex-column h-100">
                                    <!-- Action Buttons dan Search Box -->
                                    <div class="d-flex gap-1 mb-2 flex-wrap">
                                        <button type="button"
                                            class="btn btn-success btn-sm btn-icon"
                                            onclick="refreshPage()"
                                            data-bs-toggle="tooltip"
                                            title="Refresh">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>

                                        <div class="input-group input-group-sm" style="width: auto; max-width: 200px;">
                                            <input type="text"
                                                name="search"
                                                class="form-control form-control-sm"
                                                placeholder="Cari..."
                                                value="<?= htmlspecialchars($search) ?>"
                                                style="max-width: 120px;">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <?php if (!empty($search) || !empty($status_filter) || $sort_by !== 'waktu_asc'): ?>
                                                <a href="index.php?module=rekam_medis&action=manajemen_antrian"
                                                    class="btn btn-secondary btn-sm"
                                                    data-bs-toggle="tooltip"
                                                    title="Reset Filter">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Filter Dropdowns dalam Satu Baris -->
                                    <div class="d-flex gap-1 filter-container">
                                        <select name="status" class="form-select form-select-sm filter-item">
                                            <option value="">Status</option>
                                            <option value="Dikonfirmasi" <?= $status_filter === 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                            <option value="Menunggu Konfirmasi" <?= $status_filter === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu</option>
                                        </select>
                                        <select name="hari" class="form-select form-select-sm filter-item">
                                            <option value="">Hari</option>
                                            <option value="Senin">Senin</option>
                                            <option value="Selasa">Selasa</option>
                                            <option value="Rabu">Rabu</option>
                                            <option value="Kamis">Kamis</option>
                                            <option value="Jumat">Jumat</option>
                                            <option value="Sabtu">Sabtu</option>
                                            <option value="Minggu">Minggu</option>
                                        </select>
                                        <select name="dokter" class="form-select form-select-sm filter-item">
                                            <option value="">Dokter</option>
                                            <?php
                                            $query_dokter = "SELECT DISTINCT Nama_Dokter FROM dokter WHERE Status_Aktif = 1";
                                            $stmt_dokter = $conn->query($query_dokter);
                                            while ($row = $stmt_dokter->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($_GET['dokter'] ?? '') === $row['Nama_Dokter'] ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($row['Nama_Dokter']) . "' $selected>" .
                                                    htmlspecialchars($row['Nama_Dokter']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <select name="tempat" class="form-select form-select-sm filter-item">
                                            <option value="">Tempat</option>
                                            <?php
                                            $query_tempat = "SELECT DISTINCT Nama_Tempat FROM tempat_praktek WHERE Status_Aktif = 1";
                                            $stmt_tempat = $conn->query($query_tempat);
                                            while ($row = $stmt_tempat->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($_GET['tempat'] ?? '') === $row['Nama_Tempat'] ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($row['Nama_Tempat']) . "' $selected>" .
                                                    htmlspecialchars($row['Nama_Tempat']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <select name="sort" class="form-select form-select-sm filter-item">
                                            <option value="waktu_asc" <?= $sort_by === 'waktu_asc' ? 'selected' : '' ?>>Terlama</option>
                                            <option value="waktu_desc" <?= $sort_by === 'waktu_desc' ? 'selected' : '' ?>>Terbaru</option>
                                            <option value="nama_asc" <?= $sort_by === 'nama_asc' ? 'selected' : '' ?>>Nama (A-Z)</option>
                                            <option value="nama_desc" <?= $sort_by === 'nama_desc' ? 'selected' : '' ?>>Nama (Z-A)</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary filter-button">
                                            <i class="bi bi-filter"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <style>
                        /* Tambahan style untuk filter */
                        .form-select-sm,
                        .form-control-sm,
                        .btn-sm {
                            height: 30px;
                            font-size: 0.85rem;
                            padding: 2px 6px;
                        }

                        .btn-sm.btn-icon {
                            width: 30px;
                            height: 30px;
                            padding: 0;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .input-group-sm .form-control {
                            height: 30px;
                        }

                        .d-flex.gap-1 {
                            column-gap: 4px !important;
                        }

                        .filter-container {
                            flex-wrap: nowrap;
                            overflow-x: auto;
                            padding-bottom: 4px;
                            scrollbar-width: thin;
                            -ms-overflow-style: none;
                        }

                        .filter-container::-webkit-scrollbar {
                            height: 4px;
                        }

                        .filter-container::-webkit-scrollbar-thumb {
                            background-color: rgba(0, 0, 0, 0.2);
                            border-radius: 4px;
                        }

                        .filter-item {
                            flex: 0 0 auto;
                            width: auto;
                            min-width: 80px;
                            max-width: 120px;
                        }

                        .filter-button {
                            flex: 0 0 auto;
                        }

                        @media (max-width: 768px) {
                            .stat-box {
                                padding: 6px !important;
                            }

                            .stat-box h4 {
                                font-size: 1.1rem;
                                margin: 0;
                            }

                            .stat-box small {
                                font-size: 0.75rem;
                            }

                            .form-select,
                            .form-control,
                            .btn {
                                font-size: 0.8rem;
                                height: 28px;
                                padding: 2px 6px;
                            }

                            .btn-icon {
                                width: 28px;
                                height: 28px;
                                padding: 0;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                            }

                            .input-group {
                                width: auto !important;
                            }

                            .filter-item {
                                min-width: 70px;
                                max-width: 90px;
                            }

                            .form-control {
                                min-width: 80px;
                                max-width: 100px !important;
                            }

                            .waktu-input {
                                width: 80px;
                            }
                        }

                        @media (max-width: 576px) {

                            .col-5,
                            .col-7 {
                                padding-left: 8px;
                                padding-right: 8px;
                            }

                            .card-body {
                                padding: 12px 8px;
                            }

                            .filter-item {
                                min-width: 60px;
                                max-width: 80px;
                            }
                        }

                        .waktu-perkiraan-cell {
                            position: relative;
                        }

                        .waktu-display {
                            cursor: pointer;
                        }

                        .waktu-display:hover {
                            color: #0d6efd;
                            text-decoration: underline;
                        }

                        .waktu-input {
                            width: 100px;
                            display: inline-block;
                            margin-right: 5px;
                        }

                        .edit-btn {
                            padding: 2px 6px;
                            font-size: 12px;
                        }

                        .table {
                            width: 100%;
                            margin-bottom: 0;
                        }

                        .table th,
                        .table td {
                            white-space: nowrap;
                            vertical-align: middle;
                            padding: 8px 12px;
                        }

                        /* Style khusus untuk setiap kolom */
                        .table td:first-child,
                        /* Kolom aksi */
                        .table th:first-child {
                            min-width: 200px;
                            width: auto;
                        }

                        .table td:nth-child(2),
                        /* Kolom No */
                        .table th:nth-child(2) {
                            min-width: 40px;
                            text-align: center;
                        }

                        .table td:nth-child(3),
                        /* Kolom Nama */
                        .table th:nth-child(3) {
                            min-width: 200px;
                            padding-right: 24px;
                        }

                        .table td:nth-child(4),
                        /* Kolom Waktu */
                        .table th:nth-child(4) {
                            min-width: 120px;
                            text-align: center;
                        }

                        .table td:nth-child(5),
                        /* Kolom Keluhan */
                        .table th:nth-child(5) {
                            min-width: 150px;
                        }

                        .table td:nth-child(6),
                        /* Kolom Status */
                        .table th:nth-child(6) {
                            min-width: 130px;
                            text-align: center;
                        }

                        .table td:first-child .btn-group {
                            display: flex;
                            flex-wrap: nowrap;
                            gap: 2px;
                        }

                        .table-responsive {
                            overflow-x: auto;
                            -webkit-overflow-scrolling: touch;
                        }

                        .nowrap {
                            white-space: nowrap;
                            width: 100%;
                        }

                        /* Tambahan style untuk statistik box kecil */
                        .stat-box-small {
                            width: 80px;
                            height: 70px;
                            padding: 8px 4px;
                            text-align: center;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .stat-value {
                            font-size: 1.5rem;
                            font-weight: bold;
                            line-height: 1;
                        }

                        .stat-label {
                            font-size: 0.7rem;
                            margin-top: 2px;
                        }

                        @media (max-width: 768px) {
                            .stat-box-small {
                                width: 70px;
                                height: 60px;
                                padding: 6px 2px;
                            }

                            .stat-value {
                                font-size: 1.3rem;
                            }

                            .stat-label {
                                font-size: 0.65rem;
                                margin-top: 1px;
                            }
                        }

                        @media (max-width: 576px) {
                            .stat-box-small {
                                width: 60px;
                                height: 55px;
                                padding: 4px 2px;
                            }

                            .stat-value {
                                font-size: 1.2rem;
                            }

                            .stat-label {
                                font-size: 0.6rem;
                            }
                        }
                    </style>

                    <?php if (empty($antrian)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Tidak ada data antrian saat ini.
                        </div>
                    <?php else: ?>
                        <?php
                        // Kelompokkan antrian berdasarkan dokter, hari, dan tempat
                        $grouped_antrian = [];
                        foreach ($antrian as $a) {
                            $key = $a['Nama_Dokter'] . '|' . $a['Hari'] . '|' . $a['Nama_Tempat'];
                            if (!isset($grouped_antrian[$key])) {
                                $grouped_antrian[$key] = [
                                    'dokter' => $a['Nama_Dokter'],
                                    'hari' => $a['Hari'],
                                    'tempat' => $a['Nama_Tempat'],
                                    'jam_praktek' => $a['Jam_Mulai'] . ' - ' . $a['Jam_Selesai'],
                                    'data' => []
                                ];
                            }
                            $grouped_antrian[$key]['data'][] = $a;
                        }
                        
                        // Sort each group's data by Waktu_Perkiraan
                        foreach ($grouped_antrian as &$group) {
                            usort($group['data'], function($a, $b) {
                                if (empty($a['Waktu_Perkiraan']) && empty($b['Waktu_Perkiraan'])) {
                                    return 0;
                                } elseif (empty($a['Waktu_Perkiraan'])) {
                                    return 1;
                                } elseif (empty($b['Waktu_Perkiraan'])) {
                                    return -1;
                                }
                                return strtotime($a['Waktu_Perkiraan']) - strtotime($b['Waktu_Perkiraan']);
                            });
                        }
                        unset($group); // Unset the reference to avoid issues

                        // Urutkan grup berdasarkan hari
                        $hari_order = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];
                        uksort($grouped_antrian, function ($a, $b) use ($hari_order) {
                            $a_parts = explode('|', $a);
                            $b_parts = explode('|', $b);

                            // Bandingkan hari terlebih dahulu
                            $a_hari = $hari_order[$a_parts[1]] ?? 8;
                            $b_hari = $hari_order[$b_parts[1]] ?? 8;

                            if ($a_hari !== $b_hari) {
                                return $a_hari - $b_hari;
                            }

                            // Jika hari sama, bandingkan dokter
                            return strcmp($a_parts[0], $b_parts[0]);
                        });
                        ?>

                        <?php foreach ($grouped_antrian as $group): ?>
                            <div class="card mb-2 shadow-sm">
                                <div class="card-header bg-light py-1 px-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <span class="fw-medium me-1"><?= htmlspecialchars($group['dokter']) ?></span>
                                                <span class="me-1"><i class="bi bi-calendar-day"></i> <?= htmlspecialchars($group['hari']) ?></span>
                                                <span class="me-1"><i class="bi bi-clock"></i> <?= htmlspecialchars($group['jam_praktek']) ?></span>
                                                <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($group['tempat']) ?></span>
                                            </div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill" style="font-size: 0.7rem;"><?= count($group['data']) ?> Pasien</span>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0 nowrap">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center small fw-normal">Aksi</th>
                                                    <th class="text-center small fw-normal">No</th>
                                                    <th class="text-center small fw-normal">Nama Pasien</th>
                                                    <th class="text-center small fw-normal">Waktu Perkiraan</th>
                                                    <th class="text-center small fw-normal">Kode Voucher</th>
                                                    <th class="text-center small fw-normal">Keluhan</th>
                                                    <th class="text-center small fw-normal">Mohon Keringanan</th>
                                                    <th class="text-center small fw-normal">Gratis</th>
                                                    <th class="text-center small fw-normal">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // No longer need to initialize $no = 1 here as we'll calculate it dynamically
                                                foreach ($group['data'] as $index => $a):
                                                    // Calculate queue number based on position in the sorted array (by Waktu_Perkiraan)
                                                    $no = $index + 1;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <!-- Tombol untuk melihat rekam medis -->
                                                                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $a['no_rkm_medis'] ?>&id_pendaftaran=<?= $a['ID_Pendaftaran'] ?>&source=antrian"
                                                                    class="btn btn-primary btn-sm btn-icon" data-bs-toggle="tooltip"
                                                                    title="Lihat Rekam Medis">
                                                                    <i class="bi bi-clipboard2-pulse"></i>
                                                                </a>
                                                                
                                                                <?php if ($a['Status_Pendaftaran'] !== 'Menunggu Konfirmasi'): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-warning btn-icon"
                                                                        onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Menunggu Konfirmasi')"
                                                                        data-bs-toggle="tooltip" title="Ubah ke Menunggu Konfirmasi">
                                                                        <i class="bi bi-hourglass"></i>
                                                                    </button>
                                                                <?php endif; ?>

                                                                <?php if ($a['Status_Pendaftaran'] !== 'Dikonfirmasi'): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-success btn-icon"
                                                                        onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Dikonfirmasi')"
                                                                        data-bs-toggle="tooltip" title="Konfirmasi Pendaftaran">
                                                                        <i class="bi bi-check-circle"></i>
                                                                    </button>
                                                                <?php endif; ?>

                                                                <?php if ($a['Status_Pendaftaran'] !== 'Selesai'): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-info btn-icon"
                                                                        onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Selesai')"
                                                                        data-bs-toggle="tooltip" title="Tandai Selesai">
                                                                        <i class="bi bi-flag"></i>
                                                                    </button>
                                                                <?php endif; ?>

                                                                <?php if ($a['Status_Pendaftaran'] !== 'Dibatalkan'): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon"
                                                                        onclick="deletePendaftaran('<?= $a['ID_Pendaftaran'] ?>')"
                                                                        data-bs-toggle="tooltip" title="Hapus Pendaftaran">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>

                                                                <?php if (!empty($a['no_tlp'])): ?>
                                                                    <?php
                                                                    // Bersihkan nomor telepon dari karakter non-numerik
                                                                    $no_tlp_clean = preg_replace('/[^0-9]/', '', $a['no_tlp']);

                                                                    // Pastikan format nomor telepon benar (awali dengan 62)
                                                                    if (substr($no_tlp_clean, 0, 1) == '0') {
                                                                        $no_tlp_clean = '62' . substr($no_tlp_clean, 1);
                                                                    } elseif (substr($no_tlp_clean, 0, 2) != '62') {
                                                                        $no_tlp_clean = '62' . $no_tlp_clean;
                                                                    }

                                                                    // Buat pesan untuk WhatsApp
                                                                    $pesan = "Halo " . $a['Nama_Pasien'] . ", ";
                                                                    $pesan .= "pendaftaran Anda dengan ID " . $a['ID_Pendaftaran'] . " ";
                                                                    $pesan .= "pada tanggal " . date('d/m/Y H:i', strtotime($a['Waktu_Pendaftaran'])) . " ";
                                                                    $pesan .= "saat ini berstatus " . $a['Status_Pendaftaran'] . ".";

                                                                    // Tambahkan informasi waktu perkiraan jika ada
                                                                    if (!empty($a['Waktu_Perkiraan'])) {
                                                                        $pesan .= "\n\nWaktu perkiraan Anda diperiksa: " . date('H:i', strtotime($a['Waktu_Perkiraan'])) . " WIB.";
                                                                    }
                                                                    
                                                                    // Tambahkan pesan untuk melihat antrian
                                                                    $pesan .= "\n\nLihat antrian anda di https://praktekobgin.com/pendaftaran/antrian.php";

                                                                    // Encode pesan untuk URL
                                                                    $pesan_encoded = urlencode($pesan);

                                                                    // Buat URL WhatsApp
                                                                    $whatsapp_url = "https://wa.me/" . $no_tlp_clean . "?text=" . $pesan_encoded;
                                                                    ?>
                                                                    <a href="<?= $whatsapp_url ?>" target="_blank"
                                                                        class="btn btn-sm btn-success btn-icon"
                                                                        data-bs-toggle="tooltip" title="Hubungi via WhatsApp">
                                                                        <i class="bi bi-whatsapp"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="text-center fw-bold"><?= $no ?></td>
                                                        <td><?= htmlspecialchars($a['Nama_Pasien']) ?></td>
                                                        <td class="waktu-perkiraan-cell" data-id="<?= $a['ID_Pendaftaran'] ?>">
                                                            <span class="waktu-display"><?= !empty($a['Waktu_Perkiraan']) ? date('H:i', strtotime($a['Waktu_Perkiraan'])) : '-' ?></span>
                                                            <input type="time" class="form-control waktu-input" style="display: none;"
                                                                value="<?= !empty($a['Waktu_Perkiraan']) ? date('H:i', strtotime($a['Waktu_Perkiraan'])) : '' ?>">
                                                            <button class="btn btn-sm btn-outline-primary edit-btn" style="display: none;">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if (!empty($a['voucher_code'])): ?>
                                                                <span class="badge bg-info">
                                                                    <?= htmlspecialchars($a['voucher_code']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= !empty($a['Keluhan']) ? htmlspecialchars($a['Keluhan']) : '-' ?></td>
                                                        <td><?= !empty($a['mohon_keringanan']) ? htmlspecialchars($a['mohon_keringanan']) : '-' ?></td>
                                                        <td class="text-center">
                                                            <?php if ($a['berikutnya_gratis'] == 1): ?>
                                                                <span class="badge bg-success">Ya</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Tidak</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?= getStatusBadgeClass($a['Status_Pendaftaran']) ?>">
                                                                <?= htmlspecialchars($a['Status_Pendaftaran']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pendaftaran -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Pendaftaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function updateStatusDirect(id, newStatus) {
        const formData = new FormData();
        formData.append('id_pendaftaran', id);
        formData.append('status', newStatus);

        // Fix: Use correct path to the controller
        fetch('<?= BASE_URL ?>/modules/rekam_medis/controllers/update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                throw new Error('Respons tidak valid (bukan JSON)');
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal mengupdate status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupdate status');
            });
    }

    function viewDetail(id) {
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        detailModal.show();

        fetch(`../modules/rekam_medis/controllers/get_pendaftaran_detail.php?id=${id}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('detailContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('detailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Terjadi kesalahan saat memuat data: ${error.message}
                </div>
            `;
            });
    }

    function refreshPage() {
        location.reload();
    }

    // Inisialisasi tooltip
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi semua cell waktu perkiraan
        document.querySelectorAll('.waktu-perkiraan-cell').forEach(cell => {
            const display = cell.querySelector('.waktu-display');
            const input = cell.querySelector('.waktu-input');
            const editBtn = cell.querySelector('.edit-btn');

            // Tampilkan input saat display diklik
            display.addEventListener('click', function() {
                display.style.display = 'none';
                input.style.display = 'inline-block';
                editBtn.style.display = 'inline-block';
                input.focus();
            });

            // Simpan perubahan saat tombol check diklik
            editBtn.addEventListener('click', function() {
                const id = cell.dataset.id;
                const newTime = input.value;

                // Kirim update ke server
                fetch('../modules/rekam_medis/controllers/update_waktu_perkiraan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_pendaftaran=${id}&waktu_perkiraan=${newTime}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update tampilan
                            display.textContent = newTime || '-';
                            display.style.display = 'inline-block';
                            input.style.display = 'none';
                            editBtn.style.display = 'none';
                        } else {
                            alert('Gagal mengupdate waktu: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengupdate waktu');
                    });
            });

            // Handle ketika input kehilangan fokus
            input.addEventListener('blur', function(e) {
                // Jika yang diklik bukan tombol edit, kembalikan ke tampilan awal
                if (!e.relatedTarget || !e.relatedTarget.classList.contains('edit-btn')) {
                    display.style.display = 'inline-block';
                    input.style.display = 'none';
                    editBtn.style.display = 'none';
                }
            });
        });
    });

    function deletePendaftaran(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data pendaftaran ini? Tindakan ini tidak dapat dibatalkan.')) {
            const formData = new FormData();
            formData.append('id_pendaftaran', id);

            fetch('modules/rekam_medis/controllers/delete_pendaftaran.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Data pendaftaran berhasil dihapus');
                        location.reload();
                    } else {
                        alert('Gagal menghapus data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus data');
                });
        }
    }
</script>

<?php
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Menunggu Konfirmasi':
            return 'bg-warning text-dark';
        case 'Dikonfirmasi':
            return 'bg-success';
        case 'Dibatalkan':
            return 'bg-danger';
        case 'Selesai':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Additional CSS
$additional_css = "
    .card {
        border: none;
        border-radius: 10px;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: normal;
        font-size: 0.875rem;
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }
    .table td {
        white-space: nowrap;
        vertical-align: middle;
    }
    .badge {
        font-size: 0.875rem;
        padding: 0.5em 0.75em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        margin-right: 0.25rem;
    }
    .modal-header {
        border-bottom: 0;
    }
    .modal-footer {
        border-top: 0;
    }
    .form-control, .form-select, .btn {
        height: 32px;
        font-size: 0.85rem;
        padding: 4px 8px;
    }
    .form-select {
        padding-right: 24px;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 12px;
        white-space: nowrap;
        min-width: unset;
    }
    .btn i {
        margin-right: 4px;
        font-size: 0.85rem;
    }
    .filter-form {
        flex-wrap: wrap;
        align-items: center;
        gap: 4px !important;
    }
    .search-container {
        width: 200px;
    }
    .filter-container {
        width: 130px;
    }
    .button-container {
        display: inline-flex;
        gap: 4px;
    }
    .button-container .btn {
        margin: 0;
    }
    @media (max-width: 1200px) {
        .search-container {
            width: 180px;
        }
        .filter-container {
            width: 120px;
        }
    }
    @media (max-width: 992px) {
        .search-container {
            width: 100%;
            margin-bottom: 8px;
        }
        .filter-container {
            width: 48%;
            margin-bottom: 8px;
        }
        .button-container {
            margin-bottom: 8px;
        }
    }
    @media (max-width: 768px) {
        .form-control, .form-select, .btn {
            font-size: 0.85rem;
            height: 32px;
        }
        .filter-container {
            width: 100%;
        }
        .button-container {
            width: 100%;
            justify-content: flex-start;
        }
        .button-container .btn {
            flex: 1;
        }
    }
    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
    }
    .btn-icon i {
        margin: 0;
        font-size: 1rem;
    }
    .btn-group .btn-icon {
        width: 28px;
        height: 28px;
    }
    .btn-group .btn-icon i {
        font-size: 0.875rem;
    }
    .filter-form .btn-icon {
        margin: 0;
    }
    @media (max-width: 768px) {
        .btn-icon {
            width: 36px;
            height: 36px;
        }
        .btn-group .btn-icon {
            width: 32px;
            height: 32px;
        }
    }
";
?>