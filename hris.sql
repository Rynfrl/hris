-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 19, 2026 at 01:00 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hris`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `activity_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` int UNSIGNED DEFAULT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`activity_id`, `user_id`, `action`, `entity_type`, `entity_id`, `summary`, `ip_address`, `created_at`) VALUES
(1, NULL, 'LOGIN', 'auth', 3, 'Login budi.santoso@company.co.id berhasil', '::1', '2026-09-23 13:50:09'),
(2, NULL, 'LOGIN', 'auth', 3, 'Login budi.santoso@company.co.id berhasil', '::1', '2026-09-30 13:12:14'),
(3, NULL, 'LOGIN', 'auth', 8, 'Login dannyindrapramana@gmail.com berhasil', '::1', '2026-10-04 23:02:29'),
(4, NULL, 'LOGOUT', 'auth', 8, 'Logout berhasil', '::1', '2026-10-04 23:02:42'),
(5, NULL, 'LOGIN', 'auth', 8, 'Login dannyindrapramana@gmail.com berhasil', '::1', '2026-10-04 23:03:01'),
(6, NULL, 'LOGOUT', 'auth', 8, 'Logout berhasil', '::1', '2026-10-05 00:15:04'),
(7, NULL, 'LOGIN', 'auth', 10, 'Login ravy@gmail.com berhasil', '::1', '2026-10-05 00:15:25'),
(8, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-05 06:35:15'),
(10, 14, 'LOGIN', 'auth', 14, 'Login salma@gmail.com berhasil', '::1', '2026-10-08 04:24:53'),
(11, 14, 'LOGIN', 'auth', 14, 'Login salma@gmail.com berhasil', '::1', '2026-10-08 04:31:10'),
(12, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-08 13:31:04'),
(13, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-08 13:46:00'),
(14, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-08 13:46:11'),
(15, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-08 13:47:47'),
(16, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '::1', '2026-10-08 13:47:56'),
(17, 19, 'LOGOUT', 'auth', 19, 'Logout berhasil', '::1', '2026-10-08 13:48:08'),
(18, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-08 13:48:16'),
(19, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-08 13:49:53'),
(20, 20, 'LOGIN', 'auth', 20, 'Login zakie@gmail.com berhasil', '::1', '2026-10-08 13:49:59'),
(21, 20, 'LOGOUT', 'auth', 20, 'Logout berhasil', '::1', '2026-10-08 14:03:55'),
(22, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '::1', '2026-10-08 14:04:02'),
(23, 19, 'LOGOUT', 'auth', 19, 'Logout berhasil', '::1', '2026-10-08 14:04:55'),
(24, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-08 14:05:06'),
(25, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-08 14:13:03'),
(26, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '::1', '2026-10-08 14:13:09'),
(27, 19, 'LOGOUT', 'auth', 19, 'Logout berhasil', '::1', '2026-10-08 14:56:45'),
(28, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-08 14:56:57'),
(29, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-11 22:16:54'),
(30, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-11 22:18:32'),
(31, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '::1', '2026-10-11 22:18:42'),
(32, 21, 'LOGOUT', 'auth', 21, 'Logout berhasil', '::1', '2026-10-11 22:39:27'),
(33, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-11 22:39:43'),
(34, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-11 22:39:46'),
(35, 14, 'LOGIN', 'auth', 14, 'Login salma@gmail.com berhasil', '::1', '2026-10-11 22:39:52'),
(36, 14, 'LOGOUT', 'auth', 14, 'Logout berhasil', '::1', '2026-10-11 22:40:14'),
(37, 18, 'LOGIN', 'auth', 18, 'Login arafat@gmail.com berhasil', '::1', '2026-10-11 22:40:33'),
(38, 18, 'LOGOUT', 'auth', 18, 'Logout berhasil', '::1', '2026-10-11 22:40:41'),
(39, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '::1', '2026-10-11 22:40:56'),
(40, 19, 'LOGOUT', 'auth', 19, 'Logout berhasil', '::1', '2026-10-11 22:41:02'),
(41, 20, 'LOGIN', 'auth', 20, 'Login zakie@gmail.com berhasil', '::1', '2026-10-11 22:41:18'),
(42, 20, 'LOGOUT', 'auth', 20, 'Logout berhasil', '::1', '2026-10-11 22:41:22'),
(43, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-11 22:41:37'),
(44, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-11 22:43:22'),
(45, 22, 'LOGIN', 'auth', 22, 'Login thio@gmail.com berhasil', '::1', '2026-10-11 22:43:37'),
(46, 22, 'LOGOUT', 'auth', 22, 'Logout berhasil', '::1', '2026-10-11 22:51:53'),
(47, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '::1', '2026-10-11 22:52:02'),
(48, 21, 'LOGOUT', 'auth', 21, 'Logout berhasil', '::1', '2026-10-11 22:57:23'),
(49, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '::1', '2026-10-11 22:57:32'),
(50, 21, 'LOGOUT', 'auth', 21, 'Logout berhasil', '::1', '2026-10-11 22:57:36'),
(51, 22, 'LOGIN', 'auth', 22, 'Login thio@gmail.com berhasil', '::1', '2026-10-11 22:57:45'),
(52, 22, 'LOGOUT', 'auth', 22, 'Logout berhasil', '::1', '2026-10-11 22:57:47'),
(53, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '::1', '2026-10-11 22:57:56'),
(54, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '::1', '2026-10-11 23:03:22'),
(55, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '::1', '2026-10-11 23:03:32'),
(56, 21, 'LOGOUT', 'auth', 21, 'Logout berhasil', '::1', '2026-10-11 23:03:42'),
(57, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '::1', '2026-10-13 08:18:46'),
(58, 19, 'LOGOUT', 'auth', 19, 'Logout berhasil', '::1', '2026-10-13 08:18:55'),
(59, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '::1', '2026-10-13 08:19:13'),
(60, 21, 'LOGOUT', 'auth', 21, 'Logout berhasil', '::1', '2026-10-13 08:19:40'),
(61, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '127.0.0.1', '2026-10-15 07:43:15'),
(62, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '127.0.0.1', '2026-10-17 15:17:03'),
(63, 3, 'EXPENSE_CREATED', 'expenses', 8, 'Membuat pengeluaran baru: Test Unit Pengeluaran Budi (Rp 50.000)', NULL, '2026-10-15 07:49:52'),
(64, 3, 'REPORT_CREATED', 'expense_reports', 6, 'Membuat laporan biaya baru: Laporan Uji Otomatis Budi', NULL, '2026-10-15 07:49:52'),
(65, 3, 'EXPENSE_CREATED', 'expenses', 9, 'Membuat pengeluaran baru: Item Laporan Uji (Rp 120.000)', NULL, '2026-10-15 07:49:52'),
(66, 3, 'REPORT_ITEM_ADDED', 'expense_reports', 6, 'Menambahkan item EXP-2026-000001 ke laporan GTE-2026-000001', NULL, '2026-10-15 07:49:52'),
(67, 3, 'REPORT_CREATED', 'expense_reports', 7, 'Membuat laporan biaya baru: Laporan Kedua', NULL, '2026-10-15 07:49:52'),
(68, 3, 'REPORT_SUBMITTED', 'expense_reports', 6, 'Mengajukan laporan GTE-2026-000001 sebesar Rp 120.000', NULL, '2026-10-15 07:49:52'),
(69, 2, 'REPORT_REFUSED', 'expense_reports', 6, 'Menolak laporan GTE-2026-000001 dengan alasan: Kuitansi tidak terbaca jelas. Mohon unggah ulang.', NULL, '2026-10-15 07:49:52'),
(70, 3, 'REPORT_REVISED', 'expense_reports', 6, 'Mengembalikan laporan GTE-2026-000001 ke draft untuk revisi', NULL, '2026-10-15 07:49:52'),
(71, 2, 'REPORT_APPROVED', 'expense_reports', 6, 'Menyetujui laporan GTE-2026-000001', NULL, '2026-10-15 07:49:52'),
(72, 5, 'REPORT_POSTED', 'expense_reports', 3, 'Membukukan laporan GTE-DEMO-003', NULL, '2026-10-15 07:49:52'),
(73, 5, 'REIMBURSEMENT_QUEUED', 'reimbursements', 2, 'Membuat antrean reimbursement untuk laporan GTE-DEMO-003', NULL, '2026-10-15 07:49:52'),
(74, 5, 'REIMBURSEMENT_PAID', 'reimbursements', 2, 'Menyelesaikan pembayaran reimbursement laporan GTE-DEMO-003 (Rp 320.000)', NULL, '2026-10-15 07:49:52'),
(75, 3, 'EXPENSE_CREATED', 'expenses', 10, 'Membuat pengeluaran baru: Test Unit Pengeluaran Budi (Rp 50.000)', NULL, '2026-10-17 15:24:44'),
(76, 3, 'REPORT_CREATED', 'expense_reports', 8, 'Membuat laporan biaya baru: Laporan Uji Otomatis Budi', NULL, '2026-10-17 15:24:44'),
(77, 3, 'EXPENSE_CREATED', 'expenses', 11, 'Membuat pengeluaran baru: Item Laporan Uji (Rp 120.000)', NULL, '2026-10-17 15:24:44'),
(78, 3, 'REPORT_ITEM_ADDED', 'expense_reports', 8, 'Menambahkan item EXP-2026-000002 ke laporan GTE-2026-000003', NULL, '2026-10-17 15:24:44'),
(79, 3, 'REPORT_CREATED', 'expense_reports', 9, 'Membuat laporan biaya baru: Laporan Kedua', NULL, '2026-10-17 15:24:44'),
(80, 3, 'REPORT_SUBMITTED', 'expense_reports', 8, 'Mengajukan laporan GTE-2026-000003 sebesar Rp 120.000', NULL, '2026-10-17 15:24:44'),
(81, 2, 'REPORT_REFUSED', 'expense_reports', 8, 'Menolak laporan GTE-2026-000003 dengan alasan: Kuitansi tidak terbaca jelas. Mohon unggah ulang.', NULL, '2026-10-17 15:24:44'),
(82, 3, 'REPORT_REVISED', 'expense_reports', 8, 'Mengembalikan laporan GTE-2026-000003 ke draft untuk revisi', NULL, '2026-10-17 15:24:44'),
(83, 2, 'REPORT_APPROVED', 'expense_reports', 8, 'Menyetujui laporan GTE-2026-000003', NULL, '2026-10-17 15:24:44'),
(84, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '127.0.0.1', '2026-10-17 15:44:55'),
(85, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:10'),
(86, 2, 'LOGIN', 'auth', 2, 'Login manager@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:10'),
(87, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:10'),
(88, 5, 'LOGIN', 'auth', 5, 'Login finance@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:10'),
(89, 4, 'LOGIN', 'auth', 4, 'Login sari@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:10'),
(90, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:10'),
(91, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:11'),
(92, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:18:11'),
(93, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:31'),
(94, 2, 'LOGIN', 'auth', 2, 'Login manager@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(95, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(96, 5, 'LOGIN', 'auth', 5, 'Login finance@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(97, 4, 'LOGIN', 'auth', 4, 'Login sari@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(98, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(99, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(100, 14, 'LOGIN', 'auth', 14, 'Login salma@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(101, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(102, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(103, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:32'),
(104, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:19:33'),
(105, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:33'),
(106, 14, 'LOGIN', 'auth', 14, 'Login salma@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:33'),
(107, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:33'),
(108, 21, 'LOGIN', 'auth', 21, 'Login sabiq@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:19:33'),
(109, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:21:20'),
(110, 2, 'LOGIN', 'auth', 2, 'Login manager@example.test berhasil', '127.0.0.1', '2026-10-17 16:25:23'),
(111, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:26:01'),
(112, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '127.0.0.1', '2026-10-17 16:26:04'),
(113, 14, 'LOGIN', 'auth', 14, 'Login salma@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:26:17'),
(114, 14, 'LOGOUT', 'auth', 14, 'Logout berhasil', '127.0.0.1', '2026-10-17 16:26:24'),
(115, 19, 'LOGIN', 'auth', 19, 'Login afsaal@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:26:33'),
(116, 19, 'LOGOUT', 'auth', 19, 'Logout berhasil', '127.0.0.1', '2026-10-17 16:26:37'),
(117, 4, 'LOGIN', 'auth', 4, 'Login sari@example.test berhasil', '127.0.0.1', '2026-10-17 16:26:59'),
(118, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:39:59'),
(119, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:43:41'),
(120, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:44:30'),
(121, 3, 'LOGIN', 'auth', 3, 'Login budi@example.test berhasil', '127.0.0.1', '2026-10-17 16:50:51'),
(122, 3, 'LOGOUT', 'auth', 3, 'Logout berhasil', '127.0.0.1', '2026-10-17 16:50:59'),
(123, 9, 'LOGIN', 'auth', 9, 'Login yudha@gmail.com berhasil', '127.0.0.1', '2026-10-17 16:51:38'),
(124, 9, 'LOGOUT', 'auth', 9, 'Logout berhasil', '127.0.0.1', '2026-10-17 16:51:44'),
(125, 1, 'LOGIN', 'auth', 1, 'Login admin@example.test berhasil', '127.0.0.1', '2026-10-17 16:51:49'),
(126, 2, 'LOGIN', 'auth', 2, 'Login manager@example.test berhasil', '127.0.0.1', '2026-10-17 16:54:05'),
(127, 4, 'LOGIN', 'auth', 4, 'Login sari@example.test berhasil', '127.0.0.1', '2026-10-17 16:54:54');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint UNSIGNED DEFAULT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`audit_id`, `user_id`, `action`, `module`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'UPDATE', 'expenses', 8, '{\"amount\": \"50000.00\", \"description\": \"Test Unit Pengeluaran Budi\"}', '{\"amount\": 60000, \"description\": \"Test Unit Pengeluaran Budi Diperbarui\"}', NULL, NULL, '2026-10-15 00:49:52'),
(2, 3, 'UPDATE', 'expenses', 10, '{\"amount\": \"50000.00\", \"description\": \"Test Unit Pengeluaran Budi\"}', '{\"amount\": 60000, \"description\": \"Test Unit Pengeluaran Budi Diperbarui\"}', NULL, NULL, '2026-10-17 08:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'CAT-EL', 'Elektronik', 'Peralatan elektronik kantor, laptop, gadget, dan aksesoris komputer', '2026-10-11 13:29:45'),
(2, 'CAT-FR', 'Perabot', 'Meja kerja, kursi ergonomis, lemari berkas, dan perabot kantor lainnya', '2026-10-11 13:29:45'),
(3, 'CAT-AT', 'Alat Kantor', 'Kertas HVS, pulpen, binder, map dokumen, dan alat tulis harian', '2026-10-11 13:29:45'),
(4, 'CAT-PL', 'Perlengkapan', 'Perlengkapan kebersihan gudang, packaging, lakban, dan kotak kardus', '2026-10-11 13:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_id`, `customer_code`, `customer_name`, `code`, `name`, `email`, `phone`, `address`, `city`, `is_active`, `created_at`) VALUES
(2, 2, 'CUST-0001', 'Gajah Duduk', 'CUST-0001', 'Gajah Duduk', 'duduk@company.co.id', '212213214', 'JL. Industri Gajah Duduk', 'Bandung', 1, '2026-10-08 07:14:21');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` bigint UNSIGNED NOT NULL,
  `department_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager_id` bigint UNSIGNED DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_code`, `department_name`, `manager_id`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'DEPT-IT', 'Teknologi Informasi', NULL, 'Divisi IT & Sistem', 1, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(2, 'DEPT-FIN', 'Keuangan & Akuntansi', NULL, 'Divisi Finance & Tax', 1, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(3, 'DEPT-OPS', 'Operasional & Bisnis', NULL, 'Divisi Operasional', 1, '2026-10-15 00:49:48', '2026-10-15 00:49:48');

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id_divisi` int NOT NULL,
  `divisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `divisi`
--

INSERT INTO `divisi` (`id_divisi`, `divisi`) VALUES
(1, 'HR'),
(3, 'Warehouse Staff'),
(4, 'Purchasing Staff'),
(5, 'Finance Manager'),
(6, 'Sales Marketing');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id_employee` int NOT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `employee_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `manager_id` bigint UNSIGNED DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ACTIVE',
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_user` int UNSIGNED DEFAULT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `divisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Operasional',
  `jabatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `gaji` decimal(15,2) NOT NULL,
  `tanggal_masuk` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id_employee`, `employee_id`, `employee_number`, `full_name`, `email`, `phone`, `department_id`, `manager_id`, `position`, `bank_name`, `bank_account`, `join_date`, `status`, `photo`, `created_at`, `updated_at`, `id_user`, `nama`, `divisi`, `jabatan`, `gaji`, `tanggal_masuk`) VALUES
