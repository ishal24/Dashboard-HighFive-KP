-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Sep 2025 pada 10.03
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rlegs_data`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `account_managers`
--

CREATE TABLE `account_managers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(191) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `role` enum('AM','HOTDA') NOT NULL DEFAULT 'AM',
  `divisi_id` bigint(20) UNSIGNED DEFAULT NULL,
  `witel_id` bigint(20) UNSIGNED NOT NULL,
  `telda_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `account_managers`
--

INSERT INTO `account_managers` (`id`, `nama`, `nik`, `role`, `divisi_id`, `witel_id`, `telda_id`, `created_at`, `updated_at`) VALUES
(1, 'Faisal Ramadhan', '0001', 'AM', 1, 5, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(2, 'Amalia Putri', 'AM0002', 'AM', 1, 6, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(3, 'Rizky Pratama', 'AM0003', 'AM', 2, 5, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(4, 'Nadia Azahra', 'AM0004', 'AM', 1, 6, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(5, 'Bima Aditya', 'AM0005', 'AM', 2, 7, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(6, 'Rani Maharani', 'AM0006', 'AM', 2, 1, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(7, 'Yoga Prabowo', 'AM0007', 'AM', 3, 2, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(8, 'Citra Lestari', 'AM0008', 'AM', 3, 3, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(9, 'Dewi Kartika', 'AM0009', 'AM', 3, 4, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29'),
(10, 'Rifqi Hidayat', 'AM0010', 'AM', 1, 8, NULL, '2025-09-18 06:34:29', '2025-09-18 06:34:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `account_manager_divisi`
--

CREATE TABLE `account_manager_divisi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_manager_id` bigint(20) UNSIGNED NOT NULL,
  `divisi_id` bigint(20) UNSIGNED NOT NULL,
  `is_primary` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `account_manager_divisi`
--

INSERT INTO `account_manager_divisi` (`id`, `account_manager_id`, `divisi_id`, `is_primary`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(2, 1, 2, 0, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(3, 3, 2, 1, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(4, 3, 3, 0, '2025-09-22 07:54:39', '2025-09-22 07:54:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `am_revenues`
--

CREATE TABLE `am_revenues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_manager_id` bigint(20) UNSIGNED NOT NULL,
  `corporate_customer_id` bigint(20) UNSIGNED NOT NULL,
  `divisi_id` bigint(20) UNSIGNED DEFAULT NULL,
  `witel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `telda_id` bigint(20) UNSIGNED DEFAULT NULL,
  `proporsi` decimal(5,2) NOT NULL DEFAULT 0.00,
  `target_revenue` decimal(20,2) NOT NULL DEFAULT 0.00,
  `real_revenue` decimal(20,2) NOT NULL DEFAULT 0.00,
  `achievement_rate` decimal(5,2) DEFAULT NULL,
  `bulan` tinyint(3) UNSIGNED NOT NULL,
  `tahun` smallint(5) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `am_revenues`
--

