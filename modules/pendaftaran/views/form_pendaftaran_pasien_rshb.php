<?php
// [INFO] File view ini hanya berisi konten utama.
// Harus dirender melalui template/layout.php agar style dan struktur konsisten.
// Jika butuh style/script khusus, set variabel $additional_css/$additional_js di controller.

// --- Konten utama form pendaftaran pasien mulai di bawah ini ---
// (Lanjutkan dengan konten form, tabel, dsb. Tanpa tag <html>, <head>, <body>, atau link CSS/script global)

// Fetch registered patients from RSHB database
try {
    // Create connection to RSHB database
    $db1_host = '103.76.149.29';
    $db1_username = 'web_hasta';
    $db1_password = '@Admin123/';
    $db1_database = 'simsvbaru';

    $conn_rshb = new PDO(
        "mysql:host=$db1_host;dbname=$db1_database;charset=utf8",
        $db1_username,
        $db1_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Get filter date from various sources or default to today's date in Y-m-d format
    $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : (isset($_GET['return_filter_date']) ? $_GET['return_filter_date'] : date('Y-m-d'));

    // Query to get registered patients with kd_poli='OBG' and kd_dokter='DS0007' for the selected date
    $query = "SELECT r.no_reg, r.no_rawat, r.tgl_registrasi, r.jam_reg, p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_tlp, p.kd_kec, p.pekerjaan, p.pekerjaanpj, r.stts 
              FROM reg_periksa r 
              JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis 
              WHERE r.kd_poli = 'OBG' AND r.kd_dokter = 'DS0007' AND r.tgl_registrasi = ? 
              ORDER BY r.jam_reg ASC";
    $stmt = $conn_rshb->prepare($query);
    $stmt->execute([$filter_date]);
    $registered_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("RSHB Database Error: " . $e->getMessage());
    $registered_patients = [];
}

// Kredensial database untuk widget pengumuman (MySQLi)
require_once __DIR__ . '/../../../config/database.php';
$db_host = $db2_host;
$db_username = $db2_username;
$db_password = $db2_password;
$db_database = $db2_database;

// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host;

// Ambil parameter dari URL jika ada
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';

// Set default tempat praktek to RS Bhayangkara Batu
$default_tempat_nama = 'RS Bhayangkara Batu';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';
$id_jadwal = isset($_GET['jadwal']) ? $_GET['jadwal'] : '';

// Ambil data tempat praktek
try {
    $query = "SELECT * FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tempat_praktek = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Find RS Bhayangkara Batu in the tempat_praktek array and set its ID as default
    if (empty($id_tempat_praktek)) {
        foreach ($tempat_praktek as $tp) {
            if (stripos($tp['Nama_Tempat'], $default_tempat_nama) !== false) {
                $id_tempat_praktek = $tp['ID_Tempat_Praktek'];
                break;
            }
        }
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $tempat_praktek = array();
}

// Set default dokter Arifian
$default_dokter_id = 'b81a5b13-1bd4-4298-b294-285735630c0d';

// Ambil data dokter
try {
    // Untuk sementara hanya tampilkan dokter Arifian
    $query = "SELECT * FROM dokter WHERE ID_Dokter = :id_dokter AND Status_Aktif = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id_dokter' => $default_dokter_id]);
    $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set default value untuk id_dokter jika belum diset
    if (empty($id_dokter)) {
        $id_dokter = $default_dokter_id;
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $dokter = [];
}

// Ambil data kecamatan
try {
    // Menggunakan array statis untuk wilayah yang diminta
    $kecamatan = [
        ['kd_kec' => '1', 'nm_kec' => 'Batu'],
        ['kd_kec' => '2', 'nm_kec' => 'Bumiaji'],
        ['kd_kec' => '3', 'nm_kec' => 'Junrejo'],
        ['kd_kec' => '4', 'nm_kec' => 'Pujon'],
        ['kd_kec' => '5', 'nm_kec' => 'Ngantang'],
        ['kd_kec' => '6', 'nm_kec' => 'Lainnya']
    ];
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $kecamatan = [];
}

// Proses form jika disubmit
$errors = [];
$success = false;
$id_pendaftaran = '';

// Jika ada parameter success dari session, tampilkan pesan sukses
if (isset($_SESSION['success_message'])) {
    $success = true;
    $id_pendaftaran = isset($_SESSION['id_pendaftaran']) ? $_SESSION['id_pendaftaran'] : '';
    // Hapus session setelah digunakan
    unset($_SESSION['success_message']);
    unset($_SESSION['id_pendaftaran']);
}

// Periksa koneksi database
try {
    $conn->query("SELECT 1");
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    $errors[] = "Tidak dapat terhubung ke database. Silakan coba lagi nanti atau hubungi administrator.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $no_ktp = trim($_POST['no_ktp'] ?? '');
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kd_kec = trim($_POST['kd_kec'] ?? '');
    $pekerjaan = trim($_POST['pekerjaan'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $yang_menyarankan = trim($_POST['yang_menyarankan'] ?? '');
    $id_tempat_praktek = trim($_POST['id_tempat_praktek'] ?? '');
    $id_dokter = trim($_POST['id_dokter'] ?? '');
    $id_jadwal = trim($_POST['id_jadwal'] ?? '');

    // Validasi data
    if (empty($no_ktp)) {
        $errors[] = "NIK harus diisi";
    } elseif (strlen($no_ktp) != 16) {
        $errors[] = "NIK harus 16 digit";
    }
    if (empty($nama_pasien)) {
        $errors[] = "Nama pasien harus diisi";
    }
    if (empty($tanggal_lahir)) {
        $errors[] = "Tanggal lahir harus diisi";
    }
    if (empty($jenis_kelamin)) {
        $errors[] = "Jenis kelamin harus dipilih";
    }
    if (empty($nomor_telepon)) {
        $errors[] = "Nomor telepon harus diisi";
    }
    // Wilayah is now optional
    // if (empty($kd_kec)) {
    //     $errors[] = "Wilayah harus dipilih";
    // }
    if (empty($id_tempat_praktek)) {
        $errors[] = "Tempat praktek harus dipilih";
    }
    if (empty($id_dokter)) {
        $errors[] = "Dokter harus dipilih";
    }
    if (empty($id_jadwal)) {
        $errors[] = "Jadwal harus dipilih";
    }
    
    // Validasi voucher jika diisi
    $voucher_code = trim($_POST['voucher_code'] ?? '');
    if (!empty($voucher_code)) {
        try {
            // Periksa validitas voucher
            $stmt = $conn->prepare("SELECT * FROM voucher WHERE voucher_code = :voucher_code");
            $stmt->execute(['voucher_code' => $voucher_code]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voucher) {
                $errors[] = "Kode voucher tidak ditemukan";
            } else {
                $now = new DateTime();
                $valid_awal = new DateTime($voucher['valid_awal']);
                $valid_akhir = new DateTime($voucher['valid_akhir']);
                
                // Cek status
                if ($voucher['status'] !== 'aktif') {
                    $errors[] = "Voucher tidak dapat digunakan (status: " . $voucher['status'] . ")";
                }
                // Cek periode validitas
                else if ($now < $valid_awal) {
                    $errors[] = "Voucher belum berlaku";
                }
                else if ($now > $valid_akhir) {
                    $errors[] = "Voucher sudah kadaluarsa";
                }
                // Cek kuota
                else {
                    $kuota = isset($voucher['kuota']) ? intval($voucher['kuota']) : 1;
                    $terpakai = isset($voucher['terpakai']) ? intval($voucher['terpakai']) : 0;
                    
                    if ($terpakai >= $kuota) {
                        $errors[] = "Voucher sudah mencapai batas penggunaan (kuota habis)";
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Voucher validation error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan saat memvalidasi voucher";
        }
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Log untuk debugging
            error_log("Memulai proses pendaftaran untuk NIK: " . $no_ktp);

            // Cek apakah pasien sudah ada di tabel pasien
            $stmt = $conn->prepare("SELECT no_ktp FROM pasien WHERE no_ktp = ?");
            $stmt->execute([$no_ktp]);
            $pasien_exists = $stmt->fetch();

            error_log("Pasien exists: " . ($pasien_exists ? "Ya" : "Tidak"));

            // Jika pasien belum ada, simpan ke tabel pasien
            if (!$pasien_exists) {
                // Generate nomor RM dengan format RM-YYYYMMDD-nnn
                $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(no_rkm_medis, '-', -1) AS UNSIGNED)) as last_num FROM pasien WHERE no_rkm_medis LIKE ?");
                $prefix = 'RM-' . date('Ymd') . '-%';
                $stmt->execute([$prefix]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                $next_num = 1;
                if ($result && $result['last_num']) {
                    $next_num = $result['last_num'] + 1;
                }

                $no_rkm_medis = 'RM-' . date('Ymd') . '-' . $next_num;

                $nm_ibu = '-';
                $umur = date_diff(date_create($tanggal_lahir), date_create('today'))->y;
                $tgl_daftar = date('Y-m-d H:i:s');
                $namakeluarga = '-';
                $kd_pj = 'UMU'; // Umum
                $kd_kel = 0;
                $kd_kab = 0;

                error_log("Menyimpan data pasien baru dengan no_rkm_medis: " . $no_rkm_medis);

                $query = "INSERT INTO pasien (
                    no_rkm_medis, nm_pasien, no_ktp, jk, tgl_lahir, nm_ibu, 
                    alamat, pekerjaan, no_tlp, umur, kd_kec, namakeluarga, kd_pj, kd_kel, kd_kab, tgl_daftar
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $no_rkm_medis,
                    $nama_pasien,
                    $no_ktp,
                    $jenis_kelamin,
                    $tanggal_lahir,
                    $nm_ibu,
                    $alamat,
                    $pekerjaan,
                    $nomor_telepon,
                    $umur,
                    $kd_kec,
                    $namakeluarga,
                    $kd_pj,
                    $kd_kel,
                    $kd_kab,
                    $tgl_daftar
                ]);

                error_log("Data pasien baru berhasil disimpan");
            } else {
                // Update data pasien yang sudah ada
                $umur = date_diff(date_create($tanggal_lahir), date_create('today'))->y;

                error_log("Memperbarui data pasien dengan NIK: " . $no_ktp);

                $query = "UPDATE pasien SET 
                    nm_pasien = ?, 
                    jk = ?, 
                    tgl_lahir = ?, 
                    alamat = ?, 
                    pekerjaan = ?, 
                    no_tlp = ?, 
                    umur = ?, 
                    kd_kec = ? 
                    WHERE no_ktp = ?";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $nama_pasien,
                    $jenis_kelamin,
                    $tanggal_lahir,
                    $alamat,
                    $pekerjaan,
                    $nomor_telepon,
                    $umur,
                    $kd_kec,
                    $no_ktp
                ]);

                error_log("Data pasien berhasil diperbarui");
            }

            // Buat ID pendaftaran dengan pendekatan yang lebih sederhana dan robust
            $tanggal_format = date('Ymd');

            // Gunakan pendekatan yang lebih sederhana untuk mendapatkan nomor urut terakhir
            $query = "SELECT MAX(SUBSTRING_INDEX(ID_Pendaftaran, '-', -1)) as last_number 
                      FROM pendaftaran 
                      WHERE ID_Pendaftaran LIKE ?";
            $stmt = $conn->prepare($query);
            $prefix = "REG-" . $tanggal_format . "%";
            $stmt->execute([$prefix]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Pastikan last_number adalah integer valid
            $last_number = 0; // Default ke 0
            if ($result && isset($result['last_number'])) {
                // Hapus karakter non-numerik dan konversi ke integer
                $clean_number = preg_replace('/[^0-9]/', '', $result['last_number']);
                if (is_numeric($clean_number) && $clean_number !== '') {
                    $last_number = intval($clean_number);
                }
            }

            // Log untuk debugging
            error_log("Last number found: " . ($result ? $result['last_number'] : 'none') . ", cleaned to: " . $last_number);

            $new_number = $last_number + 1;
            $id_pendaftaran = "REG-" . $tanggal_format . "-" . str_pad($new_number, 4, "0", STR_PAD_LEFT);

            // Verifikasi ID unik dengan pendekatan yang lebih sederhana
            $is_unique = false;
            $max_attempts = 100; // Tingkatkan jumlah percobaan
            $attempt = 0;

            while (!$is_unique && $attempt < $max_attempts) {
                // Cek apakah ID sudah ada
                $check_query = "SELECT COUNT(*) as count FROM pendaftaran WHERE ID_Pendaftaran = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute([$id_pendaftaran]);
                $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($check_result['count'] == 0) {
                    $is_unique = true;
                } else {
                    // Jika ID sudah ada, tambahkan nomor urut
                    $new_number++;
                    $id_pendaftaran = "REG-" . $tanggal_format . "-" . str_pad($new_number, 4, "0", STR_PAD_LEFT);
                    $attempt++;

                    // Log untuk debugging
                    error_log("ID sudah ada, mencoba dengan nomor baru: " . $new_number);
                }
            }

            if (!$is_unique) {
                throw new PDOException("Tidak dapat membuat ID pendaftaran unik setelah " . $max_attempts . " percobaan");
            }

            error_log("ID Pendaftaran dibuat: " . $id_pendaftaran);

            // Dapatkan informasi jadwal untuk menghitung Waktu_Perkiraan
            $query_jadwal = "SELECT Jam_Mulai FROM jadwal_rutin WHERE ID_Jadwal_Rutin = ?";
            $stmt_jadwal = $conn->prepare($query_jadwal);
            $stmt_jadwal->execute([$id_jadwal]);
            $jadwal_info = $stmt_jadwal->fetch(PDO::FETCH_ASSOC);

            // Hitung nomor antrian saat ini untuk jadwal tersebut
            $query_antrian = "SELECT COUNT(*) as jumlah_antrian FROM pendaftaran 
                              WHERE ID_Jadwal = ? AND DATE(Waktu_Pendaftaran) = CURDATE()";
            $stmt_antrian = $conn->prepare($query_antrian);
            $stmt_antrian->execute([$id_jadwal]);
            $antrian_info = $stmt_antrian->fetch(PDO::FETCH_ASSOC);
            $nomor_antrian = $antrian_info['jumlah_antrian'] + 1; // Antrian berikutnya

            // Hitung Waktu_Perkiraan: Jam_Mulai + (8 menit × nomor antrian)
            $jam_mulai = $jadwal_info['Jam_Mulai'];
            $waktu_perkiraan = date('Y-m-d H:i:s', strtotime(date('Y-m-d ') . $jam_mulai . ' + ' . ($nomor_antrian * 8) . ' minutes'));

            error_log("Jam Mulai: " . $jam_mulai . ", Nomor Antrian: " . $nomor_antrian . ", Waktu Perkiraan: " . $waktu_perkiraan);

            // Simpan data pendaftaran - sesuaikan dengan struktur tabel yang ada
            $query = "INSERT INTO pendaftaran (
                        ID_Pendaftaran, 
                        no_ktp,
                        nm_pasien,
                        tgl_lahir,
                        jk,
                        no_tlp,
                        alamat,
                        Keluhan,
                        yang_menyarankan,
                        ID_Tempat_Praktek,
                        ID_Dokter,
                        ID_Jadwal,
                        Status_Pendaftaran,
                        Waktu_Pendaftaran,
                        Waktu_Perkiraan,
                        voucher_code,
                        mohon_keringanan
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Konfirmasi', ?, ?, ?, ?)";

            // Buat timestamp dengan zona waktu Asia/Jakarta
            $waktu_pendaftaran = date('Y-m-d H:i:s');
            error_log("Waktu pendaftaran: " . $waktu_pendaftaran);

            error_log("Query pendaftaran: " . $query);
            error_log("Parameter: " . json_encode([
                $id_pendaftaran,
                $no_ktp,
                $nama_pasien,
                $tanggal_lahir,
                $jenis_kelamin,
                $nomor_telepon,
                $alamat,
                $keluhan,
                $id_tempat_praktek,
                $id_dokter,
                $id_jadwal,
                $waktu_pendaftaran,
                $waktu_perkiraan,
                !empty($_POST['voucher_code']) ? trim($_POST['voucher_code']) : null,
                !empty($_POST['mohon_keringanan']) ? trim($_POST['mohon_keringanan']) : null
            ]));

            $stmt = $conn->prepare($query);
            $stmt->execute([
                $id_pendaftaran,
                $no_ktp,
                $nama_pasien,
                $tanggal_lahir,
                $jenis_kelamin,
                $nomor_telepon,
                $alamat,
                $keluhan,
                $yang_menyarankan,
                $id_tempat_praktek,
                $id_dokter,
                $id_jadwal,
                $waktu_pendaftaran,
                $waktu_perkiraan,
                !empty($_POST['voucher_code']) ? trim($_POST['voucher_code']) : null,
                !empty($_POST['mohon_keringanan']) ? trim($_POST['mohon_keringanan']) : null
            ]);

            error_log("Data pendaftaran berhasil disimpan");

            // Tambahkan setelah data pendaftaran berhasil disimpan
            if (!empty($_POST['voucher_code'])) {
                try {
                    // Update status voucher menjadi terpakai
                    $formData = [
                        'voucher_code' => trim($_POST['voucher_code']),
                        'mode' => 'use',
                        'id_pendaftaran' => $id_pendaftaran
                    ];

                    // Buat context untuk HTTP request
                    $options = [
                        'http' => [
                            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method' => 'POST',
                            'content' => http_build_query($formData)
                        ]
                    ];

                    $context = stream_context_create($options);

                    // Tentukan URL berdasarkan environment
                    $host = $_SERVER['HTTP_HOST'];
                    if ($host === 'localhost' || strpos($host, 'localhost:') === 0) {
                        $url = 'http://' . $host . '/pendaftaran/check_voucher.php';
                    } else {
                        $url = 'https://' . $host . '/pendaftaran/check_voucher.php';
                    }

                    // Kirim request untuk update status voucher
                    $result = file_get_contents($url, false, $context);

                    if ($result === FALSE) {
                        throw new Exception('Gagal mengupdate status voucher');
                    }

                    $response = json_decode($result, true);
                    if (!$response['valid']) {
                        error_log("Voucher update failed: " . $response['message']);
                    }
                } catch (Exception $e) {
                    error_log("Error updating voucher status: " . $e->getMessage());
                }
            }

            // Commit transaction
            $conn->commit();
            error_log("Transaction committed");

            // Set pesan sukses
            $success = true;



            // Simpan pesan sukses dalam session
            if (!isset($_SESSION)) {
                if (function_exists('session_status')) {
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }
                } else {
                    if (!headers_sent()) {
                        @session_start();
                    }
                }
            }

            if ($pasien_exists) {
                $_SESSION['success_message'] = "Pendaftaran berhasil dilakukan dengan ID: " . $id_pendaftaran . ". Data pasien telah diperbarui.";
            } else {
                $_SESSION['success_message'] = "Pendaftaran berhasil dilakukan dengan ID: " . $id_pendaftaran;
            }

            // Redirect ke manajemen antrian jika diminta
            if (isset($_GET['redirect']) && $_GET['redirect'] === 'manajemen_antrian') {
                header("Location: ../index.php?module=rekam_medis&action=manajemen_antrian&pendaftaran_sukses=1&id=" . urlencode($id_pendaftaran));
                exit;
            } else {
                // Tampilkan notifikasi sukses di halaman yang sama
                $success = true;
                $_SESSION['id_pendaftaran'] = $id_pendaftaran;
                
                // Set tanggal filter untuk refresh halaman
                if (isset($_POST['filter_date'])) {
                    $filter_date = $_POST['filter_date'];
                }
                
                // Commit transaksi dan selesai
                $conn->commit();
            }
        } catch (PDOException $e) {
            // Rollback transaction jika ada transaksi aktif
            try {
                // Cek apakah transaksi aktif dengan mencoba commit yang akan gagal jika tidak ada transaksi
                $inTransaction = false;
                try {
                    // Jika inTransaction tersedia gunakan metode tersebut
                    if (method_exists($conn, 'inTransaction')) {
                        $inTransaction = $conn->inTransaction();
                    }
                } catch (Exception $ex) {
                    // Jika metode tidak tersedia atau error, asumsikan tidak dalam transaksi
                    $inTransaction = false;
                }
                
                if ($inTransaction) {
                    $conn->rollBack();
                }
            } catch (Exception $rollbackEx) {
                // Jika rollback gagal, catat error tapi lanjutkan
                error_log("Rollback Error: " . $rollbackEx->getMessage());
            }
            
            error_log("Database Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $errors[] = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
        }
    }
}

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <style>
        .small-table {
            font-size: 0.75rem;
            /* Smaller font size */
        }

        .small-table th,
        .small-table td {
            padding: 0.3rem 0.5rem;
            /* Reduced padding */
            line-height: 1.2;
            /* Tighter line height */
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <!-- Registered Patients Section -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Daftar Pasien Terdaftar - Poli OBG</h6>
                            <span class="badge bg-warning text-dark">Database RSHB</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Date Filter Form -->
                        <form method="GET" action="" class="mb-3" id="dateFilterForm">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <label for="filter_date" class="col-form-label">Filter Tanggal:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : date('Y-m-d'); ?>">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Terapkan</button>
                                </div>
                                <div class="col-auto">
                                    <a href="?" class="btn btn-outline-secondary">Reset</a>
                                </div>
                                <div class="col-auto ms-auto">
                                    <span class="text-muted">Menampilkan data tanggal: <strong><?php echo date('d-m-Y', strtotime($filter_date)); ?></strong></span>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover small-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>No. Reg</th>
                                        <th>Tgl Registrasi</th>
                                        <th>Jam</th>
                                        <th>No. RM</th>
                                        <th>Nama Pasien</th>
                                        <th>NIK</th>
                                        <th>No. Telepon</th>
                                        <th>Wilayah</th>
                                        <th>Pekerjaan</th>
                                        <th>PekerjaanPJ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($registered_patients)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada data pasien terdaftar</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($registered_patients as $patient): ?>
                                            <tr>
                                                <td class="<?php echo (strtolower($patient['stts']) == 'sudah') ? 'bg-success text-white' : 'bg-warning text-dark'; ?>">
                                                    <?php echo htmlspecialchars($patient['stts']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['no_reg']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($patient['tgl_registrasi']))); ?></td>
                                                <td><?php echo htmlspecialchars($patient['jam_reg']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['no_rkm_medis']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['nm_pasien']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['no_ktp']); ?></td>
                                                <td>
                                                    <?php if (!empty($patient['no_tlp'])): ?>
                                                        <?php
                                                        // Bersihkan nomor telepon dari karakter non-numerik
                                                        $clean_number = preg_replace('/[^0-9]/', '', $patient['no_tlp']);

                                                        // Pastikan format nomor telepon benar untuk WhatsApp
                                                        if (substr($clean_number, 0, 1) == '0') {
                                                            $clean_number = '62' . substr($clean_number, 1);
                                                        } elseif (substr($clean_number, 0, 2) != '62') {
                                                            $clean_number = '62' . $clean_number;
                                                        }
                                                        ?>
                                                        <div class="d-flex align-items-center">
                                                            <a href="#" onclick="return openWhatsApp('<?php echo $clean_number; ?>')" class="btn btn-whatsapp btn-sm me-2" title="Chat WhatsApp">
                                                                <i class="bi bi-whatsapp"></i>
                                                            </a>
                                                            <span><?php echo htmlspecialchars($patient['no_tlp']); ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Get kecamatan code from patient data
                                                    $kd_kec = $patient['kd_kec'];

                                                    // Initialize variable for kecamatan name
                                                    $nm_kec = '';

                                                    // Try to find matching kecamatan in our array
                                                    foreach ($kecamatan as $kec) {
                                                        if ((string)$kec['kd_kec'] === (string)$kd_kec) {
                                                            $nm_kec = $kec['nm_kec'];
                                                            break;
                                                        }
                                                    }

                                                    // If no match found in our array, try to fetch directly from database
                                                    if (empty($nm_kec) && !empty($kd_kec)) {
                                                        try {
                                                            // First try exact match
                                                            $query = "SELECT nm_kec FROM kecamatan WHERE kd_kec = ? LIMIT 1";
                                                            $stmt = $conn_rshb->prepare($query);
                                                            $stmt->execute([$kd_kec]);
                                                            $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                                            if ($result && isset($result['nm_kec'])) {
                                                                $nm_kec = $result['nm_kec'];
                                                            } else {
                                                                // If no exact match, try to get any kecamatan with similar code
                                                                // This handles cases where the code might be stored differently
                                                                $query = "SELECT nm_kec FROM kecamatan WHERE kd_kec LIKE ? LIMIT 1";
                                                                $stmt = $conn_rshb->prepare($query);
                                                                $stmt->execute(["%{$kd_kec}%"]);
                                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                                                if ($result && isset($result['nm_kec'])) {
                                                                    $nm_kec = $result['nm_kec'];
                                                                }
                                                            }
                                                        } catch (Exception $e) {
                                                            error_log("Error fetching kecamatan name: " . $e->getMessage());
                                                        }
                                                    }

                                                    // Display name if found, otherwise display code
                                                    echo htmlspecialchars($nm_kec ?: $kd_kec);
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['pekerjaan']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['pekerjaanpj']); ?></td>
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

        <!-- Form Pendaftaran Section -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Form Pendaftaran Pasien</h6>
                            <span class="badge bg-warning text-dark">Database RSHB</span>
                        </div>
                    </div>
                    <div class="card-body">


                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> Pendaftaran Berhasil</h5>
                                <p>Pendaftaran berhasil dilakukan dengan ID: <strong><?php echo htmlspecialchars($id_pendaftaran); ?></strong></p>
                                <hr>
                                <p class="mb-0">Data pasien telah berhasil disimpan dalam database. Terima kasih telah menggunakan layanan pendaftaran online kami.</p>
                                <div class="mt-3">
                                    <a href="?" class="btn btn-primary">Daftar Pasien Baru</a>
                                    <a href="../dashboard.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
                                </div>
                            </div>
                        <?php elseif (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <hr>
                                <p class="mb-0">Silakan periksa kembali data yang Anda masukkan dan coba lagi. Jika masalah berlanjut, hubungi administrator.</p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="formPendaftaran" class="needs-validation" novalidate>
                            <?php if (isset($_GET['filter_date'])): ?>
                                <input type="hidden" name="filter_date" value="<?php echo htmlspecialchars($_GET['filter_date']); ?>">
                            <?php endif; ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5 class="border-bottom pb-2">Data Pasien</h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="no_ktp" class="form-label">NIK <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="no_ktp" name="no_ktp" maxlength="16" required>
                                        <div class="invalid-feedback">NIK harus diisi (16 digit)</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nama_pasien" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama_pasien" name="nama_pasien" required>
                                        <div class="invalid-feedback">Nama lengkap harus diisi</div>
                                        <small class="form-text text-muted">Nama akan otomatis diubah menjadi huruf kapital</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
                                        <div class="invalid-feedback">Tanggal lahir harus diisi</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kelamin" id="gender_male" value="L" required>
                                                <label class="form-check-label" for="gender_male">Laki-laki</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kelamin" id="gender_female" value="P" required>
                                                <label class="form-check-label" for="gender_female">Perempuan</label>
                                            </div>
                                            <div class="invalid-feedback">Jenis kelamin harus dipilih</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nomor_telepon" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <button class="btn btn-whatsapp" type="button" id="whatsappButton" disabled>
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                            <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" required>
                                        </div>
                                        <div class="invalid-feedback">Nomor telepon harus diisi</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="alamat" name="alamat" rows="2" required></textarea>
                                        <div class="invalid-feedback">Alamat harus diisi</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="kd_kec" class="form-label">Wilayah</label>
                                        <select class="form-select" id="kd_kec" name="kd_kec">
                                            <option value="">Pilih Wilayah</option>
                                            <?php foreach ($kecamatan as $kec): ?>
                                                <option value="<?php echo htmlspecialchars($kec['kd_kec']); ?>">
                                                    <?php echo htmlspecialchars($kec['nm_kec']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Wilayah harus dipilih</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                        <select class="form-select" id="pekerjaan" name="pekerjaan">
                                            <option value="">Pilih Pekerjaan</option>
                                            <option value="Tidak Bekerja">Tidak Bekerja</option>
                                            <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                                            <option value="Guru/Dosen">Guru/Dosen</option>
                                            <option value="PNS">PNS</option>
                                            <option value="TNI/Polri">TNI/Polri</option>
                                            <option value="Pegawai Swasta">Pegawai Swasta</option>
                                            <option value="Wiraswasta/Pengusaha">Wiraswasta/Pengusaha</option>
                                            <option value="Tenaga Kesehatan">Tenaga Kesehatan</option>
                                            <option value="Petani/Nelayan">Petani/Nelayan</option>
                                            <option value="Buruh">Buruh</option>
                                            <option value="Pelajar/Mahasiswa">Pelajar/Mahasiswa</option>
                                            <option value="Pensiunan">Pensiunan</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                        <div class="invalid-feedback">Pekerjaan tidak valid</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5 class="border-bottom pb-2">Informasi Kunjungan</h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_tempat_praktek" class="form-label">Tempat Praktek <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_tempat_praktek" name="id_tempat_praktek" required>
                                            <option value="">Pilih Tempat Praktek</option>
                                            <?php foreach ($tempat_praktek as $tp): ?>
                                                <option value="<?php echo htmlspecialchars($tp['ID_Tempat_Praktek']); ?>" <?php echo $id_tempat_praktek == $tp['ID_Tempat_Praktek'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tp['Nama_Tempat']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Tempat praktek harus dipilih</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="id_dokter" class="form-label">Dokter <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_dokter" name="id_dokter" required>
                                            <?php foreach ($dokter as $d): ?>
                                                <option value="<?php echo htmlspecialchars($d['ID_Dokter']); ?>" selected>
                                                    <?php echo htmlspecialchars($d['Nama_Dokter']); ?> (<?php echo htmlspecialchars($d['Spesialisasi']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Dokter harus dipilih</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="id_jadwal" class="form-label">Jadwal <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_jadwal" name="id_jadwal" required>
                                            <option value="">Pilih Tempat dan Dokter terlebih dahulu</option>
                                        </select>
                                        <div class="invalid-feedback">Jadwal harus dipilih</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="keluhan" class="form-label">Keluhan</label>
                                        <textarea class="form-control" id="keluhan" name="keluhan" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="yang_menyarankan" class="form-label">Yang menyarankan periksa kesini</label>
                                        <input type="text" class="form-control" id="yang_menyarankan" name="yang_menyarankan" maxlength="50">
                                    </div>
                                    <div class="mb-3">
                                        <label for="mohon_keringanan" class="form-label">Minta Keringanan</label>
                                        <textarea class="form-control" id="mohon_keringanan" name="mohon_keringanan" rows="2" placeholder="Alasan permohonan keringanan"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="voucher_code" class="form-label">Kode Voucher</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="voucher_code" name="voucher_code" placeholder="Masukkan kode voucher jika ada">
                                            <button class="btn btn-outline-secondary" type="button" id="check_voucher">Cek Voucher</button>
                                        </div>
                                        <div id="voucher_feedback" class="form-text"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                                        <button type="submit" class="btn btn-primary" id="submitBtn">Daftar</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading Overlay -->
                            <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999;">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white;">
                                    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <h4 class="mt-3">Sedang memproses pendaftaran...</h4>
                                    <p>Mohon tunggu sebentar</p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formElement = document.getElementById('formPendaftaran');
            const submitBtn = document.getElementById('submitBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const nikInput = document.getElementById('no_ktp');
            const formFields = {
                nama_pasien: document.getElementById('nama_pasien'),
                tanggal_lahir: document.getElementById('tanggal_lahir'),
                gender_male: document.getElementById('gender_male'),
                gender_female: document.getElementById('gender_female'),
                nomor_telepon: document.getElementById('nomor_telepon'),
                alamat: document.getElementById('alamat'),
                kd_kec: document.getElementById('kd_kec'),
                pekerjaan: document.getElementById('pekerjaan'),
                keluhan: document.getElementById('keluhan'),
                yang_menyarankan: document.getElementById('yang_menyarankan')
            };

            // Semua field form selain NIK
            const allFormFields = document.querySelectorAll('#formPendaftaran input:not(#no_ktp), #formPendaftaran select, #formPendaftaran textarea');

            // Nonaktifkan semua field form kecuali NIK saat halaman dimuat
            allFormFields.forEach(field => {
                field.disabled = true;
            });

            // Fungsi untuk mengubah nama menjadi huruf kapital
            const namaPasienInput = document.getElementById('nama_pasien');
            namaPasienInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            // Pastikan nama pasien dalam huruf kapital saat form disubmit
            document.getElementById('formPendaftaran').addEventListener('submit', function(e) {
                namaPasienInput.value = namaPasienInput.value.toUpperCase();
            });

            // Fungsi untuk mencari data pasien berdasarkan NIK
            function searchPatient(nik) {
                // Tampilkan loading indicator
                // Gunakan URL lengkap dengan HTTPS
                const baseUrl = window.location.protocol + '//' + window.location.host;
                let apiUrl;

                // Penanganan khusus untuk domain produksi
                if (window.location.host === 'praktekobgin.com' || window.location.host === 'www.praktekobgin.com') {
                    apiUrl = `${baseUrl}/pendaftaran/check_patient_rshb.php?nik=${nik}`;
                } else {
                    apiUrl = `${baseUrl}/pendaftaran/check_patient_rshb.php?nik=${nik}`;
                }

                console.log('Mengakses URL RSHB:', apiUrl);

                fetch(apiUrl)
                    .then(response => {
                        // Periksa apakah respons OK (status 200-299)
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }

                        // Periksa content-type untuk memastikan respons adalah JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error(`Respons bukan JSON: ${contentType}`);
                        }

                        return response.json();
                    })
                    .then(data => {
                        if (data.found) {
                            // Isi form dengan data pasien
                            formFields.nama_pasien.value = data.patient.nm_pasien.toUpperCase();
                            formFields.tanggal_lahir.value = data.patient.tgl_lahir;
                            if (data.patient.jk === 'L') {
                                formFields.gender_male.checked = true;
                            } else if (data.patient.jk === 'P') {
                                formFields.gender_female.checked = true;
                            }
                            formFields.nomor_telepon.value = data.patient.no_tlp;
                            formFields.alamat.value = data.patient.alamat;
                            formFields.kd_kec.value = data.patient.kd_kec;
                            // Use pekerjaanpj as the initial value for pekerjaan if available, otherwise fall back to pekerjaan
                            formFields.pekerjaan.value = data.patient.pekerjaanpj || data.patient.pekerjaan || '';
                            formFields.keluhan.value = '';
                            formFields.yang_menyarankan.value = '';

                            // Aktifkan semua field agar bisa diedit
                            allFormFields.forEach(field => {
                                field.disabled = false;
                            });

                            // Update pesan informasi
                            // Success message removed
                        } else {
                            // Aktifkan semua field untuk pasien baru
                            allFormFields.forEach(field => {
                                field.disabled = false;
                            });

                            // Reset form fields
                            Object.values(formFields).forEach(field => {
                                if (field.type === 'radio') {
                                    field.checked = false;
                                } else if (field !== nikInput) {
                                    field.value = '';
                                }
                            });

                            // Update pesan informasi
                            // New patient message removed
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Update pesan informasi jika terjadi error
                        // Error message removed

                        // Log error lebih detail untuk debugging
                        console.log('Detail error:', error.message);

                        // Tampilkan informasi URL yang diakses untuk debugging
                        console.log('URL yang diakses:', apiUrl);

                        // Aktifkan semua field untuk memungkinkan input manual
                        allFormFields.forEach(field => {
                            field.disabled = false;
                        });
                    });
            }

            // Event listener untuk input NIK
            let typingTimer;
            nikInput.addEventListener('input', function() {
                clearTimeout(typingTimer);

                // Reset dan nonaktifkan form jika NIK tidak lengkap
                if (this.value.length !== 16) {
                    allFormFields.forEach(field => {
                        field.disabled = true;
                    });

                    // Update pesan informasi
                    // Alert styling removed
                    // Alert content removed
                    return;
                }

                // Cek NIK jika sudah 16 digit
                typingTimer = setTimeout(() => searchPatient(this.value), 500);
            });

            // Trigger manual check if NIK is already filled with 16 digits
            if (nikInput.value.length === 16) {
                searchPatient(nikInput.value);
            }

            // Form validation
            const form = document.getElementById('formPendaftaran');
            form.addEventListener('submit', async function(event) {
                event.preventDefault();

                if (!this.checkValidity()) {
                    event.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }

                // Cek apakah ada voucher yang digunakan
                const voucherCode = document.getElementById('voucher_code').value.trim();
                if (voucherCode) {
                    try {
                        // Buat FormData baru khusus untuk validasi voucher
                        const voucherFormData = new FormData();
                        voucherFormData.append('voucher_code', voucherCode);

                        // Cek validitas voucher terakhir kali sebelum submit
                        const response = await fetch('check_voucher.php', {
                            method: 'POST',
                            body: voucherFormData
                        });
                        const data = await response.json();
                        if (!data.valid) {
                            // Tampilkan pesan error dan fokus ke field voucher
                            const feedbackElement = document.getElementById('voucher_feedback');
                            const voucherInput = document.getElementById('voucher_code');
                            
                            feedbackElement.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle"></i> ${data.message}</span>`;
                            voucherInput.classList.add('is-invalid');
                            voucherInput.classList.remove('is-valid');
                            voucherInput.focus();
                            
                            // Hilangkan loading overlay
                            loadingOverlay.style.display = 'none';
                            submitBtn.disabled = false;
                            
                            return false;
                        }
                        
                        // Jika voucher valid, tambahkan ke form data utama
                        const formData = new FormData(this);
                        formData.append('voucher_code', voucherCode);
                    } catch (error) {
                        console.error('Error checking voucher:', error);
                        alert('Terjadi kesalahan saat memvalidasi voucher');
                        loadingOverlay.style.display = 'none';
                        submitBtn.disabled = false;
                        return false;
                    }
                }

                // Jika sampai di sini, lanjutkan dengan submit form
                this.submit();
            });

            // Load jadwal when tempat or dokter changes
            const tempatSelect = document.getElementById('id_tempat_praktek');
            const dokterSelect = document.getElementById('id_dokter');
            const jadwalSelect = document.getElementById('id_jadwal');

            function loadJadwal() {
                var tempat = tempatSelect.value;
                var dokter = dokterSelect.value;

                // Reset jadwal dropdown
                jadwalSelect.innerHTML = '<option value="">Pilih Jadwal</option>';

                if (tempat && dokter) {
                    // Tampilkan loading
                    jadwalSelect.innerHTML = '<option value="">Memuat jadwal...</option>';

                    // Buat URL dengan timestamp untuk mencegah caching
                    var timestamp = new Date().getTime();
                    var url = '../get_jadwal.php?tempat=' + encodeURIComponent(tempat) +
                        '&dokter=' + encodeURIComponent(dokter) +
                        '&_=' + timestamp;

                    // Log untuk debugging
                    console.log('Memuat jadwal dari: ' + url);

                    // Gunakan XMLHttpRequest (kompatibel dengan browser lama)
                    var xhr = new XMLHttpRequest();

                    // Setup request
                    xhr.open('GET', url, true);
                    xhr.setRequestHeader('Accept', 'application/json');

                    // Handler untuk response
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) { // Request selesai
                            console.log('Status response: ' + xhr.status);

                            if (xhr.status === 200) { // Sukses
                                try {
                                    // Parse JSON response
                                    var data = JSON.parse(xhr.responseText);
                                    console.log('Data jadwal diterima:', data);

                                    // Reset dropdown
                                    jadwalSelect.innerHTML = '<option value="">Pilih Jadwal</option>';

                                    // Cek error
                                    if (data.error) {
                                        console.error('Error server:', data.error);
                                        jadwalSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                                        return;
                                    }

                                    // Cek apakah data adalah array
                                    if (!Array.isArray(data)) {
                                        console.error('Data bukan array:', data);
                                        jadwalSelect.innerHTML = '<option value="">Format data tidak valid</option>';
                                        return;
                                    }

                                    // Cek apakah data kosong
                                    if (data.length === 0) {
                                        jadwalSelect.innerHTML = '<option value="">Tidak ada jadwal tersedia</option>';
                                        return;
                                    }

                                    // Tambahkan opsi untuk setiap jadwal
                                    for (var i = 0; i < data.length; i++) {
                                        var jadwal = data[i];
                                        var option = document.createElement('option');
                                        option.value = jadwal.ID_Jadwal_Rutin;

                                        // Format teks jadwal
                                        var jadwalText = jadwal.Hari + ' - ' +
                                            jadwal.Jam_Mulai + '-' +
                                            jadwal.Jam_Selesai + ' (' +
                                            jadwal.Jenis_Layanan + ')';

                                        option.textContent = jadwalText;
                                        jadwalSelect.appendChild(option);
                                    }
                                } catch (e) {
                                    // Error parsing JSON
                                    console.error('Error parsing JSON:', e);
                                    console.error('Response text:', xhr.responseText.substring(0, 200) + '...');
                                    jadwalSelect.innerHTML = '<option value="">Error: Format respons tidak valid</option>';
                                }
                            } else {
                                // HTTP error
                                console.error('HTTP error:', xhr.status);
                                jadwalSelect.innerHTML = '<option value="">Error: Gagal memuat jadwal (HTTP ' + xhr.status + ')</option>';
                            }
                        }
                    };

                    // Handler untuk network error
                    xhr.onerror = function() {
                        console.error('Network error');
                        jadwalSelect.innerHTML = '<option value="">Error: Koneksi jaringan gagal</option>';
                    };

                    // Kirim request
                    xhr.send();
                } else {
                    // Tidak ada tempat atau dokter yang dipilih
                    jadwalSelect.innerHTML = '<option value="">Pilih tempat praktek dan dokter terlebih dahulu</option>';
                }
            }

            tempatSelect.addEventListener('change', loadJadwal);
            dokterSelect.addEventListener('change', loadJadwal);

            // Prevent multiple form submissions
            formElement.addEventListener('submit', function(e) {
                // Check if form is already being submitted
                if (formElement.classList.contains('is-submitting')) {
                    e.preventDefault();
                    return false;
                }

                // Check form validity
                if (!formElement.checkValidity()) {
                    return;
                }

                // Mark form as being submitted
                formElement.classList.add('is-submitting');

                // Disable submit button and show loading overlay
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
                loadingOverlay.style.display = 'block';

                // Allow form submission to continue
                return true;
            });

            if (tempatSelect.value && dokterSelect.value) {
                loadJadwal();
            }

            // Fungsi untuk memeriksa validitas voucher
            document.getElementById('check_voucher').addEventListener('click', function() {
                const voucherCode = document.getElementById('voucher_code').value.trim();
                const feedbackElement = document.getElementById('voucher_feedback');
                const voucherInput = document.getElementById('voucher_code');

                if (!voucherCode) {
                    feedbackElement.innerHTML = '<span class="text-danger">Silakan masukkan kode voucher</span>';
                    voucherInput.classList.add('is-invalid');
                    voucherInput.classList.remove('is-valid');
                    return;
                }

                // Tampilkan loading state
                feedbackElement.innerHTML = '<span class="text-warning"><i class="bi bi-hourglass-split"></i> Memeriksa voucher...</span>';
                voucherInput.classList.remove('is-valid', 'is-invalid');

                // Buat URL untuk pengecekan voucher
                const baseUrl = window.location.protocol + '//' + window.location.host;
                let checkUrl;

                if (window.location.host === 'praktekobgin.com' || window.location.host === 'www.praktekobgin.com') {
                    checkUrl = `${baseUrl}/pendaftaran/check_voucher.php`;
                } else {
                    checkUrl = `${baseUrl}/pendaftaran/check_voucher.php`;
                }

                // Log untuk debugging
                console.log('Checking voucher at URL:', checkUrl);
                console.log('Voucher code:', voucherCode);

                fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'voucher_code=' + encodeURIComponent(voucherCode)
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Voucher check response:', data);

                        if (data.valid) {
                            feedbackElement.innerHTML = `
                        <div class="text-success">
                            <i class="bi bi-check-circle"></i> ${data.nama_voucher}<br>
                            <small>${data.message}</small>
                        </div>`;
                            voucherInput.classList.add('is-valid');
                            voucherInput.classList.remove('is-invalid');
                        } else {
                            feedbackElement.innerHTML = `
                        <div class="text-danger">
                            <i class="bi bi-x-circle"></i> ${data.message}
                        </div>`;
                            voucherInput.classList.add('is-invalid');
                            voucherInput.classList.remove('is-valid');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking voucher:', error);
                        feedbackElement.innerHTML = `
                    <div class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan saat memeriksa voucher
                    </div>`;
                        voucherInput.classList.add('is-invalid');
                        voucherInput.classList.remove('is-valid');
                    });
            });
        });
    </script>

    <?php
    $content = ob_get_clean();

    // Additional CSS
    $additional_css = "
    .btn-whatsapp {
        background-color: #25D366;
        border-color: #25D366;
        color: white;
    }
    .btn-whatsapp:hover {
        background-color: #128C7E;
        border-color: #128C7E;
        color: white;
    }

    .card {
        border-radius: 10px;
        overflow: hidden;
    }
    .card-header {
        background-color: #0d6efd;
    }
    .form-label {
        font-weight: 500;
    }
    .border-bottom {
        border-bottom: 2px solid #dee2e6 !important;
        margin-bottom: 1rem;
    }
    
    /* CSS untuk widget pengumuman */
    .card-body .card {
        border-radius: 0;
        box-shadow: none !important;
    }
    .pengumuman-preview {
        font-size: 0.9rem;
        color: #555;
    }
    .border-end:last-child {
        border-right: none !important;
    }
    @media (max-width: 767.98px) {
        .border-end {
            border-right: none !important;
            border-bottom: 1px solid #dee2e6;
        }
    }
";

    // Add JavaScript function for WhatsApp button
    $additional_js = "
    // Fungsi untuk membuka WhatsApp dengan pesan default
    function openWhatsApp(number) {
        const defaultMessage = 'Halo, ini dari RS. Kami ingin menginformasikan mengenai jadwal kunjungan Anda.';
        const encodedMessage = encodeURIComponent(defaultMessage);
        window.open('https://wa.me/' + number + '?text=' + encodedMessage, '_blank');
        return false;
    }
    
    // Script untuk tombol WhatsApp pada form input
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('nomor_telepon');
        const whatsappButton = document.getElementById('whatsappButton');
        
        if (phoneInput && whatsappButton) {
            phoneInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    whatsappButton.disabled = false;
                } else {
                    whatsappButton.disabled = true;
                }
            });
            
            whatsappButton.addEventListener('click', function() {
                let phoneNumber = phoneInput.value.trim();
                if (phoneNumber) {
                    // Bersihkan nomor telepon dari karakter non-numerik
                    phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
                    
                    // Format untuk WhatsApp
                    if (phoneNumber.startsWith('0')) {
                        phoneNumber = '62' + phoneNumber.substring(1);
                    } else if (!phoneNumber.startsWith('62')) {
                        phoneNumber = '62' + phoneNumber;
                    }
                    
                    openWhatsApp(phoneNumber);
                }
            });
        }
    });
";

    // Additional JavaScript for pengumuman widget
    $additional_scripts = '
<script>
    $(document).ready(function() {
        // Tampilkan detail pengumuman pada modal
        $(".view-pengumuman").click(function() {
            const judul = $(this).data("judul");
            const isi = $(this).data("isi");
            const mulai = $(this).data("mulai");
            const berakhir = $(this).data("berakhir");
            const penulis = $(this).data("penulis");
            
            let tanggalText = mulai;
            if (berakhir !== "-") {
                tanggalText += " s/d " + berakhir;
            }
            
            $("#modal-judul").text(judul);
            $("#modal-isi").html(isi);
            $("#modal-tanggal").text(tanggalText);
            $("#modal-penulis").text(penulis);
        });
    });
</script>
';

    // Include template
    include_once __DIR__ . '/../../../template/layout.php';
    ?>