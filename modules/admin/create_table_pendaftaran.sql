CREATE TABLE IF NOT EXISTS pendaftaran (
    ID_Pendaftaran VARCHAR(20) PRIMARY KEY,
    Nama_Pasien VARCHAR(100) NOT NULL,
    Tanggal_Lahir DATE NOT NULL,
    Jenis_Kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    Nomor_Telepon VARCHAR(20) NOT NULL,
    Alamat TEXT,
    Keluhan TEXT,
    ID_Tempat_Praktek CHAR(36) NOT NULL,
    ID_Dokter CHAR(36) NOT NULL,
    ID_Jadwal VARCHAR(20) NOT NULL,
    Status_Pendaftaran ENUM('Menunggu Konfirmasi', 'Dikonfirmasi', 'Dibatalkan', 'Selesai') NOT NULL DEFAULT 'Menunggu Konfirmasi',
    Waktu_Pendaftaran DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createdAt DATETIME DEFAULT NULL,
    updatedAt DATETIME DEFAULT NULL,
    FOREIGN KEY (ID_Tempat_Praktek) REFERENCES tempat_praktek(ID_Tempat_Praktek),
    FOREIGN KEY (ID_Dokter) REFERENCES dokter(ID_Dokter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 