CREATE TABLE IF NOT EXISTS `pengumuman` (
  `id_pengumuman` CHAR(36) NOT NULL PRIMARY KEY,
  `judul` VARCHAR(255) NOT NULL,
  `isi_pengumuman` TEXT NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_berakhir` DATE NULL,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `dibuat_oleh` CHAR(36) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 