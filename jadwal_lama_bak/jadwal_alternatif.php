<?php
// File: jadwal_alternatif.php
// Deskripsi: Alternatif untuk menampilkan jadwal praktek dokter

// Pastikan parameter ada
$id_tempat = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';

// Header untuk mencegah caching
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Koneksi database
require_once '../config/database.php';

// Fungsi untuk mendapatkan jadwal
function getJadwal($conn, $id_tempat, $id_dokter)
{
    // Validasi parameter
    if (empty($id_tempat) || empty($id_dokter)) {
        return '<option value="">Pilih tempat praktek dan dokter terlebih dahulu</option>';
    }

    try {
        // Query untuk mendapatkan jadwal
        $query = "
            SELECT 
                jr.ID_Jadwal_Rutin,
                jr.Hari,
                jr.Jam_Mulai,
                jr.Jam_Selesai,
                jr.Jenis_Layanan
            FROM 
                jadwal_rutin jr
            WHERE 
                jr.Status_Aktif = 1
                AND jr.ID_Tempat_Praktek = ?
                AND jr.ID_Dokter = ?
            ORDER BY 
                CASE jr.Hari
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                    WHEN 'Minggu' THEN 7
                END ASC,
                jr.Jam_Mulai ASC
        ";

        // Prepare dan execute
        $stmt = $conn->prepare($query);
        $stmt->execute(array($id_tempat, $id_dokter));

        // Ambil hasil
        $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Jika tidak ada jadwal
        if (count($jadwal) === 0) {
            return '<option value="">Tidak ada jadwal tersedia</option>';
        }

        // Buat opsi untuk setiap jadwal
        $options = '<option value="">Pilih Jadwal</option>';
        foreach ($jadwal as $j) {
            // Format jam
            $jam_mulai = substr($j['Jam_Mulai'], 0, 5);  // Ambil hanya HH:MM
            $jam_selesai = substr($j['Jam_Selesai'], 0, 5);  // Ambil hanya HH:MM

            // Format teks jadwal
            $jadwalText = $j['Hari'] . ' - ' . $jam_mulai . '-' . $jam_selesai . ' (' . $j['Jenis_Layanan'] . ')';

            // Tambahkan opsi
            $options .= '<option value="' . htmlspecialchars($j['ID_Jadwal_Rutin']) . '">' . htmlspecialchars($jadwalText) . '</option>';
        }

        return $options;
    } catch (Exception $e) {
        error_log('Error in jadwal_alternatif.php: ' . $e->getMessage());
        return '<option value="">Error: Gagal memuat jadwal</option>';
    }
}

// Jika ini adalah request AJAX
if (!empty($id_tempat) && !empty($id_dokter)) {
    echo getJadwal($conn, $id_tempat, $id_dokter);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Praktek Dokter</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .card {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="mb-4">Jadwal Praktek Dokter</h2>

        <div class="card">
            <div class="card-header">
                <h5>Informasi Kunjungan</h5>
            </div>
            <div class="card-body">
                <form id="jadwalForm" method="get">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tempat">Tempat Praktek <span class="text-danger">*</span></label>
                                <select id="tempat" name="tempat" class="form-select" required>
                                    <option value="">Pilih Tempat Praktek</option>
                                    <?php
                                    // Ambil data tempat praktek
                                    try {
                                        $query = "SELECT * FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat ASC";
                                        $stmt = $conn->prepare($query);
                                        $stmt->execute();
                                        $tempat_praktek = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($tempat_praktek as $tp) {
                                            $selected = ($id_tempat == $tp['ID_Tempat_Praktek']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($tp['ID_Tempat_Praktek']) . '" ' . $selected . '>' . htmlspecialchars($tp['Nama_Tempat']) . '</option>';
                                        }
                                    } catch (PDOException $e) {
                                        error_log("Database Error: " . $e->getMessage());
                                        echo '<option value="">Error memuat data</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dokter">Dokter <span class="text-danger">*</span></label>
                                <select id="dokter" name="dokter" class="form-select" required>
                                    <option value="">Pilih Dokter</option>
                                    <?php
                                    // Ambil data dokter
                                    try {
                                        $query = "SELECT * FROM dokter WHERE Status_Aktif = 1 ORDER BY Nama_Dokter ASC";
                                        $stmt = $conn->prepare($query);
                                        $stmt->execute();
                                        $dokter_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($dokter_list as $d) {
                                            $selected = ($id_dokter == $d['ID_Dokter']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($d['ID_Dokter']) . '" ' . $selected . '>' . htmlspecialchars($d['Nama_Dokter']) . '</option>';
                                        }
                                    } catch (PDOException $e) {
                                        error_log("Database Error: " . $e->getMessage());
                                        echo '<option value="">Error memuat data</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="jadwal">Jadwal <span class="text-danger">*</span></label>
                        <select id="jadwal" name="jadwal" class="form-select" required>
                            <?php
                            // Tampilkan jadwal jika tempat dan dokter sudah dipilih
                            if (!empty($id_tempat) && !empty($id_dokter)) {
                                echo getJadwal($conn, $id_tempat, $id_dokter);
                            } else {
                                echo '<option value="">Pilih tempat praktek dan dokter terlebih dahulu</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="keluhan">Keluhan</label>
                        <textarea id="keluhan" name="keluhan" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tempatSelect = document.getElementById('tempat');
            var dokterSelect = document.getElementById('dokter');
            var jadwalSelect = document.getElementById('jadwal');

            // Fungsi untuk memuat jadwal
            function loadJadwal() {
                var tempat = tempatSelect.value;
                var dokter = dokterSelect.value;

                if (tempat && dokter) {
                    jadwalSelect.innerHTML = '<option value="">Memuat jadwal...</option>';

                    // Buat request
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'jadwal_alternatif.php?tempat=' + encodeURIComponent(tempat) + '&dokter=' + encodeURIComponent(dokter), true);

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                jadwalSelect.innerHTML = xhr.responseText;
                            } else {
                                jadwalSelect.innerHTML = '<option value="">Error: Gagal memuat jadwal</option>';
                            }
                        }
                    };

                    xhr.onerror = function() {
                        jadwalSelect.innerHTML = '<option value="">Error: Koneksi gagal</option>';
                    };

                    xhr.send();
                } else {
                    jadwalSelect.innerHTML = '<option value="">Pilih tempat praktek dan dokter terlebih dahulu</option>';
                }
            }

            // Event listener untuk perubahan tempat dan dokter
            tempatSelect.addEventListener('change', loadJadwal);
            dokterSelect.addEventListener('change', loadJadwal);
        });
    </script>
</body>

</html>