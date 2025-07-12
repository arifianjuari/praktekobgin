<?php
// Periksa apakah session sudah dimulai dengan cara yang kompatibel dengan berbagai versi PHP
if (function_exists('session_status')) {
    // PHP 5.4.0 atau lebih baru
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} else {
    // PHP versi lama
    if (!headers_sent()) {
        @session_start();
    }
}

// Define ROOT_PATH if not already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Impor konfigurasi zona waktu dengan path absolut
require_once dirname(__DIR__, 3) . '/config/timezone.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
$page_title = "Form Pendaftaran Pasien";

// Pastikan koneksi database global PDO sudah tersedia
require_once dirname(__DIR__, 3) . '/config/database.php';
global $conn; // $conn sudah merupakan instance PDO dari config/database.php


// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host;

// Ambil parameter dari URL jika ada
$id_layanan = isset($_GET['layanan']) ? $_GET['layanan'] : '';
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';
$id_jadwal = isset($_GET['jadwal']) ? $_GET['jadwal'] : '';

// Ambil data layanan
try {
    $query = "SELECT id_layanan, nama_layanan FROM menu_layanan WHERE status_aktif = 1 ORDER BY nama_layanan ASC";
    $stmt = $conn->query($query);
    $layanan_list = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $layanan_list = array();
}

// Ambil data tempat praktek
try {
    $query = "SELECT * FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat ASC";
    $stmt = $conn->query($query);
    $tempat_praktek = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $tempat_praktek = array();
}