(1, 1, 'EMP-0101', 'Rina Manajer', 'manager@example.test', NULL, 3, NULL, 'Engineering Manager', 'BCA', '1234567890', '2026-10-15', 'ACTIVE', NULL, '2026-10-15 00:49:48', '2026-10-17 08:36:04', 2, 'Rina Manajer', 'Operasional', 'Engineering Manager', 0.00, '2026-10-15'),
(2, 2, 'EMP-0102', 'Budi Santoso', 'budi@example.test', NULL, 3, 1, 'Senior Engineer', 'BCA', '2345678901', '2026-10-15', 'ACTIVE', NULL, '2026-10-15 00:49:48', '2026-10-17 08:36:04', 3, 'Budi Santoso', 'Operasional', 'Senior Engineer', 0.00, '2026-10-15'),
(3, 3, 'EMP-0103', 'Sari Putri', 'sari@example.test', NULL, 3, 1, 'Junior Analyst', 'Mandiri', '3456789012', '2026-10-15', 'ACTIVE', NULL, '2026-10-15 00:49:48', '2026-10-17 08:36:04', 4, 'Sari Putri', 'Operasional', 'Junior Analyst', 0.00, '2026-10-15'),
(4, 4, 'EMP-0104', 'Dimas Finance', 'finance@example.test', NULL, 2, NULL, 'Finance Officer', 'BNI', '4567890123', '2026-10-15', 'ACTIVE', NULL, '2026-10-15 00:49:48', '2026-10-17 08:36:04', 5, 'Dimas Finance', 'Operasional', 'Finance Officer', 0.00, '2026-10-15'),
(5, 5, 'EMP-0105', 'Admin Expenses', 'admin@example.test', NULL, 1, NULL, 'System Administrator', 'BCA', '5678901234', '2026-10-15', 'ACTIVE', NULL, '2026-10-15 00:49:48', '2026-10-17 08:36:04', 1, 'Admin Expenses', 'Operasional', 'System Administrator', 0.00, '2026-10-15'),
(9, 9, 'EMP-HRIS-0009', 'Yudha Dwi', NULL, NULL, NULL, NULL, 'HR Director', NULL, NULL, '2026-10-05', 'ACTIVE', NULL, '2026-10-17 08:35:27', '2026-10-17 08:35:27', 9, 'Yudha Dwi', 'HR', 'HR Director', 123000.00, '2026-10-05'),
(11, 11, 'EMP-HRIS-0011', 'Salma Suhaimah', NULL, NULL, NULL, NULL, 'Purchasing Director', NULL, NULL, '2026-10-05', 'ACTIVE', NULL, '2026-10-17 08:35:27', '2026-10-17 08:35:27', 14, 'Salma Suhaimah', 'Purchasing Staff', 'Purchasing Director', 123000.00, '2026-10-05'),
(14, 14, 'EMP-HRIS-0014', 'Arafat Hamid', NULL, NULL, NULL, NULL, 'Purchasing Manager', NULL, NULL, '2026-10-05', 'ACTIVE', NULL, '2026-10-17 08:35:27', '2026-10-17 08:35:27', 18, 'Arafat Hamid', 'Purchasing Staff', 'Purchasing Manager', 123000.00, '2026-10-05'),
(15, 15, 'EMP-HRIS-0015', 'Afsaal Fayyaz', NULL, NULL, NULL, NULL, 'Head Director', NULL, NULL, '2026-10-08', 'ACTIVE', NULL, '2026-10-17 08:35:27', '2026-10-17 08:35:27', 19, 'Afsaal Fayyaz', 'Sales Marketing', 'Head Director', 212000.00, '2026-10-08'),
(16, 16, 'EMP-HRIS-0016', 'Zakie', NULL, NULL, NULL, NULL, 'Marketing Officer', NULL, NULL, '2026-10-08', 'ACTIVE', NULL, '2026-10-17 08:35:27', '2026-10-17 08:35:27', 20, 'Zakie', 'Sales Marketing', 'Marketing Officer', 123000.00, '2026-10-08'),
(17, 17, 'EMP-HRIS-0017', 'Halim', NULL, NULL, NULL, NULL, 'Warehouse Officer', NULL, NULL, '2026-10-09', 'ACTIVE', NULL, '2026-10-17 08:35:27', '2026-10-17 08:35:27', 22, 'Halim', 'Warehouse Staff', 'Warehouse Officer', 123000.00, '2026-10-09');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` bigint UNSIGNED NOT NULL,
  `expense_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `project_id` bigint UNSIGNED DEFAULT NULL,
  `is_reimbursable` tinyint(1) NOT NULL DEFAULT '1',
  `is_reinvoiceable` tinyint(1) NOT NULL DEFAULT '0',
  `reinvoice_status` enum('NOT_INVOICED','READY_TO_INVOICE','INVOICED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_INVOICED',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REFUSED','POSTED','REIMBURSEMENT_PENDING','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `receipt_original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_stored_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_mime` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_size` bigint UNSIGNED DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED NOT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` bigint UNSIGNED DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `paid_by` bigint UNSIGNED DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `expense_number`, `employee_id`, `category_id`, `expense_date`, `description`, `amount`, `currency`, `payment_method`, `bank_name`, `merchant`, `department_id`, `customer_id`, `project_id`, `is_reimbursable`, `is_reinvoiceable`, `reinvoice_status`, `status`, `receipt_original_name`, `receipt_stored_name`, `receipt_path`, `receipt_mime`, `receipt_size`, `notes`, `created_by`, `updated_by`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `paid_by`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 'EXP-DEMO-001', 2, 2, '2026-10-13', 'Makan siang koordinasi tim', 150000.00, 'IDR', 'BANK_TRANSFER', 'BCA', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'DRAFT', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(2, 'EXP-DEMO-002', 2, 3, '2026-10-14', 'Taksi Bluebird ke klien', 85000.00, 'IDR', 'BANK_TRANSFER', 'BCA', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'DRAFT', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(3, 'EXP-DEMO-003', 3, 1, '2026-10-11', 'Tiket Kereta Argo Parahyangan PP', 450000.00, 'IDR', 'BANK_TRANSFER', 'Mandiri', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'SUBMITTED', NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(4, 'EXP-DEMO-004', 3, 2, '2026-10-12', 'Makan malam dinas Bandung', 175000.00, 'IDR', 'BANK_TRANSFER', 'Mandiri', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'SUBMITTED', NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(5, 'EXP-DEMO-005', 2, 4, '2026-10-09', 'Buku catatan & tinta printer workshop', 320000.00, 'IDR', 'BANK_TRANSFER', 'BCA', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'PAID', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, 5, '2026-10-15 07:49:52', '2026-10-15 00:49:48', '2026-10-15 00:49:52'),
(6, 'EXP-DEMO-006', 3, 5, '2026-10-08', 'Pembelian cinderamata tanpa kuitansi resmi', 600000.00, 'IDR', 'BANK_TRANSFER', 'Mandiri', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'REFUSED', NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(7, 'EXP-DEMO-007', 2, 1, '2026-09-30', 'Bensin dinas luar kota', 250000.00, 'IDR', 'BANK_TRANSFER', 'BCA', NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'PAID', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(9, 'EXP-2026-000001', 2, 1, '2026-10-15', 'Item Laporan Uji', 120000.00, 'IDR', 'CASH', NULL, NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'APPROVED', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 2, '2026-10-15 07:49:52', NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:52', '2026-10-15 00:49:52'),
(11, 'EXP-2026-000002', 2, 1, '2026-10-17', 'Item Laporan Uji', 120000.00, 'IDR', 'CASH', NULL, NULL, NULL, NULL, NULL, 1, 0, 'NOT_INVOICED', 'APPROVED', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 2, '2026-10-17 15:24:44', NULL, NULL, NULL, NULL, NULL, '2026-10-17 08:24:44', '2026-10-17 08:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `expense_approvals`
--

CREATE TABLE `expense_approvals` (
  `approval_id` bigint UNSIGNED NOT NULL,
  `report_id` bigint UNSIGNED NOT NULL,
  `approver_id` bigint UNSIGNED NOT NULL,
  `action` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_approvals`
--

INSERT INTO `expense_approvals` (`approval_id`, `report_id`, `approver_id`, `action`, `comment`, `created_at`) VALUES
(1, 2, 4, 'SUBMITTED', 'Laporan diajukan.', '2026-10-15 00:49:48'),
(2, 3, 2, 'APPROVED', 'Disetujui.', '2026-10-15 00:49:48'),
(3, 4, 2, 'REFUSED', 'Kuitansi belum resmi dari toko/merchant. Mohon lampirkan faktur atau nota resmi.', '2026-10-15 00:49:48'),
(4, 5, 2, 'APPROVED', 'Disetujui.', '2026-10-15 00:49:48'),
(5, 6, 3, 'SUBMITTED', 'Laporan diajukan untuk persetujuan manajer.', '2026-10-15 00:49:52'),
(6, 6, 2, 'REFUSED', 'Kuitansi tidak terbaca jelas. Mohon unggah ulang.', '2026-10-15 00:49:52'),
(7, 6, 3, 'RETURNED_TO_DRAFT', 'Laporan dikembalikan ke status draft untuk revisi.', '2026-10-15 00:49:52'),
(8, 6, 3, 'SUBMITTED', 'Laporan diajukan untuk persetujuan manajer.', '2026-10-15 00:49:52'),
(9, 6, 2, 'APPROVED', 'Sekarang sudah lengkap. Disetujui.', '2026-10-15 00:49:52'),
(10, 3, 5, 'POSTED', 'Laporan telah dibukukan oleh tim Keuangan.', '2026-10-15 00:49:52'),
(11, 3, 5, 'REIMBURSEMENT_PENDING', 'Dimasukkan ke antrean pembayaran reimbursement.', '2026-10-15 00:49:52'),
(12, 3, 5, 'PAID', 'Reimbursement telah dibayarkan. Ref: TRF-TEST-20261011-999', '2026-10-15 00:49:52'),
(13, 8, 3, 'SUBMITTED', 'Laporan diajukan untuk persetujuan manajer.', '2026-10-17 08:24:44'),
(14, 8, 2, 'REFUSED', 'Kuitansi tidak terbaca jelas. Mohon unggah ulang.', '2026-10-17 08:24:44'),
(15, 8, 3, 'RETURNED_TO_DRAFT', 'Laporan dikembalikan ke status draft untuk revisi.', '2026-10-17 08:24:44'),
(16, 8, 3, 'SUBMITTED', 'Laporan diajukan untuk persetujuan manajer.', '2026-10-17 08:24:44'),
(17, 8, 2, 'APPROVED', 'Sekarang sudah lengkap. Disetujui.', '2026-10-17 08:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `category_id` bigint UNSIGNED NOT NULL,
  `category_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_amount` decimal(15,2) DEFAULT NULL,
  `is_fixed_price` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`category_id`, `category_code`, `category_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'TRAVEL', 'Travel', 'Biaya perjalanan dinas', 1, '2026-10-15 00:49:33', '2026-10-15 00:49:33'),
(2, 'MEAL', 'Meal', 'Biaya makan', 1, '2026-10-15 00:49:33', '2026-10-15 00:49:33'),
(3, 'TRANSPORT', 'Transport', 'Biaya transportasi', 1, '2026-10-15 00:49:33', '2026-10-15 00:49:33'),
(4, 'OFFICE', 'Office', 'Biaya kebutuhan kantor', 1, '2026-10-15 00:49:33', '2026-10-15 00:49:33'),
(5, 'OTHER', 'Other', 'Biaya lainnya', 1, '2026-10-15 00:49:33', '2026-10-15 00:49:33');

-- --------------------------------------------------------

--
-- Table structure for table `expense_comments`
--

CREATE TABLE `expense_comments` (
  `comment_id` bigint UNSIGNED NOT NULL,
  `report_id` bigint UNSIGNED NOT NULL,
  `author_id` bigint UNSIGNED NOT NULL,
  `comment_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_reports`
--

CREATE TABLE `expense_reports` (
  `report_id` bigint UNSIGNED NOT NULL,
  `report_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REFUSED','POSTED','REIMBURSEMENT_PENDING','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `refused_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_reports`
--

INSERT INTO `expense_reports` (`report_id`, `report_number`, `employee_id`, `title`, `description`, `start_date`, `end_date`, `total_amount`, `status`, `submitted_at`, `approved_at`, `posted_at`, `paid_at`, `refused_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'GTE-DEMO-001', 2, 'Biaya Transport & Jamuan Budi', NULL, NULL, NULL, 235000.00, 'DRAFT', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(2, 'GTE-DEMO-002', 3, 'Kunjungan Lapangan Sari ke Bandung', NULL, NULL, NULL, 625000.00, 'SUBMITTED', '2026-10-15 07:49:48', NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(3, 'GTE-DEMO-003', 2, 'Pengadaan Kebutuhan Workshop Internal', NULL, NULL, NULL, 320000.00, 'PAID', '2026-10-15 07:49:48', '2026-10-15 07:49:48', '2026-10-15 07:49:52', '2026-10-15 07:49:52', NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:52'),
(4, 'GTE-DEMO-004', 3, 'Cinderamata Klien Sari (Ditolak)', NULL, NULL, NULL, 600000.00, 'REFUSED', '2026-10-15 07:49:48', NULL, NULL, NULL, '2026-10-15 07:49:48', 'Kuitansi belum resmi dari toko/merchant. Mohon lampirkan faktur atau nota resmi.', '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(5, 'GTE-DEMO-005', 2, 'Operasional Transport Budi Bulan Lalu', NULL, NULL, NULL, 250000.00, 'PAID', '2026-10-15 07:49:48', '2026-10-15 07:49:48', '2026-10-15 07:49:48', '2026-10-15 07:49:48', NULL, NULL, '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(6, 'GTE-2026-000001', 2, 'Laporan Uji Otomatis Budi', 'Uji alur submit - approve - refuse', '2026-10-12', '2026-10-15', 120000.00, 'APPROVED', '2026-10-15 07:49:52', '2026-10-15 07:49:52', NULL, NULL, NULL, NULL, '2026-10-15 00:49:52', '2026-10-15 00:49:52'),
(7, 'GTE-2026-000002', 2, 'Laporan Kedua', NULL, NULL, NULL, 0.00, 'DRAFT', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-15 00:49:52', '2026-10-15 00:49:52'),
(8, 'GTE-2026-000003', 2, 'Laporan Uji Otomatis Budi', 'Uji alur submit - approve - refuse', '2026-10-14', '2026-10-17', 120000.00, 'APPROVED', '2026-10-17 15:24:44', '2026-10-17 15:24:44', NULL, NULL, NULL, NULL, '2026-10-17 08:24:44', '2026-10-17 08:24:44'),
(9, 'GTE-2026-000004', 2, 'Laporan Kedua', NULL, NULL, NULL, 0.00, 'DRAFT', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-17 08:24:44', '2026-10-17 08:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `expense_report_items`
--

CREATE TABLE `expense_report_items` (
  `report_item_id` bigint UNSIGNED NOT NULL,
  `report_id` bigint UNSIGNED NOT NULL,
  `expense_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_report_items`
--

INSERT INTO `expense_report_items` (`report_item_id`, `report_id`, `expense_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 3),
(4, 2, 4),
(5, 3, 5),
(6, 4, 6),
(7, 5, 7),
(8, 6, 9),
(9, 8, 11);

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `goods_receipt_id` int UNSIGNED NOT NULL,
  `grn_no` varchar(40) NOT NULL,
  `purchase_transaction_id` int UNSIGNED NOT NULL,
  `received_by` int UNSIGNED DEFAULT NULL,
  `received_date` date NOT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_details`
--

CREATE TABLE `goods_receipt_details` (
  `goods_receipt_detail_id` int UNSIGNED NOT NULL,
  `goods_receipt_id` int UNSIGNED NOT NULL,
  `purchase_transaction_detail_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `qty_received` decimal(15,2) NOT NULL,
  `condition_note` enum('Good','Damaged','Shortage') NOT NULL DEFAULT 'Good'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int UNSIGNED NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int UNSIGNED NOT NULL,
  `identifier` varchar(150) NOT NULL COMMENT 'email yang dicoba login',
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` enum('cash','transfer','card') DEFAULT 'transfer',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id_payroll` bigint UNSIGNED NOT NULL,
  `id_employee` int NOT NULL,
  `periode` date NOT NULL,
  `total_gaji` decimal(15,2) NOT NULL,
  `tanggal_bayar` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `permission_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `description`) VALUES
(1, 'dashboard.view', 'Melihat dashboard'),
(2, 'users.manage', 'Mengelola user'),
(3, 'employees.manage', 'Mengelola employee'),
(4, 'payrolls.manage', 'Mengelola payroll'),
(5, 'products.manage', 'Mengelola product'),
(6, 'customers.manage', 'Mengelola customer'),
(7, 'suppliers.manage', 'Mengelola supplier'),
(8, 'purchasing.manage', 'Mengelola purchasing'),
(9, 'inventory.manage', 'Mengelola inventory'),
(10, 'sales.manage', 'Mengelola sales'),
(11, 'invoices.manage', 'Mengelola invoice'),
(12, 'payments.manage', 'Mengelola payment'),
(13, 'expenses.manage', 'Mengelola expense'),
(14, 'expense.approve', 'Menyetujui expense'),
(15, 'reports.view', 'Melihat laporan'),
(16, 'settings.manage', 'Mengelola pengaturan'),
(17, 'expense.post', 'Mem-posting laporan expense yang telah disetujui'),
(18, 'expense.reimburse', 'Memproses reimbursement expense');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text,
  `unit` varchar(20) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `minimum_stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `sku`, `product_name`, `description`, `unit`, `selling_price`, `purchase_price`, `stock`, `minimum_stock`, `location`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'PRD-001', 'Kertas HVS A4 80gsm (rim)', NULL, 'Rim', 0.00, 55000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(2, NULL, 'PRD-002', 'Tinta Printer Epson 664 Black', NULL, 'Botol', 0.00, 95000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(3, NULL, 'PRD-003', 'Toner Cartridge HP 85A', NULL, 'Unit', 0.00, 725000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(4, NULL, 'PRD-004', 'Map Plastik Bening', NULL, 'Pack', 0.00, 32000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(5, NULL, 'PRD-005', 'Baterai AA Alkaline', NULL, 'Pack', 0.00, 28000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(6, NULL, 'PRD-006', 'Flashdisk 32GB', NULL, 'Unit', 0.00, 65000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(7, NULL, 'PRD-007', 'Amplop Coklat Folio', NULL, 'Pack', 0.00, 21000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(8, NULL, 'PRD-008', 'Stabilizer / UPS 600VA', NULL, 'Unit', 0.00, 385000.00, 0.000, 0.000, NULL, 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(9, NULL, 'SKU-001', 'Produk Contoh A', NULL, 'pcs', 50000.00, 0.00, 95.000, 0.000, NULL, 0, '2026-10-08 06:45:32', '2026-10-11 22:19:17', NULL),
(10, NULL, 'SKU-002', 'Produk Contoh B', NULL, 'pcs', 125000.00, 0.00, 50.000, 0.000, NULL, 1, '2026-10-08 06:45:32', '2026-10-08 06:45:32', NULL),
(11, NULL, 'SKU-003', 'Produk Contoh C', NULL, 'pcs', 75000.00, 0.00, 200.000, 0.000, NULL, 1, '2026-10-08 06:45:32', '2026-10-08 06:45:32', NULL),
(12, 1, 'EL-001', 'Laptop ThinkPad X1', 'Laptop bisnis ultra-ringan performa tinggi untuk manajerial', 'unit', 15000000.00, 12000000.00, 18.000, 5.000, 'Rak Elektronik A1', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(13, 1, 'EL-002', 'Mouse Wireless M1', 'Mouse nirkabel ergonomis baterai tahan lama', 'pcs', 180000.00, 120000.00, 4.000, 10.000, 'Rak Aksesoris A2', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(14, 2, 'AT-001', 'Meja Kerja 120 cm', 'Meja kerja kokoh minimalis kayu jati belanda', 'unit', 1250000.00, 850000.00, 12.000, 5.000, 'Gudang Furnitur F1', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(15, 2, 'AT-002', 'Kursi Kantor Ergonomis', 'Kursi putar sandaran jaring lumbar support', 'unit', 1600000.00, 1100000.00, 0.000, 5.000, 'Gudang Furnitur F2', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(16, 3, 'AK-001', 'Paper A4 80gsm', 'Kertas HVS putih bersih ukuran A4 isi 500 lembar per rim', 'rim', 58000.00, 45000.00, 120.000, 30.000, 'Rak ATK C1', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(17, 3, 'AK-002', 'Pulpen Gel Hitam 0.5mm', 'Pulpen tinta gel hitam pekat anti macet 1 lusin', 'box', 42000.00, 28000.00, 35.000, 15.000, 'Rak ATK C2', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(18, 1, 'EL-003', 'Keyboard Mechanical RGB', 'Keyboard mechanical switch tactile responsif dengan lampu RGB', 'unit', 680000.00, 450000.00, 8.000, 10.000, 'Rak Elektronik A3', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(19, 1, 'EL-004', 'Monitor 24 Inch FHD', 'Monitor IPS 1080p 75Hz bezel-less proteksi mata', 'unit', 1950000.00, 1400000.00, 15.000, 5.000, 'Rak Elektronik A4', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(20, 4, 'PK-001', 'Lakban Bening 2 Inch', 'Lakban perekat kuat serbaguna untuk packing karton', 'roll', 18000.00, 12000.00, 60.000, 20.000, 'Gudang Packing P1', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL),
(21, 4, 'PK-002', 'Kardus Box Ukuran Sedang', 'Kotak karton gelombang tebal ukuran 40x30x25 cm', 'pcs', 14000.00, 8500.00, 200.000, 50.000, 'Gudang Packing P2', 1, '2026-10-11 13:29:45', '2026-10-11 13:29:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` bigint UNSIGNED NOT NULL,
  `project_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PLANNING','ACTIVE','COMPLETED','ON_HOLD','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisitions`
--

CREATE TABLE `purchase_requisitions` (
  `purchase_requisition_id` int UNSIGNED NOT NULL,
  `pr_no` varchar(40) NOT NULL,
  `requester_id` int UNSIGNED DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `needed_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REJECTED','CONVERTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `notes` text,
  `approval_threshold_snapshot` decimal(15,2) DEFAULT NULL COMMENT 'nilai ambang batas approval matrix pada saat submit, untuk jejak audit',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisition_details`
--

CREATE TABLE `purchase_requisition_details` (
  `purchase_requisition_detail_id` int UNSIGNED NOT NULL,
  `purchase_requisition_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `qty` decimal(15,2) NOT NULL,
  `estimated_unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `purchase_return_id` int UNSIGNED NOT NULL,
  `return_no` varchar(40) NOT NULL,
  `goods_receipt_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `qty_returned` decimal(15,2) NOT NULL,
  `condition` enum('Defective','Shortage','Wrong Item','Other') NOT NULL,
  `reason` text,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_transactions`
--

CREATE TABLE `purchase_transactions` (
  `purchase_transaction_id` int UNSIGNED NOT NULL,
  `transaction_no` varchar(40) NOT NULL,
  `purchase_requisition_id` int UNSIGNED DEFAULT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SENT','CONFIRMED','PARTIALLY_RECEIVED','RECEIVED','INVOICED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_transaction_details`
--

CREATE TABLE `purchase_transaction_details` (
  `purchase_transaction_detail_id` int UNSIGNED NOT NULL,
  `purchase_transaction_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `qty` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reimbursements`
--

CREATE TABLE `reimbursements` (
  `reimbursement_id` bigint UNSIGNED NOT NULL,
  `report_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('PENDING','PROCESSING','PAID','FAILED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `processed_by` bigint UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reimbursements`
--

INSERT INTO `reimbursements` (`reimbursement_id`, `report_id`, `employee_id`, `amount`, `payment_method`, `payment_reference`, `payment_date`, `notes`, `status`, `processed_by`, `processed_at`, `created_at`, `updated_at`) VALUES
(1, 5, 2, 250000.00, 'BANK_TRANSFER', 'TRF-DEMO-20261011-01', '2026-10-15', NULL, 'PAID', 5, '2026-10-15 07:49:48', '2026-10-15 00:49:48', '2026-10-15 00:49:48'),
(2, 3, 2, 320000.00, 'BANK_TRANSFER', 'TRF-TEST-20261011-999', '2026-10-15', 'Pembayaran selesai melalui internet banking', 'PAID', 5, '2026-10-15 07:49:52', '2026-10-15 00:49:52', '2026-10-15 00:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_role` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED DEFAULT NULL,
  `role_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id_role`, `role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 1, 'Admin_HR\r\n', NULL, '2026-10-17 08:35:27'),
(2, 2, 'Admin_Purchase', NULL, '2026-10-17 08:35:27'),
(3, 3, 'Purchasing_Staff', NULL, '2026-10-17 08:35:27'),
(4, 4, 'Admin_Sales', NULL, '2026-10-17 08:35:27'),
(5, 5, 'Marketing_Staff', NULL, '2026-10-17 08:35:27'),
(6, 6, 'Admin_Warehouse', NULL, '2026-10-17 08:35:27'),
(7, 7, 'Warehouse_Staff', NULL, '2026-10-17 08:35:27'),
(8, 8, 'ADMIN', 'Administrator sistem', '2026-10-17 08:35:27'),
(9, 9, 'HR', 'Pengelola HR dan payroll', '2026-10-17 08:35:27'),
(10, 10, 'PURCHASING', 'Pengelola purchasing/procurement', '2026-10-17 08:35:27'),
(11, 11, 'WAREHOUSE', 'Pengelola gudang dan inventory', '2026-10-17 08:35:27'),
(12, 12, 'SALES', 'Pengelola penjualan', '2026-10-17 08:35:27'),
(13, 13, 'FINANCE', 'Pengelola finance dan expense', '2026-10-17 08:35:27'),
(14, 14, 'MANAGER', 'Approver/manajer', '2026-10-17 08:35:27'),
(15, 15, 'EMPLOYEE', 'Pengguna karyawan untuk modul GT Expense', '2026-10-17 08:35:27');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(8, 1),
(13, 1),
(14, 1),
(15, 1),
(8, 2),
(8, 3),
(13, 12),
(8, 13),
(13, 13),
(14, 13),
(15, 13),
(14, 14),
(8, 15),
(13, 15),
(14, 15),
(15, 15),
(8, 16),
(13, 17),
(13, 18);

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `id` int UNSIGNED NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('draft','confirmed','invoiced','paid','cancelled') DEFAULT 'draft',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `discount` decimal(15,2) DEFAULT '0.00',
  `tax` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `order_no`, `customer_id`, `order_date`, `status`, `subtotal`, `discount`, `tax`, `total_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SO-20261008-0001', 2, '2026-10-08', 'confirmed', 250000.00, 10.00, 27500.00, 277490.00, '', 19, '2026-10-08 07:42:09', '2026-10-08 07:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

CREATE TABLE `sales_order_items` (
  `id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_order_items`
--

INSERT INTO `sales_order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 9, 5, 50000.00, 250000.00);

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `migration_id` int UNSIGNED NOT NULL,
  `migration_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`migration_id`, `migration_name`, `applied_at`) VALUES
(1, '001_gt_expense_integrity.sql', '2026-10-17 08:37:40'),
(2, '002_add_bank_name_to_expenses.sql', '2026-10-17 08:37:40'),
(3, '20261017_expenses_integration.sql', '2026-10-17 08:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` bigint UNSIGNED NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'company_name', 'ERP Company', '2026-10-15 00:49:33'),
(2, 'currency', 'IDR', '2026-10-15 00:49:33'),
(3, 'tax_rate', '11', '2026-10-15 00:49:33');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `stock_movement_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `type` enum('PURCHASE','PURCHASE_RETURN','SALE','SALES_RETURN','ADJUSTMENT_IN','ADJUSTMENT_OUT') NOT NULL,
  `qty` decimal(15,3) NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `supplier_id` int UNSIGNED DEFAULT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`stock_movement_id`, `product_id`, `type`, `qty`, `reference_no`, `supplier_id`, `customer_id`, `created_by`, `notes`, `created_at`) VALUES
(1, 9, 'SALE', 5.000, 'SO-SO-20261008-0001-SALE', NULL, NULL, NULL, NULL, '2026-10-08 14:42:12'),
(2, 12, 'ADJUSTMENT_IN', 15.000, 'INV-OPEN-EL-001', NULL, NULL, NULL, 'Saldo awal sebelum riwayat transaksi Inventory', '2026-09-01 00:00:00'),
(3, 13, 'ADJUSTMENT_IN', 4.000, 'INV-OPEN-EL-002', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(4, 14, 'ADJUSTMENT_IN', 12.000, 'INV-OPEN-AT-001', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(5, 16, 'ADJUSTMENT_IN', 30.000, 'INV-OPEN-AK-001', NULL, NULL, NULL, 'Saldo awal sebelum riwayat transaksi Inventory', '2026-09-01 00:00:00'),
(6, 17, 'ADJUSTMENT_IN', 35.000, 'INV-OPEN-AK-002', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(7, 18, 'ADJUSTMENT_IN', 8.000, 'INV-OPEN-EL-003', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(8, 19, 'ADJUSTMENT_IN', 15.000, 'INV-OPEN-EL-004', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(9, 20, 'ADJUSTMENT_IN', 60.000, 'INV-OPEN-PK-001', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(10, 21, 'ADJUSTMENT_IN', 200.000, 'INV-OPEN-PK-002', NULL, NULL, NULL, 'Saldo awal migrasi dari ERP Inventory', '2026-10-11 13:29:45'),
(11, 12, 'PURCHASE', 5.000, 'PO-202609-001', NULL, NULL, NULL, 'Pembelian lama ERP Inventory; supplier: PT Maju Teknologi Sejahtera', '2026-09-10 12:00:00'),
(12, 12, 'SALE', 2.000, 'INV-202609-001', NULL, NULL, NULL, 'Penjualan lama ERP Inventory; customer: PT Sinergi Media Digital', '2026-09-15 12:00:00'),
(13, 16, 'PURCHASE', 100.000, 'PO-202609-002', NULL, NULL, NULL, 'Pembelian lama ERP Inventory; supplier: PT Paperindo Prima Mandiri', '2026-09-12 12:00:00'),
(14, 16, 'SALE', 10.000, 'INV-202609-002', NULL, NULL, NULL, 'Penjualan lama ERP Inventory; customer: CV Solusi Finansial Kita', '2026-09-16 12:00:00');

--
-- Triggers `stock_movements`
--
DELIMITER $$
CREATE TRIGGER `trg_stock_movement_after_insert` AFTER INSERT ON `stock_movements` FOR EACH ROW UPDATE `products`
SET `stock` = `stock` + IF(
  NEW.`type` IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'),
  NEW.`qty`,
  -NEW.`qty`
)
WHERE `product_id` = NEW.`product_id`
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int UNSIGNED NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_code`, `supplier_name`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SUP-001', 'PT Sumber Makmur Sejahtera', '021-5551234', 'purchasing@sumbermakmur.co.id', 'Jl. Industri Raya No. 12, Jakarta Utara', 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(2, 'SUP-002', 'CV Mitra Elektronik Nusantara', '022-7778890', 'sales@mitraelektronik.co.id', 'Jl. Soekarno Hatta No. 88, Bandung', 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(3, 'SUP-003', 'PT Anugerah Kertas Abadi', '031-8889991', 'order@anugerahkertas.co.id', 'Jl. Rungkut Industri No. 5, Surabaya', 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL),
(4, 'SUP-004', 'UD Sinar Plastik Jaya', '024-6667778', 'cs@sinarplastik.co.id', 'Jl. Kaligawe No. 21, Semarang', 1, '2026-10-07 13:48:25', '2026-10-07 13:48:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `role_id` int UNSIGNED DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`, `deleted_at`, `user_id`, `employee_id`, `role_id`, `last_login`) VALUES
(1, 'Admin Gte', 'admin_gte', 'admin@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'ADMIN', 1, '2026-10-15 07:49:48', '2026-10-17 16:51:49', NULL, 1, 5, 8, '2026-10-17 16:51:49'),
(2, 'Rina Manager', 'rina_manager', 'manager@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'MANAGER', 1, '2026-10-15 07:49:48', '2026-10-17 16:54:05', NULL, 2, 1, 14, '2026-10-17 16:54:05'),
(3, 'Budi Employee', 'budi_employee', 'budi@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'EMPLOYEE', 1, '2026-10-15 07:49:48', '2026-10-17 16:50:51', NULL, 3, 2, 15, '2026-10-17 16:50:51'),
(4, 'Sari Employee', 'sari_employee', 'sari@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'EMPLOYEE', 1, '2026-10-15 07:49:48', '2026-10-17 16:54:54', NULL, 4, 3, 15, '2026-10-17 16:54:54'),
(5, 'Dimas Finance', 'dimas_finance', 'finance@example.test', '$2y$10$7sVcZVJ3imE3Q0R7KI.gzuTKKkosWb2DIJZCHtPofTU1JRpQ.S2vy', 'FINANCE', 1, '2026-10-15 07:49:48', '2026-10-17 16:19:32', NULL, 5, 4, 13, '2026-10-17 16:19:32'),
(9, 'Yudha Dwi', 'Yuraa_n', 'yudha@gmail.com', '$2y$10$DHyunySX2O0t7Z/jt/3Jeedh9MkeY9Qpvk//.JwiUBeU4q7c5YE5u', 'Admin_HR', 1, '2026-10-04 23:21:10', '2026-10-17 16:51:38', NULL, 9, 9, 1, '2026-10-17 16:51:38'),
(14, 'Salma Suhaimah', 'saruma', 'salma@gmail.com', '$2y$10$PypH2eBZkk6b//vTLRRYNuz3TOIVGMEnqPs0VzKdJUbvzUpSMt4vK', 'Admin_Purchase', 1, '2026-10-05 08:55:43', '2026-10-17 16:26:17', NULL, 14, 11, 2, '2026-10-17 16:26:17'),
(18, 'Arafat Hamid', 'alfckqih', 'arafat@gmail.com', '$2y$10$ukUUPpFdl.uysAzap0rhkONcr/fZC7QH.MQtrTVuoFm9e4y7Ea.YW', 'Purchasing_Staff', 1, '2026-10-08 06:47:09', '2026-10-17 15:35:27', NULL, 18, 14, 3, '2026-10-11 22:40:33'),
(19, 'Afsaal Fayyaz', 'fayyaz', 'afsaal@gmail.com', '$2y$10$BByiUIEp9O7DvhW/6Mz20OtP9ePBRTzveTZpT8LNC1ll37wQMLWUS', 'Admin_Sales', 1, '2026-10-08 13:46:50', '2026-10-17 16:26:33', NULL, 19, 15, 4, '2026-10-17 16:26:33'),
(20, 'Zakie Dinar', 'zakiee', 'zakie@gmail.com', '$2y$10$mmaQw18P/Vl8PJtn.aBwuOWRjg/AbMLkZ8vpanh4f3mRI0XR1r4Ji', 'Marketing_Staff', 1, '2026-10-08 13:49:04', '2026-10-17 15:35:27', NULL, 20, 16, 5, '2026-10-11 22:41:18'),
(21, 'Sabiq Hibatul', 'masbiku', 'sabiq@gmail.com', '$2y$10$vmpoJuAiqumTgzV5L4yCnuul0/CkTTijsqbTetEpX2VDutsi7lqVa', 'Admin_Warehouse', 1, '2026-10-11 22:17:34', '2026-10-17 16:19:33', NULL, 21, NULL, 6, '2026-10-17 16:19:33'),
(22, 'Thio Halimawan', 'halimawan', 'thio@gmail.com', '$2y$10$BZsZfZMCJ6XNcJhUMBTkpuoG2hD68k5Wl2HPFI1xTBWDFLVfhsXWW', 'Warehouse_Staff', 1, '2026-10-11 22:43:09', '2026-10-17 15:35:27', NULL, 22, 17, 7, '2026-10-11 22:57:45');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_invoices`
--

CREATE TABLE `vendor_invoices` (
  `vendor_invoice_id` int UNSIGNED NOT NULL,
  `invoice_no` varchar(60) NOT NULL COMMENT 'nomor invoice dari supplier',
  `purchase_transaction_id` int UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `invoice_date` date NOT NULL,
  `entered_by` int UNSIGNED DEFAULT NULL,
  `match_status` enum('PENDING','MATCHED','MISMATCHED','EXCEPTION') NOT NULL,
  `match_notes` text COMMENT 'rincian selisih PO vs GRN vs Invoice jika mismatch',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REJECTED','PAID','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_employee_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_employee_summary` (
`employee_id` bigint unsigned
,`employee_number` varchar(50)
,`full_name` varchar(150)
,`position` varchar(100)
,`department_code` varchar(30)
,`department_name` varchar(100)
,`status` enum('ACTIVE','INACTIVE')
,`username` varchar(50)
,`role_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_stock`
-- (See below for the actual view)
--
CREATE TABLE `v_product_stock` (
`product_id` int unsigned
,`sku` varchar(50)
,`product_name` varchar(150)
,`unit` varchar(20)
,`stock` decimal(15,3)
,`minimum_stock` decimal(15,3)
,`is_low_stock` int
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_al_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_al_user` (`user_id`),
  ADD KEY `idx_al_created` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_module_record` (`module`,`record_id`),
  ADD KEY `idx_audit_created_at` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_code` (`code`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sales_customer_code` (`code`),
  ADD UNIQUE KEY `uq_customers_customer_id` (`customer_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `idx_departments_manager` (`manager_id`);

--
-- Indexes for table `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id_divisi`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id_employee`),
  ADD UNIQUE KEY `uq_employees_employee_id` (`employee_id`),
  ADD UNIQUE KEY `uq_employees_employee_number` (`employee_number`),
  ADD UNIQUE KEY `uq_employees_email` (`email`),
  ADD KEY `idx_employees_user` (`id_user`),
  ADD KEY `fk_employees_department` (`department_id`),
  ADD KEY `fk_employees_manager` (`manager_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD UNIQUE KEY `expense_number` (`expense_number`),
  ADD KEY `idx_expense_employee` (`employee_id`),
  ADD KEY `idx_expense_category` (`category_id`),
  ADD KEY `idx_expense_department` (`department_id`),
  ADD KEY `idx_expense_customer` (`customer_id`),
  ADD KEY `idx_expense_project` (`project_id`),
  ADD KEY `idx_expense_status` (`status`),
  ADD KEY `idx_expense_created_by` (`created_by`),
  ADD KEY `fk_expense_updated_by` (`updated_by`),
  ADD KEY `fk_expense_approved_by` (`approved_by`),
  ADD KEY `fk_expense_rejected_by` (`rejected_by`),
  ADD KEY `fk_expense_paid_by` (`paid_by`);

--
-- Indexes for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `idx_expense_approval_report` (`report_id`),
  ADD KEY `idx_expense_approval_approver` (`approver_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- Indexes for table `expense_comments`
--
ALTER TABLE `expense_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_expense_comments_report_created` (`report_id`,`created_at`),
  ADD KEY `fk_expense_comments_author` (`author_id`);

--
-- Indexes for table `expense_reports`
--
ALTER TABLE `expense_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD UNIQUE KEY `report_number` (`report_number`),
  ADD KEY `idx_expense_report_employee` (`employee_id`),
  ADD KEY `idx_expense_report_status` (`status`);

--
-- Indexes for table `expense_report_items`
--
ALTER TABLE `expense_report_items`
  ADD PRIMARY KEY (`report_item_id`),
  ADD UNIQUE KEY `uq_report_expense` (`report_id`,`expense_id`),
  ADD UNIQUE KEY `uq_expense_report_items_expense` (`expense_id`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`goods_receipt_id`),
  ADD UNIQUE KEY `grn_no` (`grn_no`),
  ADD KEY `idx_grn_po` (`purchase_transaction_id`),
  ADD KEY `fk_grn_user` (`received_by`);

--
-- Indexes for table `goods_receipt_details`
--
ALTER TABLE `goods_receipt_details`
  ADD PRIMARY KEY (`goods_receipt_detail_id`),
  ADD KEY `fk_grnd_grn` (`goods_receipt_id`),
  ADD KEY `fk_grnd_po_detail` (`purchase_transaction_detail_id`),
  ADD KEY `fk_grnd_product` (`product_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sales_invoice_no` (`invoice_no`),
  ADD UNIQUE KEY `uq_sales_invoice_order` (`order_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_la_identifier_time` (`identifier`,`attempted_at`),
  ADD KEY `idx_la_ip_time` (`ip_address`,`attempted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_payment_invoice` (`invoice_id`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id_payroll`),
  ADD KEY `fk_payroll_employee` (`id_employee`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_category_id` (`category_id`),
  ADD KEY `idx_products_name` (`product_name`),
  ADD KEY `idx_products_stock` (`stock`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `idx_projects_customer` (`customer_id`);

--
-- Indexes for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD PRIMARY KEY (`purchase_requisition_id`),
  ADD UNIQUE KEY `pr_no` (`pr_no`),
  ADD KEY `idx_pr_status` (`status`),
  ADD KEY `fk_pr_requester` (`requester_id`),
  ADD KEY `fk_pr_approver` (`approved_by`);

--
-- Indexes for table `purchase_requisition_details`
--
ALTER TABLE `purchase_requisition_details`
  ADD PRIMARY KEY (`purchase_requisition_detail_id`),
  ADD KEY `fk_prd_pr` (`purchase_requisition_id`),
  ADD KEY `fk_prd_product` (`product_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`purchase_return_id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `fk_pret_grn` (`goods_receipt_id`),
  ADD KEY `fk_pret_product` (`product_id`),
  ADD KEY `fk_pret_user` (`created_by`);

--
-- Indexes for table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  ADD PRIMARY KEY (`purchase_transaction_id`),
  ADD UNIQUE KEY `transaction_no` (`transaction_no`),
  ADD KEY `idx_pt_status` (`status`),
  ADD KEY `idx_pt_supplier` (`supplier_id`),
  ADD KEY `idx_pt_date` (`transaction_date`),
  ADD KEY `fk_pt_pr` (`purchase_requisition_id`),
  ADD KEY `fk_pt_user` (`user_id`);

--
-- Indexes for table `purchase_transaction_details`
--
ALTER TABLE `purchase_transaction_details`
  ADD PRIMARY KEY (`purchase_transaction_detail_id`),
  ADD KEY `fk_ptd_transaction` (`purchase_transaction_id`),
  ADD KEY `fk_ptd_product` (`product_id`);

--
-- Indexes for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD PRIMARY KEY (`reimbursement_id`),
  ADD UNIQUE KEY `uq_reimbursements_report` (`report_id`),
  ADD KEY `idx_reimbursement_report` (`report_id`),
  ADD KEY `idx_reimbursement_employee` (`employee_id`),
  ADD KEY `idx_reimbursement_status` (`status`),
  ADD KEY `fk_reimbursement_processed_by` (`processed_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `uq_roles_name` (`role_name`),
  ADD UNIQUE KEY `uq_roles_role_id` (`role_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sales_order_no` (`order_no`),
  ADD KEY `idx_sales_order_customer` (`customer_id`),
  ADD KEY `idx_sales_order_creator` (`created_by`);

--
-- Indexes for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_item_order` (`order_id`),
  ADD KEY `idx_sales_item_product` (`product_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`migration_id`),
  ADD UNIQUE KEY `migration_name` (`migration_name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`stock_movement_id`),
  ADD UNIQUE KEY `uq_sm_reference_product` (`reference_no`,`product_id`),
  ADD KEY `idx_sm_product` (`product_id`),
  ADD KEY `idx_sm_supplier` (`supplier_id`),
  ADD KEY `idx_sm_customer` (`customer_id`),
  ADD KEY `idx_sm_created_by` (`created_by`),
  ADD KEY `idx_sm_reference` (`reference_no`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`),
  ADD KEY `idx_suppliers_name` (`supplier_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_users_user_id` (`user_id`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `fk_users_role` (`role_id`),
  ADD KEY `idx_users_employee` (`employee_id`);

--
-- Indexes for table `vendor_invoices`
--
ALTER TABLE `vendor_invoices`
  ADD PRIMARY KEY (`vendor_invoice_id`),
  ADD UNIQUE KEY `uq_vendor_invoice_po_no` (`purchase_transaction_id`,`invoice_no`),
  ADD KEY `idx_vi_po` (`purchase_transaction_id`),
  ADD KEY `fk_vi_entered_by` (`entered_by`),
  ADD KEY `fk_vi_approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `activity_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `audit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id_divisi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id_employee` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  MODIFY `approval_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expense_comments`
--
ALTER TABLE `expense_comments`
  MODIFY `comment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_reports`
--
ALTER TABLE `expense_reports`
  MODIFY `report_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `expense_report_items`
--
ALTER TABLE `expense_report_items`
  MODIFY `report_item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `goods_receipt_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt_details`
--
ALTER TABLE `goods_receipt_details`
  MODIFY `goods_receipt_detail_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id_payroll` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  MODIFY `purchase_requisition_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requisition_details`
--
ALTER TABLE `purchase_requisition_details`
  MODIFY `purchase_requisition_detail_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `purchase_return_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  MODIFY `purchase_transaction_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_transaction_details`
--
ALTER TABLE `purchase_transaction_details`
  MODIFY `purchase_transaction_detail_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reimbursements`
--
ALTER TABLE `reimbursements`
  MODIFY `reimbursement_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `migration_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `stock_movement_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `vendor_invoices`
--
ALTER TABLE `vendor_invoices`
  MODIFY `vendor_invoice_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `v_employee_summary`
--
DROP TABLE IF EXISTS `v_employee_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_employee_summary`  AS SELECT `e`.`employee_id` AS `employee_id`, `e`.`employee_number` AS `employee_number`, `e`.`full_name` AS `full_name`, `e`.`position` AS `position`, `d`.`department_code` AS `department_code`, `d`.`department_name` AS `department_name`, `e`.`status` AS `status`, `u`.`username` AS `username`, `r`.`role_name` AS `role_name` FROM (((`employees` `e` left join `departments` `d` on((`d`.`department_id` = `e`.`department_id`))) left join `users` `u` on((`u`.`employee_id` = `e`.`employee_id`))) left join `roles` `r` on((`r`.`role_id` = `u`.`role_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_product_stock`
--
DROP TABLE IF EXISTS `v_product_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_product_stock`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`sku` AS `sku`, `p`.`product_name` AS `product_name`, `p`.`unit` AS `unit`, `p`.`stock` AS `stock`, `p`.`minimum_stock` AS `minimum_stock`, (case when (`p`.`stock` <= `p`.`minimum_stock`) then 1 else 0 end) AS `is_low_stock` FROM `products` AS `p` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD CONSTRAINT `fk_expense_approval_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_approval_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `expense_comments`
--
ALTER TABLE `expense_comments`
  ADD CONSTRAINT `fk_expense_comments_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expense_comments_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `expense_reports`
--
ALTER TABLE `expense_reports`
  ADD CONSTRAINT `fk_expense_report_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `expense_report_items`
--
ALTER TABLE `expense_report_items`
  ADD CONSTRAINT `fk_report_item_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_item_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `fk_grn_po` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`purchase_transaction_id`),
  ADD CONSTRAINT `fk_grn_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `goods_receipt_details`
--
ALTER TABLE `goods_receipt_details`
  ADD CONSTRAINT `fk_grnd_grn` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`goods_receipt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grnd_po_detail` FOREIGN KEY (`purchase_transaction_detail_id`) REFERENCES `purchase_transaction_details` (`purchase_transaction_detail_id`),
  ADD CONSTRAINT `fk_grnd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_sales_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_sales_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`id_employee`) REFERENCES `employees` (`id_employee`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD CONSTRAINT `fk_pr_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pr_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_requisition_details`
--
ALTER TABLE `purchase_requisition_details`
  ADD CONSTRAINT `fk_prd_pr` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`purchase_requisition_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `fk_pret_grn` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`goods_receipt_id`),
  ADD CONSTRAINT `fk_pret_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_pret_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_transactions`
--
ALTER TABLE `purchase_transactions`
  ADD CONSTRAINT `fk_pt_pr` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`purchase_requisition_id`),
  ADD CONSTRAINT `fk_pt_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_transaction_details`
--
ALTER TABLE `purchase_transaction_details`
  ADD CONSTRAINT `fk_ptd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_ptd_transaction` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`purchase_transaction_id`) ON DELETE CASCADE;

--
-- Constraints for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD CONSTRAINT `fk_reimbursement_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reimbursement_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reimbursement_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `fk_sales_order_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `fk_sales_item_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sales_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_sm_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_sm_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_role`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vendor_invoices`
--
ALTER TABLE `vendor_invoices`
  ADD CONSTRAINT `fk_vi_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vi_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vi_po` FOREIGN KEY (`purchase_transaction_id`) REFERENCES `purchase_transactions` (`purchase_transaction_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
