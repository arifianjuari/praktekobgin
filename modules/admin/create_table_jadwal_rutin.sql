CREATE TABLE IF NOT EXISTS jadwal_rutin (
    ID_Jadwal_Rutin CHAR(36) NOT NULL,
    ID_Tempat_Praktek CHAR(36) NOT NULL,
    ID_Dokter CHAR(36) NOT NULL,
    Hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    Jam_Mulai TIME NOT NULL,
    Jam_Selesai TIME NOT NULL,
    Status_Aktif TINYINT(1) DEFAULT 1,
    Kuota_Pasien INT(11) DEFAULT NULL,
    Jenis_Layanan VARCHAR(50) DEFAULT NULL,
    createdAt DATETIME DEFAULT NULL,
    updatedAt DATETIME DEFAULT NULL,
    PRIMARY KEY (ID_Jadwal_Rutin),
    FOREIGN KEY (ID_Tempat_Praktek) REFERENCES tempat_praktek(ID_Tempat_Praktek),
    FOREIGN KEY (ID_Dokter) REFERENCES dokter(ID_Dokter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 