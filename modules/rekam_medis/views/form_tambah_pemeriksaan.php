<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate no_reg dengan format YYYYMMDD-HHMMSS
$no_reg = date('Ymd-His');
error_log("Form tambah pemeriksaan - no_reg yang digenerate: " . $no_reg);
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Tambah Kunjungan Baru</h6>
            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Peringatan:</strong> <?= $_SESSION['warning'] ?>
                    <?php unset($_SESSION['warning']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Sukses:</strong> <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']) ?>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="150">No. Rekam Medis</th>
                            <td><?= $pasien['no_rkm_medis'] ?></td>
                        </tr>
                        <tr>
                            <th>Nama Pasien</th>
                            <td><?= $pasien['nm_pasien'] ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Registrasi</th>
                            <td><?= date('d-m-Y') ?></td>
                        </tr>
                        <tr>
                            <th>Jam Registrasi</th>
                            <td><?= date('H:i:s') ?></td>
                        </tr>
                        <tr>
                            <th>No. Rawat</th>
                            <td><?= $no_rawat ?></td>
                        </tr>
                        <tr>
                            <th>No. Registrasi</th>
                            <td><?= $no_reg ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <form action="index.php?module=rekam_medis&action=simpan_pemeriksaan" method="post" id="formTambahKunjungan">
                <input type="hidden" name="no_rkm_medis" value="<?= $pasien['no_rkm_medis'] ?>">
                <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
                <input type="hidden" name="tgl_registrasi" value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="jam_reg" value="<?= date('H:i:s') ?>">
                <input type="hidden" name="no_reg" value="<?= $no_reg ?>">

                <!-- Status Bayar -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status Bayar</label>
                            <select name="status_bayar" class="form-control" required>
                                <option value="Sudah Bayar">Sudah Bayar</option>
                                <option value="Belum Bayar">Belum Bayar</option>
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
                                    <textarea name="rincian" id="rincian" class="form-control" rows="4" placeholder="Masukkan rincian kunjungan"></textarea>
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


                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan Kunjungan
                    </button>
                    <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>" class="btn btn-secondary">
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
                            global $conn;

                            if (!$conn) {
                                die("Koneksi database tidak tersedia.");
                            }

                            $sql = "SELECT * FROM menu_layanan WHERE status_aktif = 1 ORDER BY nama_layanan ASC";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr class='layanan-row' data-kategori='" . htmlspecialchars($row['kategori']) . "'>";
                                    echo "<td><input type='checkbox' class='form-check-input layanan-checkbox' 
                                              data-nama='" . htmlspecialchars($row['nama_layanan']) . "'
                                              data-tarif='" . htmlspecialchars(number_format($row['harga'], 0, ',', '.')) . "'
                                              data-keterangan='" . htmlspecialchars($row['keterangan'] ?? '') . "'></td>";
                                    echo "<td>" . htmlspecialchars($row['nama_layanan']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                    echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['keterangan'] ?? '') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Tidak ada data layanan</td></tr>";
                            }

                            
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Keranjang Layanan -->
<div class="mt-4">
    <h6 class="font-weight-bold">Keranjang Layanan (Nota)</h6>
    <table class="table table-bordered" id="keranjangLayanan">
        <thead>
            <tr>
                <th>Nama Layanan</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <!-- Baris layanan terpilih akan muncul di sini -->
        </tbody>
    </table>
    <div class="d-flex justify-content-end">
        <h6 class="mt-2">Total Biaya: <span id="totalBiaya">Rp 0</span></h6>
    </div>
</div>

<script>
    document.getElementById('formTambahKunjungan').addEventListener('submit', function(e) {
        document.getElementById('btnSimpan').disabled = true;
        document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    });

    // Fungsi untuk checkbox "Pilih Semua" layanan
    document.getElementById('checkAllLayanan').addEventListener('change', function() {
        var checkboxes = document.getElementsByClassName('layanan-checkbox');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
            checkbox.dispatchEvent(new Event('change'));
        }
    });

    // Fungsi untuk format angka ke rupiah
    function formatRupiah(angka) {
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Tambahkan/kurangi layanan ke keranjang otomatis
    var checkboxes = document.getElementsByClassName('layanan-checkbox');
    for (var checkbox of checkboxes) {
        checkbox.addEventListener('change', function() {
            updateKeranjang();
        });
    }

    function updateKeranjang() {
        var tbody = document.querySelector('#keranjangLayanan tbody');
        tbody.innerHTML = '';
        var total = 0;
        var checkboxes = document.getElementsByClassName('layanan-checkbox');
        var rincianArr = [];
        var no = 1;
        for (var checkbox of checkboxes) {
            if (checkbox.checked) {
                var nama = checkbox.getAttribute('data-nama');
                var kategori = checkbox.closest('tr').querySelectorAll('td')[2].textContent;
                var hargaStr = checkbox.getAttribute('data-tarif').replace(/[^\d]/g, '');
                var harga = parseInt(hargaStr) || 0;
                var keterangan = checkbox.getAttribute('data-keterangan');
                var row = document.createElement('tr');
                row.innerHTML = `<td>${nama}</td><td>${kategori}</td><td>${formatRupiah(harga)}</td><td>${keterangan}</td><td><button type="button" class="btn btn-danger btn-sm btn-hapus-layanan">Hapus</button></td>`;
                tbody.appendChild(row);
                total += harga;
                rincianArr.push(no + '. ' + nama + ' (' + kategori + ') - ' + formatRupiah(harga) + (keterangan && keterangan.trim() !== '' ? '\n   Keterangan: ' + keterangan : ''));
                no++;
            }
        }
        document.getElementById('totalBiaya').textContent = formatRupiah(total);
        // Isi textarea rincian otomatis
        var rincianField = document.getElementById('rincian');
        if (rincianArr.length > 0) {
            rincianField.value = rincianArr.join('\n') + '\n---------------------\nTOTAL: ' + formatRupiah(total);
        } else {
            rincianField.value = '';
        }
        // Event hapus layanan dari keranjang
        var hapusBtns = document.getElementsByClassName('btn-hapus-layanan');
        for (var btn of hapusBtns) {
            btn.onclick = function() {
                var row = this.closest('tr');
                var nama = row.children[0].textContent;
                // Uncheck checkbox yang sesuai
                for (var checkbox of checkboxes) {
                    if (checkbox.getAttribute('data-nama') === nama) {
                        checkbox.checked = false;
                        break;
                    }
                }
                updateKeranjang();
            }
        }
    }

    // Jalankan updateKeranjang pertama kali jika ada layanan terpilih
    updateKeranjang();

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