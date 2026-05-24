-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 21, 2026 at 03:44 PM
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
  `jumlah_bayar` decimal(15,3) DEFAULT NULL,
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

INSERT INTO `cash_flow` (`id`, `id_rental`, `tipe_transaksi`, `metode_pembayaran`, `total`, `jumlah_bayar`, `keterangan`, `waktu_pembayaran`, `bukti_transaksi`, `idc`, `idm`, `doc`, `dom`) VALUES
(1, 1, 'income', 'kartu', 38500.000, 38500.000, 'Sewa meja Meja 4 (Toko ABC) — Maya Lestari #001', '2026-04-07 20:52:00', NULL, 0, 0, '2026-04-07 20:52:00', '2026-04-07 20:52:00'),
(2, 2, 'income', 'transfer', 41416.667, 41416.667, 'Sewa meja Meja 3 (Toko ABC) — Dewi Anggraini #002', '2026-05-14 02:05:00', 'cash-flow-bukti/dummy-8dfhefactlgaohaxknoh9fi6.jpg', 0, 0, '2026-05-14 02:05:00', '2026-05-14 02:05:00'),
(3, 3, 'income', 'tunai', 69583.333, 69583.333, 'Sewa meja Meja 2 (Toko ABC 2) — Yoga Prasetyo #003', '2026-03-06 03:25:00', NULL, 0, 0, '2026-03-06 03:25:00', '2026-03-06 03:25:00'),
(4, 4, 'income', 'transfer', 101666.667, 101666.667, 'Sewa meja Meja 2 (Toko ABC) — Vina Melati #004', '2026-04-01 00:22:00', 'cash-flow-bukti/dummy-cknrrmebv8wgfbq0k4nlaeif.jpg', 0, 0, '2026-04-01 00:22:00', '2026-04-01 00:22:00'),
(5, 5, 'income', 'tunai', 26000.000, 26000.000, 'Sewa meja Meja 1 (Toko ABC) — Maya Lestari #005', '2026-03-05 04:48:00', NULL, 0, 0, '2026-03-05 04:48:00', '2026-03-05 04:48:00'),
(6, 6, 'income', 'tunai', 93500.000, 93500.000, 'Sewa meja Meja 5 (Toko ABC) — Indah Puspita #006', '2026-05-09 02:22:00', NULL, 0, 0, '2026-05-09 02:22:00', '2026-05-09 02:22:00'),
(7, 7, 'income', 'tunai', 37000.000, 37000.000, 'Sewa meja Meja 4 (Toko ABC) — Gita Permata #007', '2026-05-19 06:48:00', NULL, 0, 0, '2026-05-19 06:48:00', '2026-05-19 06:48:00'),
(8, 8, 'income', 'lainnya', 160833.333, 160833.333, 'Sewa meja Meja 2 (Toko ABC) — Lukman Hakim #008', '2026-05-10 23:42:00', NULL, 0, 0, '2026-05-10 23:42:00', '2026-05-10 23:42:00'),
(9, 9, 'income', NULL, 22500.000, NULL, 'Sewa meja Meja 4 (Toko ABC) — Hendra Gunawan #009', '2026-05-13 07:26:00', NULL, 0, 0, '2026-05-13 07:26:00', '2026-05-13 07:26:00'),
(10, 10, 'income', 'qris', 59166.667, NULL, 'Sewa meja Meja 2 (Toko ABC 2) — Salsa Bintang #010', '2026-04-04 05:15:00', NULL, 0, 0, '2026-04-04 05:15:00', '2026-04-04 05:15:00'),
(11, 11, 'income', 'lainnya', 118500.000, 118500.000, 'Sewa meja Meja 1 (Toko ABC) — Dedi Pratama #011', '2026-05-15 22:19:00', NULL, 0, 0, '2026-05-15 22:19:00', '2026-05-15 22:19:00'),
(12, 12, 'income', 'lainnya', 9500.000, 9500.000, 'Sewa meja Meja 1 (Toko ABC) — Eko Wibowo #012', '2026-04-27 22:55:00', 'cash-flow-bukti/dummy-rcmoxwcdh04muirqpoztjuma.jpg', 0, 0, '2026-04-27 22:55:00', '2026-04-27 22:55:00'),
(13, 13, 'income', 'transfer', 53000.000, 47170.000, 'Sewa meja Meja 4 (Toko ABC) — Siti Rahayu #013', '2026-05-16 04:46:00', 'cash-flow-bukti/dummy-xiurbngdnrb8kzqudmnkutlm.jpg', 0, 0, '2026-05-16 04:46:00', '2026-05-16 04:46:00'),
(14, 14, 'income', 'transfer', 25833.333, 25833.333, 'Sewa meja Meja 2 (Toko ABC) — Oki Setiawan #014', '2026-03-03 02:44:00', 'cash-flow-bukti/dummy-n6bya3ztftjmnptsb8ou6rno.jpg', 0, 0, '2026-03-03 02:44:00', '2026-03-03 02:44:00'),
(15, 15, 'income', 'tunai', 84166.667, 84166.667, 'Sewa meja Meja 2 (Toko ABC) — Oki Setiawan #015', '2026-04-17 06:24:00', NULL, 0, 0, '2026-04-17 06:24:00', '2026-04-17 06:24:00'),
(16, 16, 'income', 'qris', 103000.000, 103000.000, 'Sewa meja Meja 1 (Toko ABC) — Joko Widodo #016', '2026-04-27 02:48:00', NULL, 0, 0, '2026-04-27 02:48:00', '2026-04-27 02:48:00'),
(17, 17, 'income', 'tunai', 35000.000, 35000.000, 'Sewa meja Meja 5 (Toko ABC) — Agus Hermawan #017', '2026-05-13 03:45:00', NULL, 0, 0, '2026-05-13 03:45:00', '2026-05-13 03:45:00'),
(18, 18, 'income', 'tunai', 60000.000, 60000.000, 'Sewa meja Meja 2 (Toko ABC 2) — Kartika Sari #018', '2026-04-02 04:55:00', NULL, 0, 0, '2026-04-02 04:55:00', '2026-04-02 04:55:00'),
(19, 19, 'income', 'lainnya', 20416.667, 16945.834, 'Sewa meja Meja 2 (Toko ABC 2) — Dedi Pratama #019', '2026-05-20 17:32:00', 'cash-flow-bukti/dummy-1bzjioswnck8ornwlynz52cu.jpg', 0, 0, '2026-05-20 17:32:00', '2026-05-20 17:32:00'),
(20, 20, 'income', 'lainnya', 82500.000, 82500.000, 'Sewa meja Meja 1 (Toko ABC) — Zaki Ramadhan #020', '2026-04-26 05:50:00', NULL, 0, 0, '2026-04-26 05:50:00', '2026-04-26 05:50:00'),
(21, 21, 'income', 'tunai', 60500.000, 60500.000, 'Sewa meja Meja 5 (Toko ABC) — Budi Santoso #021', '2026-03-30 01:48:00', NULL, 0, 0, '2026-03-30 01:48:00', '2026-03-30 01:48:00'),
(22, 22, 'income', 'tunai', 115500.000, 115500.000, 'Sewa meja Meja 5 (Toko ABC) — Xena Putri #022', '2026-04-26 22:50:00', NULL, 0, 0, '2026-04-26 22:50:00', '2026-04-26 22:50:00'),
(23, 23, 'income', 'tunai', 121666.667, 121666.667, 'Sewa meja Meja 2 (Toko ABC) — Hadi Susanto #023', '2026-03-14 23:33:00', NULL, 0, 0, '2026-03-14 23:33:00', '2026-03-14 23:33:00'),
(24, 24, 'income', 'transfer', 22000.000, 22000.000, 'Sewa meja Meja 1 (Toko ABC 2) — Zaki Ramadhan #024', '2026-03-24 22:21:00', NULL, 0, 0, '2026-03-24 22:21:00', '2026-03-24 22:21:00'),
(25, 25, 'income', 'qris', 104416.667, 104416.667, 'Sewa meja Meja 3 (Toko ABC) — Rina Kusuma #025', '2026-03-20 03:03:00', NULL, 0, 0, '2026-03-20 03:03:00', '2026-03-20 03:03:00'),
(26, 26, 'income', 'transfer', 96000.000, 96000.000, 'Sewa meja Meja 5 (Toko ABC) — Rina Kusuma #026', '2026-04-21 05:23:00', NULL, 0, 0, '2026-04-21 05:23:00', '2026-04-21 05:23:00'),
(27, 27, 'income', 'kartu', 12500.000, 12500.000, 'Sewa meja Meja 4 (Toko ABC) — Ani Wijaya #027', '2026-04-23 01:06:00', 'cash-flow-bukti/dummy-bh7wsxbm9gssmqfm8nezeiqy.jpg', 0, 0, '2026-04-23 01:06:00', '2026-04-23 01:06:00'),
(28, 28, 'income', 'tunai', 68666.667, 68666.667, 'Sewa meja Meja 1 (Toko ABC 2) — Yoga Prasetyo #028', '2026-04-14 01:41:00', NULL, 0, 0, '2026-04-14 01:41:00', '2026-04-14 01:41:00'),
(29, 29, 'income', 'transfer', 57000.000, 57000.000, 'Sewa meja Meja 4 (Toko ABC) — Rina Kusuma #029', '2026-03-08 07:48:00', NULL, 0, 0, '2026-03-08 07:48:00', '2026-03-08 07:48:00'),
(30, 30, 'income', 'tunai', 19000.000, 19000.000, 'Sewa meja Meja 1 (Toko ABC 2) — Rafi Ahmad #030', '2026-03-05 00:25:00', NULL, 0, 0, '2026-03-05 00:25:00', '2026-03-05 00:25:00'),
(31, 31, 'income', 'kartu', 12000.000, 12000.000, 'Sewa meja Meja 1 (Toko ABC 2) — Xena Putri #031', '2026-03-05 03:38:00', 'cash-flow-bukti/dummy-ydycfihy3aa9qfhwsv3ym5ry.jpg', 0, 0, '2026-03-05 03:38:00', '2026-03-05 03:38:00'),
(32, 32, 'income', 'tunai', 64000.000, 64000.000, 'Sewa meja Meja 5 (Toko ABC) — Ahmad Fauzi #032', '2026-05-04 18:54:00', NULL, 0, 0, '2026-05-04 18:54:00', '2026-05-04 18:54:00'),
(33, 33, 'income', 'transfer', 60333.333, 60333.333, 'Sewa meja Meja 1 (Toko ABC 2) — Agus Hermawan #033', '2026-04-29 04:02:00', 'cash-flow-bukti/dummy-21hksgumqjdlwqlyyslfobam.jpg', 0, 0, '2026-04-29 04:02:00', '2026-04-29 04:02:00'),
(34, 34, 'income', 'tunai', 17000.000, 17000.000, 'Sewa meja Meja 1 (Toko ABC) — Tono Hartono #034', '2026-04-29 19:49:00', NULL, 0, 0, '2026-04-29 19:49:00', '2026-04-29 19:49:00'),
(35, 35, 'income', 'kartu', 77333.333, 45626.666, 'Sewa meja Meja 1 (Toko ABC 2) — Oki Setiawan #035', '2026-03-10 23:13:00', NULL, 0, 0, '2026-03-10 23:13:00', '2026-03-10 23:13:00'),
(36, 36, 'income', 'transfer', 80000.000, 80000.000, 'Sewa meja Meja 5 (Toko ABC) — Siti Rahayu #036', '2026-04-08 06:51:00', 'cash-flow-bukti/dummy-1xwlnkow0200kqch6f7im2p3.jpg', 0, 0, '2026-04-08 06:51:00', '2026-04-08 06:51:00'),
(37, 37, 'income', NULL, 77500.000, NULL, 'Sewa meja Meja 4 (Toko ABC) — Agus Hermawan #037', '2026-03-16 02:14:00', NULL, 0, 0, '2026-03-16 02:14:00', '2026-03-16 02:14:00'),
(38, 38, 'income', 'transfer', 146666.667, NULL, 'Sewa meja Meja 2 (Toko ABC) — Lukman Hakim #038', '2026-03-07 05:03:00', 'cash-flow-bukti/dummy-eetfij6bp1asj3sdv03kw9lc.jpg', 0, 0, '2026-03-07 05:03:00', '2026-03-07 05:03:00'),
(39, 39, 'income', 'kartu', 73000.000, 73000.000, 'Sewa meja Meja 1 (Toko ABC) — Vina Melati #039', '2026-03-19 23:02:00', 'cash-flow-bukti/dummy-i51psvtrhhpe7ve9pskkugn2.jpg', 0, 0, '2026-03-19 23:02:00', '2026-03-19 23:02:00'),
(40, 40, 'income', 'tunai', 190000.000, 190000.000, 'Sewa meja Meja 2 (Toko ABC) — Siti Rahayu #040', '2026-03-31 01:41:00', NULL, 0, 0, '2026-03-31 01:41:00', '2026-03-31 01:41:00'),
(41, 41, 'income', 'qris', 75000.000, 75000.000, 'Sewa meja Meja 2 (Toko ABC 2) — Siti Rahayu #041', '2026-03-17 03:39:00', 'cash-flow-bukti/dummy-ezdrlaixn2ydabju7xos95yy.jpg', 0, 0, '2026-03-17 03:39:00', '2026-03-17 03:39:00'),
(42, 42, 'income', 'tunai', 26500.000, 26500.000, 'Sewa meja Meja 1 (Toko ABC) — Citra Dewi #042', '2026-05-01 19:31:00', NULL, 0, 0, '2026-05-01 19:31:00', '2026-05-01 19:31:00'),
(43, 43, 'income', 'transfer', 100500.000, 89445.000, 'Sewa meja Meja 1 (Toko ABC) — Rafi Ahmad #043', '2026-02-27 04:05:00', NULL, 0, 0, '2026-02-27 04:05:00', '2026-02-27 04:05:00'),
(44, 44, 'income', 'tunai', 34500.000, 34500.000, 'Sewa meja Meja 4 (Toko ABC) — Budi Santoso #044', '2026-04-13 19:14:00', NULL, 0, 0, '2026-04-13 19:14:00', '2026-04-13 19:14:00'),
(45, 45, 'income', 'lainnya', 66500.000, 66500.000, 'Sewa meja Meja 1 (Toko ABC) — Kartika Sari #045', '2026-05-16 05:21:00', NULL, 0, 0, '2026-05-16 05:21:00', '2026-05-16 05:21:00'),
(46, 46, 'income', 'tunai', 44500.000, 44500.000, 'Sewa meja Meja 4 (Toko ABC) — Vina Melati #046', '2026-05-14 23:42:00', NULL, 0, 0, '2026-05-14 23:42:00', '2026-05-14 23:42:00'),
(47, 47, 'income', 'tunai', 71333.333, 71333.333, 'Sewa meja Meja 1 (Toko ABC 2) — Xena Putri #047', '2026-05-21 07:10:00', NULL, 0, 0, '2026-05-21 07:10:00', '2026-05-21 07:10:00'),
(48, 48, 'income', NULL, 30416.667, NULL, 'Sewa meja Meja 2 (Toko ABC 2) — Lukman Hakim #048', '2026-04-11 04:58:00', NULL, 0, 0, '2026-04-11 04:58:00', '2026-04-11 04:58:00'),
(49, 49, 'income', 'tunai', 13416.667, 13416.667, 'Sewa meja Meja 3 (Toko ABC) — Maya Lestari #049', '2026-03-29 20:35:00', NULL, 0, 0, '2026-03-29 20:35:00', '2026-03-29 20:35:00'),
(50, 50, 'income', 'kartu', 25833.333, 12916.667, 'Sewa meja Meja 2 (Toko ABC 2) — Putri Maharani #050', '2026-04-25 03:12:00', 'cash-flow-bukti/dummy-rymhsgpw2j2rxv9ht8tbcuys.jpg', 0, 0, '2026-04-25 03:12:00', '2026-04-25 03:12:00'),
(51, 51, 'income', 'transfer', 89166.667, 89166.667, 'Sewa meja Meja 2 (Toko ABC) — Yoga Prasetyo #051', '2026-05-09 18:54:00', NULL, 0, 0, '2026-05-09 18:54:00', '2026-05-09 18:54:00'),
(52, 52, 'income', 'tunai', 37000.000, 37000.000, 'Sewa meja Meja 5 (Toko ABC) — Gita Permata #052', '2026-04-14 08:23:00', NULL, 0, 0, '2026-04-14 08:23:00', '2026-04-14 08:23:00'),
(53, 53, 'income', 'tunai', 64166.667, 64166.667, 'Sewa meja Meja 2 (Toko ABC) — Qori Sandria #053', '2026-04-11 01:45:00', NULL, 0, 0, '2026-04-11 01:45:00', '2026-04-11 01:45:00'),
(54, 54, 'income', 'kartu', 40500.000, 40500.000, 'Sewa meja Meja 4 (Toko ABC) — Dedi Pratama #054', '2026-03-01 03:30:00', NULL, 0, 0, '2026-03-01 03:30:00', '2026-03-01 03:30:00'),
(55, 55, 'income', 'transfer', 9000.000, 9000.000, 'Sewa meja Meja 1 (Toko ABC) — Joko Widodo #055', '2026-04-11 17:54:00', NULL, 0, 0, '2026-04-11 17:54:00', '2026-04-11 17:54:00'),
(56, 56, 'income', 'lainnya', 120000.000, 111600.000, 'Sewa meja Meja 2 (Toko ABC) — Lina Hartono #056', '2026-03-13 00:17:00', 'cash-flow-bukti/dummy-vjxwgqspvun7i2dbeah08byk.jpg', 0, 0, '2026-03-13 00:17:00', '2026-03-13 00:17:00'),
(57, 57, 'income', NULL, 30000.000, NULL, 'Sewa meja Meja 2 (Toko ABC 2) — Gita Permata #057', '2026-04-03 18:52:00', NULL, 0, 0, '2026-04-03 18:52:00', '2026-04-03 18:52:00'),
(58, 58, 'income', 'tunai', 78500.000, 78500.000, 'Sewa meja Meja 4 (Toko ABC) — Kartika Sari #058', '2026-03-23 21:09:00', NULL, 0, 0, '2026-03-23 21:09:00', '2026-03-23 21:09:00'),
(59, 59, 'income', 'tunai', 80000.000, 80000.000, 'Sewa meja Meja 2 (Toko ABC 2) — Dedi Pratama #059', '2026-04-02 06:07:00', NULL, 0, 0, '2026-04-02 06:07:00', '2026-04-02 06:07:00'),
(60, 60, 'income', 'tunai', 22166.667, 22166.667, 'Sewa meja Meja 3 (Toko ABC) — Eko Wibowo #060', '2026-03-13 19:24:00', NULL, 0, 0, '2026-03-13 19:24:00', '2026-03-13 19:24:00'),
(61, 61, 'income', 'lainnya', 16250.000, 16250.000, 'Sewa meja Meja 2 (Toko ABC 2) — Indah Puspita #061', '2026-03-03 06:14:00', NULL, 0, 0, '2026-03-03 06:14:00', '2026-03-03 06:14:00'),
(62, 62, 'income', 'tunai', 172500.000, 172500.000, 'Sewa meja Meja 2 (Toko ABC) — Ani Wijaya #062', '2026-05-20 03:33:00', NULL, 0, 0, '2026-05-20 03:33:00', '2026-05-20 03:33:00'),
(63, 63, 'income', 'transfer', 12666.667, 12666.667, 'Sewa meja Meja 1 (Toko ABC 2) — Xena Putri #063', '2026-04-21 05:46:00', 'cash-flow-bukti/dummy-bcudb2hyrvwtfxe8lrymm2n7.jpg', 0, 0, '2026-04-21 05:46:00', '2026-04-21 05:46:00'),
(64, 64, 'income', 'transfer', 28333.333, 28333.333, 'Sewa meja Meja 2 (Toko ABC 2) — Hendra Gunawan #064', '2026-03-25 21:56:00', 'cash-flow-bukti/dummy-ds3kqa2uig5bc7iywxe8sprv.jpg', 0, 0, '2026-03-25 21:56:00', '2026-03-25 21:56:00'),
(65, 65, 'income', 'tunai', 91000.000, 91000.000, 'Sewa meja Meja 3 (Toko ABC) — Eko Wibowo #065', '2026-04-01 21:32:00', NULL, 0, 0, '2026-04-01 21:32:00', '2026-04-01 21:32:00'),
(66, 66, 'income', 'tunai', 20416.667, 20416.667, 'Sewa meja Meja 2 (Toko ABC 2) — Eko Wibowo #066', '2026-03-19 19:59:00', NULL, 0, 0, '2026-03-19 19:59:00', '2026-03-19 19:59:00'),
(67, 67, 'income', 'tunai', 111666.667, 111666.667, 'Sewa meja Meja 2 (Toko ABC) — Kartika Sari #067', '2026-05-05 20:59:00', NULL, 0, 0, '2026-05-05 20:59:00', '2026-05-05 20:59:00'),
(68, 68, 'income', NULL, 61250.000, NULL, 'Sewa meja Meja 3 (Toko ABC) — Umi Kalsum #068', '2026-04-01 22:50:00', NULL, 0, 0, '2026-04-01 22:50:00', '2026-04-01 22:50:00'),
(69, 69, 'income', 'tunai', 120166.667, 120166.667, 'Sewa meja Meja 3 (Toko ABC) — Rina Kusuma #069', '2026-03-28 07:18:00', NULL, 0, 0, '2026-03-28 07:18:00', '2026-03-28 07:18:00'),
(70, 70, 'income', 'tunai', 98000.000, 98000.000, 'Sewa meja Meja 5 (Toko ABC) — Rizki Aditya #070', '2026-05-13 20:30:00', NULL, 0, 0, '2026-05-13 20:30:00', '2026-05-13 20:30:00'),
(71, 71, 'income', 'tunai', 17000.000, 17000.000, 'Sewa meja Meja 4 (Toko ABC) — Belinda Rose #071', '2026-03-15 02:53:00', NULL, 0, 0, '2026-03-15 02:53:00', '2026-03-15 02:53:00'),
(72, 72, 'income', 'qris', 26500.000, 21465.000, 'Sewa meja Meja 4 (Toko ABC) — Dedi Pratama #072', '2026-04-03 02:23:00', 'cash-flow-bukti/dummy-tcdlif16z2rrz54izeuj0smo.jpg', 0, 0, '2026-04-03 02:23:00', '2026-04-03 02:23:00'),
(73, 73, 'income', 'tunai', 19000.000, 19000.000, 'Sewa meja Meja 5 (Toko ABC) — Indah Puspita #073', '2026-03-21 19:34:00', NULL, 0, 0, '2026-03-21 19:34:00', '2026-03-21 19:34:00'),
(74, 74, 'income', 'transfer', 61666.667, 61666.667, 'Sewa meja Meja 2 (Toko ABC 2) — Umi Kalsum #074', '2026-02-21 01:17:00', 'cash-flow-bukti/dummy-2jxhmkfrpcqbv664x84nizmb.jpg', 0, 0, '2026-02-21 01:17:00', '2026-02-21 01:17:00'),
(75, 75, 'income', 'tunai', 63750.000, 63750.000, 'Sewa meja Meja 2 (Toko ABC 2) — Fajar Nugroho #075', '2026-04-11 09:27:00', NULL, 0, 0, '2026-04-11 09:27:00', '2026-04-11 09:27:00'),
(76, 76, 'income', 'qris', 63000.000, 63000.000, 'Sewa meja Meja 5 (Toko ABC) — Indah Puspita #076', '2026-04-05 19:16:00', NULL, 0, 0, '2026-04-05 19:16:00', '2026-04-05 19:16:00'),
(77, 77, 'income', 'qris', 21000.000, 21000.000, 'Sewa meja Meja 3 (Toko ABC) — Vina Melati #077', '2026-02-22 07:20:00', 'cash-flow-bukti/dummy-na9xgwgtxl6fmbo8xob33ltn.jpg', 0, 0, '2026-02-22 07:20:00', '2026-02-22 07:20:00'),
(78, 78, 'income', 'tunai', 71750.000, 71750.000, 'Sewa meja Meja 3 (Toko ABC) — Fajar Nugroho #078', '2026-04-30 05:49:00', NULL, 0, 0, '2026-04-30 05:49:00', '2026-04-30 05:49:00'),
(79, 79, 'income', 'qris', 22333.333, 22333.333, 'Sewa meja Meja 1 (Toko ABC 2) — Yoga Prasetyo #079', '2026-03-19 22:07:00', 'cash-flow-bukti/dummy-q8vf0eacgewfb57ku1bef4yx.jpg', 0, 0, '2026-03-19 22:07:00', '2026-03-19 22:07:00'),
(80, 80, 'income', 'tunai', 107500.000, 107500.000, 'Sewa meja Meja 4 (Toko ABC) — Indah Puspita #080', '2026-05-02 08:42:00', NULL, 0, 0, '2026-05-02 08:42:00', '2026-05-02 08:42:00'),
(81, 81, 'income', 'kartu', 73500.000, 73500.000, 'Sewa meja Meja 5 (Toko ABC) — Vina Melati #081', '2026-05-03 22:13:00', NULL, 0, 0, '2026-05-03 22:13:00', '2026-05-03 22:13:00'),
(82, 82, 'income', 'tunai', 78333.333, 78333.333, 'Sewa meja Meja 2 (Toko ABC 2) — Putri Maharani #082', '2026-02-27 00:01:00', NULL, 0, 0, '2026-02-27 00:01:00', '2026-02-27 00:01:00'),
(83, 83, 'income', 'tunai', 105000.000, 105000.000, 'Sewa meja Meja 2 (Toko ABC) — Nanda Pratama #083', '2026-04-17 18:51:00', NULL, 0, 0, '2026-04-17 18:51:00', '2026-04-17 18:51:00'),
(84, 84, 'income', 'kartu', 58333.333, 58333.333, 'Sewa meja Meja 3 (Toko ABC) — Maya Lestari #084', '2026-03-30 22:04:00', NULL, 0, 0, '2026-03-30 22:04:00', '2026-03-30 22:04:00'),
(85, 85, 'income', 'kartu', 62500.000, NULL, 'Sewa meja Meja 2 (Toko ABC) — Dewi Anggraini #085', '2026-02-26 17:55:00', 'cash-flow-bukti/dummy-4rpu8qniqw6pokrwyfzuang2.jpg', 0, 0, '2026-02-26 17:55:00', '2026-02-26 17:55:00'),
(86, 86, 'income', 'tunai', 52500.000, 52500.000, 'Sewa meja Meja 1 (Toko ABC) — Joko Widodo #086', '2026-04-06 23:11:00', NULL, 0, 0, '2026-04-06 23:11:00', '2026-04-06 23:11:00'),
(87, 87, 'income', 'kartu', 23000.000, 23000.000, 'Sewa meja Meja 1 (Toko ABC 2) — Citra Dewi #087', '2026-02-22 08:06:00', NULL, 0, 0, '2026-02-22 08:06:00', '2026-02-22 08:06:00'),
(88, 88, 'income', 'transfer', 77500.000, 77500.000, 'Sewa meja Meja 4 (Toko ABC) — Umi Kalsum #088', '2026-03-13 01:17:00', 'cash-flow-bukti/dummy-qlli6hhqjnaayhyo6xacy8hc.jpg', 0, 0, '2026-03-13 01:17:00', '2026-03-13 01:17:00'),
(89, 89, 'income', 'transfer', 53000.000, 53000.000, 'Sewa meja Meja 4 (Toko ABC) — Yuni Astuti #089', '2026-04-30 01:27:00', 'cash-flow-bukti/dummy-du7sorvfaoguz7nhs4ygrqxk.jpg', 0, 0, '2026-04-30 01:27:00', '2026-04-30 01:27:00'),
(90, 90, 'income', 'qris', 80833.333, 80833.333, 'Sewa meja Meja 2 (Toko ABC 2) — Eko Wibowo #090', '2026-05-20 09:14:00', 'cash-flow-bukti/dummy-43goyhkbdxm8nuf4elj57mxi.jpg', 0, 0, '2026-05-20 09:14:00', '2026-05-20 09:14:00'),
(91, 91, 'income', 'tunai', 113333.333, 113333.333, 'Sewa meja Meja 2 (Toko ABC) — Tono Hartono #091', '2026-03-05 08:50:00', NULL, 0, 0, '2026-03-05 08:50:00', '2026-03-05 08:50:00'),
(92, 92, 'income', 'transfer', 12666.667, 12666.667, 'Sewa meja Meja 1 (Toko ABC 2) — Pratiwi Utami #092', '2026-03-03 03:00:00', NULL, 0, 0, '2026-03-03 03:00:00', '2026-03-03 03:00:00'),
(93, 93, 'income', NULL, 27500.000, NULL, 'Sewa meja Meja 5 (Toko ABC) — Putri Maharani #093', '2026-04-09 23:38:00', NULL, 0, 0, '2026-04-09 23:38:00', '2026-04-09 23:38:00'),
(94, 94, 'income', 'lainnya', 86000.000, 86000.000, 'Sewa meja Meja 4 (Toko ABC) — Wahyu Nugroho #094', '2026-02-24 10:08:00', NULL, 0, 0, '2026-02-24 10:08:00', '2026-02-24 10:08:00'),
(95, 95, 'income', 'tunai', 89166.667, 89166.667, 'Sewa meja Meja 2 (Toko ABC 2) — Bambang Sutrisno #095', '2026-03-06 10:06:00', NULL, 0, 0, '2026-03-06 10:06:00', '2026-03-06 10:06:00'),
(96, 96, 'income', 'tunai', 109000.000, 109000.000, 'Sewa meja Meja 1 (Toko ABC) — Rina Kusuma #096', '2026-04-16 03:52:00', NULL, 0, 0, '2026-04-16 03:52:00', '2026-04-16 03:52:00'),
(97, 97, 'income', 'tunai', 37000.000, 37000.000, 'Sewa meja Meja 1 (Toko ABC 2) — Rina Kusuma #097', '2026-03-20 04:21:00', NULL, 0, 0, '2026-03-20 04:21:00', '2026-03-20 04:21:00'),
(98, 98, 'income', 'qris', 34000.000, 34000.000, 'Sewa meja Meja 5 (Toko ABC) — Nanda Pratama #098', '2026-04-01 07:49:00', 'cash-flow-bukti/dummy-oy2osexbwc6cbkkikxvyorcg.jpg', 0, 0, '2026-04-01 07:49:00', '2026-04-01 07:49:00'),
(99, 99, 'income', 'tunai', 97500.000, 97500.000, 'Sewa meja Meja 5 (Toko ABC) — Yoga Prasetyo #099', '2026-04-05 02:19:00', NULL, 0, 0, '2026-04-05 02:19:00', '2026-04-05 02:19:00'),
(100, 100, 'income', 'qris', 49500.000, 49500.000, 'Sewa meja Meja 4 (Toko ABC) — Dewi Anggraini #100', '2026-03-24 22:58:00', 'cash-flow-bukti/dummy-fu7ycnkek6cyezj6adelw4fj.jpg', 0, 0, '2026-03-24 22:58:00', '2026-03-24 22:58:00');

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
(8, '2026_05_16_000002_add_guest_token_to_rental_table', 5),
(9, '2026_05_21_000001_add_jumlah_bayar_to_cash_flow_table', 6);

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
(6, 2, 'Meja 2', 50000, 'active', 1, '2026-05-16 21:11:30', 1, '2026-05-21 13:53:54'),
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
(1, 8, 'Maya Lestari #001', '2026-04-07 19:35:00', '2026-04-07 20:52:00', 77, 30000, 38500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(2, 7, 'Dewi Anggraini #002', '2026-05-14 00:54:00', '2026-05-14 02:05:00', 71, 35000, 41417, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(3, 14, 'Yoga Prasetyo #003', '2026-03-06 00:38:00', '2026-03-06 03:25:00', 167, 25000, 69583, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(4, 6, 'Vina Melati #004', '2026-03-31 22:20:00', '2026-04-01 00:22:00', 122, 50000, 101667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(5, 5, 'Maya Lestari #005', '2026-03-05 03:56:00', '2026-03-05 04:48:00', 52, 30000, 26000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(6, 9, 'Indah Puspita #006', '2026-05-08 23:15:00', '2026-05-09 02:22:00', 187, 30000, 93500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(7, 8, 'Gita Permata #007', '2026-05-19 05:34:00', '2026-05-19 06:48:00', 74, 30000, 37000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(8, 6, 'Lukman Hakim #008', '2026-05-10 20:29:00', '2026-05-10 23:42:00', 193, 50000, 160833, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(9, 8, 'Hendra Gunawan #009', '2026-05-13 06:41:00', '2026-05-13 07:26:00', 45, 30000, 22500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(10, 14, 'Salsa Bintang #010', '2026-04-04 02:53:00', '2026-04-04 05:15:00', 142, 25000, 59167, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(11, 5, 'Dedi Pratama #011', '2026-05-15 18:22:00', '2026-05-15 22:19:00', 237, 30000, 118500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(12, 5, 'Eko Wibowo #012', '2026-04-27 22:36:00', '2026-04-27 22:55:00', 19, 30000, 9500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(13, 8, 'Siti Rahayu #013', '2026-05-16 03:00:00', '2026-05-16 04:46:00', 106, 30000, 53000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(14, 6, 'Oki Setiawan #014', '2026-03-03 02:13:00', '2026-03-03 02:44:00', 31, 50000, 25833, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(15, 6, 'Oki Setiawan #015', '2026-04-17 04:43:00', '2026-04-17 06:24:00', 101, 50000, 84167, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(16, 5, 'Joko Widodo #016', '2026-04-26 23:22:00', '2026-04-27 02:48:00', 206, 30000, 103000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(17, 9, 'Agus Hermawan #017', '2026-05-13 02:35:00', '2026-05-13 03:45:00', 70, 30000, 35000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(18, 14, 'Kartika Sari #018', '2026-04-02 02:31:00', '2026-04-02 04:55:00', 144, 25000, 60000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(19, 14, 'Dedi Pratama #019', '2026-05-20 16:43:00', '2026-05-20 17:32:00', 49, 25000, 20417, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(20, 5, 'Zaki Ramadhan #020', '2026-04-26 03:05:00', '2026-04-26 05:50:00', 165, 30000, 82500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(21, 9, 'Budi Santoso #021', '2026-03-29 23:47:00', '2026-03-30 01:48:00', 121, 30000, 60500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(22, 9, 'Xena Putri #022', '2026-04-26 18:59:00', '2026-04-26 22:50:00', 231, 30000, 115500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(23, 6, 'Hadi Susanto #023', '2026-03-14 21:07:00', '2026-03-14 23:33:00', 146, 50000, 121667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(24, 13, 'Zaki Ramadhan #024', '2026-03-24 21:15:00', '2026-03-24 22:21:00', 66, 20000, 22000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(25, 7, 'Rina Kusuma #025', '2026-03-20 00:04:00', '2026-03-20 03:03:00', 179, 35000, 104417, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(26, 9, 'Rina Kusuma #026', '2026-04-21 02:11:00', '2026-04-21 05:23:00', 192, 30000, 96000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(27, 8, 'Ani Wijaya #027', '2026-04-23 00:41:00', '2026-04-23 01:06:00', 25, 30000, 12500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(28, 13, 'Yoga Prasetyo #028', '2026-04-13 22:15:00', '2026-04-14 01:41:00', 206, 20000, 68667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(29, 8, 'Rina Kusuma #029', '2026-03-08 05:54:00', '2026-03-08 07:48:00', 114, 30000, 57000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(30, 13, 'Rafi Ahmad #030', '2026-03-04 23:28:00', '2026-03-05 00:25:00', 57, 20000, 19000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(31, 13, 'Xena Putri #031', '2026-03-05 03:02:00', '2026-03-05 03:38:00', 36, 20000, 12000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(32, 9, 'Ahmad Fauzi #032', '2026-05-04 16:46:00', '2026-05-04 18:54:00', 128, 30000, 64000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(33, 13, 'Agus Hermawan #033', '2026-04-29 01:01:00', '2026-04-29 04:02:00', 181, 20000, 60333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(34, 5, 'Tono Hartono #034', '2026-04-29 19:15:00', '2026-04-29 19:49:00', 34, 30000, 17000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(35, 13, 'Oki Setiawan #035', '2026-03-10 19:21:00', '2026-03-10 23:13:00', 232, 20000, 77333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(36, 9, 'Siti Rahayu #036', '2026-04-08 04:11:00', '2026-04-08 06:51:00', 160, 30000, 80000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(37, 8, 'Agus Hermawan #037', '2026-03-15 23:39:00', '2026-03-16 02:14:00', 155, 30000, 77500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(38, 6, 'Lukman Hakim #038', '2026-03-07 02:07:00', '2026-03-07 05:03:00', 176, 50000, 146667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(39, 5, 'Vina Melati #039', '2026-03-19 20:36:00', '2026-03-19 23:02:00', 146, 30000, 73000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(40, 6, 'Siti Rahayu #040', '2026-03-30 21:53:00', '2026-03-31 01:41:00', 228, 50000, 190000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(41, 14, 'Siti Rahayu #041', '2026-03-17 00:39:00', '2026-03-17 03:39:00', 180, 25000, 75000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(42, 5, 'Citra Dewi #042', '2026-05-01 18:38:00', '2026-05-01 19:31:00', 53, 30000, 26500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(43, 5, 'Rafi Ahmad #043', '2026-02-27 00:44:00', '2026-02-27 04:05:00', 201, 30000, 100500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(44, 8, 'Budi Santoso #044', '2026-04-13 18:05:00', '2026-04-13 19:14:00', 69, 30000, 34500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(45, 5, 'Kartika Sari #045', '2026-05-16 03:08:00', '2026-05-16 05:21:00', 133, 30000, 66500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(46, 8, 'Vina Melati #046', '2026-05-14 22:13:00', '2026-05-14 23:42:00', 89, 30000, 44500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(47, 13, 'Xena Putri #047', '2026-05-21 03:36:00', '2026-05-21 07:10:00', 214, 20000, 71333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(48, 14, 'Lukman Hakim #048', '2026-04-11 03:45:00', '2026-04-11 04:58:00', 73, 25000, 30417, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(49, 7, 'Maya Lestari #049', '2026-03-29 20:12:00', '2026-03-29 20:35:00', 23, 35000, 13417, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(50, 14, 'Putri Maharani #050', '2026-04-25 02:10:00', '2026-04-25 03:12:00', 62, 25000, 25833, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(51, 6, 'Yoga Prasetyo #051', '2026-05-09 17:07:00', '2026-05-09 18:54:00', 107, 50000, 89167, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(52, 9, 'Gita Permata #052', '2026-04-14 07:09:00', '2026-04-14 08:23:00', 74, 30000, 37000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(53, 6, 'Qori Sandria #053', '2026-04-11 00:28:00', '2026-04-11 01:45:00', 77, 50000, 64167, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(54, 8, 'Dedi Pratama #054', '2026-03-01 02:09:00', '2026-03-01 03:30:00', 81, 30000, 40500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(55, 5, 'Joko Widodo #055', '2026-04-11 17:36:00', '2026-04-11 17:54:00', 18, 30000, 9000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(56, 6, 'Lina Hartono #056', '2026-03-12 21:53:00', '2026-03-13 00:17:00', 144, 50000, 120000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(57, 14, 'Gita Permata #057', '2026-04-03 17:40:00', '2026-04-03 18:52:00', 72, 25000, 30000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(58, 8, 'Kartika Sari #058', '2026-03-23 18:32:00', '2026-03-23 21:09:00', 157, 30000, 78500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(59, 14, 'Dedi Pratama #059', '2026-04-02 02:55:00', '2026-04-02 06:07:00', 192, 25000, 80000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(60, 7, 'Eko Wibowo #060', '2026-03-13 18:46:00', '2026-03-13 19:24:00', 38, 35000, 22167, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(61, 14, 'Indah Puspita #061', '2026-03-03 05:35:00', '2026-03-03 06:14:00', 39, 25000, 16250, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(62, 6, 'Ani Wijaya #062', '2026-05-20 00:06:00', '2026-05-20 03:33:00', 207, 50000, 172500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(63, 13, 'Xena Putri #063', '2026-04-21 05:08:00', '2026-04-21 05:46:00', 38, 20000, 12667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(64, 14, 'Hendra Gunawan #064', '2026-03-25 20:48:00', '2026-03-25 21:56:00', 68, 25000, 28333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(65, 7, 'Eko Wibowo #065', '2026-04-01 18:56:00', '2026-04-01 21:32:00', 156, 35000, 91000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(66, 14, 'Eko Wibowo #066', '2026-03-19 19:10:00', '2026-03-19 19:59:00', 49, 25000, 20417, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(67, 6, 'Kartika Sari #067', '2026-05-05 18:45:00', '2026-05-05 20:59:00', 134, 50000, 111667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(68, 7, 'Umi Kalsum #068', '2026-04-01 21:05:00', '2026-04-01 22:50:00', 105, 35000, 61250, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(69, 7, 'Rina Kusuma #069', '2026-03-28 03:52:00', '2026-03-28 07:18:00', 206, 35000, 120167, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(70, 9, 'Rizki Aditya #070', '2026-05-13 17:14:00', '2026-05-13 20:30:00', 196, 30000, 98000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(71, 8, 'Belinda Rose #071', '2026-03-15 02:19:00', '2026-03-15 02:53:00', 34, 30000, 17000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(72, 8, 'Dedi Pratama #072', '2026-04-03 01:30:00', '2026-04-03 02:23:00', 53, 30000, 26500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(73, 9, 'Indah Puspita #073', '2026-03-21 18:56:00', '2026-03-21 19:34:00', 38, 30000, 19000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(74, 14, 'Umi Kalsum #074', '2026-02-20 22:49:00', '2026-02-21 01:17:00', 148, 25000, 61667, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(75, 14, 'Fajar Nugroho #075', '2026-04-11 06:54:00', '2026-04-11 09:27:00', 153, 25000, 63750, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(76, 9, 'Indah Puspita #076', '2026-04-05 17:10:00', '2026-04-05 19:16:00', 126, 30000, 63000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(77, 7, 'Vina Melati #077', '2026-02-22 06:44:00', '2026-02-22 07:20:00', 36, 35000, 21000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(78, 7, 'Fajar Nugroho #078', '2026-04-30 03:46:00', '2026-04-30 05:49:00', 123, 35000, 71750, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(79, 13, 'Yoga Prasetyo #079', '2026-03-19 21:00:00', '2026-03-19 22:07:00', 67, 20000, 22333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(80, 8, 'Indah Puspita #080', '2026-05-02 05:07:00', '2026-05-02 08:42:00', 215, 30000, 107500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(81, 9, 'Vina Melati #081', '2026-05-03 19:46:00', '2026-05-03 22:13:00', 147, 30000, 73500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(82, 14, 'Putri Maharani #082', '2026-02-26 20:53:00', '2026-02-27 00:01:00', 188, 25000, 78333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(83, 6, 'Nanda Pratama #083', '2026-04-17 16:45:00', '2026-04-17 18:51:00', 126, 50000, 105000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(84, 7, 'Maya Lestari #084', '2026-03-30 20:24:00', '2026-03-30 22:04:00', 100, 35000, 58333, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(85, 6, 'Dewi Anggraini #085', '2026-02-26 16:40:00', '2026-02-26 17:55:00', 75, 50000, 62500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(86, 5, 'Joko Widodo #086', '2026-04-06 21:26:00', '2026-04-06 23:11:00', 105, 30000, 52500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(87, 13, 'Citra Dewi #087', '2026-02-22 06:57:00', '2026-02-22 08:06:00', 69, 20000, 23000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(88, 8, 'Umi Kalsum #088', '2026-03-12 22:42:00', '2026-03-13 01:17:00', 155, 30000, 77500, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(89, 8, 'Yuni Astuti #089', '2026-04-29 23:41:00', '2026-04-30 01:27:00', 106, 30000, 53000, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(90, 14, 'Eko Wibowo #090', '2026-05-20 06:00:00', '2026-05-20 09:14:00', 194, 25000, 80833, 'completed', NULL, 0, '2026-05-21 15:29:22', 0, '2026-05-21 15:29:22'),
(91, 6, 'Tono Hartono #091', '2026-03-05 06:34:00', '2026-03-05 08:50:00', 136, 50000, 113333, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(92, 13, 'Pratiwi Utami #092', '2026-03-03 02:22:00', '2026-03-03 03:00:00', 38, 20000, 12667, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(93, 9, 'Putri Maharani #093', '2026-04-09 22:43:00', '2026-04-09 23:38:00', 55, 30000, 27500, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(94, 8, 'Wahyu Nugroho #094', '2026-02-24 07:16:00', '2026-02-24 10:08:00', 172, 30000, 86000, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(95, 14, 'Bambang Sutrisno #095', '2026-03-06 06:32:00', '2026-03-06 10:06:00', 214, 25000, 89167, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(96, 5, 'Rina Kusuma #096', '2026-04-16 00:14:00', '2026-04-16 03:52:00', 218, 30000, 109000, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(97, 13, 'Rina Kusuma #097', '2026-03-20 02:30:00', '2026-03-20 04:21:00', 111, 20000, 37000, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(98, 9, 'Nanda Pratama #098', '2026-04-01 06:41:00', '2026-04-01 07:49:00', 68, 30000, 34000, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(99, 9, 'Yoga Prasetyo #099', '2026-04-04 23:04:00', '2026-04-05 02:19:00', 195, 30000, 97500, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23'),
(100, 8, 'Dewi Anggraini #100', '2026-03-24 21:19:00', '2026-03-24 22:58:00', 99, 30000, 49500, 'completed', NULL, 0, '2026-05-21 15:29:23', 0, '2026-05-21 15:29:23');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
