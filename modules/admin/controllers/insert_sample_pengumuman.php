<?php
// Kredensial database
require_once __DIR__ . '/../../../config/database.php';
$db_host = $db2_host;
$db_username = $db2_username;
$db_password = $db2_password;
$db_database = $db2_database;

// Buat koneksi
// Gunakan koneksi global PDO
require_once __DIR__ . '/../../../config/database.php';
global $conn; // $conn sudah merupakan instance PDO dari config/database.php

// Cek koneksi database
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Fungsi untuk generate UUID
function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

// Data contoh pengumuman
$sample_data = [
    [
        'judul' => 'Jadwal Praktek Dokter Spesialis Kandungan',
        'isi_pengumuman' => '<p>Kami informasikan jadwal praktek dokter spesialis kandungan untuk bulan ini telah diperbarui. Silakan cek di menu Jadwal Praktek untuk informasi lebih lanjut.</p><p>Terima kasih.</p>',
        'tanggal_mulai' => date('Y-m-d'),
        'tanggal_berakhir' => date('Y-m-d', strtotime('+30 days')),
        'status_aktif' => 1
    ],
    [
        'judul' => 'Pemeriksaan Gratis untuk Ibu Hamil',
        'isi_pengumuman' => '<p>Dalam rangka Hari Kesehatan Nasional, kami mengadakan pemeriksaan gratis untuk ibu hamil pada tanggal 12 November 2023.</p><p>Pendaftaran dibuka mulai tanggal 1 November 2023. Kuota terbatas!</p>',
        'tanggal_mulai' => date('Y-m-d'),
        'tanggal_berakhir' => date('Y-m-d', strtotime('+15 days')),
        'status_aktif' => 1
    ],
    [
        'judul' => 'Perubahan Jam Operasional Klinik',
        'isi_pengumuman' => '<p>Diberitahukan kepada seluruh pasien bahwa mulai tanggal 1 November 2023, jam operasional klinik akan berubah menjadi:</p><ul><li>Senin - Jumat: 08.00 - 20.00 WIB</li><li>Sabtu: 08.00 - 15.00 WIB</li><li>Minggu: Tutup</li></ul><p>Terima kasih atas perhatiannya.</p>',
        'tanggal_mulai' => date('Y-m-d'),
        'tanggal_berakhir' => null,
        'status_aktif' => 1
    ]
];

// Tambahkan data ke database
$success_count = 0;
foreach ($sample_data as $data) {
    $id_pengumuman = generateUUID();

    $query = "INSERT INTO pengumuman (id_pengumuman, judul, isi_pengumuman, tanggal_mulai, tanggal_berakhir, status_aktif, dibuat_oleh) 
              VALUES (?, ?, ?, ?, ?, ?, NULL)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "sssssi",
        $id_pengumuman,
        $data['judul'],
        $data['isi_pengumuman'],
        $data['tanggal_mulai'],
        $data['tanggal_berakhir'],
        $data['status_aktif']
    );

    if ($stmt->execute()) {
        $success_count++;
    } else {
        echo "Error menambahkan pengumuman '{$data['judul']}': " . $conn->error . "<br>";
    }
}

echo "Berhasil menambahkan $success_count dari " . count($sample_data) . " pengumuman.";

$conn->close();
