<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah ada data kunjungan
if (!isset($kunjungan) || !$kunjungan) {
    $_SESSION['error'] = 'Data kunjungan tidak ditemukan';
    header('Location: index.php?module=rekam_medis');
    exit;
}
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Edit Data Kunjungan</h6>
            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $kunjungan['no_rkm_medis'] ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php unset($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php unset($_SESSION['success']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <?= $_SESSION['warning'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php unset($_SESSION['warning']) ?>
                </div>
            <?php endif; ?>

            <!-- Informasi Pasien -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="150">No. Rawat</th>
                            <td><?= $kunjungan['no_rawat'] ?></td>
                        </tr>
                        <tr>
                            <th>No. Rekam Medis</th>
                            <td><?= $kunjungan['no_rkm_medis'] ?></td>
                        </tr>
                        <tr>
                            <th>Nama Pasien</th>
                            <td><?= $kunjungan['nm_pasien'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <form action="index.php?module=rekam_medis&action=update_kunjungan" method="post">
                <input type="hidden" name="no_rawat" value="<?= $kunjungan['no_rawat'] ?>">
                <input type="hidden" name="no_rkm_medis" value="<?= $kunjungan['no_rkm_medis'] ?>">

                <!-- Tanggal dan Waktu -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Registrasi</label>
                            <input type="date" name="tgl_registrasi" class="form-control" value="<?= $kunjungan['tgl_registrasi'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jam Registrasi</label>
                            <input type="time" name="jam_reg" class="form-control" value="<?= $kunjungan['jam_reg'] ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Status Bayar -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status Bayar</label>
                            <select name="status_bayar" class="form-control" required>
                                <option value="Sudah Bayar" <?= $kunjungan['status_bayar'] == 'Sudah Bayar' ? 'selected' : '' ?>>Sudah Bayar</option>
                                <option value="Belum Bayar" <?= $kunjungan['status_bayar'] == 'Belum Bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Rincian -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Rincian</label>
                            <div class="row">
                                <div class="col-md-9">
                                    <textarea name="rincian" id="rincian" class="form-control" rows="4" placeholder="Masukkan rincian kunjungan"><?= htmlspecialchars($kunjungan['rincian'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border">
                                        <div class="card-header py-1 bg-light">
                                            <h6 class="mb-0 small">Template Layanan</h6>
                                        </div>
                                        <div class="card-body p-2">
                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarLayanan">
                                                <i class="fas fa-list"></i> Pilih Layanan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $kunjungan['no_rkm_medis'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Daftar Layanan -->
<div class="modal fade" id="modalDaftarLayanan" tabindex="-1" aria-labelledby="modalDaftarLayananLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarLayananLabel">Daftar Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_layanan" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="Pemeriksaan">Pemeriksaan</option>
                            <option value="Tindakan">Tindakan</option>
                            <option value="USG">USG</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="search_layanan" class="form-control" placeholder="Cari layanan...">
                    </div>
                </div>

                <!-- Tabel Layanan -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelLayanan">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">
                                    <input type="checkbox" id="checkAllLayanan" class="form-check-input">
                                </th>
                                <th width="30%">Nama Layanan</th>
                                <th width="15%">Kategori</th>
                                <th width="15%">Tarif</th>
                                <th width="35%">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $db_config_path = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
                            if (!file_exists($db_config_path)) {
                                echo "<pre>Debug: $db_config_path</pre>";
                                die("Error: Config database.php tidak ditemukan di: $db_config_path");
                            }
                            require_once $db_config_path;
                            // Fallback jika env kosong
                            // if (empty($db2_host)) $db2_host = '127.0.0.1';
                            if (empty($db2_host)) $db2_host = 'localhost';
                            if (empty($db2_username)) $db2_username = 'root';
                            if (empty($db2_password)) $db2_password = 'root';
                            if (empty($db2_database)) $db2_database = 'praktekobgin_db';
                            if (empty($db2_port)) $db2_port = '8889';
                            // $conn = new mysqli($db2_host, $db2_username, $db2_password, $db2_database, $db2_port);
                            $conn = new mysqli($db2_host, $db2_username, $db2_password, $db2_database, $db2_port);

                            if ($conn->connect_error) {
                                die("Koneksi gagal: " . $conn->connect_error);
                            }

                            $sql = "SELECT * FROM menu_layanan WHERE status_aktif = 1 ORDER BY nama_layanan ASC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr class='layanan-row' data-kategori='" . htmlspecialchars($row['kategori']) . "'>";
                                    echo "<td><input type='checkbox' class='form-check-input layanan-checkbox' 
                                              data-nama='" . htmlspecialchars($row['nama_layanan']) . "'
                                              data-tarif='" . htmlspecialchars(number_format($row['harga'], 0, ',', '.')) . "'
                                              data-keterangan='" . htmlspecialchars($row['keterangan']) . "'></td>";
                                    echo "<td>" . htmlspecialchars($row['nama_layanan']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                    echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Tidak ada data layanan</td></tr>";
                            }

                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="tambahkanLayananTerpilih()">Tambahkan Layanan Terpilih</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk checkbox "Pilih Semua" layanan
    document.getElementById('checkAllLayanan').addEventListener('change', function() {
        var checkboxes = document.getElementsByClassName('layanan-checkbox');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    // Fungsi untuk menambahkan layanan yang dipilih ke textarea rincian
    function tambahkanLayananTerpilih() {
        var checkboxes = document.getElementsByClassName('layanan-checkbox');
        var rincianField = document.getElementById('rincian');
        var layananTerpilih = [];

        for (var checkbox of checkboxes) {
            if (checkbox.checked) {
                var namaLayanan = checkbox.getAttribute('data-nama');
                var tarif = checkbox.getAttribute('data-tarif');
                var keterangan = checkbox.getAttribute('data-keterangan');

                var textLayanan = namaLayanan + ' - Rp ' + tarif;
                if (keterangan && keterangan.trim() !== '') {
                    textLayanan += '\nKeterangan: ' + keterangan;
                }
                layananTerpilih.push(textLayanan);
            }
        }

        if (layananTerpilih.length > 0) {
            var currentValue = rincianField.value;
            var newValue = layananTerpilih.join('\n\n');

            if (currentValue && currentValue.trim() !== '') {
                rincianField.value = currentValue + '\n\n' + newValue;
            } else {
                rincianField.value = newValue;
            }
        }

        $('#modalDaftarLayanan').modal('hide');
    }

    // Filter untuk layanan
    document.addEventListener('DOMContentLoaded', function() {
        function filterLayanan() {
            var kategori = document.getElementById('filter_kategori_layanan').value;
            var searchTerm = document.getElementById('search_layanan').value.toLowerCase();
            var rows = document.querySelectorAll('#tabelLayanan tbody tr.layanan-row');

            rows.forEach(function(row) {
                var rowKategori = row.getAttribute('data-kategori');
                var namaLayanan = row.cells[1].textContent.toLowerCase();
                var keterangan = row.cells[4].textContent.toLowerCase();

                var showByKategori = kategori === '' || rowKategori === kategori;
                var showBySearch = searchTerm === '' ||
                    namaLayanan.includes(searchTerm) ||
                    keterangan.includes(searchTerm);

                if (showByKategori && showBySearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Uncheck "Pilih Semua" checkbox saat filter berubah
            document.getElementById('checkAllLayanan').checked = false;
        }

        // Event listener untuk filter kategori dan pencarian
        document.getElementById('filter_kategori_layanan').addEventListener('change', filterLayanan);
        document.getElementById('search_layanan').addEventListener('input', filterLayanan);
    });
</script>