-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 17, 2026 at 04:59 PM
-- Server version: 5.7.39
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbboardgame`
--

-- --------------------------------------------------------

--
-- Table structure for table `cash_flow`
--

CREATE TABLE `cash_flow` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_rental` bigint(20) UNSIGNED DEFAULT NULL,
  `tipe_transaksi` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pendapatan;pengeluaran',
  `metode_pembayaran` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tunai;transfer;qris;kartu;lainnya',
  `total` decimal(15,3) NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waktu_pembayaran` datetime NOT NULL,
  `bukti_transaksi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idc` bigint(20) UNSIGNED DEFAULT NULL,
  `idm` bigint(20) UNSIGNED DEFAULT NULL,
  `doc` datetime DEFAULT NULL,
  `dom` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_flow`
--

INSERT INTO `cash_flow` (`id`, `id_rental`, `tipe_transaksi`, `metode_pembayaran`, `total`, `keterangan`, `waktu_pembayaran`, `bukti_transaksi`, `idc`, `idm`, `doc`, `dom`) VALUES
(1, 2, 'income', 'transfer', 60325.000, 'Sewa meja Meja 1 (Toko ABC) — Customer B', '2026-05-14 16:27:38', 'cash-flow-bukti/oPdlL0TeM3Y0A9qy9ai17fRQgakQD4YSYWCdNCcn.jpg', 2, 2, '2026-05-14 16:27:38', '2026-05-15 15:49:53'),
(2, 3, 'income', 'transfer', 22008.333, 'Sewa meja Meja 1 (Toko ABC) — Customer 3', '2026-05-15 16:44:26', NULL, 2, 2, '2026-05-15 16:44:26', '2026-05-16 04:42:32'),
(3, 4, 'income', 'kartu', 252083.333, 'Sewa meja Meja 1 (Toko ABC 2) — Customer A', '2026-05-16 04:42:44', NULL, 2, 4, '2026-05-16 04:42:44', '2026-05-16 05:24:55'),
(4, 5, 'income', NULL, 2168.056, 'Sewa meja Meja 3 (Toko ABC) — Customer Mandiri 1', '2026-05-17 04:39:42', NULL, 2, 2, '2026-05-17 04:39:42', '2026-05-17 04:39:42'),
(5, 6, 'income', NULL, 1883.333, 'Sewa meja Meja 4 (Toko ABC) — Customer Mandiri 2', '2026-05-17 04:40:14', NULL, 0, 0, '2026-05-17 04:40:14', '2026-05-17 04:40:14'),
(6, 7, 'income', NULL, 377.778, 'Sewa meja Meja 1 (Toko ABC 2) — Customer Mandiri 1', '2026-05-17 04:46:45', NULL, 0, 0, '2026-05-17 04:46:45', '2026-05-17 04:46:45');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2026_05_14_000001_add_status_to_m_meja_table', 2),
(6, '2026_05_14_000003_create_cash_flow_table', 3),
(7, '2026_05_16_000001_add_id_toko_to_m_users_table', 4),
(8, '2026_05_16_000002_add_guest_token_to_rental_table', 5);

-- --------------------------------------------------------

--
-- Table structure for table `m_meja`
--

