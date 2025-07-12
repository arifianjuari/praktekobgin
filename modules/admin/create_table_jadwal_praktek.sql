CREATE TABLE IF NOT EXISTS jadwal_praktek (
    ID_Jadwal_Praktek CHAR(36) NOT NULL,
    ID_Tempat_Praktek CHAR(36) NOT NULL,
    ID_Dokter CHAR(36) NOT NULL,
    Tanggal_Praktek DATE DEFAULT NULL,
    Jam_Mulai TIME DEFAULT NULL,
    Jam_Selesai TIME DEFAULT NULL,
    Status_Praktek VARCHAR(50) DEFAULT NULL,
    Alasan_Tutup VARCHAR(255) DEFAULT NULL,
    Tanggal_Buka_Kembali DATE DEFAULT NULL,
    Kuota_Pasien INT(11) DEFAULT NULL,
    Jenis_Layanan VARCHAR(50) DEFAULT NULL,
    createdAt DATETIME DEFAULT NULL,
    updatedAt DATETIME DEFAULT NULL,
    PRIMARY KEY (ID_Jadwal_Praktek),
    FOREIGN KEY (ID_Tempat_Praktek) REFERENCES tempat_praktek(ID_Tempat_Praktek),
    FOREIGN KEY (ID_Dokter) REFERENCES dokter(ID_Dokter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 