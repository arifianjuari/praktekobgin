<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tambah Pasien Baru</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="index.php?module=rekam_medis&action=simpan_pasien" method="POST">
                <!-- Data Pribadi -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Data Pasien</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="no_ktp" class="form-label">NIK</label>
                                <input type="text" class="form-control" id="no_ktp" name="no_ktp" maxlength="20">
                                <div id="nik-feedback" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="nama_pasien" class="form-label">Nama Pasien <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_pasien" name="nm_pasien" maxlength="40" required>
                                <small class="form-text text-muted">Nama akan otomatis diubah menjadi huruf kapital</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select" id="jenis_kelamin" name="jk" required>
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P" selected>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="no_tlp" class="form-label">No. Telepon</label>
                                <input type="text" class="form-control" id="no_tlp" name="no_tlp" maxlength="40">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tgl_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" required>
                            </div>
                            <div class="col-md-6">
                                <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                <select class="form-select" id="pekerjaan" name="pekerjaan">
                                    <option value="">-- Pilih Pekerjaan --</option>
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
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="2" maxlength="200"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="kd_kec" class="form-label">Kecamatan</label>
                                <select class="form-select" id="kd_kec" name="kd_kec">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <?php foreach ($kecamatan as $kec): ?>
                                        <option value="<?= $kec['kd_kec'] ?>"><?= $kec['nm_kec'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="catatan_pasien" class="form-label">Catatan Pasien</label>
                                <textarea class="form-control" id="catatan_pasien" name="catatan_pasien" rows="3" maxlength="500" placeholder="Masukkan catatan khusus untuk pasien ini (opsional)"></textarea>
                            </div>
                        </div>

                        <!-- Hidden field untuk umur dan tgl_daftar -->
                        <input type="hidden" name="umur" id="umur" value="">
                        <input type="hidden" name="tgl_daftar" id="tgl_daftar" value="<?= date('Y-m-d H:i:s') ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="index.php?module=rekam_medis&action=data_pasien" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menghitung umur berdasarkan tanggal lahir
    function hitungUmur(tanggalLahir) {
        const today = new Date();
        const birthDate = new Date(tanggalLahir);
        let umurTahun = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();

        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            umurTahun--;
        }

        return umurTahun;
    }

    // Event listener untuk tanggal lahir
    document.getElementById('tgl_lahir').addEventListener('change', function() {
        const tanggalLahir = this.value;
        if (tanggalLahir) {
            const umur = hitungUmur(tanggalLahir);
            document.getElementById('umur').value = umur;
        }
    });

    // Set tanggal daftar saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const tahun = now.getFullYear();
        const bulan = String(now.getMonth() + 1).padStart(2, '0');
        const tanggal = String(now.getDate()).padStart(2, '0');
        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');
        const detik = String(now.getSeconds()).padStart(2, '0');

        const tanggalDaftar = `${tahun}-${bulan}-${tanggal} ${jam}:${menit}:${detik}`;
        document.getElementById('tgl_daftar').value = tanggalDaftar;

        // Cek NIK saat input berubah
        const nikInput = document.getElementById('no_ktp');
        const nikFeedback = document.getElementById('nik-feedback');
        const submitButton = document.querySelector('button[type="submit"]');

        // Mengubah nama pasien menjadi huruf kapital
        const namaPasienInput = document.getElementById('nama_pasien');
        namaPasienInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        let typingTimer;
        const doneTypingInterval = 1000; // 1 detik

        nikInput.addEventListener('input', function() {
            clearTimeout(typingTimer);

            // Reset status
            nikInput.classList.remove('is-invalid');
            nikInput.classList.remove('is-valid');
            submitButton.disabled = false;

            if (this.value.length > 0) {
                typingTimer = setTimeout(cekNik, doneTypingInterval);
            }
        });

        // Pastikan nama pasien dalam huruf kapital saat form disubmit
        document.querySelector('form').addEventListener('submit', function(e) {
            namaPasienInput.value = namaPasienInput.value.toUpperCase();
        });

        function cekNik() {
            const nik = nikInput.value;
            if (nik.length === 0) return;

            // Buat objek FormData
            const formData = new FormData();
            formData.append('nik', nik);

            // Kirim request AJAX
            fetch('index.php?module=rekam_medis&action=cek_nik_pasien', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'exists') {
                        // NIK sudah ada
                        nikInput.classList.add('is-invalid');
                        nikFeedback.textContent = data.message;
                        submitButton.disabled = true;
                    } else if (data.status === 'not_exists') {
                        // NIK belum ada
                        nikInput.classList.add('is-valid');
                        submitButton.disabled = false;
                    } else if (data.error) {
                        console.error('Error:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    });
</script>