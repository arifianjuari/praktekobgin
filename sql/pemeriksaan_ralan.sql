CREATE TABLE IF NOT EXISTS `pemeriksaan_ralan` (
  `no_rawat` varchar(17) NOT NULL,
  `tanggal` datetime NOT NULL,
  `anamnesis` text NOT NULL,
  `hubungan` varchar(20) NOT NULL,
  `keluhan_utama` text NOT NULL,
  `rps` text,
  `rpd` text,
  `alergi` varchar(50),
  `keadaan` varchar(20) NOT NULL,
  `kesadaran` varchar(20) NOT NULL,
  `gcs` varchar(10) NOT NULL,
  `td` varchar(20) NOT NULL,
  `nadi` varchar(10) NOT NULL,
  `rr` varchar(10) NOT NULL,
  `suhu` varchar(10) NOT NULL,
  `spo` varchar(10),
  `bb` varchar(10),
  `tb` varchar(10),
  `kepala` varchar(20) NOT NULL,
  `mata` varchar(20) NOT NULL,
  `gigi` varchar(20) NOT NULL,
  `tht` varchar(20) NOT NULL,
  `thoraks` varchar(20) NOT NULL,
  `abdomen` varchar(20) NOT NULL,
  `genital` varchar(20) NOT NULL,
  `ekstremitas` varchar(20) NOT NULL,
  `kulit` varchar(20) NOT NULL,
  `ket_fisik` text,
  `ultra` text,
  `lab` text,
  `diagnosis` text,
  `tata` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`no_rawat`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 