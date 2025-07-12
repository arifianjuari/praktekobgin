<?php
// Cek apakah ini mode edit
$is_edit = isset($penilaian_medis);
$title = $is_edit ? 'Edit Penilaian Medis Ralan Kandungan' : 'Tambah Penilaian Medis Ralan Kandungan';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $title ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?module=rekam_medis&action=<?= $is_edit ? 'update_penilaian_ralan' : 'simpan_penilaian_ralan' ?>">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="no_rawat" value="<?= $penilaian_medis['no_rawat'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="no_rkm_medis" value="<?= $no_rkm_medis ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Anamnesis</label>
                                    <input type="text" class="form-control" name="anamnesis" value="<?= $is_edit ? $penilaian_medis['anamnesis'] : '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hubungan dengan Pasien</label>
                                    <input type="text" class="form-control" name="hubungan" value="<?= $is_edit ? $penilaian_medis['hubungan'] : '' ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Keluhan Utama</label>
                            <textarea class="form-control" name="keluhan_utama" rows="3" required><?= $is_edit ? $penilaian_medis['keluhan_utama'] : '' ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Riwayat Penyakit Sekarang</label>
                                    <textarea class="form-control" name="rps" rows="3"><?= $is_edit ? $penilaian_medis['rps'] : '' ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Riwayat Penyakit Dahulu</label>
                                    <textarea class="form-control" name="rpd" rows="3"><?= $is_edit ? $penilaian_medis['rpd'] : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Riwayat Penyakit Keluarga</label>
                                    <textarea class="form-control" name="rpk" rows="3"><?= $is_edit ? $penilaian_medis['rpk'] : '' ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Riwayat Pengobatan</label>
                                    <textarea class="form-control" name="rpo" rows="3"><?= $is_edit ? $penilaian_medis['rpo'] : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Alergi</label>
                                    <input type="text" class="form-control" name="alergi" value="<?= $is_edit ? $penilaian_medis['alergi'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Keadaan Umum</label>
                                    <input type="text" class="form-control" name="keadaan" value="<?= $is_edit ? $penilaian_medis['keadaan'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kesadaran</label>
                                    <input type="text" class="form-control" name="kesadaran" value="<?= $is_edit ? $penilaian_medis['kesadaran'] : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>TD (mmHg)</label>
                                    <input type="text" class="form-control" name="td" value="<?= $is_edit ? $penilaian_medis['td'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Nadi (/menit)</label>
                                    <input type="text" class="form-control" name="nadi" value="<?= $is_edit ? $penilaian_medis['nadi'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Suhu (°C)</label>
                                    <input type="text" class="form-control" name="suhu" value="<?= $is_edit ? $penilaian_medis['suhu'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>RR (/menit)</label>
                                    <input type="text" class="form-control" name="rr" value="<?= $is_edit ? $penilaian_medis['rr'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>BB (kg)</label>
                                    <input type="number" class="form-control" name="bb" value="<?= $is_edit ? $penilaian_medis['bb'] : '' ?>" step="0.01" min="0" max="500" placeholder="Gunakan titik untuk desimal" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">
                                    <small class="text-muted">Gunakan titik (.) untuk desimal, bukan koma</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>TB (cm)</label>
                                    <input type="number" class="form-control" name="tb" value="<?= $is_edit ? $penilaian_medis['tb'] : '' ?>" step="0.1" min="0" max="300" placeholder="Gunakan titik untuk desimal" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">
                                    <small class="text-muted">Gunakan titik (.) untuk desimal, bukan koma</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>LILA (cm)</label>
                                    <input type="text" class="form-control" name="lila" value="<?= $is_edit ? $penilaian_medis['lila'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>TFU (cm)</label>
                                    <input type="text" class="form-control" name="tfu" value="<?= $is_edit ? $penilaian_medis['tfu'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>TBJ (gram)</label>
                                    <input type="text" class="form-control" name="tbj" value="<?= $is_edit ? $penilaian_medis['tbj'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>His (x/10 menit)</label>
                                    <input type="text" class="form-control" name="his" value="<?= $is_edit ? $penilaian_medis['his'] : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kontraksi</label>
                                    <input type="text" class="form-control" name="kontraksi" value="<?= $is_edit ? $penilaian_medis['kontraksi'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>DJJ</label>
                                    <input type="text" class="form-control" name="djj" value="<?= $is_edit ? $penilaian_medis['djj'] : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Inspeksi</label>
                            <textarea class="form-control" name="inspeksi" rows="3"><?= $is_edit ? $penilaian_medis['inspeksi'] : '' ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>Inspekulo</label>
                            <textarea class="form-control" name="inspekulo" rows="3"><?= $is_edit ? $penilaian_medis['inspekulo'] : '' ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fluxus</label>
                                    <input type="text" class="form-control" name="fluxus" value="<?= $is_edit ? $penilaian_medis['fluxus'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fluor</label>
                                    <input type="text" class="form-control" name="fluor" value="<?= $is_edit ? $penilaian_medis['fluor'] : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Dalam</label>
                            <textarea class="form-control" name="dalam" rows="3"><?= $is_edit ? $penilaian_medis['dalam'] : '' ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Pembukaan</label>
                                    <input type="text" class="form-control" name="pembukaan" value="<?= $is_edit ? $penilaian_medis['pembukaan'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Portio</label>
                                    <input type="text" class="form-control" name="portio" value="<?= $is_edit ? $penilaian_medis['portio'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ketuban</label>
                                    <input type="text" class="form-control" name="ketuban" value="<?= $is_edit ? $penilaian_medis['ketuban'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Presentasi</label>
                                    <input type="text" class="form-control" name="presentasi" value="<?= $is_edit ? $penilaian_medis['presentasi'] : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Penurunan</label>
                                    <input type="text" class="form-control" name="penurunan" value="<?= $is_edit ? $penilaian_medis['penurunan'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Denominator</label>
                                    <input type="text" class="form-control" name="denominator" value="<?= $is_edit ? $penilaian_medis['denominator'] : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ukuran Panggul</label>
                                    <input type="text" class="form-control" name="ukuran_panggul" value="<?= $is_edit ? $penilaian_medis['ukuran_panggul'] : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Diagnosa</label>
                            <textarea class="form-control" name="diagnosa" rows="3" required><?= $is_edit ? $penilaian_medis['diagnosa'] : '' ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>Tindakan</label>
                            <textarea class="form-control" name="tindakan" rows="3" required><?= $is_edit ? $penilaian_medis['tindakan'] : '' ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>Edukasi</label>
                            <textarea class="form-control" name="edukasi" rows="3"><?= $is_edit ? $penilaian_medis['edukasi'] : '' ?></textarea>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-10 offset-sm-2">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $no_rkm_medis ?>" class="btn btn-secondary">Kembali</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>