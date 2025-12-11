-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 10, 2025 at 11:55 PM
-- Server version: 5.7.39
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ptun_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','alfa','izin') COLLATE utf8mb4_unicode_ci DEFAULT 'alfa',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `approved` tinyint(1) DEFAULT '0',
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `peserta_id`, `tanggal`, `status`, `catatan`, `approved`, `approved_at`, `created_at`) VALUES
(3, 3, '2025-12-01', 'izin', 'Sakit flu', 0, NULL, '2025-12-10 04:44:47'),
(4, 4, '2025-12-01', 'alfa', 'Belum mulai', 0, NULL, '2025-12-10 04:44:47'),
(5, 5, '2025-12-01', 'hadir', NULL, 0, NULL, '2025-12-10 04:44:47'),
(6, 5, '2025-12-02', 'hadir', NULL, 0, NULL, '2025-12-10 04:44:47'),
(7, 3, '2025-12-10', 'hadir', 'test', 1, '2025-12-10 06:54:23', '2025-12-10 06:52:47'),
(8, 9, '2025-12-15', 'hadir', 'Hadir untuk uji sistem', 1, NULL, '2025-12-10 22:01:19'),
(9, 11, '2025-12-07', 'hadir', 'Hadir tepat waktu', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(10, 11, '2025-12-08', 'hadir', 'Hadir', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(11, 11, '2025-12-09', 'hadir', 'Hadir', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(12, 11, '2025-12-10', 'hadir', 'Hadir', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(13, 11, '2025-12-11', 'hadir', 'Hadir - Hari Terakhir', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(16, 4, '2025-12-10', 'hadir', 'test', 0, NULL, '2025-12-10 23:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `aktivitas`
--

CREATE TABLE `aktivitas` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `bukti_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `approved` tinyint(1) DEFAULT '0',
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `aktivitas`
--

INSERT INTO `aktivitas` (`id`, `peserta_id`, `tanggal`, `deskripsi`, `bukti_file`, `jam_mulai`, `jam_selesai`, `approved`, `approved_at`, `created_at`) VALUES
(1, 3, '2025-12-10', 'apalaaa test', 'uploads/perkara/perkara_3_1765349595.png', '01:53:00', '01:57:00', 0, NULL, '2025-12-10 06:53:15'),
(2, 9, '2025-12-15', 'Melakukan input data dan pengecekan sistem.', NULL, '08:00:00', '12:00:00', 1, NULL, '2025-12-10 22:01:19'),
(3, 11, '2025-12-07', 'Orientasi lingkungan kerja dan perkenalan staff', NULL, '08:00:00', '16:00:00', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(4, 11, '2025-12-08', 'Mempelajari berkas perkara Tata Usaha Negara', NULL, '08:30:00', '12:00:00', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(5, 11, '2025-12-09', 'Mengikuti jalannya persidangan di Ruang Sidang Utama', NULL, '09:00:00', '11:00:00', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(6, 11, '2025-12-10', 'Membantu administrasi panitera pengganti', NULL, '13:00:00', '15:00:00', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(7, 11, '2025-12-11', 'Menyusun laporan akhir magang dan perpisahan', NULL, '08:00:00', '14:00:00', 1, '2025-12-10 22:23:52', '2025-12-10 22:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_ringkasan`
--

CREATE TABLE `laporan_ringkasan` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `periode_start` date NOT NULL,
  `periode_end` date NOT NULL,
  `total_hadir` int(11) DEFAULT '0',
  `total_alfa` int(11) DEFAULT '0',
  `total_perkara` int(11) DEFAULT '0',
  `ringkasan` text COLLATE utf8mb4_unicode_ci,
  `approved` tinyint(1) DEFAULT '0',
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laporan_ringkasan`
--

INSERT INTO `laporan_ringkasan` (`id`, `peserta_id`, `periode_start`, `periode_end`, `total_hadir`, `total_alfa`, `total_perkara`, `ringkasan`, `approved`, `approved_at`) VALUES
(1, 9, '2025-12-10', '2025-12-17', 1, 0, 0, 'Ringkasan kegiatan untuk peserta demo.', 1, NULL),
(2, 11, '2025-11-11', '2025-12-11', 20, 0, 5, 'Peserta telah mengikuti seluruh rangkaian kegiatan magang dengan sangat baik, disiplin, dan mampu memahami alur persidangan PTUN.', 1, '2025-12-10 22:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` enum('info','warning','success','error') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `dibaca` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `to_user_id`, `title`, `pesan`, `tipe`, `dibaca`, `created_at`) VALUES
(2, 3, 'Absensi Perlu Verifikasi', 'Absensi 1 Desember status izin perlu konfirmasi dokumen', 'warning', 0, '2025-12-10 04:44:47'),
(3, 7, 'Registrasi Disetujui', 'Selamat! Akun Anda telah diaktifkan oleh admin', 'success', 0, '2025-12-10 10:35:43'),
(4, 3, 'test notif', 'tststststs', 'warning', 0, '2025-12-14 11:37:23'),
(5, 3, 'cdscd', 'test tet', 'success', 0, '2025-12-14 11:37:39'),
(6, 3, 'cdscd', 'dcdsc', 'success', 0, '2025-12-14 11:37:47'),
(7, 3, 'cdcsd', 'cdcdsc', 'warning', 1, '2025-12-14 11:38:01'),
(8, 11, 'Selamat Datang', 'Akun Anda telah aktif. Selamat menjalankan magang!', 'info', 1, '2025-12-10 22:23:52'),
(9, 11, 'Aktivitas Disetujui', 'Laporan aktivitas harian Anda telah disetujui pembimbing.', 'success', 1, '2025-12-10 22:23:52'),
(10, 11, 'Penilaian Selesai', 'Nilai akhir magang Anda telah diterbitkan. Selamat!', 'success', 1, '2025-12-10 22:23:52'),
(11, 11, 'Sertifikat Terbit', 'Sertifikat digital Anda sudah tersedia untuk diunduh.', 'warning', 0, '2025-12-10 22:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `disiplin` int(11) DEFAULT '0',
  `kerjasama` int(11) DEFAULT '0',
  `inisiatif` int(11) DEFAULT '0',
  `kerajinan` int(11) DEFAULT '0',
  `kualitas_kerja` int(11) DEFAULT '0',
  `nilai_rata_rata` decimal(5,2) DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id`, `peserta_id`, `disiplin`, `kerjasama`, `inisiatif`, `kerajinan`, `kualitas_kerja`, `nilai_rata_rata`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 9, 90, 85, 88, 92, 95, 90.00, 'Peserta menunjukkan kinerja yang sangat baik.', '2025-12-10 22:01:19', '2025-12-10 22:01:19'),
(2, 10, 95, 90, 92, 95, 88, 92.00, 'Sangat Memuaskan', '2025-12-10 22:19:19', '2025-12-10 22:19:19'),
(3, 11, 95, 90, 88, 95, 92, 92.00, 'Sangat memuaskan, pertahankan kinerja Anda!', '2025-12-10 22:23:52', '2025-12-10 22:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `sertifikat`
--

CREATE TABLE `sertifikat` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `nomor_sertifikat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `penilaian_final` decimal(3,1) DEFAULT '0.0',
  `status` enum('pending','tersedia','selesai') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sertifikat`
--

INSERT INTO `sertifikat` (`id`, `peserta_id`, `nomor_sertifikat`, `file_path`, `issued_date`, `penilaian_final`, `status`, `created_at`) VALUES
(1, 3, 'SERT/2025/0003', 'uploads/sertifikat/sertifikat_3_1765349705.pdf', '2025-12-10', 10.0, 'tersedia', '2025-12-10 06:55:05'),
(2, 5, 'SERT-2025-0005', NULL, '2025-12-10', 92.0, 'selesai', '2025-12-10 08:00:23'),
(3, 9, 'SERT/2025/0009', 'uploads/sertifikat/sertifikat_9_demo.pdf', '2025-12-15', 90.0, 'tersedia', '2025-12-10 22:01:19'),
(4, 10, '003/PTUN/MAGANG/XII/2024', 'sertifikat_dummy.pdf', '2025-12-11', 92.0, 'tersedia', '2025-12-10 22:19:19'),
(5, 11, 'SERT/PTUN/2024/011', 'dummy_file.pdf', '2025-12-11', 92.0, 'tersedia', '2025-12-10 22:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` enum('text','textarea','url','image','number','boolean','date') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `group` enum('institusi','footer','menu','sistem') COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int(11) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `order`, `updated_at`) VALUES
(1, 'nama_website', 'PTUN Banjarmasinn', 'text', 'institusi', 1, '2025-12-14 12:12:13'),
(2, 'nama_panjang', 'Pengadilan Tata Usaha Negara Banjarmasin', 'text', 'institusi', 2, '2025-12-10 04:44:47'),
(3, 'tagline', 'Keadilan Administrasi Negara', 'text', 'institusi', 3, '2025-12-10 04:44:47'),
(4, 'logo_url', '/uploads/logos/logo_1765714349.jpg', 'image', 'institusi', 4, '2025-12-14 12:12:29'),
(5, 'alamat_lengkap', 'Jl. Court of Justice No.123, Banjarmasin', 'textarea', 'institusi', 5, '2025-12-10 04:44:47'),
(6, 'no_telepon', '0511-123456', 'text', 'institusi', 6, '2025-12-10 04:44:47'),
(7, 'email_kontak', 'info@ptun-bjm.go.id', 'text', 'institusi', 7, '2025-12-10 04:44:47'),
(8, 'copyright_text', '© 2025 PTUN Banjarmasin. Hak Cipta Dilindungi.', 'text', 'footer', 10, '2025-12-10 04:44:47'),
(9, 'social_facebook', 'https://facebook.com/ptunbjm', 'url', 'footer', 11, '2025-12-10 04:44:47'),
(10, 'social_instagram', 'https://instagram.com/ptunbjm', 'url', 'footer', 12, '2025-12-10 04:44:47'),
(11, 'menu_beranda', 'Beranda|/', 'text', 'menu', 20, '2025-12-10 04:44:47'),
(12, 'menu_tentang', 'Tentang PTUN|/tentang', 'text', 'menu', 21, '2025-12-10 04:44:47'),
(13, 'menu_layanan', 'Layanan|/layanan', 'text', 'menu', 22, '2025-12-10 04:44:47'),
(14, 'maintenance_mode', '0', 'boolean', 'sistem', 1, '2025-12-10 07:45:46'),
(15, 'absensi_start_date', '2025-01-01', 'date', 'sistem', 31, '2025-12-10 04:44:47'),
(16, 'absensi_max_hari', '22', 'number', 'sistem', 32, '2025-12-10 07:45:46'),
(17, 'perkara_max_perday', '5', 'number', 'sistem', 36, '2025-12-10 07:45:46'),
(22, 'sertifikat_min_hadir', '80', 'number', 'sistem', 41, '2025-12-10 08:00:23'),
(23, 'sertifikat_min_score', '75', 'number', 'sistem', 42, '2025-12-10 08:00:23'),
(24, 'sertifikat_bobot_hadir', '60', 'number', 'sistem', 43, '2025-12-10 08:00:23'),
(25, 'sertifikat_bobot_laporan', '40', 'number', 'sistem', 44, '2025-12-10 08:00:23'),
(26, 'kota_instansi', 'Banjarmasin', 'text', 'institusi', 10, '2025-12-10 12:52:51'),
(27, 'kepala_nama', 'BAPAK KAU', 'text', 'institusi', 11, '2025-12-10 12:57:19'),
(28, 'kepala_nip', '324433', 'text', 'institusi', 12, '2025-12-10 12:57:19'),
(29, 'kepala_jabatan', 'KIW KIW IMAN', 'text', 'institusi', 13, '2025-12-10 12:57:19'),
(30, 'pembimbing_nama', 'IMANNN', 'text', 'institusi', 14, '2025-12-10 12:57:19'),
(31, 'pembimbing_nip', '01999292', 'text', 'institusi', 15, '2025-12-10 12:57:19'),
(32, 'favicon', '/uploads/logos/favicon_1765402640.jpg', 'text', 'institusi', 20, '2025-12-10 21:37:20'),
(44, 'ttd_img_kepala', '/uploads/sertifikat/ttd_img_kepala_1765407840.jpg', 'image', 'institusi', 0, '2025-12-10 23:04:00'),
(45, 'ttd_img_pembimbing', '/uploads/sertifikat/ttd_img_pembimbing_1765407840.jpg', 'image', 'institusi', 0, '2025-12-10 23:04:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','peserta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'peserta',
  `instansi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurusan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '/uploads/profiles/default.jpg',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `status` enum('aktif','pending','selesai') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `instansi`, `jurusan`, `profile_foto`, `bio`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin SIM-PTUN', 'admin@ptun-bjm.go.id', 'admin123', 'admin', NULL, NULL, '/uploads/profiles/default.jpg', 'test bio admin', 'aktif', '2025-12-10 04:44:47', '2025-12-10 10:59:17'),
(3, 'Andi Saputra', 'andi@uniska.ac.id', 'andi123', 'peserta', 'UNISKA Banjarmasin', 'Informatika', '/uploads/profiles/default.jpg', 'Sering telat tapi kerja bagus', 'aktif', '2025-12-10 04:44:47', '2025-12-10 07:10:10'),
(4, 'Siti Aminah', 'siti@smp2.sch.id', 'siti123', 'peserta', 'SMP 2 Banjarmasin', 'MI', '/uploads/profiles/default.jpg', 'Sering absen, butuh bimbingan', 'aktif', '2025-12-10 04:44:47', '2025-12-10 04:44:47'),
(5, 'Budi Santoso', 'budi@smk3.sch.id', 'budi123', 'peserta', 'SMK 3 Banjarmasin', 'Teknik Komputer', '/uploads/profiles/default.jpg', 'Belum mulai magang', 'pending', '2025-12-10 04:44:47', '2025-12-10 04:44:47'),
(6, 'Rina Kartika', 'rina@unlam.ac.id', 'rina123', 'peserta', 'UNLAM Banjarmasin', 'Sistem Informasi', '/uploads/profiles/default.jpg', 'Magang perfect, sertifikat ready', 'selesai', '2025-12-10 04:44:47', '2025-12-10 04:44:47'),
(7, 'test', 'test@test', 'test', 'peserta', 'test sekolah', 'test jurusan', '/uploads/profiles/default.jpg', NULL, 'aktif', '2025-12-10 10:14:55', '2025-12-10 10:35:43'),
(8, 'test2', 'test1@test', 'test', 'peserta', 'scdscdscsc', 'test jurusan', '/uploads/profiles/default.jpg', NULL, 'selesai', '2025-12-14 11:03:24', '2025-12-14 11:43:31'),
(9, 'Peserta Demo', 'demo@ptun.ac.id', 'demo123', 'peserta', 'UNISKA Banjarmasin', 'Teknik Informatika', '/uploads/profiles/default.jpg', 'Peserta demo untuk uji sertifikat.', 'aktif', '2025-12-10 22:01:19', '2025-12-10 22:02:39'),
(10, 'Aditya Tester Fix', 'aditya_fix@test.com', '12345', 'peserta', 'Universitas Indonesia', 'Hukum Tata Negara', '/uploads/profiles/default.jpg', NULL, 'aktif', '2025-12-10 22:19:19', '2025-12-10 22:19:19'),
(11, 'Aditya Tester Fix', 'aditya_full_466@test.com', '12345', 'peserta', 'Universitas Indonesia', 'Hukum Tata Negara', '/uploads/profiles/default.jpg', NULL, 'aktif', '2025-12-10 22:23:52', '2025-12-10 22:23:52'),
(12, 'Aditya Manual', 'aditya_manual_01@test.com', '12345', 'peserta', 'Univ Test', 'Hukum', '/uploads/profiles/default.jpg', NULL, 'aktif', '2025-12-10 22:30:25', '2025-12-10 22:30:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_absensi` (`peserta_id`,`tanggal`);

--
-- Indexes for table `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indexes for table `laporan_ringkasan`
--
ALTER TABLE `laporan_ringkasan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_laporan_ringkasan` (`peserta_id`,`periode_start`,`periode_end`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `to_user_id` (`to_user_id`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_peserta` (`peserta_id`);

--
-- Indexes for table `sertifikat`
--
ALTER TABLE `sertifikat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_sertifikat` (`nomor_sertifikat`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `aktivitas`
--
ALTER TABLE `aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `laporan_ringkasan`
--
ALTER TABLE `laporan_ringkasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sertifikat`
--
ALTER TABLE `sertifikat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD CONSTRAINT `aktivitas_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_ringkasan`
--
ALTER TABLE `laporan_ringkasan`
  ADD CONSTRAINT `laporan_ringkasan_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD CONSTRAINT `penilaian_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sertifikat`
--
ALTER TABLE `sertifikat`
  ADD CONSTRAINT `sertifikat_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