// Ambil data dokter berdasarkan layanan & tempat praktek jika ada
try {
    if (!empty($id_layanan) && !empty($id_tempat_praktek)) {
        // Ambil dokter berdasarkan kombinasi layanan & tempat praktek yang dipilih dan hanya yang punya jadwal_rutin aktif
        $query = "SELECT DISTINCT d.* FROM dokter d 
                 INNER JOIN jadwal_rutin jr ON d.ID_Dokter = jr.ID_Dokter 
                 WHERE jr.ID_Tempat_Praktek = ? 
                 AND jr.ID_Layanan = ?
                 AND d.Status_Aktif = 1
                 AND jr.status_aktif = 1
                 ORDER BY d.Nama_Dokter ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_tempat_praktek, $id_layanan]);
        $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!empty($id_tempat_praktek)) {
        // Jika hanya tempat praktek yang dipilih, filter dokter aktif yang punya jadwal_rutin aktif di tempat itu
        $query = "SELECT DISTINCT d.* FROM dokter d 
                 INNER JOIN jadwal_rutin jr ON d.ID_Dokter = jr.ID_Dokter 
                 WHERE jr.ID_Tempat_Praktek = ?
                 AND d.Status_Aktif = 1
                 AND jr.status_aktif = 1
                 ORDER BY d.Nama_Dokter ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_tempat_praktek]);
        $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Jika tidak ada tempat praktek yang dipilih, tampilkan semua dokter aktif
        $query = "SELECT * FROM dokter WHERE Status_Aktif = 1 ORDER BY Nama_Dokter ASC";
        $stmt = $conn->query($query);
        $dokter = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $dokter = array();
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
    $id_layanan = trim($_POST['id_layanan'] ?? '');
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
    if (empty($kd_kec)) {
        $errors[] = "Wilayah harus dipilih";
    }
    if (empty($id_layanan)) {
        $errors[] = "Layanan harus dipilih";
    }
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
            $stmt = $conn->prepare("SELECT * FROM voucher WHERE voucher_code = ? AND status = 'aktif'");
            $stmt->execute([$voucher_code]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$voucher) {
                $errors[] = "Kode voucher tidak ditemukan atau tidak aktif";
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
                } else if ($now > $valid_akhir) {
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
            $errors[] = "Terjadi kesalahan saat memvalidasi voucher: " . htmlspecialchars($e->getMessage());
        }
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            // Begin transaction (PDO)
            $conn->beginTransaction();

            // Log untuk debugging
            error_log("Memulai proses pendaftaran untuk NIK: " . $no_ktp);

            // Cek apakah pasien sudah ada di tabel pasien
            $stmt = $conn->prepare("SELECT no_ktp FROM pasien WHERE no_ktp = ?");
            $stmt->execute([$no_ktp]);
            $pasien_exists = $stmt->fetch(PDO::FETCH_ASSOC);

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
                // Update kolom terpakai voucher secara langsung jika voucher digunakan dan belum melebihi kuota
                if (!empty($voucher_code)) {
                    try {
                        $stmt = $conn->prepare("UPDATE voucher SET terpakai = terpakai + 1 WHERE voucher_code = ? AND terpakai < kuota");
                        $stmt->execute([$voucher_code]);
                    } catch (PDOException $e) {
                        error_log("Gagal update kolom terpakai voucher: " . $e->getMessage());
                    }
                }
            }

            // Commit transaction
            $conn->commit();
            // No need to set autocommit(TRUE) in PDO
            error_log("Transaction committed");

            // Set pesan sukses
            $success = true;

            try {
                // --- Ambil nama layanan, tempat praktek, dokter, dan jadwal ---
                $nama_layanan = '';
                $nama_tempat = '';
                $nama_dokter = '';
                $jadwal_jam = '';
                // Nama layanan
                $stmt = $conn->prepare("SELECT nama_layanan FROM menu_layanan WHERE id_layanan = ? LIMIT 1");
                $stmt->execute([$id_layanan]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) $nama_layanan = $row['nama_layanan'];
                // Nama tempat praktek
                $stmt = $conn->prepare("SELECT Nama_Tempat FROM tempat_praktek WHERE ID_Tempat_Praktek = ? LIMIT 1");
                $stmt->execute([$id_tempat_praktek]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) $nama_tempat = $row['Nama_Tempat'];
                // Nama dokter
                $stmt = $conn->prepare("SELECT Nama_Dokter FROM dokter WHERE ID_Dokter = ? LIMIT 1");
                $stmt->execute([$id_dokter]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) $nama_dokter = $row['Nama_Dokter'];
                // Jadwal (jam mulai)
                $stmt = $conn->prepare("SELECT Jam_Mulai, Hari FROM jadwal_rutin WHERE ID_Jadwal_Rutin = ? LIMIT 1");
                $stmt->execute([$id_jadwal]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $jadwal_jam = $row['Hari'] . ', ' . $row['Jam_Mulai'];
                }
                // Siapkan pesan WhatsApp
                $message = "Pendaftaran pasien baru berhasil!\n";
                $message .= "ID Pendaftaran: {$id_pendaftaran}\n";
                $message .= "Nama: {$nama_pasien}\n";
                $message .= "NIK: {$no_ktp}\n";
                $message .= "Tanggal Lahir: {$tanggal_lahir}\n";
                $message .= "Jenis Layanan: {$nama_layanan}\n";
                $message .= "Tempat Praktek: {$nama_tempat}\n";
                $message .= "Dokter/Bidan: {$nama_dokter}\n";
                $message .= "Jadwal: {$jadwal_jam}\n";
                $message .= "Waktu Pendaftaran: " . date('Y-m-d H:i:s') . "\n";
                $message .= "Perkiraan Waktu Periksa: " . date('H:i', strtotime($waktu_perkiraan)) . " WIB";

                // Daftar nomor WhatsApp yang akan menerima notifikasi
                $whatsapp_numbers = array(
                    '+6281334179767'  // Nomor pertama
                );

                // Tambahkan nomor telepon pasien ke daftar penerima notifikasi
                // Pastikan nomor telepon dalam format yang benar (diawali dengan kode negara)
                $patient_phone = $nomor_telepon;
                // Jika nomor tidak diawali dengan +62, tambahkan
                if (substr($patient_phone, 0, 1) === '0') {
                    $patient_phone = '+62' . substr($patient_phone, 1);
                } elseif (substr($patient_phone, 0, 3) !== '+62') {
                    $patient_phone = '+62' . $patient_phone;
                }

                // Tambahkan nomor pasien ke array penerima
                $whatsapp_numbers[] = $patient_phone;

                // Kirim pesan ke setiap nomor
                foreach ($whatsapp_numbers as $number) {
                    // Parameter untuk API UltraMsg
                    $params = array(
                        'token' => '15suezbff95b7xzn',
                        'to' => $number,
                        'body' => $message
                    );

                    // Inisialisasi cURL
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.ultramsg.com/instance119166/messages/chat",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => http_build_query($params),
                        CURLOPT_HTTPHEADER => array(
                            "content-type: application/x-www-form-urlencoded"
                        ),
                    ));

                    // Eksekusi request
                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ($err) {
                        error_log("WhatsApp Notification Error for {$number}: " . $err);
                    } else {
                        error_log("WhatsApp Notification Sent to {$number}: " . $response);
                    }
                }
            } catch (Exception $e) {
                error_log("WhatsApp Notification Exception: " . $e->getMessage());
                // Tidak menghentikan proses pendaftaran jika notifikasi gagal
            }


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

            // Redirect ke halaman sukses dengan menggunakan BASE_URL dan router yang benar
            header("Location: " . $protocol . $host . "/index.php?module=pendaftaran&action=pendaftaran_sukses&id=" . urlencode($id_pendaftaran));
            exit;
        } catch (PDOException $e) {
            // Rollback transaction
            $conn->rollBack(); // Rollback transaction
            // No need to set autocommit(TRUE) in PDO
            error_log("Database Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $errors[] = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
        }
    }
}

