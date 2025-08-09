<?php
// Surat Sakit
if (!isset($surat)) { die('Data surat tidak ditemukan'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Surat Sakit - <?= htmlspecialchars($surat['nomor_surat'] ?? '-') ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12pt; color:#000; }
    .container { max-width: 800px; margin: 0 auto; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 16px; }
    .title { text-align:center; margin: 16px 0; text-transform: uppercase; font-weight: bold; text-decoration: underline; }
    .section { margin: 12px 0; }
    .label { width: 180px; display: inline-block; vertical-align: top; }
    .value { display: inline-block; min-width: 200px; }
    .footer { margin-top: 32px; display: flex; justify-content: space-between; }
    .ttd { text-align: center; width: 40%; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <div style="font-size:14pt; font-weight:bold;">KLINIK / PRAKTEK OB GIN</div>
    <div>Alamat Klinik</div>
    <div>Telp: -</div>
  </div>

  <div class="section" style="text-align:right;">Nomor: <strong><?= htmlspecialchars($surat['nomor_surat'] ?? '-') ?></strong></div>
  <div class="title">Surat Sakit</div>

  <div class="section">
    <div>Yang bertanda tangan di bawah ini menerangkan bahwa:</div>
    <div class="section">
      <div><span class="label">Nama</span><span class="value">: <?= htmlspecialchars($surat['nama_pasien'] ?? $surat['no_rkm_medis'] ?? '-') ?></span></div>
      <div><span class="label">No. RM</span><span class="value">: <?= htmlspecialchars($surat['no_rkm_medis'] ?? '-') ?></span></div>
      <div><span class="label">Diagnosa</span><span class="value">: <?= htmlspecialchars($surat['diagnosa'] ?? '-') ?></span></div>
      <div><span class="label">Tanggal Surat</span><span class="value">: <?= htmlspecialchars($surat['tanggal_surat']) ?></span></div>
      <div><span class="label">Istirahat</span><span class="value">: <?= htmlspecialchars($surat['mulai_sakit'] ? date('d/m/Y', strtotime($surat['mulai_sakit'])) : '-') ?> s/d <?= htmlspecialchars($surat['selesai_sakit'] ? date('d/m/Y', strtotime($surat['selesai_sakit'])) : '-') ?></span></div>
    </div>
    <div>Yang bersangkutan perlu beristirahat selama periode tersebut di atas.</div>
  </div>

  <div class="footer">
    <div></div>
    <div class="ttd">
      <div><?= date('d/m/Y', strtotime($surat['tanggal_surat'])) ?></div>
      <div><strong>Dokter Pemeriksa</strong></div>
      <div style="height:72px"></div>
      <div><u><?= htmlspecialchars($surat['dokter_pemeriksa'] ?? '-') ?></u></div>
    </div>
  </div>

  <div class="no-print" style="margin-top:24px; text-align:center;">
    <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= urlencode($surat['no_rkm_medis']) ?>#surat">Kembali</a>
  </div>
</div>
</body>
</html>
