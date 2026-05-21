-- ============================================================
-- Database Ayosehat untuk MySQL/MariaDB
-- Import lewat phpMyAdmin: pilih database > tab Import > pilih file ini
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ayosehat`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ayosehat`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `pesanan_obat`;
DROP TABLE IF EXISTS `riwayat_antrian`;
DROP TABLE IF EXISTS `jadwal_dokter`;
DROP TABLE IF EXISTS `obat`;
DROP TABLE IF EXISTS `dokter`;
DROP TABLE IF EXISTS `pasien`;
DROP TABLE IF EXISTS `admin`;

-- ------------------------------------------------------------
-- Tabel: admin
-- ------------------------------------------------------------
CREATE TABLE `admin` (
  `id`       INT          NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: pasien
-- ------------------------------------------------------------
CREATE TABLE `pasien` (
  `id`       INT          NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pasien_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: dokter
-- ------------------------------------------------------------
CREATE TABLE `dokter` (
  `id`   INT          NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(150) NOT NULL,
  `poli` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: jadwal_dokter
-- ------------------------------------------------------------
CREATE TABLE `jadwal_dokter` (
  `id`        INT         NOT NULL AUTO_INCREMENT,
  `dokter_id` INT         NOT NULL,
  `hari`      VARCHAR(20) NOT NULL,
  `shift`     VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal_dokter_id` (`dokter_id`),
  CONSTRAINT `fk_jadwal_dokter`
    FOREIGN KEY (`dokter_id`) REFERENCES `dokter` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: obat
-- ------------------------------------------------------------
CREATE TABLE `obat` (
  `id`    INT          NOT NULL AUTO_INCREMENT,
  `nama`  VARCHAR(150) NOT NULL,
  `stok`  INT          NOT NULL DEFAULT 0,
  `harga` INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: riwayat_antrian
-- ------------------------------------------------------------
CREATE TABLE `riwayat_antrian` (
  `id`              INT          NOT NULL AUTO_INCREMENT,
  `username`        VARCHAR(100) NOT NULL,
  `kode`            VARCHAR(20)  NOT NULL,
  `nama`            VARCHAR(150) NOT NULL,
  `tanggal_lahir`   DATE         NULL,
  `jenis_kelamin`   VARCHAR(20)  NULL,
  `no_hp`           VARCHAR(30)  NULL,
  `rumah_sakit`     VARCHAR(150) NULL,
  `tanggal`         DATE         NOT NULL,
  `jam`             VARCHAR(50)  NULL,
  `dokter`          VARCHAR(150) NULL,
  `poli`            VARCHAR(120) NULL,
  `metode`          VARCHAR(50)  NULL,
  `nomor_kartu`     VARCHAR(100) NULL,
  `resep_obat_json` TEXT         NULL,
  `status`          VARCHAR(30)  NOT NULL DEFAULT 'On Progress',
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_antrian_username` (`username`),
  KEY `idx_antrian_status`   (`status`),
  KEY `idx_antrian_tanggal`  (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: pesanan_obat
-- ------------------------------------------------------------
CREATE TABLE `pesanan_obat` (
  `id`                  INT          NOT NULL AUTO_INCREMENT,
  `username`            VARCHAR(100) NOT NULL,
  `antrian_id`          INT          NULL,
  `kode_antrian`        VARCHAR(20)  NULL,
  `obat_json`           TEXT         NOT NULL,
  `waktu`               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_pengambilan` DATE         NULL,
  `jam_pengambilan`     TIME         NULL,
  `status`              VARCHAR(30)  NOT NULL DEFAULT 'menunggu',
  PRIMARY KEY (`id`),
  KEY `idx_pesanan_username`   (`username`),
  KEY `idx_pesanan_antrian_id` (`antrian_id`),
  KEY `idx_pesanan_status`     (`status`),
  CONSTRAINT `fk_pesanan_antrian`
    FOREIGN KEY (`antrian_id`) REFERENCES `riwayat_antrian` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Seed data: admin (password: admin123)
-- ============================================================
INSERT INTO `admin` (`username`, `password`) VALUES
('ayosehat', '$2y$10$ZNTiqfvKzppYdjZhYCwhXOWvY6D8zB6BfUBC.MBT9nng1V4vcxkuu');

-- ============================================================
-- Seed data: dokter
-- ============================================================
INSERT INTO `dokter` (`id`, `nama`, `poli`) VALUES
(1,  'drg. Andi',    'Poli Gigi'),
(2,  'drg. Budi',    'Poli Gigi'),
(3,  'drg. Fatimah', 'Poli Gigi'),
(4,  'drg. Ajeng',   'Poli Gigi'),
(5,  'drg. Ricky',   'Poli Gigi'),
(6,  'drg. Alawi',   'Poli Gigi'),
(7,  'dr. Rifqi',    'Umum'),
(8,  'dr. Kunti',    'Umum'),
(9,  'dr. Zulfa',    'Umum'),
(10, 'dr. Steve',    'Umum'),
(11, 'dr. William',  'Umum'),
(12, 'dr. Monica',   'Umum'),
(13, 'dr. Lestari',  'Poli KIA'),
(14, 'dr. Wahyu',    'Poli KIA'),
(15, 'dr. Melati',   'Poli KIA'),
(16, 'dr. Hamzah',   'Poli KIA'),
(17, 'dr. Ayu',      'Poli KIA'),
(18, 'dr. Yudha',    'Poli KIA'),
(19, 'dr. Sari',     'Poli KB'),
(20, 'dr. Fajar',    'Poli KB'),
(21, 'dr. Rani',     'Poli KB'),
(22, 'dr. Hendra',   'Poli KB'),
(23, 'dr. Tika',     'Poli KB'),
(24, 'dr. Arman',    'Poli KB'),
(25, 'dr. Lina',     'Pelayanan Imunisasi'),
(26, 'dr. Dedi',     'Pelayanan Imunisasi'),
(27, 'dr. Udil',     'Pelayanan Imunisasi'),
(28, 'dr. Reza',     'Pelayanan Imunisasi'),
(29, 'dr. Wulan',    'Pelayanan Imunisasi'),
(30, 'dr. Haris',    'Pelayanan Imunisasi'),
(31, 'dr. Devi',     'Pelayanan Khusus'),
(32, 'dr. Aldi',     'Pelayanan Khusus'),
(33, 'dr. Nando',    'Pelayanan Khusus'),
(34, 'dr. Bella',    'Pelayanan Khusus'),
(35, 'dr. Yoga',     'Pelayanan Khusus'),
(36, 'dr. Cindy',    'Pelayanan Khusus'),
(37, 'dr. Rudiger',  'Poli Gigi'),
(38, 'dr. Halland',  'Poli Gigi'),
(39, 'dr. Aldi',     'Umum');

-- ============================================================
-- Seed data: obat
-- ============================================================
INSERT INTO `obat` (`id`, `nama`, `stok`, `harga`) VALUES
(1,  'Paracetamol',        100, 3000),
(2,  'Amoxicillin',         80, 8000),
(3,  'Ibuprofen',           90, 5000),
(4,  'Cetirizine',          75, 7000),
(5,  'Antasida',            60, 4000),
(6,  'Vitamin C',          120, 3500),
(7,  'Salep Kulit',         50, 12000),
(8,  'Oralit',              80, 2500),
(9,  'Tetes Mata',          40, 15000),
(10, 'Batuk Hitam Dewasa',  55, 6000),
(11, 'Antibiotik Oles',     45, 11000),
(12, 'Sanmol',              70, 4500),
(13, 'Promag',              65, 5500),
(14, 'Mixagrip',            60, 6500),
(15, 'Bodrex',              70, 5000),
(16, 'Panadol',             85, 4000),
(17, 'OBH Combi',           50, 9000),
(18, 'Actifed',             55, 8500),
(19, 'Konidin',             60, 7000),
(20, 'Minyak Kayu Putih',   90, 10000),
(21, 'Minyak Telon',        70, 12000),
(22, 'Betadine',            45, 14000),
(23, 'Mylanta',             60, 6000),
(24, 'Entrostop',           55, 5500),
(25, 'Diapet',              50, 6000),
(26, 'Tolak Angin',         80, 4500),
(27, 'Decolgen',            65, 7500),
(28, 'Procold',             60, 7000),
(29, 'Strepsils',           40, 13000),
(30, 'Neozep',              55, 8000),
(31, 'Demacolin',           50, 8500),
(32, 'Lactacyd',            35, 25000),
(33, 'Alpara',              60, 7500),
(34, 'Erphaflam',           40, 10000),
(35, 'Degirol',             45, 9000),
(36, 'L-Bio',               50, 15000),
(37, 'Tempra',              55, 6500),
(38, 'Asam Mefenamat',      70, 5000),
(39, 'Ranitidine',          65, 4500),
(40, 'Omeprazole',          60, 6000);

-- ============================================================
-- Seed data: jadwal_dokter
-- ============================================================
INSERT INTO `jadwal_dokter` (`id`, `dokter_id`, `hari`, `shift`) VALUES
(1,   1,  'Senin',  '08:00 - 10:00'),
(2,   1,  'Rabu',   '08:00 - 10:00'),
(3,   1,  'Kamis',  '10:00 - 12:00'),
(4,   1,  'Sabtu',  '12:00 - 14:00'),
(5,   2,  'Selasa', '10:00 - 12:00'),
(6,   2,  'Kamis',  '14:00 - 16:00'),
(7,   2,  'Jumat',  '08:00 - 10:00'),
(8,   2,  'Sabtu',  '14:00 - 16:00'),
(9,   3,  'Selasa', '12:00 - 14:00'),
(10,  3,  'Kamis',  '18:00 - 20:00'),
(11,  3,  'Jumat',  '12:00 - 14:00'),
(12,  3,  'Minggu', '14:00 - 16:00'),
(13,  4,  'Senin',  '10:00 - 12:00'),
(14,  4,  'Rabu',   '14:00 - 16:00'),
(15,  4,  'Jumat',  '08:00 - 10:00'),
(16,  4,  'Sabtu',  '20:00 - 22:00'),
(17,  5,  'Selasa', '06:00 - 08:00'),
(18,  5,  'Rabu',   '14:00 - 16:00'),
(19,  5,  'Kamis',  '08:00 - 10:00'),
(20,  5,  'Minggu', '14:00 - 16:00'),
(21,  6,  'Senin',  '06:00 - 08:00'),
(22,  6,  'Selasa', '12:00 - 14:00'),
(23,  6,  'Rabu',   '08:00 - 10:00'),
(24,  6,  'Kamis',  '14:00 - 16:00'),
(25,  7,  'Senin',  '06:00 - 08:00'),
(26,  7,  'Rabu',   '12:00 - 14:00'),
(28,  7,  'Kamis',  '18:00 - 20:00'),
(29,  7,  'Minggu', '10:00 - 12:00'),
(30,  8,  'Sabtu',  '08:00 - 10:00'),
(31,  8,  'Jumat',  '06:00 - 08:00'),
(32,  8,  'Kamis',  '10:00 - 12:00'),
(33,  8,  'Rabu',   '06:00 - 08:00'),
(34,  9,  'Senin',  '08:00 - 10:00'),
(35,  9,  'Selasa', '10:00 - 12:00'),
(36,  9,  'Jumat',  '08:00 - 10:00'),
(37,  9,  'Sabtu',  '10:00 - 12:00'),
(38,  10, 'Selasa', '08:00 - 10:00'),
(39,  10, 'Senin',  '10:00 - 12:00'),
(40,  10, 'Sabtu',  '06:00 - 08:00'),
(41,  10, 'Minggu', '12:00 - 14:00'),
(42,  11, 'Senin',  '10:00 - 12:00'),
(43,  11, 'Rabu',   '12:00 - 14:00'),
(44,  11, 'Jumat',  '14:00 - 16:00'),
(45,  11, 'Minggu', '06:00 - 08:00'),
(46,  12, 'Selasa', '08:00 - 10:00'),
(47,  12, 'Kamis',  '06:00 - 08:00'),
(48,  12, 'Sabtu',  '12:00 - 14:00'),
(49,  12, 'Minggu', '18:00 - 20:00'),
(50,  13, 'Senin',  '08:00 - 10:00'),
(51,  13, 'Rabu',   '10:00 - 12:00'),
(52,  13, 'Kamis',  '12:00 - 14:00'),
(53,  13, 'Sabtu',  '14:00 - 16:00'),
(54,  14, 'Selasa', '06:00 - 08:00'),
(55,  14, 'Kamis',  '08:00 - 10:00'),
(56,  14, 'Jumat',  '10:00 - 12:00'),
(57,  14, 'Minggu', '12:00 - 14:00'),
(58,  15, 'Senin',  '14:00 - 16:00'),
(59,  15, 'Rabu',   '16:00 - 18:00'),
(60,  15, 'Kamis',  '08:00 - 10:00'),
(61,  15, 'Sabtu',  '10:00 - 12:00'),
(62,  16, 'Selasa', '08:00 - 10:00'),
(63,  16, 'Jumat',  '14:00 - 16:00'),
(64,  16, 'Sabtu',  '06:00 - 08:00'),
(65,  16, 'Minggu', '08:00 - 10:00'),
(66,  17, 'Senin',  '10:00 - 12:00'),
(67,  17, 'Rabu',   '12:00 - 14:00'),
(68,  17, 'Jumat',  '08:00 - 10:00'),
(69,  17, 'Minggu', '14:00 - 16:00'),
(70,  18, 'Selasa', '12:00 - 14:00'),
(71,  18, 'Kamis',  '14:00 - 16:00'),
(72,  18, 'Sabtu',  '08:00 - 10:00'),
(73,  18, 'Minggu', '06:00 - 08:00'),
(74,  19, 'Senin',  '08:00 - 10:00'),
(75,  19, 'Selasa', '10:00 - 12:00'),
(76,  19, 'Jumat',  '06:00 - 08:00'),
(77,  19, 'Sabtu',  '14:00 - 16:00'),
(78,  20, 'Rabu',   '10:00 - 12:00'),
(79,  20, 'Kamis',  '12:00 - 14:00'),
(80,  20, 'Jumat',  '14:00 - 16:00'),
(81,  20, 'Minggu', '08:00 - 10:00'),
(82,  21, 'Senin',  '06:00 - 08:00'),
(83,  21, 'Selasa', '08:00 - 10:00'),
(84,  21, 'Rabu',   '10:00 - 12:00'),
(85,  21, 'Jumat',  '12:00 - 14:00'),
(86,  22, 'Kamis',  '14:00 - 16:00'),
(87,  22, 'Jumat',  '16:00 - 18:00'),
(88,  22, 'Sabtu',  '08:00 - 10:00'),
(89,  22, 'Minggu', '10:00 - 12:00'),
(90,  23, 'Senin',  '10:00 - 12:00'),
(91,  23, 'Rabu',   '12:00 - 14:00'),
(92,  23, 'Kamis',  '08:00 - 10:00'),
(93,  23, 'Minggu', '06:00 - 08:00'),
(94,  24, 'Selasa', '14:00 - 16:00'),
(95,  24, 'Kamis',  '06:00 - 08:00'),
(96,  24, 'Sabtu',  '10:00 - 12:00'),
(97,  24, 'Minggu', '12:00 - 14:00'),
(98,  25, 'Senin',  '08:00 - 10:00'),
(99,  25, 'Rabu',   '14:00 - 16:00'),
(100, 25, 'Jumat',  '12:00 - 14:00'),
(101, 25, 'Sabtu',  '06:00 - 08:00'),
(102, 26, 'Selasa', '08:00 - 10:00'),
(103, 26, 'Rabu',   '10:00 - 12:00'),
(104, 26, 'Kamis',  '14:00 - 16:00'),
(105, 26, 'Minggu', '08:00 - 10:00'),
(106, 27, 'Senin',  '06:00 - 08:00'),
(107, 27, 'Selasa', '08:00 - 10:00'),
(108, 27, 'Jumat',  '10:00 - 12:00'),
(109, 27, 'Minggu', '14:00 - 16:00'),
(110, 28, 'Rabu',   '12:00 - 14:00'),
(111, 28, 'Kamis',  '08:00 - 10:00'),
(112, 28, 'Jumat',  '06:00 - 08:00'),
(113, 28, 'Sabtu',  '14:00 - 16:00'),
(114, 29, 'Senin',  '08:00 - 10:00'),
(115, 29, 'Selasa', '10:00 - 12:00'),
(116, 29, 'Rabu',   '06:00 - 08:00'),
(117, 29, 'Minggu', '08:00 - 10:00'),
(118, 30, 'Kamis',  '10:00 - 12:00'),
(119, 30, 'Jumat',  '12:00 - 14:00'),
(120, 30, 'Sabtu',  '06:00 - 08:00'),
(121, 30, 'Minggu', '14:00 - 16:00'),
(122, 31, 'Senin',  '06:00 - 08:00'),
(123, 31, 'Rabu',   '08:00 - 10:00'),
(124, 31, 'Jumat',  '10:00 - 12:00'),
(125, 31, 'Sabtu',  '12:00 - 14:00'),
(126, 32, 'Selasa', '10:00 - 12:00'),
(127, 32, 'Rabu',   '12:00 - 14:00'),
(128, 32, 'Jumat',  '14:00 - 16:00'),
(129, 32, 'Minggu', '06:00 - 08:00'),
(130, 33, 'Senin',  '08:00 - 10:00'),
(131, 33, 'Kamis',  '10:00 - 12:00'),
(132, 33, 'Jumat',  '12:00 - 14:00'),
(133, 33, 'Sabtu',  '06:00 - 08:00'),
(134, 34, 'Selasa', '14:00 - 16:00'),
(135, 34, 'Rabu',   '06:00 - 08:00'),
(136, 34, 'Jumat',  '08:00 - 10:00'),
(137, 34, 'Minggu', '10:00 - 12:00'),
(138, 35, 'Senin',  '10:00 - 12:00'),
(139, 35, 'Rabu',   '12:00 - 14:00'),
(140, 35, 'Sabtu',  '14:00 - 16:00'),
(141, 35, 'Minggu', '08:00 - 10:00'),
(142, 36, 'Selasa', '08:00 - 10:00'),
(143, 36, 'Kamis',  '12:00 - 14:00'),
(144, 36, 'Jumat',  '06:00 - 08:00'),
(145, 36, 'Minggu', '06:00 - 08:00'),
(146, 37, 'Selasa', '12:00 - 14:00'),
(147, 39, 'Selasa', '06:00 - 08:00');