CREATE TABLE `m_meja` (
  `id` int(11) NOT NULL,
  `id_toko` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `harga` int(11) NOT NULL,
  `status` varchar(30) NOT NULL COMMENT 'active,rented,inactive',
  `idc` int(11) NOT NULL,
  `doc` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `idm` int(11) NOT NULL,
  `dom` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `m_meja`
--

INSERT INTO `m_meja` (`id`, `id_toko`, `nama`, `harga`, `status`, `idc`, `doc`, `idm`, `dom`) VALUES
(5, 2, 'Meja 1', 30000, 'active', 1, '2026-05-16 21:11:30', 1, '2026-05-16 21:11:30'),
(6, 2, 'Meja 2', 50000, 'active', 1, '2026-05-16 21:11:30', 1, '2026-05-16 21:11:30'),
(7, 2, 'Meja 3', 35000, 'active', 1, '2026-05-16 21:11:30', 1, '2026-05-17 04:39:42'),
(8, 2, 'Meja 4', 30000, 'active', 1, '2026-05-16 21:11:30', 1, '2026-05-17 04:40:14'),
(9, 2, 'Meja 5', 30000, 'active', 1, '2026-05-16 21:11:30', 1, '2026-05-16 21:11:30'),
(13, 3, 'Meja 1', 20000, 'active', 1, '2026-05-16 21:18:53', 1, '2026-05-17 04:46:45'),
(14, 3, 'Meja 2', 25000, 'active', 1, '2026-05-16 21:18:53', 1, '2026-05-16 21:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `m_toko`
--

CREATE TABLE `m_toko` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `jumlah_meja` int(11) NOT NULL,
  `doc` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `idm` int(11) NOT NULL,
  `dom` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `m_toko`
--

INSERT INTO `m_toko` (`id`, `nama`, `alamat`, `jumlah_meja`, `doc`, `idm`, `dom`) VALUES
(2, 'Toko ABC', 'Jl. Utama No. 100', 5, '2026-05-09 22:09:54', 1, '2026-05-16 21:11:30'),
(3, 'Toko ABC 2', 'Jl. Alamat Toko ABC 2', 2, '2026-05-15 09:05:51', 1, '2026-05-16 21:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `m_users`
--

CREATE TABLE `m_users` (
  `id` int(11) NOT NULL,
  `id_toko` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT '1',
  `doc` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dom` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `m_users`
--

INSERT INTO `m_users` (`id`, `id_toko`, `nama`, `username`, `password`, `remember_token`, `role`, `is_active`, `doc`, `dom`) VALUES
(2, 0, 'Administrator', 'admin', '$2y$10$f.syDduEebtyaGWaAEpJRuvXg/I2t1sy7cJKfHihakTnqxJ7NgRuy', '', 'admin', 1, '2026-05-05 15:53:25', '2026-05-10 04:46:40'),
(3, 2, 'Kasir ABC', 'toko_abc', '$2y$10$IeIMpAxsGgUK5Zef2839NOWBRgwvyjjuUCzeiW6GayPP3RiZaDKRi', '', 'cashier', 1, '2026-05-05 15:55:54', '2026-05-17 04:21:26'),
(4, 3, 'Kasir ABC 2', 'toko_abc2', '$2y$10$NLhIhGo89JmiH1e0yEA8cOAXuSCYPeaddY29mpjZTiqG/PTukowe2', NULL, 'cashier', 1, '2026-05-16 05:24:08', '2026-05-16 05:24:08');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental`
--

CREATE TABLE `rental` (
  `id` int(11) NOT NULL,
  `id_meja` int(11) NOT NULL,
  `nama_customer` varchar(255) NOT NULL,
  `waktu_start` datetime NOT NULL,
  `waktu_end` datetime DEFAULT NULL,
  `total_durasi` int(11) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `total_harga` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL COMMENT 'active;completed;paid',
  `guest_token` varchar(64) DEFAULT NULL,
  `idc` int(11) NOT NULL DEFAULT '0',
  `doc` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `idm` int(11) NOT NULL DEFAULT '0',
  `dom` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `rental`
--

INSERT INTO `rental` (`id`, `id_meja`, `nama_customer`, `waktu_start`, `waktu_end`, `total_durasi`, `harga`, `total_harga`, `status`, `guest_token`, `idc`, `doc`, `idm`, `dom`) VALUES
(1, 1, 'Customer 1', '2026-05-14 02:30:09', '2026-05-14 02:35:07', 5, 30000, 2483, 'completed', NULL, 0, '2026-05-14 02:30:09', 0, '2026-05-14 02:35:07'),
(2, 1, 'Customer B', '2026-05-14 14:26:59', '2026-05-14 16:27:38', 121, 30000, 60325, 'completed', NULL, 0, '2026-05-14 14:26:59', 0, '2026-05-14 16:27:38'),
(3, 1, 'Customer 3', '2026-05-15 16:00:25', '2026-05-15 16:44:26', 44, 30000, 22008, 'completed', NULL, 0, '2026-05-15 16:00:25', 0, '2026-05-15 16:44:26'),
(4, 2, 'Customer A', '2026-05-15 16:06:29', '2026-05-16 04:42:44', 756, 20000, 252083, 'completed', NULL, 0, '2026-05-15 16:06:29', 0, '2026-05-16 04:42:44'),
(5, 7, 'Customer Mandiri 1', '2026-05-17 04:35:59', '2026-05-17 04:39:42', 4, 35000, 2168, 'completed', '3pfJ1m36628ZkOfR1rpQdZuZJ7Dgnh3OMvbVgfUJH7I9mAdu', 0, '2026-05-17 04:35:59', 0, '2026-05-17 04:39:42'),
(6, 8, 'Customer Mandiri 2', '2026-05-17 04:36:28', '2026-05-17 04:40:14', 4, 30000, 1883, 'completed', NULL, 0, '2026-05-17 04:36:28', 0, '2026-05-17 04:40:14'),
(7, 13, 'Customer Mandiri 1', '2026-05-17 04:45:37', '2026-05-17 04:46:45', 1, 20000, 378, 'completed', NULL, 0, '2026-05-17 04:45:37', 0, '2026-05-17 04:46:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cash_flow`
--
ALTER TABLE `cash_flow`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `m_meja`
--
ALTER TABLE `m_meja`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `m_toko`
--
ALTER TABLE `m_toko`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `m_users`
--
ALTER TABLE `m_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_idx` (`username`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `rental`
--
ALTER TABLE `rental`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rental_guest_token_unique` (`guest_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cash_flow`
--
ALTER TABLE `cash_flow`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `m_meja`
--
ALTER TABLE `m_meja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `m_toko`
--
ALTER TABLE `m_toko`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `m_users`
--
ALTER TABLE `m_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental`
--
ALTER TABLE `rental`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
