<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Data Pasien</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-default btn-sm" onclick="history.back()">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?module=rekam_medis&action=updatePasien&t=<?= time() ?>" method="post">
                        <input type="hidden" name="no_rkm_medis" value="<?= $pasien['no_rkm_medis'] ?>">

                        <div class="small text-muted mb-3">
                            Form diakses pada: <?= date('Y-m-d H:i:s') ?>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">No. Rekam Medis</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" value="<?= $pasien['no_rkm_medis'] ?>" readonly>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">NIK</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="no_ktp" value="<?= $pasien['no_ktp'] ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Nama Pasien <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="nm_pasien" value="<?= $pasien['nm_pasien'] ?>" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <select class="form-control" name="jk" required>
                                    <option value="P" <?= !isset($pasien['jk']) || $pasien['jk'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    <option value="L" <?= isset($pasien['jk']) && $pasien['jk'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Status Menikah</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="stts_nikah">
                                    <option value="">-- Pilih Status Menikah --</option>
                                    <option value="BELUM MENIKAH" <?= isset($pasien['stts_nikah']) && $pasien['stts_nikah'] == 'BELUM MENIKAH' ? 'selected' : '' ?>>Belum Menikah</option>
                                    <option value="MENIKAH" <?= isset($pasien['stts_nikah']) && $pasien['stts_nikah'] == 'MENIKAH' ? 'selected' : '' ?>>Menikah</option>
                                    <option value="JANDA" <?= isset($pasien['stts_nikah']) && $pasien['stts_nikah'] == 'JANDA' ? 'selected' : '' ?>>Janda</option>
                                    <option value="DUDA" <?= isset($pasien['stts_nikah']) && $pasien['stts_nikah'] == 'DUDA' ? 'selected' : '' ?>>Duda</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">No. Telepon</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="no_tlp" value="<?= $pasien['no_tlp'] ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" value="<?= $pasien['tgl_lahir'] ?>" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Alamat</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="alamat" rows="3"><?= $pasien['alamat'] ?></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Kecamatan</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="kd_kec">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <?php foreach ($kecamatan as $kec): ?>
                                        <option value="<?= $kec['kd_kec'] ?>" <?= $pasien['kd_kec'] == $kec['kd_kec'] ? 'selected' : '' ?>><?= $kec['nm_kec'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Pekerjaan</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="pekerjaan">
                                    <option value="">-- Pilih Pekerjaan --</option>
                                    <option value="Tidak Bekerja" <?= $pasien['pekerjaan'] == 'Tidak Bekerja' ? 'selected' : '' ?>>Tidak Bekerja</option>
                                    <option value="Ibu Rumah Tangga" <?= $pasien['pekerjaan'] == 'Ibu Rumah Tangga' ? 'selected' : '' ?>>Ibu Rumah Tangga</option>
                                    <option value="Guru/Dosen" <?= $pasien['pekerjaan'] == 'Guru/Dosen' ? 'selected' : '' ?>>Guru/Dosen</option>
                                    <option value="PNS" <?= $pasien['pekerjaan'] == 'PNS' ? 'selected' : '' ?>>PNS</option>
                                    <option value="TNI/Polri" <?= $pasien['pekerjaan'] == 'TNI/Polri' ? 'selected' : '' ?>>TNI/Polri</option>
                                    <option value="Pegawai Swasta" <?= $pasien['pekerjaan'] == 'Pegawai Swasta' ? 'selected' : '' ?>>Pegawai Swasta</option>
                                    <option value="Wiraswasta/Pengusaha" <?= $pasien['pekerjaan'] == 'Wiraswasta/Pengusaha' ? 'selected' : '' ?>>Wiraswasta/Pengusaha</option>
                                    <option value="Tenaga Kesehatan" <?= $pasien['pekerjaan'] == 'Tenaga Kesehatan' ? 'selected' : '' ?>>Tenaga Kesehatan</option>
                                    <option value="Petani/Nelayan" <?= $pasien['pekerjaan'] == 'Petani/Nelayan' ? 'selected' : '' ?>>Petani/Nelayan</option>
                                    <option value="Buruh" <?= $pasien['pekerjaan'] == 'Buruh' ? 'selected' : '' ?>>Buruh</option>
                                    <option value="Pelajar/Mahasiswa" <?= $pasien['pekerjaan'] == 'Pelajar/Mahasiswa' ? 'selected' : '' ?>>Pelajar/Mahasiswa</option>
                                    <option value="Pensiunan" <?= $pasien['pekerjaan'] == 'Pensiunan' ? 'selected' : '' ?>>Pensiunan</option>
                                    <option value="Lainnya" <?= $pasien['pekerjaan'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Catatan Pasien</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="catatan_pasien" rows="3" maxlength="500" placeholder="Masukkan catatan khusus untuk pasien ini (opsional)"><?= $pasien['catatan_pasien'] ?? '' ?></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Ceklist</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="ceklist" rows="3" maxlength="500" placeholder="Masukkan ceklist untuk pasien ini (opsional)"><?= $pasien['ceklist'] ?? '' ?></textarea>
                            </div>
                        </div>

                        <!-- Hidden field untuk umur -->
                        <input type="hidden" name="umur" id="umur" value="<?= $pasien['umur'] ?>">

                        <div class="form-group row">
                            <div class="col-sm-10 offset-sm-2">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>" class="btn btn-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Script untuk memastikan form dikirim dengan benar dan mencegah cache
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');

        form.addEventListener('submit', function(e) {
            // Tambahkan parameter waktu untuk mencegah cache
            if (!this.action.includes('t=')) {
                this.action += '&t=' + new Date().getTime();
            }

            // Log data yang akan dikirim untuk debugging
            console.log('Form akan mengirim data:', new FormData(this));
        });

        // Fungsi untuk menghitung umur berdasarkan tanggal lahir
        function hitungUmur(tanggalLahir) {
            const today = new Date();
            const birthDate = new Date(tanggalLahir);
            let umurTahun = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                umurTahun--;
            }

            return umurTahun + " Th";
        }

        // Event listener untuk tanggal lahir
        document.getElementById('tgl_lahir').addEventListener('change', function() {
            const tanggalLahir = this.value;
            if (tanggalLahir) {
                const umur = hitungUmur(tanggalLahir);
                document.getElementById('umur').value = umur;
            }
        });
    });
</script>