INSERT INTO `am_revenues` (`id`, `account_manager_id`, `corporate_customer_id`, `divisi_id`, `witel_id`, `telda_id`, `proporsi`, `target_revenue`, `real_revenue`, `achievement_rate`, `bulan`, `tahun`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 5, NULL, 0.60, 60000000.00, 57000000.00, 95.00, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(2, 2, 1, 1, 5, NULL, 0.40, 40000000.00, 38000000.00, 95.00, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(3, 3, 2, 2, 6, NULL, 0.50, 225000000.00, 210000000.00, 93.33, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(4, 4, 2, 2, 6, NULL, 0.50, 225000000.00, 210000000.00, 93.33, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(5, 5, 3, 3, 7, NULL, 0.70, 560000000.00, 546000000.00, 97.50, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(6, 6, 3, 3, 7, NULL, 0.30, 240000000.00, 234000000.00, 97.50, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(7, 7, 4, 1, 5, NULL, 1.00, 300000000.00, 310000000.00, 103.33, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(8, 8, 5, 2, 6, NULL, 0.60, 360000000.00, 348000000.00, 96.67, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(9, 9, 5, 2, 6, NULL, 0.40, 240000000.00, 232000000.00, 96.67, 8, 2025, '2025-09-18 07:11:10', '2025-09-18 07:11:10'),
(10, 1, 1, 1, 5, NULL, 0.60, 60000000.00, 59000000.00, 98.33, 9, 2025, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(11, 2, 1, 1, 5, NULL, 0.40, 40000000.00, 39000000.00, 97.50, 9, 2025, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(12, 3, 2, 2, 6, NULL, 0.50, 225000000.00, 245000000.00, 108.89, 9, 2025, '2025-09-22 07:54:39', '2025-09-22 07:54:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cc_revenues`
--

CREATE TABLE `cc_revenues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `corporate_customer_id` bigint(20) UNSIGNED NOT NULL,
  `divisi_id` bigint(20) UNSIGNED NOT NULL,
  `segment_id` bigint(20) UNSIGNED NOT NULL,
  `witel_ho_id` bigint(20) UNSIGNED DEFAULT NULL,
  `witel_bill_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nama_cc` varchar(255) NOT NULL,
  `nipnas` varchar(50) NOT NULL,
  `target_revenue` decimal(20,2) NOT NULL DEFAULT 0.00,
  `real_revenue` decimal(20,2) NOT NULL DEFAULT 0.00,
  `revenue_source` enum('HO','BILL') NOT NULL,
  `tipe_revenue` enum('REGULER','NGTMA') NOT NULL DEFAULT 'REGULER',
  `bulan` tinyint(3) UNSIGNED NOT NULL,
  `tahun` smallint(5) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `cc_revenues`
--

INSERT INTO `cc_revenues` (`id`, `corporate_customer_id`, `divisi_id`, `segment_id`, `witel_ho_id`, `witel_bill_id`, `nama_cc`, `nipnas`, `target_revenue`, `real_revenue`, `revenue_source`, `tipe_revenue`, `bulan`, `tahun`, `created_at`, `updated_at`) VALUES
(11, 1, 1, 1, 5, NULL, 'Bank Mandiri Jateng', '1234567890', 1000000000.00, 950000000.00, 'HO', 'REGULER', 8, 2025, '2025-09-18 07:05:51', '2025-09-18 07:05:51'),
(12, 2, 2, 2, 6, NULL, 'Dinas Pendidikan Jateng', '2234567890', 450000000.00, 470000000.00, 'HO', 'REGULER', 8, 2025, '2025-09-18 07:05:51', '2025-09-18 07:05:51'),
(13, 3, 3, 3, 7, 8, 'Pemkot Surabaya', '3234567890', 800000000.00, 780000000.00, 'BILL', 'REGULER', 8, 2025, '2025-09-18 07:05:51', '2025-09-18 07:05:51'),
(14, 4, 1, 1, 5, NULL, 'RSUD Dr. Kariadi', '4234567890', 300000000.00, 310000000.00, 'HO', 'REGULER', 8, 2025, '2025-09-18 07:05:51', '2025-09-18 07:05:51'),
(15, 5, 2, 2, 6, NULL, 'Universitas Diponegoro', '5234567890', 600000000.00, 580000000.00, 'HO', 'REGULER', 8, 2025, '2025-09-18 07:05:51', '2025-09-18 07:05:51'),
(16, 6, 3, 3, 7, 8, 'PT Petrokimia Gresik', '6234567890', 900000000.00, 870000000.00, 'BILL', 'REGULER', 8, 2025, '2025-09-18 07:05:51', '2025-09-18 07:05:51'),
(17, 1, 1, 1, 5, NULL, 'Bank Mandiri Jateng', '1234567890', 1000000000.00, 980000000.00, 'HO', 'REGULER', 9, 2025, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(18, 2, 2, 2, 6, NULL, 'Dinas Pendidikan Jateng', '2234567890', 450000000.00, 490000000.00, 'HO', 'REGULER', 9, 2025, '2025-09-22 07:54:39', '2025-09-22 07:54:39'),
(19, 3, 3, 3, 7, NULL, 'Pemkot Surabaya', '3234567890', 800000000.00, 820000000.00, 'HO', 'REGULER', 9, 2025, '2025-09-22 07:54:39', '2025-09-22 07:54:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `corporate_customers`
--

CREATE TABLE `corporate_customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nipnas` varchar(30) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `corporate_customers`
--

INSERT INTO `corporate_customers` (`id`, `nama`, `nipnas`, `created_at`, `updated_at`) VALUES
(1, 'BANK JATIM', '76590001', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(2, 'PEMKOT SEMARANG', '76590002', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(3, 'POLDA JAWA TENGAH', '76590003', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(4, 'PT NUSANTARA EKSPRES', '76590004', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(5, 'HONDA PROSPECT MOTOR', '76590005', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(6, 'INDOFOOD CBP SUKSES MAKMUR', '76590006', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(7, 'TELKOM UNIVERSITY', '76590007', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(8, 'RS DR KARIADI', '76590008', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(9, 'PT KRAKATAU STEEL', '76590009', '2025-09-18 06:35:11', '2025-09-18 06:35:11'),
(10, 'PT PERTAMINA PATRA NIAGA', '76590010', '2025-09-18 06:35:11', '2025-09-18 06:35:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `divisi`
--

CREATE TABLE `divisi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(150) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `divisi`
--

INSERT INTO `divisi` (`id`, `nama`, `kode`, `created_at`, `updated_at`) VALUES
(1, 'Government Service', 'DGS', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(2, 'SOE/State Service', 'DSS', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(3, 'Private Service', 'DPS', '2025-09-17 23:30:21', '2025-09-17 23:30:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(16, '2025_09_17_090217_create_users_table', 1),
(17, '2025_09_17_090347_create_witel_table', 1),
(18, '2025_09_17_090439_create_divisi_table', 1),
(19, '2025_09_17_090512_create_segments_table', 1),
(20, '2025_09_17_090602_create_teldas_table', 1),
(21, '2025_09_17_090740_create_account_managers_table', 1),
(22, '2025_09_17_091108_create_account_manager_divisi_table', 1),
(23, '2025_09_17_091129_create_corporate_customers_table', 1),
(24, '2025_09_17_091209_create_cc_revenues_table', 1),
(25, '2025_09_17_091235_create_am_revenues_table', 1),
(26, '2025_09_17_091322_add_foreign_keys_to_users_table', 1),
(27, '2025_09_17_092327_create_password_reset_tokens_table', 1),
(28, '2025_09_17_092354_create_sessions_table', 1),
(29, '2025_09_17_092406_create_cache_table', 1),
(30, '2025_09_17_092423_add_performance_indexes', 1),
(31, '2025_09_18_042823_add_divisi_id_to_account_managers_table', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `segments`
--

CREATE TABLE `segments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lsegment_ho` varchar(150) NOT NULL,
  `ssegment_ho` varchar(30) NOT NULL,
  `divisi_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `segments`
--

INSERT INTO `segments` (`id`, `lsegment_ho`, `ssegment_ho`, `divisi_id`, `created_at`, `updated_at`) VALUES
(1, 'GOVERNMENT PUBLIC SERVICE', 'GPS', 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(2, 'GOVERNMENT DEFENSE SERVICE', 'GDS', 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(3, 'GOVERNMENT INFRASTRUCTURE SERVICE', 'GIS', 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(4, 'GOVERNMENT REGIONAL SERVICE', 'GRS', 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(5, 'GOVERNMENT REGIONAL SERVICE', 'LGS', 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(6, 'LOGISTIC & MANUFACTURING SERVICE', 'LMS', 2, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(7, 'MANUFACTURING & INFRASTRUCTURE SERVICE', 'MIS', 2, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(8, 'ENERGY & RESOURCES SERVICE', 'ERS', 2, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(9, 'FINANCIAL & WELFARE SERVICE', 'FWS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(10, 'PRIVATE BANKING SERVICE', 'PBS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(11, 'RETAIL & MEDIA SERVICE', 'RMS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(12, 'PRIVATE CONGLOMERATION SERVICE', 'PCS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(13, 'PROPERTY & RESOURCES SERVICE', 'PRS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(14, 'FINANCIAL & REGIONAL BANKING SERVICE', 'FRBS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(15, 'TOURISM & WELFARE SERVICE', 'TWS', 3, '2025-09-17 23:30:21', '2025-09-17 23:30:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('D2Y0hGb0ke5Dm34Kud4TVsDggXOACZhECRLDdZGS', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWncxeDR2TW1tSlpNTkdWSDU1azJmR21OZmt2d0hPOElIeTdsWGduSyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjg3OiJodHRwOi8vbG9jYWxob3N0OjgwMDAvZGFzaGJvYXJkP3BlcmlvZF90eXBlPU1URCZyZXZlbnVlX3NvdXJjZT1hbGwmdGlwZV9yZXZlbnVlPVJFR1VMRVIiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1758527826),
('ft9gSgPrVj5SMvzFXx9upEr4BQ4hJVsbQPq0M57q', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNEpjWlRpeEFRbkpUc0ljTHd3R3BxYk53aTFtVVp5M0JNOVNnVmZKUyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1758189454),
('j6eLWZaeesOAfigQBWMnp4YN9cL9ZxUGPr0OiWiO', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT1pFV1U0ZFEyU3JuRnFrTVFRN0M3WkhxbllxN25XdlZvMzdYdE5YUiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTk6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9kYXNoYm9hcmQ/dGFodW49MjAyNSZ0aXBlX3JldmVudWU9YWxsIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1758511911);

-- --------------------------------------------------------

--
-- Struktur dari tabel `teldas`
--

CREATE TABLE `teldas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `witel_id` bigint(20) UNSIGNED NOT NULL,
  `divisi_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `teldas`
--

INSERT INTO `teldas` (`id`, `nama`, `witel_id`, `divisi_id`, `created_at`, `updated_at`) VALUES
(1, 'TELKOM DAERAH GIANYAR', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(2, 'TELKOM DAERAH UBUNG', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(3, 'TELKOM DAERAH SINGARAJA', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(4, 'TELKOM DAERAH TABANAN', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(5, 'TELKOM DAERAH SANUR', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(6, 'TELKOM DAERAH JEMBRANA', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(7, 'TELKOM DAERAH KLUNGKUNG', 1, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(8, 'TELKOM DAERAH BOJONEGORO', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(9, 'TELKOM DAERAH MADIUN', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(10, 'TELKOM DAERAH NGAWI', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(11, 'TELKOM DAERAH TUBAN', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(12, 'TELKOM DAERAH TRENGGALEK', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(13, 'TELKOM DAERAH PONOROGO', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(14, 'TELKOM DAERAH BLITAR', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(15, 'TELKOM DAERAH BATU', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(16, 'TELKOM DAERAH KEPANJEN', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(17, 'TELKOM DAERAH KEDIRI', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(18, 'TELKOM DAERAH NGANJUK', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(19, 'TELKOM DAERAH TULUNGAGUNG', 2, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(20, 'TELKOM DAERAH LUMAJANG', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(21, 'TELKOM DAERAH BANYUWANGI', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(22, 'TELKOM DAERAH SITUBONDO', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(23, 'TELKOM DAERAH BONDOWOSO', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(24, 'TELKOM DAERAH JEMBER', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(25, 'TELKOM DAERAH JOMBANG', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(26, 'TELKOM DAERAH PROBOLINGGO', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(27, 'TELKOM DAERAH MOJOKERTO', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(28, 'TELKOM DAERAH PASURUAN', 3, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(29, 'TELKOM DAERAH BIMA', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(30, 'TELKOM DAERAH SUMBAWA', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(31, 'TELKOM DAERAH ENDE', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(32, 'TELKOM DAERAH MAUMERE', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(33, 'TELKOM DAERAH LABUAN BAJO', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(34, 'TELKOM DAERAH WAINGAPU', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(35, 'TELKOM DAERAH KUPANG', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(36, 'TELKOM DAERAH LOMBOK BARU TENGAH', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(37, 'TELKOM DAERAH LOMBOK TIMUR UTARA', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(38, 'TELKOM DAERAH ATAMBUA', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(39, 'TELKOM DAERAH WAIKABUBAK', 4, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(40, 'MEA SEMARANG', 5, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(41, 'TELKOM DAERAH KENDAL', 5, 1, '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(42, 'TELKOM DAERAH UNGARAN', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(43, 'TELKOM DAERAH SALATIGA', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(44, 'TELKOM DAERAH BATANG', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(45, 'TELKOM DAERAH PEKALONGAN', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(46, 'TELKOM DAERAH PEMALANG', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(47, 'TELKOM DAERAH BREBES', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(48, 'TELKOM DAERAH SLAWI', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(49, 'TELKOM DAERAH TEGAL', 5, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(50, 'TELKOM DAERAH BLORA', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(51, 'TELKOM DAERAH KUDUS', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(52, 'TELKOM DAERAH PURWODADI', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(53, 'TELKOM DAERAH JEPARA', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(54, 'TELKOM DAERAH PATI', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(55, 'TELKOM DAERAH REMBANG', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(56, 'TELKOM DAERAH WONOGIRI', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(57, 'TELKOM DAERAH BOYOLALI', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(58, 'TELKOM DAERAH SRAGEN', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(59, 'TELKOM DAERAH KLATEN', 6, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(60, 'TELKOM DAERAH BANGKALAN', 7, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(61, 'TELKOM DAERAH GRESIK', 7, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(62, 'TELKOM DAERAH LAMONGAN', 7, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(63, 'TELKOM DAERAH PAMEKASAN', 7, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(64, 'TELKOM DAERAH MANYAR', 7, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(65, 'TELKOM DAERAH KETINTANG', 7, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(66, 'TELKOM DAERAH CILACAP', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(67, 'TELKOM DAERAH BANJARNEGARA', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(68, 'TELKOM DAERAH PURBALINGGA', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(69, 'TELKOM DAERAH SLEMAN', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(70, 'TELKOM DAERAH PURWOKERTO', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(71, 'TELKOM DAERAH GUNUNG KIDUL', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(72, 'TELKOM DAERAH BANTUL', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(73, 'TELKOM DAERAH MUNTILAN', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(74, 'TELKOM DAERAH TEMANGGUNG', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(75, 'TELKOM DAERAH MAGELANG', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(76, 'TELKOM DAERAH KEBUMEN', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(77, 'TELKOM DAERAH PURWOREJO', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(78, 'TELKOM DAERAH WONOSOBO', 8, 1, '2025-09-17 23:30:22', '2025-09-17 23:30:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `role` enum('admin','witel','account_manager') NOT NULL,
  `witel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `witel_id`, `account_manager_id`, `profile_image`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Telkom', 'admin@telkom.co.id', '2025-09-17 23:30:22', '$2y$12$3KbdDRLcG4js3/LklxAuEuFrGw0u5Q7dB0XYB0fK3XziWhAnENt0e', 'admin', NULL, NULL, NULL, NULL, '2025-09-17 23:30:22', '2025-09-17 23:30:22'),
(2, 'Faisal Ramadhan', 'adito@gmail.com', NULL, '$2y$12$sNamv6/oSKq4vwSb2B.Owe3GGOnSVJG2nYIk731QFmJOKmDTIYQla', 'account_manager', NULL, 1, NULL, NULL, '2025-09-18 00:48:42', '2025-09-18 00:48:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `witel`
--

CREATE TABLE `witel` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `witel`
--

INSERT INTO `witel` (`id`, `nama`, `created_at`, `updated_at`) VALUES
(1, 'BALI', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(2, 'JATIM BARAT', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(3, 'JATIM TIMUR', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(4, 'NUSA TENGGARA', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(5, 'SEMARANG JATENG UTARA', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(6, 'SOLO JATENG TIMUR', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(7, 'SURAMADU', '2025-09-17 23:30:21', '2025-09-17 23:30:21'),
(8, 'YOGYA JATENG SELATAN', '2025-09-17 23:30:21', '2025-09-17 23:30:21');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `account_managers`
--
ALTER TABLE `account_managers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_managers_nik_unique` (`nik`),
  ADD KEY `account_managers_witel_id_role_index` (`witel_id`,`role`),
  ADD KEY `account_managers_telda_id_index` (`telda_id`),
  ADD KEY `account_managers_nama_index` (`nama`),
  ADD KEY `account_managers_role_index` (`role`),
  ADD KEY `am_role_witel_index` (`role`,`witel_id`),
  ADD KEY `am_nik_nama_index` (`nik`,`nama`),
  ADD KEY `account_managers_divisi_id_foreign` (`divisi_id`);

--
-- Indeks untuk tabel `account_manager_divisi`
--
ALTER TABLE `account_manager_divisi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_manager_divisi_account_manager_id_divisi_id_unique` (`account_manager_id`,`divisi_id`),
  ADD KEY `account_manager_divisi_account_manager_id_index` (`account_manager_id`),
  ADD KEY `account_manager_divisi_divisi_id_index` (`divisi_id`),
  ADD KEY `account_manager_divisi_is_primary_index` (`is_primary`),
  ADD KEY `am_divisi_primary_index` (`is_primary`,`divisi_id`);

--
-- Indeks untuk tabel `am_revenues`
--
ALTER TABLE `am_revenues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `am_revenue_unique` (`account_manager_id`,`corporate_customer_id`,`tahun`,`bulan`),
  ADD KEY `am_revenues_tahun_bulan_account_manager_id_index` (`tahun`,`bulan`,`account_manager_id`),
  ADD KEY `am_revenues_tahun_bulan_witel_id_index` (`tahun`,`bulan`,`witel_id`),
  ADD KEY `am_revenues_tahun_bulan_divisi_id_index` (`tahun`,`bulan`,`divisi_id`),
  ADD KEY `am_revenues_account_manager_id_index` (`account_manager_id`),
  ADD KEY `am_revenues_corporate_customer_id_index` (`corporate_customer_id`),
  ADD KEY `am_revenues_divisi_id_index` (`divisi_id`),
  ADD KEY `am_revenues_witel_id_index` (`witel_id`),
  ADD KEY `am_revenues_telda_id_index` (`telda_id`),
  ADD KEY `am_rev_period_am_index` (`tahun`,`bulan`,`account_manager_id`),
  ADD KEY `am_rev_period_divisi_index` (`tahun`,`bulan`,`divisi_id`),
  ADD KEY `am_rev_period_witel_index` (`tahun`,`bulan`,`witel_id`),
  ADD KEY `am_rev_telda_period_index` (`telda_id`,`tahun`,`bulan`),
  ADD KEY `am_rev_real_revenue_index` (`real_revenue`);

--
-- Indeks untuk tabel `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `cc_revenues`
--
ALTER TABLE `cc_revenues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cc_revenue_unique` (`corporate_customer_id`,`tahun`,`bulan`,`tipe_revenue`),
  ADD KEY `cc_revenues_tahun_bulan_divisi_id_index` (`tahun`,`bulan`,`divisi_id`),
  ADD KEY `cc_revenues_tahun_bulan_witel_ho_id_index` (`tahun`,`bulan`,`witel_ho_id`),
  ADD KEY `cc_revenues_tahun_bulan_witel_bill_id_index` (`tahun`,`bulan`,`witel_bill_id`),
  ADD KEY `cc_revenues_corporate_customer_id_index` (`corporate_customer_id`),
  ADD KEY `cc_revenues_divisi_id_index` (`divisi_id`),
  ADD KEY `cc_revenues_segment_id_index` (`segment_id`),
  ADD KEY `cc_revenues_witel_ho_id_index` (`witel_ho_id`),
  ADD KEY `cc_revenues_witel_bill_id_index` (`witel_bill_id`),
  ADD KEY `cc_revenues_nama_cc_index` (`nama_cc`),
  ADD KEY `cc_revenues_nipnas_index` (`nipnas`),
  ADD KEY `cc_revenues_revenue_source_index` (`revenue_source`),
  ADD KEY `cc_revenues_tipe_revenue_index` (`tipe_revenue`),
  ADD KEY `cc_rev_period_divisi_index` (`tahun`,`bulan`,`divisi_id`),
  ADD KEY `cc_rev_period_segment_index` (`tahun`,`bulan`,`segment_id`),
  ADD KEY `cc_rev_period_tipe_index` (`tahun`,`bulan`,`tipe_revenue`),
  ADD KEY `cc_rev_nama_nipnas_index` (`nama_cc`,`nipnas`),
  ADD KEY `cc_rev_revenue_source_index` (`revenue_source`);

--
-- Indeks untuk tabel `corporate_customers`
--
ALTER TABLE `corporate_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `corporate_customers_nipnas_unique` (`nipnas`),
  ADD KEY `corporate_customers_nama_index` (`nama`);

--
-- Indeks untuk tabel `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `divisi_kode_unique` (`kode`),
  ADD KEY `divisi_nama_index` (`nama`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `segments`
--
ALTER TABLE `segments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `segments_ssegment_ho_unique` (`ssegment_ho`),
  ADD KEY `segments_lsegment_ho_index` (`lsegment_ho`),
  ADD KEY `segments_divisi_id_index` (`divisi_id`),
  ADD KEY `segments_divisi_kode_index` (`divisi_id`,`ssegment_ho`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `teldas`
--
ALTER TABLE `teldas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teldas_nama_witel_id_divisi_id_unique` (`nama`,`witel_id`,`divisi_id`),
  ADD KEY `teldas_nama_index` (`nama`),
  ADD KEY `teldas_witel_id_index` (`witel_id`),
  ADD KEY `teldas_divisi_id_index` (`divisi_id`),
  ADD KEY `teldas_witel_divisi_index` (`witel_id`,`divisi_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_name_index` (`name`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_witel_id_index` (`witel_id`),
  ADD KEY `users_account_manager_id_index` (`account_manager_id`),
  ADD KEY `users_role_witel_index` (`role`,`witel_id`);

--
-- Indeks untuk tabel `witel`
--
ALTER TABLE `witel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `witel_nama_index` (`nama`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `account_managers`
--
ALTER TABLE `account_managers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `account_manager_divisi`
--
ALTER TABLE `account_manager_divisi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `am_revenues`
--
ALTER TABLE `am_revenues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `cc_revenues`
--
ALTER TABLE `cc_revenues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `corporate_customers`
--
ALTER TABLE `corporate_customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT untuk tabel `segments`
--
ALTER TABLE `segments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `teldas`
--
ALTER TABLE `teldas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `witel`
--
ALTER TABLE `witel`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `account_managers`
--
ALTER TABLE `account_managers`
  ADD CONSTRAINT `account_managers_divisi_id_foreign` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `account_managers_telda_id_foreign` FOREIGN KEY (`telda_id`) REFERENCES `teldas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `account_managers_witel_id_foreign` FOREIGN KEY (`witel_id`) REFERENCES `witel` (`id`);

--
-- Ketidakleluasaan untuk tabel `account_manager_divisi`
--
ALTER TABLE `account_manager_divisi`
  ADD CONSTRAINT `account_manager_divisi_account_manager_id_foreign` FOREIGN KEY (`account_manager_id`) REFERENCES `account_managers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `account_manager_divisi_divisi_id_foreign` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`);

--
-- Ketidakleluasaan untuk tabel `am_revenues`
--
ALTER TABLE `am_revenues`
  ADD CONSTRAINT `am_revenues_account_manager_id_foreign` FOREIGN KEY (`account_manager_id`) REFERENCES `account_managers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `am_revenues_corporate_customer_id_foreign` FOREIGN KEY (`corporate_customer_id`) REFERENCES `corporate_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `am_revenues_divisi_id_foreign` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `am_revenues_telda_id_foreign` FOREIGN KEY (`telda_id`) REFERENCES `teldas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `am_revenues_witel_id_foreign` FOREIGN KEY (`witel_id`) REFERENCES `witel` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `cc_revenues`
--
ALTER TABLE `cc_revenues`
  ADD CONSTRAINT `cc_revenues_corporate_customer_id_foreign` FOREIGN KEY (`corporate_customer_id`) REFERENCES `corporate_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cc_revenues_divisi_id_foreign` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`),
  ADD CONSTRAINT `cc_revenues_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `segments` (`id`),
  ADD CONSTRAINT `cc_revenues_witel_bill_id_foreign` FOREIGN KEY (`witel_bill_id`) REFERENCES `witel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cc_revenues_witel_ho_id_foreign` FOREIGN KEY (`witel_ho_id`) REFERENCES `witel` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `segments`
--
ALTER TABLE `segments`
  ADD CONSTRAINT `segments_divisi_id_foreign` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`);

--
-- Ketidakleluasaan untuk tabel `teldas`
--
ALTER TABLE `teldas`
  ADD CONSTRAINT `teldas_divisi_id_foreign` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`),
  ADD CONSTRAINT `teldas_witel_id_foreign` FOREIGN KEY (`witel_id`) REFERENCES `witel` (`id`);

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_account_manager_id_foreign` FOREIGN KEY (`account_manager_id`) REFERENCES `account_managers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_witel_id_foreign` FOREIGN KEY (`witel_id`) REFERENCES `witel` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
