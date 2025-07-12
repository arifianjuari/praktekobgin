<?php
$title = 'Detail Penilaian Medis Ralan Kandungan';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $title ?></h3>
                    <div class="card-tools">
                        <a href="index.php?module=rekam_medis&action=edit_penilaian_ralan&id=<?= $penilaian_medis['no_rawat'] ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $penilaian_medis['no_rkm_medis'] ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">No. Rawat</th>
                                    <td><?= $penilaian_medis['no_rawat'] ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal & Jam</th>
                                    <td><?= date('d-m-Y H:i', strtotime($penilaian_medis['tanggal'] . ' ' . $penilaian_medis['jam'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Dokter</th>
                                    <td><?= $penilaian_medis['Nama_Dokter'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Anamnesis</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Anamnesis</th>
                                    <td><?= $penilaian_medis['anamnesis'] ?></td>
                                </tr>
                                <tr>
                                    <th>Hubungan</th>
                                    <td><?= $penilaian_medis['hubungan'] ?></td>
                                </tr>
                                <tr>
                                    <th>Keluhan Utama</th>
                                    <td><?= nl2br($penilaian_medis['keluhan_utama']) ?></td>
                                </tr>
                                <tr>
                                    <th>Riwayat Penyakit Sekarang</th>
                                    <td><?= nl2br($penilaian_medis['rps']) ?></td>
                                </tr>
                                <tr>
                                    <th>Riwayat Penyakit Dahulu</th>
                                    <td><?= nl2br($penilaian_medis['rpd']) ?></td>
                                </tr>
                                <tr>
                                    <th>Riwayat Penyakit Keluarga</th>
                                    <td><?= nl2br($penilaian_medis['rpk']) ?></td>
                                </tr>
                                <tr>
                                    <th>Riwayat Pengobatan</th>
                                    <td><?= nl2br($penilaian_medis['rpo']) ?></td>
                                </tr>
                                <tr>
                                    <th>Alergi</th>
                                    <td><?= $penilaian_medis['alergi'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Pemeriksaan Fisik</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Keadaan Umum</th>
                                    <td><?= $penilaian_medis['keadaan'] ?></td>
                                </tr>
                                <tr>
                                    <th>Kesadaran</th>
                                    <td><?= $penilaian_medis['kesadaran'] ?></td>
                                </tr>
                                <tr>
                                    <th>TD</th>
                                    <td><?= $penilaian_medis['td'] ?> mmHg</td>
                                </tr>
                                <tr>
                                    <th>Nadi</th>
                                    <td><?= $penilaian_medis['nadi'] ?> /menit</td>
                                </tr>
                                <tr>
                                    <th>Suhu</th>
                                    <td><?= $penilaian_medis['suhu'] ?> °C</td>
                                </tr>
                                <tr>
                                    <th>RR</th>
                                    <td><?= $penilaian_medis['rr'] ?> /menit</td>
                                </tr>
                                <tr>
                                    <th>BB</th>
                                    <td><?= $penilaian_medis['bb'] ?> kg</td>
                                </tr>
                                <tr>
                                    <th>TB</th>
                                    <td><?= $penilaian_medis['tb'] ?> cm</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h5>Pemeriksaan Obstetri</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">LILA</th>
                                    <td><?= $penilaian_medis['lila'] ?> cm</td>
                                    <th width="200">TFU</th>
                                    <td><?= $penilaian_medis['tfu'] ?> cm</td>
                                </tr>
                                <tr>
                                    <th>TBJ</th>
                                    <td><?= $penilaian_medis['tbj'] ?> gram</td>
                                    <th>His</th>
                                    <td><?= $penilaian_medis['his'] ?> x/10 menit</td>
                                </tr>
                                <tr>
                                    <th>Kontraksi</th>
                                    <td><?= $penilaian_medis['kontraksi'] ?></td>
                                    <th>DJJ</th>
                                    <td><?= $penilaian_medis['djj'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h5>Pemeriksaan Ginekologi</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Inspeksi</th>
                                    <td colspan="3"><?= nl2br($penilaian_medis['inspeksi']) ?></td>
                                </tr>
                                <tr>
                                    <th>Inspekulo</th>
                                    <td colspan="3"><?= nl2br($penilaian_medis['inspekulo']) ?></td>
                                </tr>
                                <tr>
                                    <th>Fluxus</th>
                                    <td><?= $penilaian_medis['fluxus'] ?></td>
                                    <th width="200">Fluor</th>
                                    <td><?= $penilaian_medis['fluor'] ?></td>
                                </tr>
                                <tr>
                                    <th>Dalam</th>
                                    <td colspan="3"><?= nl2br($penilaian_medis['dalam']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h5>Status Obstetri</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Pembukaan</th>
                                    <td><?= $penilaian_medis['pembukaan'] ?></td>
                                    <th width="200">Portio</th>
                                    <td><?= $penilaian_medis['portio'] ?></td>
                                </tr>
                                <tr>
                                    <th>Ketuban</th>
                                    <td><?= $penilaian_medis['ketuban'] ?></td>
                                    <th>Presentasi</th>
                                    <td><?= $penilaian_medis['presentasi'] ?></td>
                                </tr>
                                <tr>
                                    <th>Penurunan</th>
                                    <td><?= $penilaian_medis['penurunan'] ?></td>
                                    <th>Denominator</th>
                                    <td><?= $penilaian_medis['denominator'] ?></td>
                                </tr>
                                <tr>
                                    <th>Ukuran Panggul</th>
                                    <td colspan="3"><?= $penilaian_medis['ukuran_panggul'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h5>Kesimpulan dan Tindakan</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Diagnosa</th>
                                    <td><?= nl2br($penilaian_medis['diagnosa']) ?></td>
                                </tr>
                                <tr>
                                    <th>Tindakan</th>
                                    <td><?= nl2br($penilaian_medis['tindakan']) ?></td>
                                </tr>
                                <tr>
                                    <th>Edukasi</th>
                                    <td><?= nl2br($penilaian_medis['edukasi']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>