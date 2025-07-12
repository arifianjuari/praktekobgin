<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger">Akses ditolak. Anda tidak memiliki izin.</div>';
    exit;
}

// Cek ID pendaftaran
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">ID Pendaftaran tidak valid.</div>';
    exit;
}

$id_pendaftaran = $_GET['id'];

try {
    // Query untuk mengambil data pendaftaran
    $query = "
        SELECT 
            p.ID_Pendaftaran,
            p.nm_pasien,
            p.ID_Jadwal,
            p.ID_Dokter,
            p.ID_Tempat_Praktek,
            p.Status_Pendaftaran,
            p.Waktu_Pendaftaran,
            p.Waktu_Perkiraan,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            jr.Jenis_Layanan
        FROM 
            pendaftaran p
        JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        WHERE 
            p.ID_Pendaftaran = :id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_pendaftaran);
    $stmt->execute();

    $pendaftaran = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pendaftaran) {
        echo '<div class="alert alert-warning">Data pendaftaran tidak ditemukan.</div>';
        exit;
    }

    // Query untuk mengambil data dokter
    $query_dokter = "SELECT ID_Dokter, Nama_Dokter FROM dokter ORDER BY Nama_Dokter";
    $stmt_dokter = $conn->query($query_dokter);
    $dokter_list = $stmt_dokter->fetchAll(PDO::FETCH_ASSOC);

    // Query untuk mengambil data jadwal
    $query_jadwal = "SELECT ID_Jadwal_Rutin, Hari, Jam_Mulai, Jam_Selesai, Jenis_Layanan FROM jadwal_rutin ORDER BY 
        CASE Hari 
            WHEN 'Senin' THEN 1 
            WHEN 'Selasa' THEN 2 
            WHEN 'Rabu' THEN 3 
            WHEN 'Kamis' THEN 4 
            WHEN 'Jumat' THEN 5 
            WHEN 'Sabtu' THEN 6 
            WHEN 'Minggu' THEN 7 
        END, Jam_Mulai";
    $stmt_jadwal = $conn->query($query_jadwal);
    $jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

    // Query untuk mengambil data tempat praktek
    $query_tempat = "SELECT ID_Tempat_Praktek, Nama_Tempat FROM tempat_praktek ORDER BY Nama_Tempat";
    $stmt_tempat = $conn->query($query_tempat);
    $tempat_list = $stmt_tempat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Terjadi kesalahan: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<form id="formEditPendaftaran">
    <input type="hidden" name="id_pendaftaran" value="<?= htmlspecialchars($pendaftaran['ID_Pendaftaran']) ?>">

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">ID Pendaftaran</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($pendaftaran['ID_Pendaftaran']) ?>" readonly>
                <small class="text-muted">ID Pendaftaran tidak dapat diubah</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Pasien</label>
                <input type="text" class="form-control" name="nm_pasien" value="<?= htmlspecialchars($pendaftaran['nm_pasien']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Status Pendaftaran</label>
                <select name="status" class="form-select" required>
                    <option value="Menunggu Konfirmasi" <?= $pendaftaran['Status_Pendaftaran'] === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                    <option value="Dikonfirmasi" <?= $pendaftaran['Status_Pendaftaran'] === 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                    <option value="Dibatalkan" <?= $pendaftaran['Status_Pendaftaran'] === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    <option value="Selesai" <?= $pendaftaran['Status_Pendaftaran'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Dokter</label>
                <select name="id_dokter" class="form-select" required>
                    <?php foreach ($dokter_list as $dokter): ?>
                        <option value="<?= $dokter['ID_Dokter'] ?>" <?= $pendaftaran['ID_Dokter'] == $dokter['ID_Dokter'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dokter['Nama_Dokter']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Jadwal Praktek</label>
                <select name="id_jadwal" class="form-select" required>
                    <?php foreach ($jadwal_list as $jadwal): ?>
                        <option value="<?= $jadwal['ID_Jadwal_Rutin'] ?>" <?= $pendaftaran['ID_Jadwal'] == $jadwal['ID_Jadwal_Rutin'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jadwal['Hari']) ?>,
                            <?= htmlspecialchars($jadwal['Jam_Mulai']) ?> -
                            <?= htmlspecialchars($jadwal['Jam_Selesai']) ?>
                            (<?= htmlspecialchars($jadwal['Jenis_Layanan']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tempat Praktek</label>
                <select name="id_tempat" class="form-select" required>
                    <?php foreach ($tempat_list as $tempat): ?>
                        <option value="<?= $tempat['ID_Tempat_Praktek'] ?>" <?= $pendaftaran['ID_Tempat_Praktek'] == $tempat['ID_Tempat_Praktek'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tempat['Nama_Tempat']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Waktu Pendaftaran</label>
                <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($pendaftaran['Waktu_Pendaftaran'])) ?>" readonly>
                <small class="text-muted">Waktu pendaftaran tidak dapat diubah</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Waktu Perkiraan</label>
                <input type="time" class="form-control" name="waktu_perkiraan" value="<?= !empty($pendaftaran['Waktu_Perkiraan']) ? date('H:i', strtotime($pendaftaran['Waktu_Perkiraan'])) : '' ?>">
                <small class="text-muted">Format: HH:MM (24 jam)</small>
            </div>
        </div>
    </div>
</form>

<script>
    // Inisialisasi select2 jika tersedia
    if (typeof $.fn.select2 !== 'undefined') {
        $('select[name="id_dokter"]').select2({
            dropdownParent: $('#editModal')
        });
        $('select[name="id_jadwal"]').select2({
            dropdownParent: $('#editModal')
        });
        $('select[name="id_tempat"]').select2({
            dropdownParent: $('#editModal')
        });
    }
</script>