// Start output buffering
ob_start();
?>

<!-- Link ke design system jadwal_style.css -->
<link rel="stylesheet" href="/template/jadwal_style.css">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Mulai form pendaftaran tanpa card dan tanpa judul -->
            <?php
            // Set base_url yang benar untuk keperluan lain
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            if ($host === 'localhost' || strpos($host, 'localhost:') === 0) {
                $base_url = $protocol . $host; // gunakan BASE_URL dari config.env jika ada
            } else if ($host === 'www.praktekobgin.com' || $host === 'praktekobgin.com') {
                $base_url = 'https://' . $host;
            } else {
                $base_url = $protocol . $host;
            }
            $base_url = rtrim($base_url, '/');

            // Cek apakah ada pengumuman aktif
            require_once dirname(__DIR__, 3) . '/config/koneksi.php';
            $pdo = getPDOConnection();
            $today = date('Y-m-d');
            $stmtPengumuman = $pdo->prepare("SELECT COUNT(*) FROM pengumuman WHERE status_aktif=1 AND tanggal_mulai <= ? AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= ?)");
            $stmtPengumuman->execute([$today, $today]);
            $jumlahPengumumanAktif = $stmtPengumuman->fetchColumn();
            if ($jumlahPengumumanAktif > 0) {
                include_once dirname(__DIR__, 3) . '/widgets/pengumuman_widget.php';
            }
            ?>

            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm rounded-4 px-4 py-3 mb-4">
                        <h5 class="mb-2 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan</h5>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <p class="mb-0">Silakan periksa kembali data yang Anda masukkan dan coba lagi.<br>Jika masalah berlanjut, hubungi administrator.</p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="formPendaftaran" class="needs-validation" novalidate>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-header text-teal text-uppercase pb-2 text-center" style="letter-spacing: 1.5px; font-weight: 600; background: #f8f9fa;">Data Pasien</h5>
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
                                <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" required>
                                <div class="invalid-feedback">Nomor telepon harus diisi</div>
                            </div>
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="2" required></textarea>
                                <div class="invalid-feedback">Alamat harus diisi</div>
                            </div>
                            <div class="mb-3">
                                <label for="kd_kec" class="form-label">Wilayah <span class="text-danger">*</span></label>
                                <select class="form-select" id="kd_kec" name="kd_kec" required>
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
                                <label for="pekerjaan" class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                                <select class="form-select" id="pekerjaan" name="pekerjaan" required>
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
                                <div class="invalid-feedback">Pekerjaan harus dipilih</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-header text-teal text-uppercase pb-2 text-center" style="letter-spacing: 1.5px; font-weight: 600; background: #f8f9fa;">Informasi Kunjungan</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_layanan" class="form-label">Layanan <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_layanan" name="id_layanan" required>
                                    <option value="">Pilih Layanan</option>
                                    <?php foreach ($layanan_list as $layanan): ?>
                                        <option value="<?php echo htmlspecialchars($layanan['id_layanan']); ?>" <?php echo ($id_layanan == $layanan['id_layanan']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Layanan harus dipilih</div>
                            </div>
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
                                <label for="id_dokter" class="form-label">Dokter / Bidan <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_dokter" name="id_dokter" required>
                                    <option value="">Pilih Dokter</option>
                                    <?php foreach ($dokter as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d['ID_Dokter']); ?>" <?php echo $id_dokter == $d['ID_Dokter'] ? 'selected' : ''; ?>>
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
                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-2" id="submitBtn">
                                    <span id="submitBtnText">Daftar</span>
                                    <span id="submitBtnLoading" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                                <button type="reset" class="btn btn-secondary w-100">Reset</button>
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

<script>
    // Definisikan base_url untuk digunakan di JavaScript
    const base_url = '<?= $base_url ?>';
    
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

        // Tambahkan pesan informasi di atas form
        const formContainer = document.querySelector('.card-body');
        const infoAlert = document.createElement('div');
        infoAlert.className = 'alert alert-info mb-3';
        infoAlert.innerHTML = '<strong>Petunjuk:</strong> Masukkan NIK (16 digit) terlebih dahulu untuk melanjutkan pendaftaran.';
        formContainer.insertBefore(infoAlert, formContainer.firstChild);

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
            infoAlert.className = 'alert alert-warning mb-3';
            infoAlert.innerHTML = '<strong>Sedang memproses:</strong> Mencari data pasien...';

            // Gunakan URL lengkap dengan HTTPS
            const baseUrl = window.location.protocol + '//' + window.location.host;
            let apiUrl;

            // Penanganan khusus untuk domain produksi
            if (window.location.host === 'praktekobgin.com' || window.location.host === 'www.praktekobgin.com') {
                apiUrl = `${baseUrl}/modules/pendaftaran/controllers/check_patient.php?nik=${nik}`;
            } else {
                apiUrl = `${baseUrl}/modules/pendaftaran/controllers/check_patient.php?nik=${nik}`;
            }

            console.log('Mengakses URL:', apiUrl);

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
                        formFields.pekerjaan.value = data.patient.pekerjaan;
                        formFields.keluhan.value = '';
                        formFields.yang_menyarankan.value = '';

                        // Aktifkan semua field agar bisa diedit
                        allFormFields.forEach(field => {
                            field.disabled = false;
                        });

                        // Update pesan informasi
                        infoAlert.className = 'alert alert-success mb-3';
                        infoAlert.innerHTML = '<strong>Data pasien ditemukan.</strong> Silakan lengkapi data jika ada perubahan.';
                    } else if (data.error) {
                        // Jika ada error dari backend
                        infoAlert.className = 'alert alert-danger mb-3';
                        infoAlert.innerHTML = `<strong>Error:</strong> ${data.error}`;
                        // Kosongkan field lain
                        formFields.nama_pasien.value = '';
                        formFields.tanggal_lahir.value = '';
                        formFields.gender_male.checked = false;
                        formFields.gender_female.checked = false;
                        formFields.nomor_telepon.value = '';
                        formFields.alamat.value = '';
                        formFields.kd_kec.value = '';
                    } else {
                        // Data pasien tidak ditemukan
                        infoAlert.className = 'alert alert-info mb-3';
                        infoAlert.innerHTML = '<strong>Pasien baru.</strong> Silakan lengkapi data pendaftaran.';
                        // Kosongkan field lain
                        formFields.nama_pasien.value = '';
                        formFields.tanggal_lahir.value = '';
                        formFields.gender_male.checked = false;
                        formFields.gender_female.checked = false;
                        formFields.nomor_telepon.value = '';
                        formFields.alamat.value = '';
                        formFields.kd_kec.value = '';

                        // Reset form fields
                        Object.values(formFields).forEach(field => {
                            if (field.type === 'radio') {
                                field.checked = false;
                            } else if (field !== nikInput) {
                                field.value = '';
                            }
                        });

                        // Aktifkan semua field agar bisa diisi pasien baru
                        allFormFields.forEach(field => {
                            field.disabled = false;
                        });

                        // Update pesan informasi
                        infoAlert.className = 'alert alert-primary mb-3';
                        infoAlert.innerHTML = '<strong>Pasien Baru:</strong> Silakan lengkapi semua data untuk pendaftaran pasien baru.';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Update pesan informasi jika terjadi error
                    infoAlert.className = 'alert alert-danger mb-3';
                    infoAlert.innerHTML = '<strong>Error:</strong> Terjadi kesalahan saat mencari data pasien. Silakan coba lagi.';

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
                infoAlert.className = 'alert alert-info mb-3';
                infoAlert.innerHTML = '<strong>Petunjuk:</strong> Masukkan NIK (16 digit) terlebih dahulu untuk melanjutkan pendaftaran.';
                return;
            }

            // Cek NIK jika sudah 16 digit
            typingTimer = setTimeout(() => searchPatient(this.value), 500);
        });

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
                    const response = await fetch('../controllers/check_voucher.php', {
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
        const layananSelect = document.getElementById('id_layanan');

        // Filter tempat praktek berdasarkan layanan
        layananSelect.addEventListener('change', function() {
            const idLayanan = this.value;
            // Reset value & disable berikutnya
            tempatSelect.value = '';
            dokterSelect.value = '';
            jadwalSelect.value = '';
            tempatSelect.disabled = true;
            dokterSelect.disabled = true;
            jadwalSelect.disabled = true;
            // Reset isi
            tempatSelect.innerHTML = '<option value="">Memuat tempat praktek...</option>';
            dokterSelect.innerHTML = '<option value="">Pilih tempat praktek terlebih dahulu</option>';
            jadwalSelect.innerHTML = '<option value="">Pilih tempat dan dokter terlebih dahulu</option>';
            // Trigger validasi HTML5
            tempatSelect.dispatchEvent(new Event('change'));
            dokterSelect.dispatchEvent(new Event('change'));
            jadwalSelect.dispatchEvent(new Event('change'));

            if (!idLayanan) {
                tempatSelect.innerHTML = '<option value="">Pilih layanan terlebih dahulu</option>';
                tempatSelect.disabled = true;
                return;
            }
            // Enable tempatSelect saat data sudah siap
            const timestamp = new Date().getTime();
            const url = base_url + '/modules/pendaftaran/controllers/get_tempat_by_layanan.php?layanan=' + encodeURIComponent(idLayanan) + '&_=' + timestamp;
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal memuat tempat praktek');
                    return response.json();
                })
                .then(data => {
                    tempatSelect.innerHTML = '';
                    if (data.error) {
                        tempatSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                        tempatSelect.disabled = true;
                        return;
                    }
                    if (!Array.isArray(data) || data.length === 0) {
                        tempatSelect.innerHTML = '<option value="">Tidak ada tempat praktek aktif untuk layanan ini</option>';
                        tempatSelect.disabled = true;
                        return;
                    }
                    tempatSelect.innerHTML = '<option value="">Pilih Tempat Praktek</option>';
                    data.forEach(function(tp) {
                        const option = document.createElement('option');
                        option.value = tp.ID_Tempat_Praktek;
                        option.textContent = tp.Nama_Tempat;
                        tempatSelect.appendChild(option);
                    });
                    tempatSelect.disabled = false;
                })
                .catch(error => {
                    tempatSelect.innerHTML = '<option value="">Error: ' + error.message + '</option>';
                    tempatSelect.disabled = true;
                });
        });

        // Filter dokter berdasarkan layanan & tempat praktek
        tempatSelect.addEventListener('change', function() {
            const idLayanan = layananSelect.value;
            const idTempat = this.value;
            dokterSelect.value = '';
            jadwalSelect.value = '';
            dokterSelect.disabled = true;
            jadwalSelect.disabled = true;
            dokterSelect.innerHTML = '<option value="">Memuat dokter...</option>';
            jadwalSelect.innerHTML = '<option value="">Pilih tempat dan dokter terlebih dahulu</option>';
            dokterSelect.dispatchEvent(new Event('change'));
            jadwalSelect.dispatchEvent(new Event('change'));

            if (!idLayanan || !idTempat) {
                dokterSelect.innerHTML = '<option value="">Pilih tempat praktek terlebih dahulu</option>';
                dokterSelect.disabled = true;
                return;
            }
            const timestamp = new Date().getTime();
            const url = base_url + '/modules/pendaftaran/controllers/get_dokter_by_layanan_tempat.php?layanan=' + encodeURIComponent(idLayanan) + '&tempat=' + encodeURIComponent(idTempat) + '&_=' + timestamp;
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal memuat dokter');
                    return response.json();
                })
                .then(data => {
                    dokterSelect.innerHTML = '';
                    if (data.error) {
                        dokterSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                        dokterSelect.disabled = true;
                        return;
                    }
                    if (!Array.isArray(data) || data.length === 0) {
                        dokterSelect.innerHTML = '<option value="">Tidak ada dokter/bidan aktif untuk tempat & layanan ini</option>';
                        dokterSelect.disabled = true;
                        return;
                    }
                    dokterSelect.innerHTML = '<option value="">Pilih Dokter / Bidan</option>';
                    data.forEach(function(dok) {
                        const option = document.createElement('option');
                        option.value = dok.ID_Dokter;
                        option.textContent = dok.Nama_Dokter + (dok.Spesialisasi ? ' (' + dok.Spesialisasi + ')' : '');
                        dokterSelect.appendChild(option);
                    });
                    dokterSelect.disabled = false;
                })
                .catch(error => {
                    dokterSelect.innerHTML = '<option value="">Error: ' + error.message + '</option>';
                    dokterSelect.disabled = true;
                });
        });

        // Filter jadwal berdasarkan layanan, tempat, dokter
        dokterSelect.addEventListener('change', function() {
            const idLayanan = layananSelect.value;
            const idTempat = tempatSelect.value;
            const idDokter = this.value;
            jadwalSelect.value = '';
            jadwalSelect.disabled = true;
            jadwalSelect.innerHTML = '<option value="">Memuat jadwal...</option>';
            jadwalSelect.dispatchEvent(new Event('change'));

            if (!idLayanan || !idTempat || !idDokter) {
                jadwalSelect.innerHTML = '<option value="">Pilih tempat dan dokter terlebih dahulu</option>';
                jadwalSelect.disabled = true;
                return;
            }
            const timestamp = new Date().getTime();
            const url = base_url + '/modules/pendaftaran/controllers/get_jadwal_by_layanan_tempat_dokter.php?layanan=' + encodeURIComponent(idLayanan) + '&tempat=' + encodeURIComponent(idTempat) + '&dokter=' + encodeURIComponent(idDokter) + '&_=' + timestamp;
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal memuat jadwal');
                    return response.json();
                })
                .then(data => {
                    jadwalSelect.innerHTML = '';
                    if (data.error) {
                        jadwalSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                        jadwalSelect.disabled = true;
                        return;
                    }
                    if (!Array.isArray(data) || data.length === 0) {
                        jadwalSelect.innerHTML = '<option value="">Tidak ada jadwal aktif untuk kombinasi ini</option>';
                        jadwalSelect.disabled = true;
                        return;
                    }
                    jadwalSelect.innerHTML = '<option value="">Pilih Jadwal</option>';
                    data.forEach(function(jd) {
                        const option = document.createElement('option');
                        option.value = jd.ID_Jadwal_Rutin;
                        option.textContent = jd.Hari + ' (' + jd.Jam_Mulai + ' - ' + jd.Jam_Selesai + ')';
                        jadwalSelect.appendChild(option);
                    });
                    jadwalSelect.disabled = false;
                })
                .catch(error => {
                    jadwalSelect.innerHTML = '<option value="">Error: ' + error.message + '</option>';
                    jadwalSelect.disabled = true;
                });
        });

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
                checkUrl = `${baseUrl}/modules/pendaftaran/controllers/check_voucher.php`;
            } else {
                checkUrl = `${baseUrl}/modules/pendaftaran/controllers/check_voucher.php`;
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

<!-- Floating Shortcut Button -->
<a href="<?= $base_url ?>/modules/pendaftaran/controllers/antrian.php" class="floating-antrian-btn" title="Lihat Antrian">
    <i class="bi bi-list-check"></i>
</a>
<style>
    /* Floating Button */
    .floating-antrian-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: #007bff;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .floating-antrian-btn:hover {
        background-color: #0069d9;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        color: white;
    }

    .floating-antrian-btn i {
        pointer-events: none;
    }

    @media (max-width: 600px) {
        .floating-antrian-btn {
            right: 16px;
            bottom: 16px;
            width: 48px;
            height: 48px;
            font-size: 1.5rem;
        }
    }
