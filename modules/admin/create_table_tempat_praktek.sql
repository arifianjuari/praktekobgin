CREATE TABLE IF NOT EXISTS tempat_praktek (
    ID_Tempat_Praktek CHAR(36) NOT NULL,
    Nama_Tempat VARCHAR(100) DEFAULT NULL,
    Alamat_Lengkap TEXT DEFAULT NULL,
    Kota VARCHAR(50) DEFAULT NULL,
    Provinsi VARCHAR(50) DEFAULT NULL,
    Kode_Pos VARCHAR(10) DEFAULT NULL,
    Nomor_Telepon VARCHAR(15) DEFAULT NULL,
    Jenis_Fasilitas VARCHAR(50) DEFAULT NULL,
    Status_Aktif TINYINT(1) DEFAULT 1,
    createdAt DATETIME DEFAULT NULL,
    updatedAt DATETIME DEFAULT NULL,
    PRIMARY KEY (ID_Tempat_Praktek)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 