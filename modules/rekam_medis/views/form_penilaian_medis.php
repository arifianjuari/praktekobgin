<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Pemeriksaan Medis</h3>
                    <div class="card-tools">
                        <a href="javascript:history.back()" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
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

                    <form action="index.php?module=rekam_medis&action=simpan_penilaian_medis" method="post">
                        <input type="hidden" name="no_rkm_medis" value="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>">

                        <div class="form-group">
                            <label for="no_rawat">No. Rawat</label>
                            <input type="text" class="form-control" id="no_rawat" name="no_rawat" value="<?= date('Ymd') . rand(1000, 9999) ?>" required readonly>
                        </div>

                        <div class="form-group">
                            <label for="kd_dokter">Dokter</label>
                            <select class="form-control" id="kd_dokter" name="kd_dokter" required>
                                <option value="">Pilih Dokter</option>
                                <?php foreach ($dokter as $d): ?>
                                    <option value="<?= $d['ID_Dokter'] ?>">
                                        <?= $d['Nama_Dokter'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tanggal">Tanggal Pemeriksaan</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="anamnesis">Anamnesis</label>
                            <select class="form-control" id="anamnesis" name="anamnesis" required>
                                <option value="Autoanamnesis">Autoanamnesis</option>
                                <option value="Alloanamnesis">Alloanamnesis</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hubungan">Hubungan</label>
                            <input type="text" class="form-control" id="hubungan" name="hubungan">
                        </div>

                        <div class="form-group">
                            <label for="keluhan_utama">Keluhan Utama</label>
                            <textarea class="form-control" id="keluhan_utama" name="keluhan_utama" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="rps">Riwayat Penyakit Sekarang</label>
                            <textarea class="form-control" id="rps" name="rps" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="rpd">Riwayat Penyakit Dahulu</label>
                            <textarea class="form-control" id="rpd" name="rpd" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="rpk">Riwayat Penyakit Keluarga</label>
                            <textarea class="form-control" id="rpk" name="rpk" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="rpo">Riwayat Pengobatan</label>
                            <textarea class="form-control" id="rpo" name="rpo" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="alergi">Alergi</label>
                            <input type="text" class="form-control" id="alergi" name="alergi">
                        </div>

                        <div class="form-group">
                            <label for="keadaan">Keadaan Umum</label>
                            <select class="form-control" id="keadaan" name="keadaan" required>
                                <option value="Baik">Baik</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Buruk">Buruk</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="kesadaran">Kesadaran</label>
                            <select class="form-control" id="kesadaran" name="kesadaran" required>
                                <option value="Compos Mentis">Compos Mentis</option>
                                <option value="Somnolence">Somnolence</option>
                                <option value="Sopor">Sopor</option>
                                <option value="Coma">Coma</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="gcs">GCS (E,V,M)</label>
                            <input type="text" class="form-control" id="gcs" name="gcs">
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="td">Tekanan Darah (mmHg)</label>
                                    <input type="text" class="form-control" id="td" name="td" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nadi">Nadi (x/menit)</label>
                                    <input type="text" class="form-control" id="nadi" name="nadi" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="rr">RR (x/menit)</label>
                                    <input type="text" class="form-control" id="rr" name="rr" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="suhu">Suhu (°C)</label>
                                    <input type="text" class="form-control" id="suhu" name="suhu" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="bb">Berat Badan (kg)</label>
                                    <input type="number" class="form-control" id="bb" name="bb" step="0.01" min="0" max="500" placeholder="Gunakan titik untuk desimal" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">
                                    <small class="text-muted">Gunakan titik (.) untuk desimal, bukan koma</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tb">Tinggi Badan (cm)</label>
                                    <input type="number" class="form-control" id="tb" name="tb" step="0.1" min="0" max="300" placeholder="Gunakan titik untuk desimal" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">
                                    <small class="text-muted">Gunakan titik (.) untuk desimal, bukan koma</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lila">LILA (cm)</label>
                                    <input type="text" class="form-control" id="lila" name="lila">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tfu">TFU (cm)</label>
                            <input type="text" class="form-control" id="tfu" name="tfu">
                        </div>

                        <div class="form-group">
                            <label for="tbj">TBJ (gram)</label>
                            <input type="text" class="form-control" id="tbj" name="tbj">
                        </div>

                        <div class="form-group">
                            <label for="his">His</label>
                            <input type="text" class="form-control" id="his" name="his">
                        </div>

                        <div class="form-group">
                            <label for="kontraksi">Kontraksi</label>
                            <select class="form-control" id="kontraksi" name="kontraksi">
                                <option value="Ada">Ada</option>
                                <option value="Tidak Ada">Tidak Ada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="djj">DJJ (x/menit)</label>
                            <input type="text" class="form-control" id="djj" name="djj">
                        </div>

                        <div class="form-group">
                            <label for="inspeksi">Inspeksi</label>
                            <textarea class="form-control" id="inspeksi" name="inspeksi" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="inspekulo">Inspekulo</label>
                            <textarea class="form-control" id="inspekulo" name="inspekulo" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="vt">VT</label>
                            <textarea class="form-control" id="vt" name="vt" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="rt">RT</label>
                            <textarea class="form-control" id="rt" name="rt" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="ultra">Ultrasonografi</label>
                            <textarea class="form-control" id="ultra" name="ultra" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="kardio">Kardiotokografi</label>
                            <textarea class="form-control" id="kardio" name="kardio" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="lab">Laboratorium</label>
                            <textarea class="form-control" id="lab" name="lab" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="diagnosis">Diagnosis</label>
                            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="tata">Tatalaksana</label>
                            <textarea class="form-control" id="tata" name="tata" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="konsul">Konsul</label>
                            <textarea class="form-control" id="konsul" name="konsul" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="javascript:history.back()" class="btn btn-default">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>