</style>

<!-- Modern Minimalist & Colorful Styles -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', 'Plus Jakarta Sans', Arial, sans-serif;
        background: #f8f9fa;
        color: #495057;
    }

    .card {
        border-radius: 1.2rem;
        border: none;
        box-shadow: 0 4px 32px 0 rgba(0, 171, 197, 0.07), 0 1.5px 8px 0 rgba(0, 0, 0, 0.03);
        background: #fff;
    }

    .card-header {
        border-radius: 1.2rem 1.2rem 0 0;
        background: linear-gradient(90deg, #009688 0%, #64b5f6 100%);
        color: #fff;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 171, 197, 0.10);
    }

    .card-header h6,
    .card-header h5,
    .card-header .widget-title {
        color: #fff !important;
        text-shadow: 0 1px 6px rgba(0, 0, 0, 0.07);
    }

    .card-body {
        padding: 2rem 1.5rem 1.5rem 1.5rem;
    }

    h5,
    h6 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700;
        color: #009688;
    }

    .border-bottom {
        border-color: #b2dfdb !important;
    }

    /* Form Labels */
    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border-radius: 0.7rem;
        border: 1.5px solid #b2dfdb;
        background: #fafdff;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-shadow: none;
    }

    /* Form Controls */
    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-check-input:checked {
        background-color: #009688;
        border-color: #009688;
    }

    .form-check-label {
        color: #009688;
        font-weight: 500;
    }

    /* Button Styling */
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }

    .btn-secondary {
        border-radius: 0.8rem;
        background: #e0f7fa;
        color: #00897b;
        border: none;
        font-weight: 600;
        transition: background 0.2s;
    }

    .btn-secondary:hover,
    .btn-secondary:focus {
        background: #b2ebf2;
        color: #00695c;
    }

    .alert {
        border-radius: 0.8rem;
        border: none;
        font-size: 1rem;
        background: linear-gradient(90deg, #fffde7 0%, #b2dfdb 100%);
        color: #333;
        box-shadow: 0 2px 8px rgba(0, 171, 197, 0.06);
    }

    .alert-danger {
        background: linear-gradient(90deg, #ffebee 0%, #b2dfdb 100%);
        color: #c62828;
    }

    .alert-success {
        background: linear-gradient(90deg, #e8f5e9 0%, #b2dfdb 100%);
        color: #388e3c;
    }

    .invalid-feedback {
        color: #c62828;
        font-size: 0.95rem;
    }

    .form-text.text-muted,
    .form-text {
        color: #607d8b !important;
    }

    .input-group .btn {
        border-radius: 0.7rem;
    }

    /* Section divider */
    .row.mb-4>.col-md-12>h5 {
        background: linear-gradient(90deg, #e0f7fa 0%, #fffde7 100%);
        border-radius: 0.7rem;
        padding: 0.4rem 1rem;
        color: #00897b;
        font-size: 1.07rem;
        margin-bottom: 1.2rem;
        font-weight: 700;
    }

    /* Loading overlay */
    #loadingOverlay {
        background: rgba(0, 171, 197, 0.12);
        z-index: 10000;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .card-body {
            padding: 1rem 0.5rem 1rem 0.5rem;
        }

        .row.mb-4>.col-md-12>h5 {
            font-size: 1rem;
            padding: 0.3rem 0.7rem;
        }

        .btn-primary,
        .btn-secondary {
            font-size: 1rem;
            padding: 0.6rem 1.2rem;
        }
    }
</style>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    /* Card Styling */
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem 1.25rem;
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
    /* Warna khusus untuk widget pengumuman */
    .card-header:has(i.bi-megaphone) {
        background-color: #800000 !important; /* Warna merah maroon */
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
include_once dirname(__DIR__, 3) . '/template/layout.php';
?>