-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Jan 2026 pada 15.10
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
-- Database: `absen_sell`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi_guru`
--

CREATE TABLE `absensi_guru` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guru_id` int(10) UNSIGNED NOT NULL,
  `jadwal_pelajaran_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_hadir` datetime NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Hadir',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `api_key` varchar(64) DEFAULT NULL,
  `type` enum('rfid','fingerprint','rfid_fingerprint') DEFAULT 'rfid_fingerprint',
  `active` tinyint(1) DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `api_keys`
--

INSERT INTO `api_keys` (`id`, `name`, `api_key`, `type`, `active`, `last_used_at`, `created_at`) VALUES
(7, 'F', 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'fingerprint', 1, '2025-12-28 11:55:43', '2025-12-28 07:12:41'),
(10, 'S', 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'rfid', 1, '2026-01-07 12:22:19', '2025-12-28 09:38:21'),
(11, 'MINI', 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'rfid', 1, '2026-01-06 07:30:39', '2026-01-03 08:37:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `api_logs`
--

CREATE TABLE `api_logs` (
  `id` bigint(20) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'checkin_success, checkout_success, enroll_success, etc',
  `uid` varchar(20) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `api_logs`
--

INSERT INTO `api_logs` (`id`, `api_key`, `action`, `uid`, `success`, `message`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e86', 'auth_failed', '', 0, 'Invalid API key', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-17 22:32:09'),
(2, 'Yay', 'auth_failed', '', 0, 'Invalid API key', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-17 22:34:56'),
(3, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'kelas\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-17 22:35:38'),
(4, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'kelas\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-17 22:37:05'),
(5, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'kelas\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:56:43'),
(6, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:57:32'),
(7, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '40AC7A61', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:57:36'),
(8, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '40AC7A61', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:57:40'),
(9, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '40AC7A61', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:58:29'),
(10, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_duplicate', '40AC7A61', 0, 'UID sudah terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:58:41'),
(11, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_duplicate', '40AC7A61', 0, 'UID sudah terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:58:46'),
(12, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_duplicate', '40AC7A61', 0, 'UID sudah terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:58:50'),
(13, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_duplicate', '40AC7A61', 0, 'UID sudah terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:58:53'),
(14, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'enrolled_at\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:59:36'),
(15, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'enrolled_at\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:59:41'),
(16, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'enrolled_at\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 05:59:44'),
(17, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_duplicate', '121EEC05', 0, 'UID sudah terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:01:27'),
(18, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '40AC7A61', 1, 'Enroll berhasil: Budi Santoso', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:01:30'),
(19, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:01:40'),
(20, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:01:45'),
(21, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:01:48'),
(22, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:01:51'),
(23, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '121EEC05', 1, 'Enroll berhasil: Dewi Lestari', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:02:00'),
(24, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:02:04'),
(25, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '40AC7A61', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:02:07'),
(26, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:03:01'),
(27, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '121EEC05', 0, 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'FROM siswa \r\n            WHERE uid_rfid = ? \r\n            LIMIT 1\' at line 2', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:03:20'),
(28, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:04:50'),
(29, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:04:53'),
(30, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:04:57'),
(31, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:05:02'),
(32, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:05:06'),
(33, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:05:10'),
(34, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:05:13'),
(35, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:39:12'),
(36, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:39:17'),
(37, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:39:20'),
(38, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:39:25'),
(39, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:39:29'),
(40, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:40:08'),
(41, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:40:12'),
(42, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'device_name\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:40:32'),
(43, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'device_name\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:40:36'),
(44, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'device_name\' in \'field list\'', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 07:41:14'),
(45, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_late', '40AC7A61', 0, 'Lewat batas masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:25:56'),
(46, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_late', '40AC7A61', 0, 'Lewat batas masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:26:06'),
(47, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[21S01]: Insert value list does not match column list: 1136 Column count doesn\'t match value count at row 1', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:26:41'),
(48, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'scan_error', '40AC7A61', 0, 'SQLSTATE[21S01]: Insert value list does not match column list: 1136 Column count doesn\'t match value count at row 1', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:30:02'),
(49, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:33:25'),
(50, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '121EEC05', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:33:49'),
(51, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '121EEC05', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:34:03'),
(52, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 09:34:09'),
(53, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'late_checkout', '40AC7A61', 0, 'Lewat batas pulang', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 21:18:08'),
(54, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:20:18'),
(55, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:20:28'),
(56, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:20:33'),
(57, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:20:36'),
(58, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:20:40'),
(59, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:21:15'),
(60, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '121EEC05', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 06:21:18'),
(61, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 08:40:52'),
(62, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 09:51:24'),
(63, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 09:51:29'),
(64, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:24:49'),
(65, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:25:34'),
(66, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:37:22'),
(67, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:37:25'),
(68, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:37:29'),
(69, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:38:18'),
(70, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:38:46'),
(71, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:38:50'),
(72, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:39:03'),
(73, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:39:06'),
(74, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '121EEC05', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:39:10'),
(75, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '121EEC05', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:39:54'),
(76, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkout_success', '121EEC05', 1, 'Absen pulang berhasil', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:40:14'),
(77, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:40:31'),
(78, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:41:49'),
(79, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:42:36'),
(80, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:42:54'),
(81, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:45:54'),
(82, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '121EEC05', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:45:58'),
(83, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:53:37'),
(84, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen sudah lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:53:48'),
(85, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:54:23'),
(86, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 10:54:56'),
(87, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:03:16'),
(88, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_masuk', '40AC7A61', 1, 'Sudah absen masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:03:19'),
(89, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:03:35'),
(90, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:03:38'),
(91, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:03:42'),
(92, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:04:41'),
(93, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:04:46'),
(94, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:04:55'),
(95, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:07:55'),
(96, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:10:19'),
(97, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:10:22'),
(98, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:10:26'),
(99, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:11:03'),
(100, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:13:46'),
(101, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:14:41'),
(102, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:14:47'),
(103, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '121EEC05', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:14:51'),
(104, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkout_success', '121EEC05', 1, 'Absen pulang berhasil', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:14:54'),
(105, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '121EEC05', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:14:58'),
(106, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:17:46'),
(107, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:17:49'),
(108, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:17:53'),
(109, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:18:13'),
(110, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:18:35'),
(111, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:21:39'),
(112, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:21:43'),
(113, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:21:47'),
(114, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:22:07'),
(115, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:22:17'),
(116, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:22:21'),
(117, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:22:26'),
(118, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:23:50'),
(119, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:24:02'),
(120, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:24:20'),
(121, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'direct_checkout', '121EEC05', 1, 'Langsung absen pulang', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 11:50:26'),
(122, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '40AC7A61', 1, 'Enroll berhasil: Aditya Rusliano Akbar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:45:43'),
(123, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:45:54'),
(124, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '121EEC05', 1, 'Enroll berhasil: Ahmad Muzaki', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:47:31'),
(125, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:55:52'),
(126, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:55:55'),
(127, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '121EEC05', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:55:59'),
(128, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:56:03'),
(129, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:56:07'),
(130, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:56:10'),
(131, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:56:14'),
(132, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 13:56:17'),
(133, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '40AC7A61', 1, 'Enroll berhasil: Aditya Rusliano Akbar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 14:30:08'),
(134, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '40AC7A61', 1, 'Enroll berhasil: Aditya Rusliano Akbar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 14:30:18'),
(135, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'enroll_success', '40AC7A61', 1, 'Enroll berhasil: Aditya Rusliano Akbar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 14:31:45'),
(136, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 15:16:30'),
(137, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 15:16:33'),
(138, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 15:16:39'),
(139, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_closed', '40AC7A61', 0, 'Absen masuk ditutup', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-19 15:17:37'),
(140, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 07:58:42'),
(141, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 07:59:04'),
(142, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 07:59:09'),
(143, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 07:59:17'),
(144, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 07:59:47'),
(145, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 07:59:59'),
(146, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:00:28'),
(147, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:00:41'),
(148, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:03:57'),
(149, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:08:30'),
(150, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:08:34'),
(151, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:10:49'),
(152, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:10:54'),
(153, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:10:58'),
(154, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:13:00'),
(155, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:13:03'),
(156, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:13:06'),
(157, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:13:09'),
(158, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:32'),
(159, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:34'),
(160, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:38'),
(161, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:42'),
(162, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:43'),
(163, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:44'),
(164, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:17:45'),
(165, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 08:40:24'),
(166, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:10:09'),
(167, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:10:12'),
(168, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:10:15'),
(169, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:10:35'),
(170, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:10:41'),
(171, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:11:11'),
(172, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:11:14'),
(173, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:11:40'),
(174, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:15:00'),
(175, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:15:16'),
(176, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'unknown_card', '121EEC05', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:21:43'),
(177, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'teacher_auth_failed', '40AC7A61', 0, 'Failed to create session', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:23:38'),
(178, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'teacher_auth_failed', '40AC7A61', 0, 'Failed to create session', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:23:43'),
(179, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'teacher_auth_failed', '121EEC05', 0, 'Failed to create session', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:25:00'),
(180, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:25:33'),
(181, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:28:35'),
(182, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:28:39'),
(183, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:28:43'),
(184, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:45:11'),
(185, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-20 09:45:13'),
(186, '?7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'auth_failed', '', 0, 'Invalid API key', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 15:50:38'),
(187, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 15:51:26'),
(188, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'too_early', '40AC7A61', 0, 'Belum dibuka', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 15:51:31'),
(189, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 15:51:47'),
(190, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 15:52:09'),
(191, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 15:52:13'),
(192, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 16:15:03'),
(193, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 16:15:14'),
(194, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 16:21:06'),
(195, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 16:21:11'),
(196, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 16:21:25'),
(197, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:12:14'),
(198, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:12:21'),
(199, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:42:49'),
(200, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:42:57'),
(201, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:44:09'),
(202, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:44:46'),
(203, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:44:57'),
(204, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:44:59'),
(205, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:45:58'),
(206, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:46:05'),
(207, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:46:08'),
(208, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:48:05'),
(209, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'enroll_success', '9FC0A7CC', 1, 'Enroll berhasil: Ahmad Muzaki', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:48:20'),
(210, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '9FC0A7CC', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:48:29'),
(211, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '9FC0A7CC', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:48:33'),
(212, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '9FC0A7CC', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:11'),
(213, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:15'),
(214, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'unknown_card', '39071778', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:19'),
(215, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'enroll_success', '39071778', 1, 'Enroll berhasil: Arta Kusuma', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:28'),
(216, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:32'),
(217, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '39071778', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:35'),
(218, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '39071778', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:39'),
(219, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:43'),
(220, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '9FC0A7CC', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 17:51:46'),
(221, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '39071778', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:06:34'),
(222, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '40AC7A61', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:06:36'),
(223, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'already_complete', '39071778', 1, 'Absen Lengkap', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:06:40'),
(224, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:15:31'),
(225, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '40AC7A61', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:15:34'),
(226, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '40AC7A61', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:15:59'),
(227, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'sudah_absen_masuk', '40AC7A61', 1, 'Sudah Absen Masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:16:03'),
(228, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'sudah_absen_masuk', '40AC7A61', 1, 'Sudah Absen Masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:16:06'),
(229, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '9FC0A7CC', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:16:09'),
(230, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '39071778', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:16:11'),
(231, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'sudah_absen_masuk', '9FC0A7CC', 1, 'Sudah Absen Masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:16:14'),
(232, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'sudah_absen_masuk', '39071778', 1, 'Sudah Absen Masuk', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:16:16'),
(233, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'no_teacher_auth', '9FC0A7CC', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:18:07'),
(234, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'no_teacher_auth', '9FC0A7CC', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:18:11'),
(235, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'no_teacher_auth', '40AC7A61', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:20:48'),
(236, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'no_teacher_auth', '9FC0A7CC', 0, 'No teacher authorization', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:20:51'),
(237, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'teacher_auth', '121EEC05', 1, 'Teacher authorized: SIFA ALTA FUNISA', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:20:56'),
(238, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkout_success', '9FC0A7CC', 1, 'Absen pulang berhasil (authorized by: SIFA ALTA FUNISA)', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-21 18:20:59'),
(239, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'enroll_duplicate', '39071778', 0, 'UID sudah ada', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-22 06:48:42'),
(240, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'enroll_success', '9FC0A7CC', 1, 'Enroll berhasil: Aditya Rusliano Akbar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-22 06:48:59'),
(241, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '9FC0A7CC', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-22 06:49:25'),
(242, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'checkin_success', '9FC0A7CC', 1, 'Absen masuk: H', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-22 06:50:49'),
(243, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'unknown_card', '9FC0A7CC', 0, 'Kartu tidak terdaftar', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-22 19:27:15'),
(244, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '11223344', 0, 'Belum dibuka', '::1', 'unknown', '2025-12-24 13:38:56'),
(245, 'TESTKEY123', 'auth_failed', '', 0, 'Invalid API key', '::1', NULL, '2025-12-25 13:31:45'),
(246, 'TESTKEY123', 'auth_failed', '', 0, 'Invalid API key', '::1', NULL, '2025-12-25 13:31:55'),
(247, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'enroll_finger_success', '1', 1, 'Enroll Finger: SOFI NUR HABIBAH', NULL, NULL, '2025-12-28 07:25:30'),
(248, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_auth_finger', '1', 1, 'Teacher Auth: SOFI NUR HABIBAH', NULL, NULL, '2025-12-28 07:25:31'),
(249, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'enroll_finger_success', '1', 1, 'Enroll Finger: SOFI NUR HABIBAH on Device #4', NULL, NULL, '2025-12-28 07:30:38'),
(250, 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 'teacher_auth_finger', '1', 1, 'Teacher Auth: SOFI NUR HABIBAH', NULL, NULL, '2025-12-28 07:30:38'),
(251, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'enroll_finger_success', '1', 1, 'Enroll Finger: SOFI NUR HABIBAH on Device #7', NULL, NULL, '2025-12-28 07:45:40'),
(252, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_auth_finger', '1', 1, 'Teacher Auth: SOFI NUR HABIBAH', NULL, NULL, '2025-12-28 07:45:41'),
(253, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'enroll_finger_success', '1', 1, 'Enroll Finger: SOFI NUR HABIBAH on Device #7', NULL, NULL, '2025-12-28 07:55:34'),
(254, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_auth_finger', '1', 1, 'Teacher Auth: SOFI NUR HABIBAH', NULL, NULL, '2025-12-28 07:55:35'),
(255, 'TEST-KEY', 'teacher_attendance_finger', '999', 1, 'Attendance: Guru Separation Test - SEP-SUBJECT', NULL, NULL, '2025-12-28 08:09:32'),
(256, 'TEST-KEY', 'teacher_auth', 'ABCD1234', 1, 'Teacher authorized (Gate): Guru Separation Test', '::1', 'Go-http-client/1.1', '2025-12-28 08:09:32'),
(257, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_attendance_finger', '1', 1, 'Attendance: SOFI NUR HABIBAH - SEP-SUBJECT', NULL, NULL, '2025-12-28 08:56:27'),
(258, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_attendance_finger', '1', 1, 'Attendance: SOFI NUR HABIBAH - SEP-SUBJECT', NULL, NULL, '2025-12-28 09:09:59'),
(259, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'manual_ip_entry', NULL, 1, 'Manual IP Entry for Testing', '192.168.1.103', NULL, '2025-12-28 09:44:25');
INSERT INTO `api_logs` (`id`, `api_key`, `action`, `uid`, `success`, `message`, `ip_address`, `user_agent`, `created_at`) VALUES
(260, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'ping', NULL, 1, 'Boot Ping (IP Record)', '192.168.1.103', NULL, '2025-12-28 10:51:09'),
(261, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'ping', NULL, 1, 'Boot Ping (IP Record)', '192.168.1.103', NULL, '2025-12-28 10:57:21'),
(262, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_attendance_finger', '3', 1, 'Attendance: SOFI NUR HABIBAH - TEST-SUBJECT', NULL, NULL, '2025-12-28 10:59:07'),
(263, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_attendance_finger', '3', 1, 'Attendance: SOFI NUR HABIBAH - TEST-SUBJECT', NULL, NULL, '2025-12-28 10:59:11'),
(264, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'ping', NULL, 1, 'Boot Ping (IP Record)', '192.168.1.103', NULL, '2025-12-28 11:00:27'),
(265, 'xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh', 'teacher_attendance_finger', '3', 1, 'Attendance: SOFI NUR HABIBAH - TEST-SUBJECT', NULL, NULL, '2025-12-28 11:55:40'),
(266, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'FD0B9106', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:23:24'),
(267, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'FD0B9106', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:23:26'),
(268, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'FD0B9106', 1, 'Enroll Siswa berhasil: Dwi Sampurna Jaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:23:45'),
(269, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', 'FD0B9106', 0, 'Jadwal Kosong', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:23:50'),
(270, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', 'FD0B9106', 0, 'Jadwal Kosong', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:25:22'),
(271, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FD0B9106', 1, 'Masuk: Dwi Sampurna Jaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:25:49'),
(272, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'FD0B9106', 1, 'Sudah Absen Masuk', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:26:01'),
(273, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'FD0B9106', 1, 'Sudah Absen Masuk', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:26:06'),
(274, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'DDE26E06', 1, 'Enroll Guru berhasil: SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:26:47'),
(275, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:26:51'),
(276, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FD0B9106', 1, 'Pulang: Dwi Sampurna Jaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:26:55'),
(277, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '8D7E8F06', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:27:41'),
(278, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '8D7E8F06', 1, 'Enroll Siswa berhasil: Fariz Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:27:53'),
(279, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', '8D7E8F06', 0, 'Jadwal Kosong', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:28:11'),
(280, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '7D368E06', 1, 'Enroll Siswa berhasil: Haris Vino Agusthaan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:25:43'),
(281, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '7D1C7606', 1, 'Enroll Siswa berhasil: M. Maulana Eri Fernando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:29:52'),
(282, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '7D248506', 1, 'Enroll Siswa berhasil: Arista Danu Ansa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:09'),
(283, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7D248506', 1, 'Masuk: Arista Danu Ansa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:12'),
(284, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7D1C7606', 1, 'Masuk: M. Maulana Eri Fernando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:18'),
(285, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7D368E06', 1, 'Masuk: Haris Vino Agusthaan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:21'),
(286, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD807A06', 1, 'Enroll Siswa berhasil: Arbai Soliqin', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:31'),
(287, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD807A06', 1, 'Masuk: Arbai Soliqin', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:40'),
(288, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '0DBE7C06', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:49'),
(289, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '0DBE7C06', 1, 'Enroll Siswa berhasil: Dhani Alan Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:53'),
(290, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '0DBE7C06', 1, 'Masuk: Dhani Alan Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:30:57'),
(291, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '4DBF8A06', 1, 'Enroll Siswa berhasil: Aditya Rusliano Akbar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:31:34'),
(292, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '3D517106', 1, 'Enroll Siswa berhasil: Ahmad Muzaki', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:32:41'),
(293, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '3D517106', 1, 'Masuk: Ahmad Muzaki', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:32:44'),
(294, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '8D8B7F06', 1, 'Enroll Siswa berhasil: Arta Kusuma', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:32:56'),
(295, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '8D8B7F06', 1, 'Masuk: Arta Kusuma', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:00'),
(296, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'FD348A06', 1, 'Enroll Siswa berhasil: Indra Aprianto', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:15'),
(297, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FD348A06', 1, 'Masuk: Indra Aprianto', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:17'),
(298, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'EDB58A06', 1, 'Enroll Siswa berhasil: Nanda Dwi Andi Aritama', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:29'),
(299, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'EDB58A06', 1, 'Masuk: Nanda Dwi Andi Aritama', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:32'),
(300, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '9DD08E06', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:37'),
(301, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9DD08E06', 1, 'Enroll Siswa berhasil: Rilly Meilana Wijaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:45'),
(302, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DD08E06', 1, 'Masuk: Rilly Meilana Wijaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:48'),
(303, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'DD776806', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:33:59'),
(304, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'DD776806', 1, 'Enroll Siswa berhasil: Muhamad Deni Setiawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:03'),
(305, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD776806', 1, 'Masuk: Muhamad Deni Setiawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:06'),
(306, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD3F6D06', 1, 'Enroll Siswa berhasil: Muhammad Haris Ashrori', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:20'),
(307, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD3F6D06', 1, 'Masuk: Muhammad Haris Ashrori', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:24'),
(308, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '1D6C7D06', 1, 'Enroll Siswa berhasil: Viko Afriyan Arbi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:42'),
(309, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1D6C7D06', 1, 'Masuk: Viko Afriyan Arbi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:46'),
(310, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '7DC88506', 1, 'Enroll Siswa berhasil: Adi Abdurachman', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:34:57'),
(311, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7DC88506', 1, 'Masuk: Adi Abdurachman', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:01'),
(312, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9DC88B06', 1, 'Enroll Siswa berhasil: Zulfi Aulia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:19'),
(313, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DC88B06', 1, 'Masuk: Zulfi Aulia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:23'),
(314, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'BDD48D06', 1, 'Enroll Siswa berhasil: Rina Arzeti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:34'),
(315, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD48D06', 1, 'Masuk: Rina Arzeti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:36'),
(316, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '5D799406', 1, 'Enroll Siswa berhasil: Brenda Zaskia R', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:49'),
(317, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '5D799406', 1, 'Masuk: Brenda Zaskia R', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:35:53'),
(318, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD598806', 1, 'Enroll Siswa berhasil: Tiara Indriyani Sabela', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:05'),
(319, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD598806', 1, 'Masuk: Tiara Indriyani Sabela', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:09'),
(320, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '0DEA9206', 1, 'Enroll Siswa berhasil: Denis Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:20'),
(321, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '0DEA9206', 1, 'Masuk: Denis Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:23'),
(322, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '1DA69006', 1, 'Enroll Siswa berhasil: Deni', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:36'),
(323, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DA69006', 1, 'Masuk: Deni', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:39'),
(324, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', 'FD0B9106', 0, 'UID sudah ada', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:36:53'),
(325, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9D1A9406', 1, 'Enroll Siswa berhasil: Bagus Hermawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:37:07'),
(326, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D1A9406', 1, 'Masuk: Bagus Hermawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:37:12'),
(327, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '2DD47406', 1, 'Enroll Siswa berhasil: Ahmad Agus Salim', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:17'),
(328, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '2DD47406', 1, 'Masuk: Ahmad Agus Salim', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:20'),
(329, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '1DE47C06', 1, 'Enroll Siswa berhasil: Ahmad', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:29'),
(330, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DE47C06', 1, 'Masuk: Ahmad', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:32'),
(331, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '2D958306', 1, 'Enroll Siswa berhasil: Ririn Mardiana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:44'),
(332, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '2D958306', 1, 'Masuk: Ririn Mardiana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:46'),
(333, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '3DDE8E06', 1, 'Enroll Siswa berhasil: Junia Sari', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:51:57'),
(334, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '3DDE8E06', 1, 'Masuk: Junia Sari', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:52:00'),
(335, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9DDF7A06', 1, 'Enroll Siswa berhasil: Keyla Biyan Ramadhani', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:52:53'),
(336, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DDF7A06', 1, 'Masuk: Keyla Biyan Ramadhani', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:52:56'),
(337, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9D807106', 1, 'Enroll Siswa berhasil: Dinda Amalia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:53:07'),
(338, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D807106', 1, 'Masuk: Dinda Amalia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:53:11'),
(339, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9DD47406', 1, 'Enroll Siswa berhasil: Dhani Muhamad Reza', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:53:28'),
(340, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DD47406', 1, 'Masuk: Dhani Muhamad Reza', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 07:53:32'),
(341, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '9D947C06', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:26:48'),
(342, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9D947C06', 1, 'Enroll Siswa berhasil: Ayu Vera Velinia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:26:50'),
(343, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'CD505D06', 1, 'Enroll Siswa berhasil: Arphanca Kun Nugroho', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:04'),
(344, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD505D06', 1, 'Masuk: Arphanca Kun Nugroho', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:07'),
(345, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'EDCE6E06', 1, 'Enroll Siswa berhasil: Andre Marcel', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:19'),
(346, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'ED439906', 1, 'Enroll Siswa berhasil: Akhmad Afandi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:30'),
(347, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED439906', 1, 'Masuk: Akhmad Afandi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:33'),
(348, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'ADC67506', 1, 'Enroll Siswa berhasil: Aji Irawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:45'),
(349, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ADC67506', 1, 'Masuk: Aji Irawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:27:48'),
(350, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD679606', 1, 'Enroll Siswa berhasil: Adi Abdurachman', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:28:01'),
(351, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD679606', 1, 'Enroll Siswa berhasil: Adnan Nur Rohim', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:28:55'),
(352, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'CD8A8406', 1, 'Enroll Siswa berhasil: Panji Setia Wardana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:08'),
(353, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD8A8406', 1, 'Masuk: Panji Setia Wardana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:19'),
(354, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '4DE98206', 1, 'Enroll Siswa berhasil: Musa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:28'),
(355, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4DE98206', 1, 'Masuk: Musa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:32'),
(356, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'DD618B06', 1, 'Enroll Siswa berhasil: Muhammad Irsyadul A\'la', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:41'),
(357, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD618B06', 1, 'Masuk: Muhammad Irsyadul A\'la', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:44'),
(358, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '4D3C7E06', 1, 'Enroll Siswa berhasil: Selviana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:29:58'),
(359, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D3C7E06', 1, 'Masuk: Selviana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:00'),
(360, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '7DE38706', 1, 'Enroll Siswa berhasil: Novita Dwi Wijayanti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:13'),
(361, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7DE38706', 1, 'Masuk: Novita Dwi Wijayanti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:15'),
(362, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'ED7B8106', 1, 'Enroll Siswa berhasil: Jepri Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:26'),
(363, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED7B8106', 1, 'Masuk: Jepri Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:29'),
(364, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '4D9D8506', 1, 'Enroll Siswa berhasil: Hanif Dwi Cahyono', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:41'),
(365, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D9D8506', 1, 'Masuk: Hanif Dwi Cahyono', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:44'),
(366, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'BD5B8906', 1, 'Enroll Siswa berhasil: Indah Laras Putri', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:55'),
(367, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BD5B8906', 1, 'Masuk: Indah Laras Putri', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:30:58'),
(368, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'BDA97506', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:31:41'),
(369, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'BDA97506', 1, 'Enroll Siswa berhasil: Meliana Dwi Irianti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:31:51'),
(370, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'FDEC8306', 1, 'Enroll Siswa berhasil: Firnando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:04'),
(371, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FDEC8306', 1, 'Masuk: Firnando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:06'),
(372, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'BDD67906', 1, 'Enroll Siswa berhasil: Fadli Ardiansyah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:24'),
(373, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD67906', 1, 'Masuk: Fadli Ardiansyah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:29'),
(374, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '2DA18C06', 1, 'Enroll Siswa berhasil: Davit Mubaidilah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:40'),
(375, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '2DA18C06', 1, 'Masuk: Davit Mubaidilah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:43'),
(376, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '6D6E8306', 1, 'Enroll Siswa berhasil: Dhika Hanafi Rantau', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:56'),
(377, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '6D6E8306', 1, 'Masuk: Dhika Hanafi Rantau', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:32:59'),
(378, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'DD099406', 1, 'Enroll Siswa berhasil: Vanisaul Khoiroh', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:33:16'),
(379, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD099406', 1, 'Masuk: Vanisaul Khoiroh', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:33:19'),
(380, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', '8D7E8F06', 0, 'UID sudah ada', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:33:43'),
(381, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 08:34:22'),
(382, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:32:46'),
(383, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:32:48'),
(384, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:32:55'),
(385, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:32:57'),
(386, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '8D7E8F06', 1, 'Masuk: Fariz Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:36:37'),
(387, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD67906', 1, 'Pulang: Fadli Ardiansyah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:39:22'),
(388, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDD67906', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:39:29'),
(389, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '8D7E8F06', 1, 'Pulang: Fariz Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:39:35'),
(390, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDA97506', 1, 'Masuk: Meliana Dwi Irianti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:39:46'),
(391, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDA97506', 1, 'Pulang: Meliana Dwi Irianti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 09:39:56'),
(392, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '8D7E8F06', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 11:40:21'),
(393, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDA97506', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 11:40:32'),
(394, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', '9D807106', 0, 'Belum ada izin guru.', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 11:41:24'),
(395, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', '9D807106', 0, 'Belum ada izin guru.', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 11:41:32'),
(396, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', 'FDEC8306', 0, 'Belum ada izin guru.', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 11:41:42'),
(397, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '8D7E8F06', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:16:37'),
(398, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '8D7E8F06', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:16:50'),
(399, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDD67906', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:16:56'),
(400, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', 'DD099406', 0, 'Belum ada izin guru.', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:18'),
(401, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', '6D6E8306', 0, 'Belum ada izin guru.', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:19'),
(402, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:26'),
(403, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '6D6E8306', 1, 'Pulang: Dhika Hanafi Rantau', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:30'),
(404, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FDEC8306', 1, 'Pulang: Firnando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:33'),
(405, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD099406', 1, 'Pulang: Vanisaul Khoiroh', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:36'),
(406, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '2DA18C06', 1, 'Pulang: Davit Mubaidilah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:38'),
(407, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDD67906', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:39'),
(408, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDA97506', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:41'),
(409, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '8D7E8F06', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:19:44'),
(410, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D807106', 1, 'Pulang: Dinda Amalia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:10'),
(411, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DDF7A06', 1, 'Pulang: Keyla Biyan Ramadhani', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:13'),
(412, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3DDE8E06', 1, 'Pulang: Junia Sari', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:16'),
(413, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '2D958306', 1, 'Pulang: Ririn Mardiana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:18'),
(414, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '1DE47C06', 1, 'Pulang: Ahmad', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:20'),
(415, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D1A9406', 1, 'Pulang: Bagus Hermawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:23'),
(416, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '2DD47406', 1, 'Pulang: Ahmad Agus Salim', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:26'),
(417, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', 'FD0B9106', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:28'),
(418, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '1DA69006', 1, 'Pulang: Deni', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:31'),
(419, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '0DEA9206', 1, 'Pulang: Denis Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:33'),
(420, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD598806', 1, 'Pulang: Tiara Indriyani Sabela', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:36'),
(421, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '5D799406', 1, 'Pulang: Brenda Zaskia R', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:38'),
(422, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD48D06', 1, 'Pulang: Rina Arzeti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:40'),
(423, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DC88B06', 1, 'Pulang: Zulfi Aulia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:42'),
(424, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '7DC88506', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:44'),
(425, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'EDB58A06', 1, 'Pulang: Nanda Dwi Andi Aritama', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:53'),
(426, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FD348A06', 1, 'Pulang: Indra Aprianto', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:54'),
(427, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '8D8B7F06', 1, 'Pulang: Arta Kusuma', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:57'),
(428, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3D517106', 1, 'Pulang: Ahmad Muzaki', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:20:59'),
(429, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '4DBF8A06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:00'),
(430, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '3D517106', 1, 'Absen Lengkap', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:01'),
(431, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '4DBF8A06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:07'),
(432, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '0DBE7C06', 1, 'Pulang: Dhani Alan Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:10'),
(433, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD807A06', 1, 'Pulang: Arbai Soliqin', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:12'),
(434, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7D368E06', 1, 'Pulang: Haris Vino Agusthaan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:15'),
(435, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7D1C7606', 1, 'Pulang: M. Maulana Eri Fernando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:18'),
(436, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7D248506', 1, 'Pulang: Arista Danu Ansa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:19'),
(437, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DD47406', 1, 'Pulang: Dhani Muhamad Reza', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:21'),
(438, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BD5B8906', 1, 'Pulang: Indah Laras Putri', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:23'),
(439, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D9D8506', 1, 'Pulang: Hanif Dwi Cahyono', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:25'),
(440, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED7B8106', 1, 'Pulang: Jepri Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:26'),
(441, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7DE38706', 1, 'Pulang: Novita Dwi Wijayanti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:37'),
(442, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D3C7E06', 1, 'Pulang: Selviana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:39'),
(443, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD618B06', 1, 'Pulang: Muhammad Irsyadul A\'la', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:41'),
(444, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4DE98206', 1, 'Pulang: Musa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:43'),
(445, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD8A8406', 1, 'Pulang: Panji Setia Wardana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:45'),
(446, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', 'AD679606', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:46'),
(447, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '9D947C06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:49'),
(448, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD505D06', 1, 'Pulang: Arphanca Kun Nugroho', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-29 13:21:51'),
(449, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '7DC88506', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:23:52'),
(450, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD505D06', 1, 'Masuk: Arphanca Kun Nugroho', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:23:59'),
(451, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D947C06', 1, 'Masuk: Ayu Vera Velinia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:07'),
(452, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD679606', 1, 'Masuk: Adnan Nur Rohim', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:10'),
(453, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD8A8406', 1, 'Masuk: Panji Setia Wardana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:13'),
(454, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4DE98206', 1, 'Masuk: Musa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:17'),
(455, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD618B06', 1, 'Masuk: Muhammad Irsyadul A\'la', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:21'),
(456, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D3C7E06', 1, 'Masuk: Selviana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:29'),
(457, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7DE38706', 1, 'Masuk: Novita Dwi Wijayanti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:33'),
(458, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED7B8106', 1, 'Masuk: Jepri Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:38'),
(459, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D9D8506', 1, 'Masuk: Hanif Dwi Cahyono', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:42'),
(460, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BD5B8906', 1, 'Masuk: Indah Laras Putri', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:46'),
(461, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DD47406', 1, 'Masuk: Dhani Muhamad Reza', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:51'),
(462, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7D248506', 1, 'Masuk: Arista Danu Ansa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:54'),
(463, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7D1C7606', 1, 'Masuk: M. Maulana Eri Fernando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:24:57'),
(464, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7D368E06', 1, 'Masuk: Haris Vino Agusthaan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:01'),
(465, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD807A06', 1, 'Masuk: Arbai Soliqin', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:04'),
(466, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1D6C7D06', 1, 'Masuk: Viko Afriyan Arbi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:34'),
(467, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD3F6D06', 1, 'Masuk: Muhammad Haris Ashrori', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:37'),
(468, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED439906', 1, 'Masuk: Akhmad Afandi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:39'),
(469, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ADC67506', 1, 'Masuk: Aji Irawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:41'),
(470, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD776806', 1, 'Masuk: Muhamad Deni Setiawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:45'),
(471, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'EDCE6E06', 1, 'Masuk: Andre Marcel', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:47'),
(472, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'EDB58A06', 1, 'Masuk: Nanda Dwi Andi Aritama', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:49'),
(473, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD48D06', 1, 'Masuk: Rina Arzeti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:52'),
(474, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '5D799406', 1, 'Masuk: Brenda Zaskia R', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:25:56'),
(475, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD598806', 1, 'Masuk: Tiara Indriyani Sabela', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:00'),
(476, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '0DEA9206', 1, 'Masuk: Denis Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:08'),
(477, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DA69006', 1, 'Masuk: Deni', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:10'),
(478, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FD0B9106', 1, 'Masuk: Dwi Sampurna Jaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:13'),
(479, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '6D6E8306', 1, 'Masuk: Dhika Hanafi Rantau', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:31'),
(480, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DC88B06', 1, 'Masuk: Zulfi Aulia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:36'),
(481, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DD08E06', 1, 'Masuk: Rilly Meilana Wijaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:38'),
(482, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D1A9406', 1, 'Masuk: Bagus Hermawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:40'),
(483, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DE47C06', 1, 'Masuk: Ahmad', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:42'),
(484, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '2D958306', 1, 'Masuk: Ririn Mardiana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:45'),
(485, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '3DDE8E06', 1, 'Masuk: Junia Sari', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:47'),
(486, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DDF7A06', 1, 'Masuk: Keyla Biyan Ramadhani', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:49'),
(487, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D807106', 1, 'Masuk: Dinda Amalia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:51'),
(488, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '8D7E8F06', 1, 'Masuk: Fariz Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:53'),
(489, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDA97506', 1, 'Masuk: Meliana Dwi Irianti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:56'),
(490, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD67906', 1, 'Masuk: Fadli Ardiansyah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:26:58'),
(491, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '2DA18C06', 1, 'Masuk: Davit Mubaidilah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:27:00'),
(492, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD099406', 1, 'Masuk: Vanisaul Khoiroh', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:27:02'),
(493, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '7DC88506', 1, 'Enroll Siswa berhasil: Adi Abdurachman', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 06:49:50'),
(494, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:36:50'),
(495, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7DC88506', 1, 'Pulang: Adi Abdurachman', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:04'),
(496, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD807A06', 1, 'Pulang: Arbai Soliqin', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:06'),
(497, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7D368E06', 1, 'Pulang: Haris Vino Agusthaan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:08'),
(498, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7D1C7606', 1, 'Pulang: M. Maulana Eri Fernando', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:10'),
(499, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7D248506', 1, 'Pulang: Arista Danu Ansa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:12'),
(500, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DD47406', 1, 'Pulang: Dhani Muhamad Reza', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:14'),
(501, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BD5B8906', 1, 'Pulang: Indah Laras Putri', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:16'),
(502, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D9D8506', 1, 'Pulang: Hanif Dwi Cahyono', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:18'),
(503, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED7B8106', 1, 'Pulang: Jepri Maulana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:19'),
(504, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', 'FDEC8306', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:21'),
(505, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD099406', 1, 'Pulang: Vanisaul Khoiroh', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:23'),
(506, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '2DA18C06', 1, 'Pulang: Davit Mubaidilah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:25'),
(507, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD67906', 1, 'Pulang: Fadli Ardiansyah', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:26'),
(508, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDA97506', 1, 'Pulang: Meliana Dwi Irianti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:28'),
(509, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '8D7E8F06', 1, 'Pulang: Fariz Kurniawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:29'),
(510, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D807106', 1, 'Pulang: Dinda Amalia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:31'),
(511, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DDF7A06', 1, 'Pulang: Keyla Biyan Ramadhani', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:32'),
(512, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3DDE8E06', 1, 'Pulang: Junia Sari', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:34'),
(513, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '2D958306', 1, 'Pulang: Ririn Mardiana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:36'),
(514, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '1DE47C06', 1, 'Pulang: Ahmad', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:38'),
(515, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D1A9406', 1, 'Pulang: Bagus Hermawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:40'),
(516, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DD08E06', 1, 'Pulang: Rilly Meilana Wijaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:43'),
(517, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '6D6E8306', 1, 'Pulang: Dhika Hanafi Rantau', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:47');
INSERT INTO `api_logs` (`id`, `api_key`, `action`, `uid`, `success`, `message`, `ip_address`, `user_agent`, `created_at`) VALUES
(518, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FD0B9106', 1, 'Pulang: Dwi Sampurna Jaya', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:49'),
(519, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD598806', 1, 'Pulang: Tiara Indriyani Sabela', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:37:54'),
(520, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7DE38706', 1, 'Pulang: Novita Dwi Wijayanti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:07'),
(521, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D3C7E06', 1, 'Pulang: Selviana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:09'),
(522, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD48D06', 1, 'Pulang: Rina Arzeti', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:11'),
(523, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'EDCE6E06', 1, 'Pulang: Andre Marcel', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:13'),
(524, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD776806', 1, 'Pulang: Muhamad Deni Setiawan', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:15'),
(525, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED439906', 1, 'Pulang: Akhmad Afandi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:17'),
(526, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD3F6D06', 1, 'Pulang: Muhammad Haris Ashrori', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:18'),
(527, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '1D6C7D06', 1, 'Pulang: Viko Afriyan Arbi', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:19'),
(528, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4DE98206', 1, 'Pulang: Musa', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:31'),
(529, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD8A8406', 1, 'Pulang: Panji Setia Wardana', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:33'),
(530, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D947C06', 1, 'Pulang: Ayu Vera Velinia', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:35'),
(531, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD505D06', 1, 'Pulang: Arphanca Kun Nugroho', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:36'),
(532, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '0DBE7C06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:37'),
(533, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3D517106', 1, 'Pulang: Ahmad Muzaki', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:41'),
(534, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', 'FD348A06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-30 10:38:44'),
(535, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '7D368E06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-01 09:56:40'),
(536, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '7D368E06', 0, 'Absen Masuk Ditutup', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-01 09:56:52'),
(537, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'AD807A06', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-02 17:56:13'),
(538, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD807A06', 1, 'Enroll Siswa berhasil: Arbai Soliqin', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-02 17:56:42'),
(539, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '7DC88506', 1, 'Enroll Siswa berhasil: Adi Abdurachman', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 08:39:45'),
(540, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '7D248506', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 08:50:06'),
(541, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'BDA97506', 1, 'Enroll Siswa berhasil: Meliana Dwi Irianti', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 08:50:34'),
(542, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '7D248506', 1, 'Enroll Siswa berhasil: Arista Danu Ansa', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 12:54:06'),
(543, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '7D1C7606', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 12:55:39'),
(544, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '7D1C7606', 1, 'Enroll Siswa berhasil: M. Maulana Eri Fernando', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 12:55:49'),
(545, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'BDD67906', 1, 'Enroll Siswa berhasil: Fadli Ardiansyah', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 12:58:29'),
(546, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'FDEC8306', 1, 'Enroll Siswa berhasil: Firnando', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:09:14'),
(547, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'BD5B8906', 1, 'Enroll Siswa berhasil: Indah Laras Putri', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:09:32'),
(548, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', 'AD807A06', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:11:42'),
(549, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', 'AD807A06', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:11:45'),
(550, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '2DD47406', 1, 'Enroll Siswa berhasil: Ahmad Agus Salim', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:17:17'),
(551, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'AD598806', 1, 'Enroll Siswa berhasil: Tiara Indriyani Sabela', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:17:44'),
(552, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'ED439906', 1, 'Enroll Siswa berhasil: Akhmad Afandi', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:18:11'),
(553, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'FD0B9106', 1, 'Enroll Siswa berhasil: Dwi Sampurna Jaya', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:19:16'),
(554, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '7DE38706', 1, 'Enroll Siswa berhasil: Novita Dwi Wijayanti', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:19:58'),
(555, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '9D947C06', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:20:32'),
(556, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '9D947C06', 1, 'Enroll Siswa berhasil: Ayu Vera Velinia', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:20:36'),
(557, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'CD8A8406', 1, 'Enroll Siswa berhasil: Panji Setia Wardana', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:20:48'),
(558, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '9DD47406', 1, 'Enroll Siswa berhasil: Dhani Muhamad Reza', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:21:44'),
(559, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 13:59:36'),
(560, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 14:00:00'),
(561, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-03 20:14:51'),
(562, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-03 20:19:25'),
(563, '', 'gagal', NULL, 0, 'API key tidak valid', '192.168.1.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 20:20:34'),
(564, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:27:24'),
(565, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:28:10'),
(566, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'checkin_closed', '9DD47406', 0, 'Absen Masuk Ditutup', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:28:32'),
(567, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'checkin_closed', '9DD47406', 0, 'Absen Masuk Ditutup', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:28:37'),
(568, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:28:41'),
(569, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:28:55'),
(570, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 20:29:00'),
(571, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:00:02'),
(572, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:00:08'),
(573, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:00:13'),
(574, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:49:59'),
(575, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:53:06'),
(576, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:53:12'),
(577, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:53:16'),
(578, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:53:47'),
(579, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:54:20'),
(580, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:54:56'),
(581, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:55:00'),
(582, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:55:05'),
(583, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:55:09'),
(584, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:57:05'),
(585, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:57:09'),
(586, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-03 23:57:12'),
(587, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 06:44:31'),
(588, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 06:53:05'),
(589, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 06:53:09'),
(590, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 06:54:05'),
(591, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 06:55:49'),
(592, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 06:55:51'),
(593, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:15:05'),
(594, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:15:08'),
(595, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:15:49'),
(596, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:33:04'),
(597, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:46:59'),
(598, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:47:03'),
(599, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:47:06'),
(600, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:47:10'),
(601, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:47:32'),
(602, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:47:36'),
(603, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:47:38'),
(604, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:52:39'),
(605, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:52:43'),
(606, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:52:47'),
(607, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:53:12'),
(608, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:53:45'),
(609, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:53:49'),
(610, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:53:53'),
(611, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:54:11'),
(612, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:54:15'),
(613, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:58:56'),
(614, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:58:59'),
(615, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 07:59:02'),
(616, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '192.168.1.196', 'ESP8266HTTPClient', '2026-01-04 07:59:48'),
(617, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'schedule_empty', '9DD47406', 0, 'Jadwal Kosong', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 08:00:04'),
(618, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'DD099406', 1, 'Enroll Siswa berhasil: Vanisaul Khoiroh', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:36:48'),
(619, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '9D807106', 1, 'Enroll Siswa berhasil: Dinda Amalia', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:37:17'),
(620, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '9DDF7A06', 1, 'Enroll Siswa berhasil: Keyla Biyan Ramadhani', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:37:59'),
(621, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '2D958306', 1, 'Enroll Siswa berhasil: Ririn Mardiana', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:38:19'),
(622, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '3D517106', 1, 'Enroll Siswa berhasil: Ahmad Muzaki', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:39:32'),
(623, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '0DBE7C06', 1, 'Enroll Siswa berhasil: Dhani Alan Maulana', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:39:48'),
(624, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '1D6C7D06', 1, 'Enroll Siswa berhasil: Viko Afriyan Arbi', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:40:24'),
(625, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '0DEA9206', 1, 'Enroll Siswa berhasil: Denis Kurniawan', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:40:36'),
(626, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '9DC88B06', 1, 'Enroll Siswa berhasil: Zulfi Aulia', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:40:56'),
(627, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', 'BDD48D06', 1, 'Enroll Siswa berhasil: Rina Arzeti', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:41:04'),
(628, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '4D3C7E06', 1, 'Enroll Siswa berhasil: Selviana', '192.168.1.103', 'ESP8266HTTPClient', '2026-01-04 12:41:26'),
(629, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9D1A9406', 1, 'Enroll Siswa berhasil: Bagus Hermawan', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:53:15'),
(630, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '3DDE8E06', 1, 'Enroll Siswa berhasil: Junia Sari', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:53:29'),
(631, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '4D9D8506', 1, 'Enroll Siswa berhasil: Hanif Dwi Cahyono', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:53:48'),
(632, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD099406', 1, 'Masuk: Vanisaul Khoiroh', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:57:41'),
(633, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED439906', 1, 'Masuk: Akhmad Afandi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:59:09'),
(634, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7DE38706', 1, 'Masuk: Novita Dwi Wijayanti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:59:21'),
(635, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BD5B8906', 1, 'Masuk: Indah Laras Putri', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:59:26'),
(636, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FDEC8306', 1, 'Masuk: Firnando', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:59:30'),
(637, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD67906', 1, 'Masuk: Fadli Ardiansyah', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:59:33'),
(638, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D947C06', 1, 'Masuk: Ayu Vera Velinia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 07:59:51'),
(639, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DC88B06', 1, 'Masuk: Zulfi Aulia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:01:29'),
(640, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D3C7E06', 1, 'Masuk: Selviana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:01:38'),
(641, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDA97506', 1, 'Masuk: Meliana Dwi Irianti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:01:45'),
(642, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '3D517106', 1, 'Masuk: Ahmad Muzaki', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:03:58'),
(643, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1D6C7D06', 1, 'Masuk: Viko Afriyan Arbi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:04:00'),
(644, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD48D06', 1, 'Masuk: Rina Arzeti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:04:02'),
(645, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '5D799406', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:04:03'),
(646, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'AD679606', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:04:26'),
(647, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '5D799406', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:38:22'),
(648, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '5D799406', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:38:26'),
(649, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '5D799406', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:39:07'),
(650, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '5D799406', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:39:11'),
(651, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '5D799406', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:39:16'),
(652, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '5D799406', 1, 'Enroll Siswa berhasil: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:39:47'),
(653, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'CD505D06', 1, 'Enroll Siswa berhasil: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:40:13'),
(654, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'AD679606', 1, 'Enroll Siswa berhasil: Adnan Nur Rohim', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:40:24'),
(655, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'ADC67506', 1, 'Enroll Siswa berhasil: Aji Irawan', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:40:34'),
(656, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'EDCE6E06', 1, 'Enroll Siswa berhasil: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:40:45'),
(657, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '6D6E8306', 1, 'Enroll Siswa berhasil: Dhika Hanafi Rantau', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:40:55'),
(658, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'ED7B8106', 1, 'Enroll Siswa berhasil: Jepri Maulana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:41:06'),
(659, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '2DA18C06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:41:24'),
(660, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '2DA18C06', 1, 'Enroll Siswa berhasil: Davit Mubaidilah', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:43:36'),
(661, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '5D799406', 1, 'Masuk: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:44:42'),
(662, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD679606', 1, 'Masuk: Adnan Nur Rohim', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:45:13'),
(663, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'EDCE6E06', 1, 'Masuk: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:45:15'),
(664, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD505D06', 1, 'Masuk: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 08:45:17'),
(665, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'FD348A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:03:59'),
(666, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '8D8B7F06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:04:03'),
(667, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD598806', 1, 'Masuk: Tiara Indriyani Sabela', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:04:05'),
(668, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '8D8B7F06', 1, 'Enroll Siswa berhasil: Arta Kusuma', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:04:23'),
(669, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '8D8B7F06', 1, 'Masuk: Arta Kusuma', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:04:25'),
(670, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'FD348A06', 1, 'Enroll Siswa berhasil: Indra Aprianto', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:04:35'),
(671, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FD348A06', 1, 'Masuk: Indra Aprianto', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:04:37'),
(672, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:32:14'),
(673, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'teacher_auth', 'DDE26E06', 1, 'Teacher authorized (Gate): SOFI NUR HABIBAH', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:37:42'),
(674, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FDEC8306', 1, 'Pulang: Firnando', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:37:46'),
(675, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD679606', 1, 'Pulang: Adnan Nur Rohim', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:37:51'),
(676, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'AD679606', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:37:54'),
(677, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD67906', 1, 'Pulang: Fadli Ardiansyah', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:37:57'),
(678, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED439906', 1, 'Pulang: Akhmad Afandi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:37:59'),
(679, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'EDCE6E06', 1, 'Pulang: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:02'),
(680, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD505D06', 1, 'Pulang: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:05'),
(681, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '1D6C7D06', 1, 'Pulang: Viko Afriyan Arbi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:08'),
(682, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3D517106', 1, 'Pulang: Ahmad Muzaki', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:10'),
(683, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '3D517106', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:12'),
(684, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '8D8B7F06', 1, 'Pulang: Arta Kusuma', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:14'),
(685, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '8D8B7F06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:15'),
(686, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FD348A06', 1, 'Pulang: Indra Aprianto', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:17'),
(687, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '5D799406', 1, 'Pulang: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:22'),
(688, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD598806', 1, 'Pulang: Tiara Indriyani Sabela', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:25'),
(689, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'AD598806', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:27'),
(690, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD48D06', 1, 'Pulang: Rina Arzeti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:31'),
(691, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDD48D06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:33'),
(692, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DC88B06', 1, 'Pulang: Zulfi Aulia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:35'),
(693, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D3C7E06', 1, 'Pulang: Selviana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:39'),
(694, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '4D3C7E06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:41'),
(695, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7DE38706', 1, 'Pulang: Novita Dwi Wijayanti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:43'),
(696, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '7DE38706', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:44'),
(697, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDA97506', 1, 'Pulang: Meliana Dwi Irianti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:46'),
(698, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BDA97506', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:48'),
(699, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D947C06', 1, 'Pulang: Ayu Vera Velinia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:50'),
(700, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '9D947C06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:54'),
(701, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BD5B8906', 1, 'Pulang: Indah Laras Putri', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:57'),
(702, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', 'BD5B8906', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:38:58'),
(703, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD099406', 1, 'Pulang: Vanisaul Khoiroh', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-05 09:39:00'),
(704, '', 'gagal', NULL, 0, 'API key tidak valid', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 10:42:00'),
(705, '', 'gagal', NULL, 0, 'API key tidak valid', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 10:42:15'),
(706, '', 'gagal', NULL, 0, 'API key tidak valid', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 10:43:06'),
(707, '', 'gagal', NULL, 0, 'API key tidak valid', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 10:43:09'),
(708, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '127.0.0.1', 'ESP8266HTTPClient', '2026-01-05 10:44:00'),
(709, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '0DEE6806', 0, 'Kartu tidak terdaftar', '127.0.0.1', 'ESP8266HTTPClient', '2026-01-05 10:44:04'),
(710, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'enroll_success', '0DEE6806', 1, 'Enroll Siswa berhasil: Aditya Rusliano Akbar', '127.0.0.1', 'ESP8266HTTPClient', '2026-01-05 10:44:26'),
(711, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '4D6A8B06', 0, 'Kartu tidak terdaftar', '127.0.0.1', 'ESP8266HTTPClient', '2026-01-06 07:30:37'),
(712, 'U2aHYBN4hEK8p2wAxVJJHPssffcjhSkwP5YGJxItF7Tyd6Pc3LazcJCtkx2l', 'unknown_card', '4D6A8B06', 0, 'Kartu tidak terdaftar', '127.0.0.1', 'ESP8266HTTPClient', '2026-01-06 07:30:39'),
(713, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDD48D06', 1, 'Masuk: Rina Arzeti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:31:14'),
(714, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'BDD48D06', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:31:17'),
(715, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D947C06', 1, 'Masuk: Ayu Vera Velinia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:16'),
(716, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FDEC8306', 1, 'Masuk: Firnando', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:31'),
(717, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED439906', 1, 'Masuk: Akhmad Afandi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:36'),
(718, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD679606', 1, 'Masuk: Adnan Nur Rohim', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:41'),
(719, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'AD679606', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:44'),
(720, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BD5B8906', 1, 'Masuk: Indah Laras Putri', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:50'),
(721, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'BD5B8906', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:33:54'),
(722, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDA97506', 1, 'Masuk: Meliana Dwi Irianti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:35:25'),
(723, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'BDA97506', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:35:27'),
(724, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D3C7E06', 1, 'Masuk: Selviana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:38:59'),
(725, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', '4D3C7E06', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:39:02'),
(726, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '5D799406', 1, 'Masuk: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:43:34'),
(727, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD598806', 1, 'Masuk: Tiara Indriyani Sabela', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:43:42'),
(728, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'AD598806', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:43:46'),
(729, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', '5D799406', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:43:51'),
(730, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', '5D799406', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:43:54'),
(731, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'AD598806', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:43:58'),
(732, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '3D517106', 1, 'Masuk: Ahmad Muzaki', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:49:53'),
(733, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1D6C7D06', 1, 'Masuk: Viko Afriyan Arbi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:50:11'),
(734, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD505D06', 1, 'Masuk: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:51:06'),
(735, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'CD505D06', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:51:09'),
(736, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'EDCE6E06', 1, 'Masuk: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:51:29'),
(737, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:53:38'),
(738, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:53:52'),
(739, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:53:55'),
(740, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'ADC67506', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:53:56'),
(741, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '9DD08E06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:54:15'),
(742, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '9DD08E06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:54:17'),
(743, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'ADC67506', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:54:20'),
(744, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:54:23'),
(745, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:55:42'),
(746, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:56:00'),
(747, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', '4DBF8A06', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 07:56:05'),
(748, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'unknown_card', 'ADC67506', 0, 'Kartu tidak terdaftar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:05:59'),
(749, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D9D8506', 1, 'Masuk: Hanif Dwi Cahyono', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:06:41'),
(750, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', '4D9D8506', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:06:44'),
(751, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD099406', 1, 'Masuk: Vanisaul Khoiroh', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:09:02'),
(752, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9DC88B06', 1, 'Masuk: Zulfi Aulia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:09:15'),
(753, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'ADC67506', 1, 'Enroll Siswa berhasil: Aji Irawan', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:24:09'),
(754, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '4DBF8A06', 1, 'Enroll Siswa berhasil: Aditya Rusliano Akbar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:26:03'),
(755, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '9DD08E06', 1, 'Enroll Siswa berhasil: Rilly Meilana Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:26:17'),
(756, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '2DA18C06', 1, 'Masuk: Davit Mubaidilah', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:30:21'),
(757, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', 'ED7B8106', 0, 'UID sudah ada', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:31:03'),
(758, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', 'ED7B8106', 0, 'UID sudah ada', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:31:12'),
(759, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED7B8106', 1, 'Masuk: Jepri Maulana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:31:18'),
(760, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', 'DDE26E06', 1, 'Enroll Guru berhasil: Ahmad Daqiqi Syahrulloh, S.H.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 08:32:13'),
(761, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gate_opened', 'DDE26E06', 1, 'Gate opened by: Ahmad Daqiqi Syahrulloh, S.H.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:41:23'),
(762, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gate_closed', 'DDE26E06', 1, 'Gate closed by: Ahmad Daqiqi Syahrulloh, S.H.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:41:32'),
(763, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', 'CD505D06', 0, 'Belum ada izin guru.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:41:52'),
(764, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gate_opened', 'DDE26E06', 1, 'Gate opened by: Ahmad Daqiqi Syahrulloh, S.H.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:41:55'),
(765, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD505D06', 1, 'Pulang: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:41:58'),
(766, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4DBF8A06', 1, 'Pulang: Aditya Rusliano Akbar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:14'),
(767, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'EDCE6E06', 1, 'Pulang: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:18'),
(768, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DD08E06', 1, 'Pulang: Rilly Meilana Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:20'),
(769, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ADC67506', 1, 'Pulang: Aji Irawan', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:22'),
(770, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '9DD08E06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:24'),
(771, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9DC88B06', 1, 'Pulang: Zulfi Aulia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:30'),
(772, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD598806', 1, 'Pulang: Tiara Indriyani Sabela', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:33'),
(773, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '5D799406', 1, 'Pulang: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:35'),
(774, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD099406', 1, 'Pulang: Vanisaul Khoiroh', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:49'),
(775, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3D517106', 1, 'Pulang: Ahmad Muzaki', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:51'),
(776, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D9D8506', 1, 'Pulang: Hanif Dwi Cahyono', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:42:55'),
(777, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '1D6C7D06', 1, 'Pulang: Viko Afriyan Arbi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:01'),
(778, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDD48D06', 1, 'Pulang: Rina Arzeti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:11'),
(779, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D3C7E06', 1, 'Pulang: Selviana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:23');
INSERT INTO `api_logs` (`id`, `api_key`, `action`, `uid`, `success`, `message`, `ip_address`, `user_agent`, `created_at`) VALUES
(780, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '4D3C7E06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:25'),
(781, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDA97506', 1, 'Pulang: Meliana Dwi Irianti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:45'),
(782, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D947C06', 1, 'Pulang: Ayu Vera Velinia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:49'),
(783, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '9D947C06', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:50'),
(784, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BD5B8906', 1, 'Pulang: Indah Laras Putri', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:43:53'),
(785, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FDEC8306', 1, 'Pulang: Firnando', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:44:25'),
(786, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED439906', 1, 'Pulang: Akhmad Afandi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:44:28'),
(787, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD679606', 1, 'Pulang: Adnan Nur Rohim', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:44:34'),
(788, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED7B8106', 1, 'Pulang: Jepri Maulana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:45:16'),
(789, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '2DA18C06', 1, 'Pulang: Davit Mubaidilah', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-06 10:45:33'),
(790, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '9D947C06', 1, 'Masuk: Ayu Vera Velinia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:25:24'),
(791, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'FDEC8306', 1, 'Masuk: Firnando', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:29:03'),
(792, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ED439906', 1, 'Masuk: Akhmad Afandi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:29:20'),
(793, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BDA97506', 1, 'Masuk: Meliana Dwi Irianti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:33:20'),
(794, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'BD5B8906', 1, 'Masuk: Indah Laras Putri', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:34:15'),
(795, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD679606', 1, 'Masuk: Adnan Nur Rohim', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:39:40'),
(796, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '7DE38706', 1, 'Masuk: Novita Dwi Wijayanti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:43:46'),
(797, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4D3C7E06', 1, 'Masuk: Selviana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:44:03'),
(798, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'CD505D06', 1, 'Masuk: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:44:56'),
(799, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '4DBF8A06', 1, 'Masuk: Aditya Rusliano Akbar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:45:04'),
(800, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'EDCE6E06', 1, 'Masuk: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:45:15'),
(801, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'ADC67506', 1, 'Masuk: Aji Irawan', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:45:54'),
(802, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', 'ADC67506', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:45:56'),
(803, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '5D799406', 1, 'Masuk: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:50:17'),
(804, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'AD598806', 1, 'Masuk: Tiara Indriyani Sabela', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:50:21'),
(805, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '3D517106', 1, 'Masuk: Ahmad Muzaki', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:50:25'),
(806, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', 'DD099406', 1, 'Masuk: Vanisaul Khoiroh', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 07:59:13'),
(807, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'enroll_success', '1DE47C06', 1, 'Enroll Siswa berhasil: Andi Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:23:28'),
(808, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DE47C06', 1, 'Masuk: Andi Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:23:46'),
(809, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_closed', '1DE47C06', 0, 'Absen Masuk Ditutup', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:31:32'),
(810, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DE47C06', 1, 'Masuk: Andi Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:34:53'),
(811, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DE47C06', 1, 'Masuk: Andi Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:36:28'),
(812, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_absen_masuk', '1DE47C06', 1, 'Sudah Absen Masuk', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:47:57'),
(813, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkin_success', '1DE47C06', 1, 'Masuk: Andi Wijaya', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 09:48:24'),
(814, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'no_authorization', 'FDEC8306', 0, 'Belum ada izin guru.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:18:36'),
(815, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gate_opened', 'DDE26E06', 1, 'Gate opened by: Ahmad Daqiqi Syahrulloh, S.H.', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:18:49'),
(816, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'FDEC8306', 1, 'Pulang: Firnando', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:18:51'),
(817, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'CD505D06', 1, 'Pulang: Arphanca Kun Nugroho', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:18:55'),
(818, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4DBF8A06', 1, 'Pulang: Aditya Rusliano Akbar', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:18:57'),
(819, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ED439906', 1, 'Pulang: Akhmad Afandi', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:01'),
(820, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '9D947C06', 1, 'Pulang: Ayu Vera Velinia', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:04'),
(821, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '4D3C7E06', 1, 'Pulang: Selviana', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:07'),
(822, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '7DE38706', 1, 'Pulang: Novita Dwi Wijayanti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:08'),
(823, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BD5B8906', 1, 'Pulang: Indah Laras Putri', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:10'),
(824, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'BDA97506', 1, 'Pulang: Meliana Dwi Irianti', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:13'),
(825, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '3D517106', 1, 'Pulang: Ahmad Muzaki', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:16'),
(826, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'EDCE6E06', 1, 'Pulang: Andre Marcel', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:22'),
(827, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'ADC67506', 1, 'Pulang: Aji Irawan', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:27'),
(828, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'DD099406', 1, 'Pulang: Vanisaul Khoiroh', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:19:36'),
(829, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', 'AD598806', 1, 'Pulang: Tiara Indriyani Sabela', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:22:15'),
(830, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'checkout_success', '5D799406', 1, 'Pulang: Brenda Zaskia R', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:22:17'),
(831, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'sudah_lengkap', '5D799406', 1, 'Absen Lengkap', '192.168.1.7', 'ESP8266HTTPClient', '2026-01-07 12:22:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `total_seconds` int(11) NOT NULL DEFAULT 0,
  `status` enum('H','I','S','A','B','P') DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `is_auto_extended` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `total_seconds`, `status`, `keterangan`, `is_auto_extended`, `created_at`, `updated_at`) VALUES
(346, 353, '2026-01-05', NULL, NULL, 0, 'I', '-', 0, '2026-01-05 00:50:37', '2026-01-05 00:50:37'),
(347, 333, '2026-01-05', '07:57:41', '09:38:59', -6079, 'H', NULL, 0, '2026-01-05 07:57:41', '2026-01-05 09:38:59'),
(348, 360, '2026-01-05', '07:59:09', '09:37:59', -5930, 'H', NULL, 0, '2026-01-05 07:59:09', '2026-01-05 09:37:59'),
(349, 372, '2026-01-05', '07:59:20', '09:38:42', -5963, 'H', NULL, 0, '2026-01-05 07:59:20', '2026-01-05 09:38:42'),
(350, 369, '2026-01-05', '07:59:26', '09:38:56', -5971, 'H', NULL, 0, '2026-01-05 07:59:26', '2026-01-05 09:38:56'),
(351, 367, '2026-01-05', '07:59:30', '09:37:45', -5895, 'H', NULL, 0, '2026-01-05 07:59:30', '2026-01-05 09:37:45'),
(352, 366, '2026-01-05', '07:59:32', '09:37:56', -5905, 'H', NULL, 0, '2026-01-05 07:59:32', '2026-01-05 09:37:56'),
(353, 363, '2026-01-05', '07:59:51', '09:38:50', -5939, 'H', NULL, 0, '2026-01-05 07:59:51', '2026-01-05 09:38:50'),
(354, 374, '2026-01-05', '08:01:29', '09:38:35', -5826, 'H', NULL, 0, '2026-01-05 08:01:29', '2026-01-05 09:38:35'),
(355, 373, '2026-01-05', '08:01:38', '09:38:39', -5821, 'H', NULL, 0, '2026-01-05 08:01:38', '2026-01-05 09:38:39'),
(356, 371, '2026-01-05', '08:01:44', '09:38:46', -5823, 'H', NULL, 0, '2026-01-05 08:01:44', '2026-01-05 09:38:46'),
(357, 325, '2026-01-05', '08:03:58', '09:38:10', -5652, 'H', NULL, 0, '2026-01-05 08:03:58', '2026-01-05 09:38:10'),
(358, 334, '2026-01-05', '08:03:59', '09:38:08', -5649, 'H', NULL, 0, '2026-01-05 08:03:59', '2026-01-05 09:38:08'),
(359, 331, '2026-01-05', '08:04:01', '09:38:31', -5670, 'H', NULL, 0, '2026-01-05 08:04:01', '2026-01-05 09:38:31'),
(360, 327, '2026-01-05', '08:44:41', '09:38:21', -3221, 'H', 'Telat 1 jam 44 menit', 0, '2026-01-05 08:44:41', '2026-01-05 09:38:21'),
(361, 358, '2026-01-05', '08:45:13', '09:37:51', -3159, 'H', 'Telat 1 jam 45 menit', 0, '2026-01-05 08:45:13', '2026-01-05 09:37:51'),
(362, 361, '2026-01-05', '08:45:15', '09:38:01', -3167, 'H', 'Telat 1 jam 45 menit', 0, '2026-01-05 08:45:15', '2026-01-05 09:38:01'),
(363, 362, '2026-01-05', '08:45:16', '09:38:05', -3169, 'H', 'Telat 1 jam 45 menit', 0, '2026-01-05 08:45:16', '2026-01-05 09:38:05'),
(364, 332, '2026-01-05', '09:04:04', '09:38:24', -2061, 'H', 'Telat 2 jam 4 menit', 0, '2026-01-05 09:04:04', '2026-01-05 09:38:24'),
(365, 326, '2026-01-05', '09:04:25', '09:38:13', -2029, 'H', 'Telat 2 jam 4 menit', 0, '2026-01-05 09:04:25', '2026-01-05 09:38:13'),
(366, 328, '2026-01-05', '09:04:37', '09:38:17', -2020, 'H', 'Telat 2 jam 4 menit', 0, '2026-01-05 09:04:37', '2026-01-05 09:38:17'),
(367, 324, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:04', '2026-01-05 13:15:04'),
(368, 329, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:04', '2026-01-05 13:15:04'),
(369, 330, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:04', '2026-01-05 13:15:04'),
(370, 359, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:04', '2026-01-05 13:15:04'),
(371, 364, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:04', '2026-01-05 13:15:04'),
(372, 365, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:04', '2026-01-05 13:15:04'),
(373, 368, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:05', '2026-01-05 13:15:05'),
(374, 370, '2026-01-05', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-05 13:15:05', '2026-01-05 13:15:05'),
(375, 331, '2026-01-06', '07:31:13', '10:43:11', -11519, 'H', 'Telat 1 jam 31 menit', 0, '2026-01-06 07:31:13', '2026-01-06 10:43:11'),
(376, 363, '2026-01-06', '07:33:16', '10:43:48', -11433, 'H', NULL, 0, '2026-01-06 07:33:16', '2026-01-06 10:43:48'),
(377, 367, '2026-01-06', '07:33:30', '10:44:25', -11455, 'H', NULL, 0, '2026-01-06 07:33:30', '2026-01-06 10:44:25'),
(378, 360, '2026-01-06', '07:33:36', '10:44:28', -11452, 'H', NULL, 0, '2026-01-06 07:33:36', '2026-01-06 10:44:28'),
(379, 358, '2026-01-06', '07:33:41', '10:44:33', -11453, 'H', NULL, 0, '2026-01-06 07:33:41', '2026-01-06 10:44:33'),
(380, 369, '2026-01-06', '07:33:50', '10:43:53', -11403, 'H', NULL, 0, '2026-01-06 07:33:50', '2026-01-06 10:43:53'),
(381, 371, '2026-01-06', '07:35:24', '10:43:44', -11301, 'H', 'Telat 1 jam 5 menit', 0, '2026-01-06 07:35:24', '2026-01-06 10:43:44'),
(382, 373, '2026-01-06', '07:38:58', '10:43:22', -11065, 'H', 'Telat 1 jam 8 menit', 0, '2026-01-06 07:38:58', '2026-01-06 10:43:22'),
(383, 327, '2026-01-06', '07:43:34', '10:42:35', -10742, 'H', 'Telat 1 jam 13 menit', 0, '2026-01-06 07:43:34', '2026-01-06 10:42:35'),
(384, 332, '2026-01-06', '07:43:42', '10:42:33', -10731, 'H', 'Telat 1 jam 13 menit', 0, '2026-01-06 07:43:42', '2026-01-06 10:42:33'),
(385, 325, '2026-01-06', '07:49:53', '10:42:51', -10378, 'H', 'Telat 1 jam 19 menit', 0, '2026-01-06 07:49:53', '2026-01-06 10:42:51'),
(386, 334, '2026-01-06', '07:50:11', '10:43:01', -10371, 'H', 'Telat 1 jam 20 menit', 0, '2026-01-06 07:50:11', '2026-01-06 10:43:01'),
(387, 362, '2026-01-06', '07:51:06', '10:41:58', -10252, 'H', 'Telat 1 jam 21 menit', 0, '2026-01-06 07:51:06', '2026-01-06 10:41:58'),
(388, 361, '2026-01-06', '07:51:29', '10:42:18', -10249, 'H', 'Telat 1 jam 21 menit', 0, '2026-01-06 07:51:29', '2026-01-06 10:42:18'),
(389, 368, '2026-01-06', '08:06:41', '10:42:55', -9374, 'H', 'Telat 1 jam 36 menit', 0, '2026-01-06 08:06:41', '2026-01-06 10:42:55'),
(390, 333, '2026-01-06', '08:09:02', '10:42:49', -9227, 'H', 'Telat 34 menit', 0, '2026-01-06 08:09:02', '2026-01-06 10:42:49'),
(391, 374, '2026-01-06', '08:09:15', '10:42:29', -9195, 'H', 'Telat 34 menit', 0, '2026-01-06 08:09:15', '2026-01-06 10:42:29'),
(392, 359, '2026-01-06', '08:19:48', '10:42:22', -8554, 'H', 'Absen masuk oleh Ahmad Daqiqi Syahrulloh, S.H.', 0, '2026-01-06 01:19:48', '2026-01-06 10:42:22'),
(393, 330, '2026-01-06', '08:20:19', '10:42:20', -8521, 'H', 'Absen masuk oleh Ahmad Daqiqi Syahrulloh, S.H.', 0, '2026-01-06 01:20:19', '2026-01-06 10:42:20'),
(394, 324, '2026-01-06', '08:22:04', '10:42:14', -8410, 'H', 'Absen masuk oleh Ahmad Daqiqi Syahrulloh, S.H.', 0, '2026-01-06 01:22:04', '2026-01-06 10:42:14'),
(395, 364, '2026-01-06', '08:30:21', '10:45:33', -8112, 'H', 'Telat 55 menit', 0, '2026-01-06 08:30:21', '2026-01-06 10:45:33'),
(396, 370, '2026-01-06', '08:31:18', '10:45:15', -8038, 'H', 'Telat 56 menit', 0, '2026-01-06 08:31:18', '2026-01-06 10:45:15'),
(397, 366, '2026-01-06', NULL, NULL, 0, 'S', '-', 0, '2026-01-06 01:53:42', '2026-01-06 01:53:42'),
(398, 326, '2026-01-06', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-06 13:15:03', '2026-01-06 13:15:03'),
(399, 328, '2026-01-06', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-06 13:15:04', '2026-01-06 13:15:04'),
(400, 329, '2026-01-06', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-06 13:15:04', '2026-01-06 13:15:04'),
(401, 365, '2026-01-06', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-06 13:15:04', '2026-01-06 13:15:04'),
(402, 372, '2026-01-06', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-06 13:15:04', '2026-01-06 13:15:04'),
(403, 363, '2026-01-07', '07:25:24', '12:19:03', 17620, 'H', NULL, 0, '2026-01-07 07:25:24', '2026-01-07 12:19:03'),
(404, 367, '2026-01-07', '07:29:02', '12:18:51', 17390, 'H', NULL, 0, '2026-01-07 07:29:02', '2026-01-07 12:18:51'),
(405, 360, '2026-01-07', '07:29:19', '12:19:00', 17382, 'H', NULL, 0, '2026-01-07 07:29:19', '2026-01-07 12:19:00'),
(406, 371, '2026-01-07', '07:33:19', '12:19:13', 17154, 'H', NULL, 0, '2026-01-07 07:33:19', '2026-01-07 12:19:13'),
(407, 369, '2026-01-07', '07:34:15', '12:19:10', 17095, 'H', NULL, 0, '2026-01-07 07:34:15', '2026-01-07 12:19:10'),
(408, 358, '2026-01-07', '07:39:40', NULL, 0, 'B', 'Telat 4 menit [Auto: Tidak Absen Pulang]', 0, '2026-01-07 07:39:40', '2026-01-07 13:15:03'),
(409, 372, '2026-01-07', '07:43:46', '12:19:07', 16522, 'H', 'Telat 8 menit', 0, '2026-01-07 07:43:46', '2026-01-07 12:19:08'),
(410, 373, '2026-01-07', '07:44:02', '12:19:06', 16505, 'H', 'Telat 9 menit', 0, '2026-01-07 07:44:02', '2026-01-07 12:19:06'),
(411, 362, '2026-01-07', '07:44:56', '12:18:54', 16439, 'H', 'Telat 9 menit', 0, '2026-01-07 07:44:56', '2026-01-07 12:18:54'),
(412, 324, '2026-01-07', '07:45:04', '12:18:57', 16434, 'H', 'Telat 10 menit', 0, '2026-01-07 07:45:04', '2026-01-07 12:18:57'),
(413, 361, '2026-01-07', '07:45:15', '12:19:21', 16446, 'H', 'Telat 10 menit', 0, '2026-01-07 07:45:15', '2026-01-07 12:19:21'),
(414, 359, '2026-01-07', '07:45:54', '12:19:26', 16413, 'H', 'Telat 10 menit', 0, '2026-01-07 07:45:54', '2026-01-07 12:19:26'),
(415, 327, '2026-01-07', '07:50:17', '12:22:17', 16320, 'H', 'Telat 15 menit', 0, '2026-01-07 07:50:17', '2026-01-07 12:22:17'),
(416, 332, '2026-01-07', '07:50:21', '12:22:15', 16314, 'H', 'Telat 15 menit', 0, '2026-01-07 07:50:21', '2026-01-07 12:22:15'),
(417, 325, '2026-01-07', '07:50:25', '12:19:16', 16131, 'H', 'Telat 15 menit', 0, '2026-01-07 07:50:25', '2026-01-07 12:19:16'),
(418, 333, '2026-01-07', '07:59:13', '12:19:35', 15623, 'H', 'Telat 24 menit', 0, '2026-01-07 07:59:13', '2026-01-07 12:19:35'),
(419, 365, '2026-01-07', '08:33:48', NULL, 0, 'B', 'Absen masuk oleh Ahmad Daqiqi Syahrulloh, S.H. [Auto: Tidak Absen Pulang]', 0, '2026-01-07 01:33:48', '2026-01-07 13:15:03'),
(420, 366, '2026-01-07', NULL, NULL, 0, 'S', '-', 0, '2026-01-07 01:34:54', '2026-01-07 01:34:54'),
(421, 374, '2026-01-07', NULL, NULL, 0, 'S', 'Demam', 0, '2026-01-07 01:37:19', '2026-01-07 01:37:19'),
(426, 330, '2026-01-07', NULL, NULL, 0, 'S', '-', 0, '2026-01-07 05:07:34', '2026-01-07 05:07:34'),
(427, 326, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:04', '2026-01-07 13:15:04'),
(428, 328, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:04', '2026-01-07 13:15:04'),
(429, 329, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:04', '2026-01-07 13:15:04'),
(430, 331, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:04', '2026-01-07 13:15:04'),
(431, 334, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:05', '2026-01-07 13:15:05'),
(432, 364, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:05', '2026-01-07 13:15:05'),
(433, 368, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:05', '2026-01-07 13:15:05'),
(434, 370, '2026-01-07', NULL, NULL, 0, 'A', 'Alpha (Tidak Hadir)', 0, '2026-01-07 13:15:05', '2026-01-07 13:15:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-illuminate:queue:restart', 'i:1767498644;', 2082858644);

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
-- Struktur dari tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `guru`
--

CREATE TABLE `guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `no_wa` varchar(50) NOT NULL,
  `id_finger` varchar(255) DEFAULT NULL,
  `uid_rfid` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `enroll_status` varchar(255) DEFAULT NULL,
  `enroll_finger_status` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `guru`
--

INSERT INTO `guru` (`id`, `nama`, `nip`, `no_wa`, `id_finger`, `uid_rfid`, `created_at`, `enroll_status`, `enroll_finger_status`, `updated_at`) VALUES
(11, 'Ahmad Daqiqi Syahrulloh, S.H.', NULL, '6281524824563', NULL, 'DDE26E06', '2026-01-05 11:12:46', 'done', NULL, '2026-01-07 11:10:32'),
(12, 'Walito, S.Pd.', NULL, '6285841707991', NULL, NULL, '2026-01-05 11:28:46', NULL, NULL, '2026-01-05 11:28:46'),
(13, 'Sri Mulyani', NULL, '6285609227366', NULL, NULL, '2026-01-05 11:29:10', NULL, NULL, '2026-01-05 11:29:10'),
(14, 'Dian Andriani, A.Mk.SE', NULL, '6282280250609', NULL, NULL, '2026-01-05 11:29:55', NULL, NULL, '2026-01-05 11:29:55'),
(15, 'Athiq Nur Azizah, S.Pd.', NULL, '6281532585790', NULL, NULL, '2026-01-05 11:30:44', NULL, NULL, '2026-01-05 11:30:44'),
(16, 'Shinta Ningrum H.,S.Pd.', NULL, '6285266899407', NULL, NULL, '2026-01-06 22:43:13', NULL, NULL, '2026-01-06 22:43:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `guru_fingerprints`
--

CREATE TABLE `guru_fingerprints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guru_id` int(10) UNSIGNED NOT NULL,
  `device_id` int(11) NOT NULL,
  `finger_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `guru_fingerprints`
--

INSERT INTO `guru_fingerprints` (`id`, `guru_id`, `device_id`, `finger_id`, `created_at`, `updated_at`) VALUES
(3, 9, 8, 999, '2025-12-28 01:09:32', '2025-12-28 01:09:32'),
(9, 3, 7, 3, '2025-12-28 04:55:28', '2025-12-28 04:55:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hari_libur`
--

CREATE TABLE `hari_libur` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `hari` varchar(20) NOT NULL,
  `index_hari` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `toleransi` int(11) DEFAULT 15,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `hari`, `index_hari`, `is_active`, `jam_masuk`, `jam_pulang`, `toleransi`, `created_at`, `updated_at`) VALUES
(1, 'Senin', 1, 1, '07:00:00', '13:00:00', 15, NULL, '2026-01-06 07:32:41'),
(2, 'Selasa', 2, 1, '07:30:00', '13:00:00', 5, NULL, '2026-01-06 07:32:15'),
(3, 'Rabu', 3, 1, '07:30:00', '13:00:00', 5, NULL, '2026-01-06 07:33:03'),
(4, 'Kamis', 4, 1, '07:30:00', '13:00:00', 5, NULL, '2026-01-06 07:33:18'),
(5, 'Jumat', 5, 1, '07:30:00', '11:00:00', 5, NULL, '2026-01-06 07:33:35'),
(6, 'Sabtu', 6, 1, '07:30:00', '11:00:00', 5, NULL, '2026-01-06 07:33:49'),
(7, 'Minggu', 7, 0, '17:00:00', '20:00:00', 15, NULL, '2025-12-28 10:27:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_pelajaran`
--

CREATE TABLE `jadwal_pelajaran` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guru_id` int(10) UNSIGNED NOT NULL,
  `kelas_id` int(10) UNSIGNED NOT NULL,
  `mapel_id` bigint(20) UNSIGNED NOT NULL,
  `hari` varchar(255) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `wali_kelas_id` int(10) UNSIGNED DEFAULT NULL,
  `wa_group_id` varchar(100) DEFAULT NULL,
  `is_active_attendance` tinyint(1) NOT NULL DEFAULT 1,
  `is_active_report` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `wali_kelas_id`, `wa_group_id`, `is_active_attendance`, `is_active_report`, `created_at`) VALUES
(1, 'X TSM', NULL, '120363421570491131@g.us', 1, 0, '2025-11-17 14:09:10'),
(2, 'X TB', NULL, '120363421570491131@g.us', 1, 1, '2025-11-17 14:09:10'),
(3, 'XI TSM', NULL, '120363314712900327@g.us', 0, 1, '2025-11-17 14:09:10'),
(4, 'XI TB', NULL, '120363314712900327@g.us', 0, 1, '2025-11-17 14:09:10'),
(5, 'XII TSM', NULL, '120363161431567388@g.us', 1, 0, '2025-12-22 04:35:02'),
(6, 'XII TB', NULL, '120363161431567388@g.us', 1, 1, '2025-12-22 04:35:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mapel`
--

CREATE TABLE `mapel` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_mapel` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `message_queues`
--

CREATE TABLE `message_queues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phone_number` varchar(60) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `last_error` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `message_queues`
--

INSERT INTO `message_queues` (`id`, `phone_number`, `message`, `status`, `last_error`, `attempts`, `created_at`, `updated_at`) VALUES
(1, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'failed', NULL, 0, '2025-12-28 00:25:30', '2025-12-29 07:09:05'),
(2, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1 (Device #4)`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'failed', NULL, 0, '2025-12-28 00:30:38', '2025-12-29 07:09:05'),
(3, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1 (Device #7)`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'failed', NULL, 0, '2025-12-28 00:45:40', '2025-12-29 07:09:06'),
(4, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1 (Device #7)`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'failed', NULL, 0, '2025-12-28 00:55:34', '2025-12-29 07:09:07'),
(5, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Dwi Sampurna Jaya*,\n\n📅 Tanggal: 28/12/2025\n� Jam Masuk: 17:25\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 25 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2025-12-28 10:25:49', '2025-12-29 07:09:09'),
(6, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Kartu Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `DDE26E06`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'failed', NULL, 0, '2025-12-28 10:26:47', '2025-12-29 07:09:10'),
(7, '6281524824563@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Dwi Sampurna Jaya*,\n\n📍 Jam Masuk: 17:25\n� Jam Pulang: 17:26\n⏱️ Durasi: -1 jam -2 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2025-12-28 10:26:55', '2025-12-29 07:09:12'),
(8, '6281524824563@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Haris Vino Agusthaan* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `7D368E06`\n📅 Tanggal: Monday, 29 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2025-12-29 07:25:43', '2025-12-29 07:26:05'),
(9, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Haris Vino Agusthaan*,\n\n📅 Tanggal: 29/12/2025\n� Jam Masuk: 07:30\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 30 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2025-12-29 07:30:20', '2025-12-29 07:31:04'),
(10, '62845212211@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Aditya Rusliano Akbar* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `4DBF8A06`\n📅 Tanggal: Monday, 29 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'failed', NULL, 0, '2025-12-29 07:31:34', '2025-12-29 07:32:04'),
(11, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 29/12/2025\n---------------------------\n✅ Siswa Masuk: 28\n❌ Siswa Tidak Masuk: 22\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Aji Irawan (Alpha)\n- Akhmad Afandi (Alpha)\n- Andre Marcel (Alpha)\n- Arphanca Kun Nugroho (Alpha)\n- Ayu Vera Velinia (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Dwi Sampurna Jaya (Alpha)\n- Fadli Ardiansyah (Alpha)\n- Fariz Kurniawan (Alpha)\n- Firnando (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indah Laras Putri (Alpha)\n- Jepri Maulana (Alpha)\n- Meliana Dwi Irianti (Alpha)\n- Muhammad Irsyadul A\'la (Alpha)\n- Musa (Alpha)\n- Novita Dwi Wijayanti (Alpha)\n- Panji Setia Wardana (Alpha)\n- Selviana (Alpha)\n- Vanisaul Khoiroh (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2025-12-29 08:15:05', '2025-12-29 08:16:06'),
(12, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 29/12/2025\n---------------------------\n✅ Siswa Masuk: 28\n❌ Siswa Tidak Masuk: 22\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Aji Irawan (Alpha)\n- Akhmad Afandi (Alpha)\n- Andre Marcel (Alpha)\n- Arphanca Kun Nugroho (Alpha)\n- Ayu Vera Velinia (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Dwi Sampurna Jaya (Alpha)\n- Fadli Ardiansyah (Alpha)\n- Fariz Kurniawan (Alpha)\n- Firnando (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indah Laras Putri (Alpha)\n- Jepri Maulana (Alpha)\n- Meliana Dwi Irianti (Alpha)\n- Muhammad Irsyadul A\'la (Alpha)\n- Musa (Alpha)\n- Novita Dwi Wijayanti (Alpha)\n- Panji Setia Wardana (Alpha)\n- Selviana (Alpha)\n- Vanisaul Khoiroh (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2025-12-29 08:15:05', '2025-12-29 08:16:07'),
(13, '6281524824563@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Haris Vino Agusthaan*,\n\n📍 Jam Masuk: 07:30\n� Jam Pulang: 13:21\n⏱️ Durasi: -6 jam -51 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2025-12-29 13:21:15', '2025-12-29 13:22:05'),
(14, '120363421672356407@g.us', '📋 *LAPORAN KETIDAKHADIRAN*\n📅 Tanggal: 29/12/2025\n──────────────────────────────\n\n*❌ Alpha* (2 siswa)\n  • Dwi Sampurna Jaya (XI TSM)\n  • Ayu Vera Velinia (XII TSM)\n\n*🏃 Bolos (Tidak Absen Pulang)* (7 siswa)\n  • Rilly Meilana Wijaya (X TSM)\n  • Muhamad Deni Setiawan (XI TSM)\n  • Muhammad Haris Ashrori (XI TSM)\n  • Viko Afriyan Arbi (X TSM)\n  • Adi Abdurachman (XI TSM)\n  • Akhmad Afandi (XII TSM)\n  • Aji Irawan (XII TSM)\n\n*📝 Izin* (1 siswa)\n  • Aditya Rusliano Akbar (X TSM)\n\n*🤒 Sakit* (2 siswa)\n  • Adnan Nur Rohim (XII TSM)\n  • Andre Marcel (XII TSM)\n\n──────────────────────────────\nTotal: *12 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2025-12-29 13:30:05', '2025-12-29 13:30:08'),
(15, '120363421672356407@g.us', '📋 *LAPORAN KETIDAKHADIRAN*\n📅 Tanggal: 29/12/2025\n──────────────────────────────\n\n*❌ Alpha* (2 siswa)\n  • Dwi Sampurna Jaya (XI TSM)\n  • Ayu Vera Velinia (XII TSM)\n\n*🏃 Bolos (Tidak Absen Pulang)* (7 siswa)\n  • Rilly Meilana Wijaya (X TSM)\n  • Muhamad Deni Setiawan (XI TSM)\n  • Muhammad Haris Ashrori (XI TSM)\n  • Viko Afriyan Arbi (X TSM)\n  • Adi Abdurachman (XI TSM)\n  • Akhmad Afandi (XII TSM)\n  • Aji Irawan (XII TSM)\n\n*📝 Izin* (1 siswa)\n  • Aditya Rusliano Akbar (X TSM)\n\n*🤒 Sakit* (2 siswa)\n  • Adnan Nur Rohim (XII TSM)\n  • Andre Marcel (XII TSM)\n\n──────────────────────────────\nTotal: *12 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2025-12-29 13:30:05', '2025-12-29 13:30:09'),
(16, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Haris Vino Agusthaan*,\n\n📅 Tanggal: 30/12/2025\n� Jam Masuk: 06:25\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2025-12-30 06:25:01', '2025-12-30 06:25:06'),
(17, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 30/12/2025\n---------------------------\n✅ Siswa Masuk: 44\n❌ Siswa Tidak Masuk: 7\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Ahmad Agus Salim (Izin)\n- Ahmad Muzaki (Izin)\n- Arta Kusuma (Alpha)\n- Dhani Alan Maulana (Alpha)\n- Firnando (Alpha)\n- Indra Aprianto (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2025-12-30 08:15:05', '2025-12-30 08:16:05'),
(18, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 30/12/2025\n---------------------------\n✅ Siswa Masuk: 44\n❌ Siswa Tidak Masuk: 7\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Ahmad Agus Salim (Izin)\n- Ahmad Muzaki (Izin)\n- Arta Kusuma (Alpha)\n- Dhani Alan Maulana (Alpha)\n- Firnando (Alpha)\n- Indra Aprianto (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2025-12-30 08:15:06', '2025-12-30 08:16:06'),
(19, '120363421672356407@g.us', '📋 *LAPORAN KETIDAKHADIRAN*\n📅 Tanggal: 01/01/2026\n──────────────────────────────\n\n*❌ Alpha* (51 siswa)\n  • Ayu Vera Velinia (XII TSM)\n  • Keyla Biyan Ramadhani (XI TB)\n  • M. Maulana Eri Fernando (XI TSM)\n  • Muhamad Deni Setiawan (XI TSM)\n  • Muhammad Haris Ashrori (XI TSM)\n  • Muhammad Irsyadul A\'la (XI TSM)\n  • Musa (XI TSM)\n  • Panji Setia Wardana (XI TSM)\n  • Ririn Mardiana (XI TB)\n  • Aji Irawan (XII TSM)\n  • Akhmad Afandi (XII TSM)\n  • Andre Marcel (XII TSM)\n  • Arphanca Kun Nugroho (XII TSM)\n  • Junia Sari (XI TB)\n  • Davit Mubaidilah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Firnando (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Indah Laras Putri (XII TB)\n  • Jepri Maulana (XII TSM)\n  • Meliana Dwi Irianti (XII TB)\n  • Novita Dwi Wijayanti (XII TB)\n  • Selviana (XII TB)\n  • Zulfi Aulia (XII TB)\n  • Adnan Nur Rohim (XII TSM)\n  • Ahmad Agus Salim (XI TSM)\n  • Ahmad Muzaki (X TSM)\n  • Arta Kusuma (X TSM)\n  • Brenda Zaskia R (X TB)\n  • Indra Aprianto (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Rina Arzeti (X TB)\n  • Tiara Indriyani Sabela (X TB)\n  • Vanisaul Khoiroh (X TB)\n  • Viko Afriyan Arbi (X TSM)\n  • Adi Abdurachman (XI TSM)\n  • Ahmad (XI TSM)\n  • Aditya Rusliano Akbar (X TSM)\n  • Arbai Soliqin (XI TSM)\n  • Arista Danu Ansa (XI TSM)\n  • Bagus Hermawan (XI TSM)\n  • Deni (XI TSM)\n  • Denis Kurniawan (XI TSM)\n  • Dhani Alan Maulana (XI TSM)\n  • Dhani Muhamad Reza (XI TSM)\n  • Dinda Amalia (XI TB)\n  • Dwi Sampurna Jaya (XI TSM)\n  • Fariz Kurniawan (XI TSM)\n  • Haris Vino Agusthaan (XI TSM)\n\n──────────────────────────────\nTotal: *51 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-01 13:30:14', '2026-01-01 13:30:18'),
(20, '120363421672356407@g.us', '📋 *LAPORAN KETIDAKHADIRAN*\n📅 Tanggal: 01/01/2026\n──────────────────────────────\n\n*❌ Alpha* (51 siswa)\n  • Ayu Vera Velinia (XII TSM)\n  • Keyla Biyan Ramadhani (XI TB)\n  • M. Maulana Eri Fernando (XI TSM)\n  • Muhamad Deni Setiawan (XI TSM)\n  • Muhammad Haris Ashrori (XI TSM)\n  • Muhammad Irsyadul A\'la (XI TSM)\n  • Musa (XI TSM)\n  • Panji Setia Wardana (XI TSM)\n  • Ririn Mardiana (XI TB)\n  • Aji Irawan (XII TSM)\n  • Akhmad Afandi (XII TSM)\n  • Andre Marcel (XII TSM)\n  • Arphanca Kun Nugroho (XII TSM)\n  • Junia Sari (XI TB)\n  • Davit Mubaidilah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Firnando (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Indah Laras Putri (XII TB)\n  • Jepri Maulana (XII TSM)\n  • Meliana Dwi Irianti (XII TB)\n  • Novita Dwi Wijayanti (XII TB)\n  • Selviana (XII TB)\n  • Zulfi Aulia (XII TB)\n  • Adnan Nur Rohim (XII TSM)\n  • Ahmad Agus Salim (XI TSM)\n  • Ahmad Muzaki (X TSM)\n  • Arta Kusuma (X TSM)\n  • Brenda Zaskia R (X TB)\n  • Indra Aprianto (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Rina Arzeti (X TB)\n  • Tiara Indriyani Sabela (X TB)\n  • Vanisaul Khoiroh (X TB)\n  • Viko Afriyan Arbi (X TSM)\n  • Adi Abdurachman (XI TSM)\n  • Ahmad (XI TSM)\n  • Aditya Rusliano Akbar (X TSM)\n  • Arbai Soliqin (XI TSM)\n  • Arista Danu Ansa (XI TSM)\n  • Bagus Hermawan (XI TSM)\n  • Deni (XI TSM)\n  • Denis Kurniawan (XI TSM)\n  • Dhani Alan Maulana (XI TSM)\n  • Dhani Muhamad Reza (XI TSM)\n  • Dinda Amalia (XI TB)\n  • Dwi Sampurna Jaya (XI TSM)\n  • Fariz Kurniawan (XI TSM)\n  • Haris Vino Agusthaan (XI TSM)\n\n──────────────────────────────\nTotal: *51 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-01 13:30:14', '2026-01-01 13:30:19'),
(21, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 02/01/2026\n---------------------------\n✅ Siswa Masuk: 0\n❌ Siswa Tidak Masuk: 51\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Adi Abdurachman (Alpha)\n- Aditya Rusliano Akbar (Alpha)\n- Adnan Nur Rohim (Alpha)\n- Ahmad (Alpha)\n- Ahmad Agus Salim (Alpha)\n- Ahmad Muzaki (Alpha)\n- Aji Irawan (Alpha)\n- Akhmad Afandi (Alpha)\n- Andre Marcel (Alpha)\n- Arbai Soliqin (Alpha)\n- Arista Danu Ansa (Alpha)\n- Arphanca Kun Nugroho (Alpha)\n- Arta Kusuma (Alpha)\n- Ayu Vera Velinia (Alpha)\n- Bagus Hermawan (Alpha)\n- Brenda Zaskia R (Alpha)\n- Davit Mubaidilah (Alpha)\n- Deni (Alpha)\n- Denis Kurniawan (Alpha)\n- Dhani Alan Maulana (Alpha)\n- Dhani Muhamad Reza (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Dinda Amalia (Alpha)\n- Dwi Sampurna Jaya (Alpha)\n- Fadli Ardiansyah (Alpha)\n- Fariz Kurniawan (Alpha)\n- Firnando (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Haris Vino Agusthaan (Alpha)\n- Indah Laras Putri (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Junia Sari (Alpha)\n- Keyla Biyan Ramadhani (Alpha)\n- M. Maulana Eri Fernando (Alpha)\n- Meliana Dwi Irianti (Alpha)\n- Muhamad Deni Setiawan (Alpha)\n- Muhammad Haris Ashrori (Alpha)\n- Muhammad Irsyadul A\'la (Alpha)\n- Musa (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Novita Dwi Wijayanti (Alpha)\n- Panji Setia Wardana (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Rina Arzeti (Alpha)\n- Ririn Mardiana (Alpha)\n- Selviana (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n- Vanisaul Khoiroh (Alpha)\n- Viko Afriyan Arbi (Alpha)\n- Zulfi Aulia (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-02 08:15:05', '2026-01-02 08:16:05'),
(22, '120363421672356407@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 02/01/2026\n──────────────────────────────\n\n*❌ Alpha* (51 siswa)\n  • Arphanca Kun Nugroho (XII TSM)\n  • Keyla Biyan Ramadhani (XI TB)\n  • M. Maulana Eri Fernando (XI TSM)\n  • Muhamad Deni Setiawan (XI TSM)\n  • Muhammad Haris Ashrori (XI TSM)\n  • Muhammad Irsyadul A\'la (XI TSM)\n  • Musa (XI TSM)\n  • Panji Setia Wardana (XI TSM)\n  • Ririn Mardiana (XI TB)\n  • Adnan Nur Rohim (XII TSM)\n  • Aji Irawan (XII TSM)\n  • Akhmad Afandi (XII TSM)\n  • Andre Marcel (XII TSM)\n  • Junia Sari (XI TB)\n  • Ayu Vera Velinia (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Firnando (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Indah Laras Putri (XII TB)\n  • Jepri Maulana (XII TSM)\n  • Meliana Dwi Irianti (XII TB)\n  • Novita Dwi Wijayanti (XII TB)\n  • Selviana (XII TB)\n  • Zulfi Aulia (XII TB)\n  • Ahmad Agus Salim (XI TSM)\n  • Ahmad Muzaki (X TSM)\n  • Arta Kusuma (X TSM)\n  • Brenda Zaskia R (X TB)\n  • Indra Aprianto (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Rina Arzeti (X TB)\n  • Tiara Indriyani Sabela (X TB)\n  • Vanisaul Khoiroh (X TB)\n  • Viko Afriyan Arbi (X TSM)\n  • Adi Abdurachman (XI TSM)\n  • Ahmad (XI TSM)\n  • Aditya Rusliano Akbar (X TSM)\n  • Arbai Soliqin (XI TSM)\n  • Arista Danu Ansa (XI TSM)\n  • Bagus Hermawan (XI TSM)\n  • Deni (XI TSM)\n  • Denis Kurniawan (XI TSM)\n  • Dhani Alan Maulana (XI TSM)\n  • Dhani Muhamad Reza (XI TSM)\n  • Dinda Amalia (XI TB)\n  • Dwi Sampurna Jaya (XI TSM)\n  • Fariz Kurniawan (XI TSM)\n  • Haris Vino Agusthaan (XI TSM)\n\n──────────────────────────────\nTotal: *51 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-02 13:30:13', '2026-01-02 13:30:18'),
(23, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 03/01/2026\n---------------------------\n✅ Siswa Masuk: 0\n❌ Siswa Tidak Masuk: 28\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Adnan Nur Rohim (Alpha)\n- Ahmad Muzaki (Alpha)\n- Aji Irawan (Alpha)\n- Akhmad Afandi (Alpha)\n- Andre Marcel (Alpha)\n- Arphanca Kun Nugroho (Alpha)\n- Arta Kusuma (Alpha)\n- Ayu Vera Velinia (Alpha)\n- Brenda Zaskia R (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Fadli Ardiansyah (Alpha)\n- Firnando (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indah Laras Putri (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Meliana Dwi Irianti (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Novita Dwi Wijayanti (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Rina Arzeti (Alpha)\n- Selviana (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n- Vanisaul Khoiroh (Alpha)\n- Viko Afriyan Arbi (Alpha)\n- Zulfi Aulia (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-03 08:15:05', '2026-01-03 08:16:06'),
(24, '6281273592988@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Adi Abdurachman* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `7DC88506`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 08:39:45', '2026-01-03 08:40:05'),
(25, '6285609555390@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Meliana Dwi Irianti* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `BDA97506`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 08:50:33', '2026-01-03 08:51:05'),
(26, '6285658346895@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *M. Maulana Eri Fernando* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `7D1C7606`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 12:55:49', '2026-01-03 12:56:06'),
(27, '6282294454635@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Fadli Ardiansyah* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `BDD67906`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 12:58:28', '2026-01-03 12:59:05'),
(28, '6282280108536@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Firnando* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `FDEC8306`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:09:14', '2026-01-03 13:10:05'),
(29, '6287768963763@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Indah Laras Putri* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `BD5B8906`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:09:32', '2026-01-03 13:10:07'),
(30, '6285835445980@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Ahmad Agus Salim* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `2DD47406`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:17:17', '2026-01-03 13:18:05'),
(31, '6285809285042@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Tiara Indriyani Sabela* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `AD598806`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:17:44', '2026-01-03 13:18:06'),
(32, '6287760741578@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Akhmad Afandi* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ED439906`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:18:11', '2026-01-03 13:19:05'),
(33, '6285185130325@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Dwi Sampurna Jaya* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `FD0B9106`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:19:16', '2026-01-03 13:20:05'),
(34, '6281991814008@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Novita Dwi Wijayanti* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `7DE38706`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:19:58', '2026-01-03 13:20:06'),
(35, '6283847353783@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Ayu Vera Velinia* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `9D947C06`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:20:36', '2026-01-03 13:21:05'),
(36, '6285767052478@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Panji Setia Wardana* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `CD8A8406`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:20:48', '2026-01-03 13:21:06'),
(37, '6285702877736@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Dhani Muhamad Reza* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `9DD47406`\n📅 Tanggal: Saturday, 03 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-03 13:21:44', '2026-01-03 13:22:05'),
(38, '120363421672356407@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 03/01/2026\n──────────────────────────────\n\n*❌ Alpha* (28 siswa)\n  • Andre Marcel (XII TSM)\n  • Zulfi Aulia (XII TB)\n  • Selviana (XII TB)\n  • Novita Dwi Wijayanti (XII TB)\n  • Meliana Dwi Irianti (XII TB)\n  • Jepri Maulana (XII TSM)\n  • Indah Laras Putri (XII TB)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Firnando (XII TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Ayu Vera Velinia (XII TB)\n  • Arphanca Kun Nugroho (XII TSM)\n  • Aditya Rusliano Akbar (X TSM)\n  • Akhmad Afandi (XII TSM)\n  • Aji Irawan (XII TSM)\n  • Adnan Nur Rohim (XII TSM)\n  • Viko Afriyan Arbi (X TSM)\n  • Vanisaul Khoiroh (X TB)\n  • Tiara Indriyani Sabela (X TB)\n  • Rina Arzeti (X TB)\n  • Rilly Meilana Wijaya (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Brenda Zaskia R (X TB)\n  • Arta Kusuma (X TSM)\n  • Ahmad Muzaki (X TSM)\n\n──────────────────────────────\nTotal: *28 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-03 13:30:10', '2026-01-03 13:30:14'),
(39, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 04/01/2026\n---------------------------\n✅ Siswa Masuk: 0\n❌ Siswa Tidak Masuk: 28\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Adnan Nur Rohim (Alpha)\n- Ahmad Muzaki (Alpha)\n- Aji Irawan (Alpha)\n- Akhmad Afandi (Alpha)\n- Andre Marcel (Alpha)\n- Arphanca Kun Nugroho (Alpha)\n- Arta Kusuma (Alpha)\n- Ayu Vera Velinia (Alpha)\n- Brenda Zaskia R (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Fadli Ardiansyah (Alpha)\n- Firnando (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indah Laras Putri (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Meliana Dwi Irianti (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Novita Dwi Wijayanti (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Rina Arzeti (Alpha)\n- Selviana (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n- Vanisaul Khoiroh (Alpha)\n- Viko Afriyan Arbi (Alpha)\n- Zulfi Aulia (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-04 08:15:05', '2026-01-04 08:16:04'),
(40, '6281524824563@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Ahmad Muzaki* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `3D517106`\n📅 Tanggal: Sunday, 04 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-04 12:39:31', '2026-01-04 12:40:05'),
(41, '6285809812949@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Dhani Alan Maulana* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `0DBE7C06`\n📅 Tanggal: Sunday, 04 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-04 12:39:48', '2026-01-04 12:40:06'),
(42, '6285805110953@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Rina Arzeti* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `BDD48D06`\n📅 Tanggal: Sunday, 04 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-04 12:41:04', '2026-01-04 12:42:05'),
(43, '6285839358970@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Selviana* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `4D3C7E06`\n📅 Tanggal: Sunday, 04 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-04 12:41:26', '2026-01-04 12:42:06'),
(44, '6282381393815@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Bagus Hermawan* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `9D1A9406`\n📅 Tanggal: Monday, 05 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-05 07:53:15', '2026-01-05 07:54:06'),
(45, '6287867034856@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Junia Sari* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `3DDE8E06`\n📅 Tanggal: Monday, 05 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-05 07:53:29', '2026-01-05 07:54:09'),
(46, '6281271360260@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Hanif Dwi Cahyono* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `4D9D8506`\n📅 Tanggal: Monday, 05 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-05 07:53:47', '2026-01-05 07:54:12'),
(47, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Vanisaul Khoiroh*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:57\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:57:41', '2026-01-05 07:58:04'),
(48, '6287760741578@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Akhmad Afandi*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:59\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:59:09', '2026-01-05 08:00:05'),
(49, '6281991814008@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Novita Dwi Wijayanti*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:59\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:59:20', '2026-01-05 08:00:09'),
(50, '6287768963763@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Indah Laras Putri*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:59\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:59:26', '2026-01-05 08:00:12'),
(51, '6282280108536@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Firnando*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:59\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:59:30', '2026-01-05 08:00:15'),
(52, '6282294454635@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Fadli Ardiansyah*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:59\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:59:32', '2026-01-05 08:00:18'),
(53, '6283847353783@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Ayu Vera Velinia*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 07:59\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 07:59:51', '2026-01-05 08:00:22'),
(54, '6285839358970@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Selviana*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 08:01\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 08:01:38', '2026-01-05 08:02:05'),
(55, '6285609555390@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Meliana Dwi Irianti*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 08:01\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 08:01:44', '2026-01-05 08:02:08'),
(56, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Ahmad Muzaki*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 08:03\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 08:03:58', '2026-01-05 08:04:05'),
(57, '6285805110953@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Rina Arzeti*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 08:04\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 08:04:01', '2026-01-05 08:04:08'),
(58, '120363421570491131@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 05/01/2026\n---------------------------\n✅ Siswa Masuk: 17\n❌ Siswa Tidak Masuk: 11\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Aji Irawan (Alpha)\n- Arta Kusuma (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-05 09:00:05', '2026-01-05 09:01:14'),
(59, '120363161431567388@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 05/01/2026\n---------------------------\n✅ Siswa Masuk: 17\n❌ Siswa Tidak Masuk: 11\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Aji Irawan (Alpha)\n- Arta Kusuma (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-05 09:00:06', '2026-01-05 09:01:23'),
(60, '6281369368296-1504440561@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 05/01/2026\n---------------------------\n✅ Siswa Masuk: 17\n❌ Siswa Tidak Masuk: 11\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Aditya Rusliano Akbar (Alpha)\n- Aji Irawan (Alpha)\n- Arta Kusuma (Alpha)\n- Davit Mubaidilah (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-05 09:00:06', '2026-01-05 09:01:29'),
(61, '6285809285042@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Tiara Indriyani Sabela*,\n\n📅 Tanggal: 05/01/2026\n� Jam Masuk: 09:04\n📊 Status: Terlambat\n📝 Keterangan: Telat 2 jam 4 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-05 09:04:04', '2026-01-05 09:05:05'),
(62, '6282280108536@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Firnando*,\n\n📍 Jam Masuk: 07:59\n� Jam Pulang: 09:37\n⏱️ Durasi: -2 jam -39 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:37:46', '2026-01-05 09:38:06'),
(63, '6282294454635@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Fadli Ardiansyah*,\n\n📍 Jam Masuk: 07:59\n� Jam Pulang: 09:37\n⏱️ Durasi: -2 jam -39 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:37:56', '2026-01-05 09:38:10'),
(64, '6287760741578@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Akhmad Afandi*,\n\n📍 Jam Masuk: 07:59\n� Jam Pulang: 09:37\n⏱️ Durasi: -2 jam -39 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:37:59', '2026-01-05 09:38:14'),
(65, '6281524824563@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Ahmad Muzaki*,\n\n📍 Jam Masuk: 08:03\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -35 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:10', '2026-01-05 09:39:05'),
(66, '6285809285042@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Tiara Indriyani Sabela*,\n\n📍 Jam Masuk: 09:04\n� Jam Pulang: 09:38\n⏱️ Durasi: -1 jam -35 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:25', '2026-01-05 09:39:08'),
(67, '6285805110953@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Rina Arzeti*,\n\n📍 Jam Masuk: 08:04\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -35 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:31', '2026-01-05 09:39:12'),
(68, '6285839358970@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Selviana*,\n\n📍 Jam Masuk: 08:01\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -38 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:39', '2026-01-05 09:39:16'),
(69, '6281991814008@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Novita Dwi Wijayanti*,\n\n📍 Jam Masuk: 07:59\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -40 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:43', '2026-01-05 09:39:19'),
(70, '6285609555390@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Meliana Dwi Irianti*,\n\n📍 Jam Masuk: 08:01\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -38 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:46', '2026-01-05 09:39:23'),
(71, '6283847353783@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Ayu Vera Velinia*,\n\n📍 Jam Masuk: 07:59\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -39 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:50', '2026-01-05 09:39:26'),
(72, '6287768963763@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Indah Laras Putri*,\n\n📍 Jam Masuk: 07:59\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -40 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:38:56', '2026-01-05 09:39:30'),
(73, '6281524824563@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Vanisaul Khoiroh*,\n\n📍 Jam Masuk: 07:57\n� Jam Pulang: 09:38\n⏱️ Durasi: -2 jam -42 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-05 09:39:00', '2026-01-05 09:39:33'),
(74, '120363421570491131@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 05/01/2026\n──────────────────────────────\n\n*❌ Alpha* (8 siswa)\n  • Aditya Rusliano Akbar (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Aji Irawan (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Jepri Maulana (XII TSM)\n\n──────────────────────────────\nTotal: *8 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-05 13:15:05', '2026-01-05 13:15:08'),
(75, '120363161431567388@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 05/01/2026\n──────────────────────────────\n\n*❌ Alpha* (8 siswa)\n  • Aditya Rusliano Akbar (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Aji Irawan (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Jepri Maulana (XII TSM)\n\n──────────────────────────────\nTotal: *8 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-05 13:15:05', '2026-01-05 13:15:12'),
(76, '6281369368296-1504440561@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 05/01/2026\n──────────────────────────────\n\n*❌ Alpha* (8 siswa)\n  • Aditya Rusliano Akbar (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Aji Irawan (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Jepri Maulana (XII TSM)\n\n──────────────────────────────\nTotal: *8 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-05 13:15:06', '2026-01-05 13:15:15'),
(77, '6285805110953@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Rina Arzeti*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:31\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 31 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:31:13', '2026-01-06 07:32:05'),
(78, '6283847353783@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Ayu Vera Velinia*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:33\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:33:16', '2026-01-06 07:34:05'),
(79, '6282280108536@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Firnando*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:33\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:33:30', '2026-01-06 07:34:08'),
(80, '6287760741578@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Akhmad Afandi*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:33\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:33:36', '2026-01-06 07:34:11'),
(81, '6285960139964@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Adnan Nur Rohim*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:33\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:33:41', '2026-01-06 07:34:14'),
(82, '6287768963763@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Indah Laras Putri*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:33\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:33:50', '2026-01-06 07:34:17'),
(83, '6285609555390@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Meliana Dwi Irianti*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:35\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 5 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:35:25', '2026-01-06 07:36:05'),
(84, '6285839358970@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Selviana*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:38\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 8 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:38:58', '2026-01-06 07:39:05'),
(85, '6285809285042@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Tiara Indriyani Sabela*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 07:43\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 13 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 07:43:42', '2026-01-06 07:44:05'),
(86, '6281271360260@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Hanif Dwi Cahyono*,\n\n📅 Tanggal: 06/01/2026\n� Jam Masuk: 08:06\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 36 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-06 08:06:41', '2026-01-06 08:07:05'),
(87, '6285692153273@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Aji Irawan* 👋,\n\nKartu/Perangkat *Kartu Siswa* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ADC67506`\n📅 Tanggal: Tuesday, 06 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-06 08:24:09', '2026-01-06 08:25:05'),
(88, '6281524824563@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *Ahmad Daqiqi Syahrulloh, S.H.* 👋,\n\nKartu/Perangkat *Kartu Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `DDE26E06`\n📅 Tanggal: Tuesday, 06 January 2026\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'sent', NULL, 0, '2026-01-06 08:32:12', '2026-01-06 08:33:05'),
(89, '120363421570491131@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 06/01/2026\n──────────────────────────────\n✅ Siswa Masuk: 22\n❌ Siswa Tidak Masuk: 6\n──────────────────────────────\n\n*❌ Alpha* (5 siswa)\n  • Arta Kusuma (X TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Indra Aprianto (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Novita Dwi Wijayanti (XII TB)\n\n*🤒 Sakit* (1 siswa)\n  • Fadli Ardiansyah (XII TSM)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-06 09:00:05', '2026-01-06 09:01:05'),
(90, '120363161431567388@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 06/01/2026\n──────────────────────────────\n✅ Siswa Masuk: 22\n❌ Siswa Tidak Masuk: 6\n──────────────────────────────\n\n*❌ Alpha* (5 siswa)\n  • Arta Kusuma (X TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Indra Aprianto (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Novita Dwi Wijayanti (XII TB)\n\n*🤒 Sakit* (1 siswa)\n  • Fadli Ardiansyah (XII TSM)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-06 09:00:05', '2026-01-06 09:01:08'),
(91, '6281369368296-1504440561@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 06/01/2026\n──────────────────────────────\n✅ Siswa Masuk: 22\n❌ Siswa Tidak Masuk: 6\n──────────────────────────────\n\n*❌ Alpha* (5 siswa)\n  • Arta Kusuma (X TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Indra Aprianto (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Novita Dwi Wijayanti (XII TB)\n\n*🤒 Sakit* (1 siswa)\n  • Fadli Ardiansyah (XII TSM)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-06 09:00:06', '2026-01-06 09:01:11'),
(92, '6285692153273@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Aji Irawan*,\n\n📍 Jam Masuk: 08:19\n� Jam Pulang: 10:42\n⏱️ Durasi: -3 jam -23 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:42:22', '2026-01-06 10:43:06'),
(93, '6285809285042@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Tiara Indriyani Sabela*,\n\n📍 Jam Masuk: 07:43\n� Jam Pulang: 10:42\n⏱️ Durasi: -3 jam -59 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:42:33', '2026-01-06 10:43:09'),
(94, '6281271360260@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Hanif Dwi Cahyono*,\n\n📍 Jam Masuk: 08:06\n� Jam Pulang: 10:42\n⏱️ Durasi: -3 jam -37 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:42:55', '2026-01-06 10:43:13'),
(95, '6285805110953@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Rina Arzeti*,\n\n📍 Jam Masuk: 07:31\n� Jam Pulang: 10:43\n⏱️ Durasi: -4 jam -12 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:43:11', '2026-01-06 10:44:05'),
(96, '6285839358970@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Selviana*,\n\n📍 Jam Masuk: 07:38\n� Jam Pulang: 10:43\n⏱️ Durasi: -4 jam -5 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:43:22', '2026-01-06 10:44:08'),
(97, '6285609555390@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Meliana Dwi Irianti*,\n\n📍 Jam Masuk: 07:35\n� Jam Pulang: 10:43\n⏱️ Durasi: -4 jam -9 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:43:45', '2026-01-06 10:44:12'),
(98, '6283847353783@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Ayu Vera Velinia*,\n\n📍 Jam Masuk: 07:33\n� Jam Pulang: 10:43\n⏱️ Durasi: -4 jam -11 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:43:48', '2026-01-06 10:44:15'),
(99, '6287768963763@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Indah Laras Putri*,\n\n📍 Jam Masuk: 07:33\n� Jam Pulang: 10:43\n⏱️ Durasi: -4 jam -11 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:43:53', '2026-01-06 10:44:18'),
(100, '6282280108536@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Firnando*,\n\n📍 Jam Masuk: 07:33\n� Jam Pulang: 10:44\n⏱️ Durasi: -4 jam -11 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:44:25', '2026-01-06 10:45:05'),
(101, '6287760741578@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Akhmad Afandi*,\n\n📍 Jam Masuk: 07:33\n� Jam Pulang: 10:44\n⏱️ Durasi: -4 jam -11 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:44:28', '2026-01-06 10:45:08'),
(102, '6285960139964@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Adnan Nur Rohim*,\n\n📍 Jam Masuk: 07:33\n� Jam Pulang: 10:44\n⏱️ Durasi: -4 jam -11 menit\n� Diizinkan oleh: Ahmad Daqiqi Syahrulloh, S.H.\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-06 10:44:33', '2026-01-06 10:45:12'),
(103, '120363421570491131@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 06/01/2026\n──────────────────────────────\n\n*❌ Alpha* (5 siswa)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Arta Kusuma (X TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Novita Dwi Wijayanti (XII TB)\n\n*🤒 Sakit* (1 siswa)\n  • Fadli Ardiansyah (XII TSM)\n\n──────────────────────────────\nTotal: *6 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-06 13:15:05', '2026-01-06 13:15:08'),
(104, '120363161431567388@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 06/01/2026\n──────────────────────────────\n\n*❌ Alpha* (5 siswa)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Arta Kusuma (X TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Novita Dwi Wijayanti (XII TB)\n\n*🤒 Sakit* (1 siswa)\n  • Fadli Ardiansyah (XII TSM)\n\n──────────────────────────────\nTotal: *6 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-06 13:15:05', '2026-01-06 13:15:13'),
(105, '6281369368296-1504440561@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 06/01/2026\n──────────────────────────────\n\n*❌ Alpha* (5 siswa)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Arta Kusuma (X TSM)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Novita Dwi Wijayanti (XII TB)\n\n*🤒 Sakit* (1 siswa)\n  • Fadli Ardiansyah (XII TSM)\n\n──────────────────────────────\nTotal: *6 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-06 13:15:05', '2026-01-06 13:15:16'),
(106, '6283847353783@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Ayu Vera Velinia*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:25\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:25:24', '2026-01-07 07:26:04'),
(107, '6282280108536@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Firnando*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:29\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:29:03', '2026-01-07 07:29:05'),
(108, '6287760741578@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Akhmad Afandi*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:29\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:29:20', '2026-01-07 07:30:05'),
(109, '6285609555390@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Meliana Dwi Irianti*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:33\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:33:20', '2026-01-07 07:34:05'),
(110, '6287768963763@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Indah Laras Putri*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:34\n📊 Status: Tepat Waktu\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:34:15', '2026-01-07 07:35:05');
INSERT INTO `message_queues` (`id`, `phone_number`, `message`, `status`, `last_error`, `attempts`, `created_at`, `updated_at`) VALUES
(111, '6285960139964@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Adnan Nur Rohim*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:39\n📊 Status: Terlambat\n📝 Keterangan: Telat 4 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:39:40', '2026-01-07 07:40:05'),
(112, '6281991814008@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Novita Dwi Wijayanti*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:43\n📊 Status: Terlambat\n📝 Keterangan: Telat 8 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:43:46', '2026-01-07 07:44:05'),
(113, '6285839358970@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Selviana*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:44\n📊 Status: Terlambat\n📝 Keterangan: Telat 9 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:44:03', '2026-01-07 07:44:08'),
(114, '6285789525079@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Arphanca Kun Nugroho*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:44\n📊 Status: Terlambat\n📝 Keterangan: Telat 9 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:44:56', '2026-01-07 07:45:05'),
(115, '6285692153273@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Aji Irawan*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:45\n📊 Status: Terlambat\n📝 Keterangan: Telat 10 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:45:54', '2026-01-07 07:46:05'),
(116, '6285809285042@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Tiara Indriyani Sabela*,\n\n📅 Tanggal: 07/01/2026\n� Jam Masuk: 07:50\n📊 Status: Terlambat\n📝 Keterangan: Telat 15 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'sent', NULL, 0, '2026-01-07 07:50:21', '2026-01-07 07:51:04'),
(117, '6285809366808@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Aditya Rusliano Akbar\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:32'),
(118, '6285384962358@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Rilly Meilana Wijaya\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:05'),
(119, '6285805110953@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Rina Arzeti\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:08'),
(120, '6285809285042@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Tiara Indriyani Sabela\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:11'),
(121, '6285960139964@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Adnan Nur Rohim\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:14'),
(122, '6285692153273@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Aji Irawan\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:17'),
(123, '6287760741578@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Akhmad Afandi\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:20'),
(124, '6285761230905@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Andre Marcel\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:17:23'),
(125, '6285789525079@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Arphanca Kun Nugroho\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:05'),
(126, '6283847353783@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Ayu Vera Velinia\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:30'),
(127, '6282294454635@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Fadli Ardiansyah\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:26'),
(128, '6282280108536@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Firnando\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:23'),
(129, '6281271360260@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Hanif Dwi Cahyono\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:20'),
(130, '6287768963763@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Indah Laras Putri\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:17'),
(131, '6285609555390@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Meliana Dwi Irianti\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:14'),
(132, '6281991814008@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Novita Dwi Wijayanti\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:11'),
(133, '6285839358970@s.whatsapp.net', '📢 *PENGUMUMAN SEKOLAH*\nKepada: Selviana\n\nHari ini terakhir untuk pembayaran kartu ya!, jika tidak kartumu akan non-aktif dan kamu harus absen manual ke guru piket.\n\n_Dikirim oleh Admin_', 'sent', NULL, 0, '2026-01-07 08:15:57', '2026-01-07 08:16:08'),
(134, '120363421570491131@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 07/01/2026\n──────────────────────────────\n✅ Siswa Masuk: 17\n❌ Siswa Tidak Masuk: 11\n──────────────────────────────\n\n*❌ Alpha* (9 siswa)\n  • Arta Kusuma (X TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Indra Aprianto (X TSM)\n  • Jepri Maulana (XII TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Rina Arzeti (X TB)\n  • Viko Afriyan Arbi (X TSM)\n\n*🤒 Sakit* (2 siswa)\n  • Fadli Ardiansyah (XII TSM)\n  • Zulfi Aulia (XII TB)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-07 09:00:05', '2026-01-07 09:01:06'),
(135, '120363161431567388@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 07/01/2026\n──────────────────────────────\n✅ Siswa Masuk: 17\n❌ Siswa Tidak Masuk: 11\n──────────────────────────────\n\n*❌ Alpha* (9 siswa)\n  • Arta Kusuma (X TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Indra Aprianto (X TSM)\n  • Jepri Maulana (XII TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Rina Arzeti (X TB)\n  • Viko Afriyan Arbi (X TSM)\n\n*🤒 Sakit* (2 siswa)\n  • Fadli Ardiansyah (XII TSM)\n  • Zulfi Aulia (XII TB)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-07 09:00:06', '2026-01-07 09:01:09'),
(136, '6281369368296-1504440561@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 07/01/2026\n──────────────────────────────\n✅ Siswa Masuk: 17\n❌ Siswa Tidak Masuk: 11\n──────────────────────────────\n\n*❌ Alpha* (9 siswa)\n  • Arta Kusuma (X TSM)\n  • Davit Mubaidilah (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Indra Aprianto (X TSM)\n  • Jepri Maulana (XII TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Rilly Meilana Wijaya (X TSM)\n  • Rina Arzeti (X TB)\n  • Viko Afriyan Arbi (X TSM)\n\n*🤒 Sakit* (2 siswa)\n  • Fadli Ardiansyah (XII TSM)\n  • Zulfi Aulia (XII TB)\n\n_Generated by System_', 'sent', NULL, 0, '2026-01-07 09:00:06', '2026-01-07 09:01:13'),
(137, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nAnak Anda, *Andi Wijaya*, telah absen masuk.\n\n📅 Tanggal: 07/01/2026\n🕐 Jam Masuk: 09:48\n📊 Status: Terlambat\n📝 Keterangan: Telat 2 jam 13 menit\n\n_Notifikasi otomatis dari sistem absensi sekolah._', 'sent', NULL, 0, '2026-01-07 09:48:24', '2026-01-07 09:49:05'),
(138, '6282280108536@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Firnando*,\n\n📍 Jam Masuk: 07:29\n🕐 Jam Pulang: 12:18\n⏱️ Durasi Belajar: 4 jam 49 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:18:51', '2026-01-07 12:19:05'),
(139, '6285789525079@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Arphanca Kun Nugroho*,\n\n📍 Jam Masuk: 07:44\n🕐 Jam Pulang: 12:18\n⏱️ Durasi Belajar: 4 jam 33 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:18:55', '2026-01-07 12:19:08'),
(140, '6285809366808@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Aditya Rusliano Akbar*,\n\n📍 Jam Masuk: 07:45\n🕐 Jam Pulang: 12:18\n⏱️ Durasi Belajar: 4 jam 33 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:18:57', '2026-01-07 12:19:11'),
(141, '6287760741578@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Akhmad Afandi*,\n\n📍 Jam Masuk: 07:29\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 49 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:01', '2026-01-07 12:19:15'),
(142, '6283847353783@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Ayu Vera Velinia*,\n\n📍 Jam Masuk: 07:25\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 53 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:04', '2026-01-07 12:20:05'),
(143, '6285839358970@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Selviana*,\n\n📍 Jam Masuk: 07:44\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 35 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:06', '2026-01-07 12:20:08'),
(144, '6281991814008@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Novita Dwi Wijayanti*,\n\n📍 Jam Masuk: 07:43\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 35 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:08', '2026-01-07 12:20:11'),
(145, '6287768963763@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Indah Laras Putri*,\n\n📍 Jam Masuk: 07:34\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 44 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:10', '2026-01-07 12:20:14'),
(146, '6285609555390@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Meliana Dwi Irianti*,\n\n📍 Jam Masuk: 07:33\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 45 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:13', '2026-01-07 12:20:18'),
(147, '6285383827211@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, Anak Anda, *Ahmad Muzaki*, telah absen pulang.\n\n📍 Jam Masuk: 07:50\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 28 menit\n_Notifikasi otomatis dari sistem absensi sekolah._', 'sent', NULL, 0, '2026-01-07 12:19:16', '2026-01-07 12:20:22'),
(148, '6285761230905@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Andre Marcel*,\n\n📍 Jam Masuk: 07:45\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 34 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:21', '2026-01-07 12:20:25'),
(149, '6285692153273@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Aji Irawan*,\n\n📍 Jam Masuk: 07:45\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 33 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:19:27', '2026-01-07 12:20:28'),
(150, '6285368086407@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, Anak Anda, *Vanisaul Khoiroh*, telah absen pulang.\n\n📍 Jam Masuk: 07:59\n🕐 Jam Pulang: 12:19\n⏱️ Durasi Belajar: 4 jam 20 menit\n_Notifikasi otomatis dari sistem absensi sekolah._', 'sent', NULL, 0, '2026-01-07 12:19:36', '2026-01-07 12:20:33'),
(151, '6285809285042@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Tiara Indriyani Sabela*,\n\n📍 Jam Masuk: 07:50\n🕐 Jam Pulang: 12:22\n⏱️ Durasi Belajar: 4 jam 31 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:22:15', '2026-01-07 12:23:05'),
(152, '62895322150672@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nAssalamualaikum, *Brenda Zaskia R*,\n\n📍 Jam Masuk: 07:50\n🕐 Jam Pulang: 12:22\n⏱️ Durasi Belajar: 4 jam 32 menit\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'sent', NULL, 0, '2026-01-07 12:22:17', '2026-01-07 12:23:08'),
(153, '120363421570491131@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 07/01/2026\n──────────────────────────────\n\n*❌ Alpha* (8 siswa)\n  • Viko Afriyan Arbi (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Arta Kusuma (X TSM)\n  • Rina Arzeti (X TB)\n  • Jepri Maulana (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n\n*🏃 Bolos (Tidak Absen Pulang)* (2 siswa)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Adnan Nur Rohim (XII TSM)\n\n*🤒 Sakit* (3 siswa)\n  • Rilly Meilana Wijaya (X TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Zulfi Aulia (XII TB)\n\n──────────────────────────────\nTotal: *13 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-07 13:15:06', '2026-01-07 13:15:09'),
(154, '120363161431567388@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 07/01/2026\n──────────────────────────────\n\n*❌ Alpha* (8 siswa)\n  • Viko Afriyan Arbi (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Arta Kusuma (X TSM)\n  • Rina Arzeti (X TB)\n  • Jepri Maulana (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n\n*🏃 Bolos (Tidak Absen Pulang)* (2 siswa)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Adnan Nur Rohim (XII TSM)\n\n*🤒 Sakit* (3 siswa)\n  • Rilly Meilana Wijaya (X TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Zulfi Aulia (XII TB)\n\n──────────────────────────────\nTotal: *13 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-07 13:15:06', '2026-01-07 13:15:12'),
(155, '6281369368296-1504440561@g.us', '📋 *LAPORAN FINAL KETIDAKHADIRAN*\n📅 Tanggal: 07/01/2026\n──────────────────────────────\n\n*❌ Alpha* (8 siswa)\n  • Viko Afriyan Arbi (X TSM)\n  • Nanda Dwi Andi Aritama (X TSM)\n  • Indra Aprianto (X TSM)\n  • Arta Kusuma (X TSM)\n  • Rina Arzeti (X TB)\n  • Jepri Maulana (XII TSM)\n  • Hanif Dwi Cahyono (XII TSM)\n  • Davit Mubaidilah (XII TSM)\n\n*🏃 Bolos (Tidak Absen Pulang)* (2 siswa)\n  • Dhika Hanafi Rantau (XII TSM)\n  • Adnan Nur Rohim (XII TSM)\n\n*🤒 Sakit* (3 siswa)\n  • Rilly Meilana Wijaya (X TSM)\n  • Fadli Ardiansyah (XII TSM)\n  • Zulfi Aulia (XII TB)\n\n──────────────────────────────\nTotal: *13 Siswa* tidak hadir\n\n_Laporan otomatis setelah proses harian_', 'sent', NULL, 0, '2026-01-07 13:15:06', '2026-01-07 13:15:16');

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
(1, '2025_12_26_110202_modify_jam_columns_in_attendance_table', 1),
(2, '2025_12_26_120115_add_user_id_to_siswa_table', 2),
(3, '2025_12_26_120834_add_updated_at_to_siswa_table', 3),
(4, '2025_12_26_121606_create_hari_liburs_table', 4),
(5, '2025_12_27_183000_add_fingerprint_to_guru', 5),
(6, '0001_01_01_000000_create_users_table', 6),
(7, '0001_01_01_000001_create_cache_table', 6),
(8, '0001_01_01_000002_create_jobs_table', 6),
(9, '2024_01_01_000000_create_base_schema_table', 6),
(10, '2025_12_25_133057_create_personal_access_tokens_table', 6),
(11, '2025_12_26_021225_add_uid_rfid_to_guru_table', 7),
(12, '2025_12_26_021731_add_timestamps_to_siswa_table', 7),
(13, '2025_12_26_022039_add_enroll_fields_to_guru_table', 7),
(14, '2025_12_27_000001_add_missing_rfid_columns', 7),
(15, '2025_12_27_000002_increase_phone_number_length', 7),
(16, '2025_12_28_072710_create_guru_fingerprints_table', 8),
(17, '2025_12_28_075324_make_guru_id_finger_nullable', 8),
(18, '2025_12_28_075753_create_teacher_schedule_tables', 9),
(19, '2024_01_02_000000_add_wali_kelas_id_to_kelas', 10),
(20, '2024_01_03_000000_add_is_active_attendance_to_kelas', 10),
(21, '2026_01_05_045000_add_wa_group_id_to_kelas_table', 11),
(22, '2026_01_05_050900_add_is_active_report_to_kelas_table', 12),
(23, '2026_01_06_add_status_to_teacher_checkout_sessions_table', 13),
(24, '2026_01_07_add_last_error_to_message_queues_table', 14),
(25, '2026_01_08_075835_add_is_auto_extended_to_attendance_table', 15),
(26, '2026_01_10_add_enable_checkout_attendance_setting', 15);

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
-- Struktur dari tabel `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `report_groups`
--

CREATE TABLE `report_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `jid` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `report_groups`
--

INSERT INTO `report_groups` (`id`, `name`, `jid`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'X', '120363421570491131@g.us', 1, '2026-01-05 08:58:52', '2026-01-05 08:58:52'),
(3, 'XII', '120363161431567388@g.us', 1, '2026-01-05 08:59:16', '2026-01-05 08:59:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `scan_history`
--

CREATE TABLE `scan_history` (
  `id` bigint(20) NOT NULL,
  `uid` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `scan_history`
--

INSERT INTO `scan_history` (`id`, `uid`, `created_at`) VALUES
(268, '0DBE7C06', '2025-12-29 07:30:49'),
(269, '0DBE7C06', '2025-12-29 07:30:52'),
(270, '0DBE7C06', '2025-12-29 07:30:57'),
(412, '0DBE7C06', '2025-12-29 13:21:10'),
(512, '0DBE7C06', '2025-12-30 10:38:36'),
(602, '0DBE7C06', '2026-01-04 12:39:47'),
(300, '0DEA9206', '2025-12-29 07:36:19'),
(301, '0DEA9206', '2025-12-29 07:36:22'),
(399, '0DEA9206', '2025-12-29 13:20:33'),
(456, '0DEA9206', '2025-12-30 06:26:07'),
(604, '0DEA9206', '2026-01-04 12:40:35'),
(539, '0DEE6806', '2026-01-03 13:59:36'),
(540, '0DEE6806', '2026-01-03 14:00:00'),
(541, '0DEE6806', '2026-01-03 20:14:51'),
(542, '0DEE6806', '2026-01-03 20:19:25'),
(543, '0DEE6806', '2026-01-03 20:27:24'),
(544, '0DEE6806', '2026-01-03 20:28:10'),
(547, '0DEE6806', '2026-01-03 20:28:40'),
(548, '0DEE6806', '2026-01-03 20:28:55'),
(549, '0DEE6806', '2026-01-03 20:29:00'),
(550, '0DEE6806', '2026-01-03 23:00:02'),
(551, '0DEE6806', '2026-01-03 23:00:08'),
(552, '0DEE6806', '2026-01-03 23:00:12'),
(553, '0DEE6806', '2026-01-03 23:49:59'),
(554, '0DEE6806', '2026-01-03 23:53:06'),
(555, '0DEE6806', '2026-01-03 23:53:11'),
(556, '0DEE6806', '2026-01-03 23:53:16'),
(557, '0DEE6806', '2026-01-03 23:53:46'),
(558, '0DEE6806', '2026-01-03 23:54:20'),
(559, '0DEE6806', '2026-01-03 23:54:56'),
(560, '0DEE6806', '2026-01-03 23:55:00'),
(561, '0DEE6806', '2026-01-03 23:55:05'),
(562, '0DEE6806', '2026-01-03 23:55:09'),
(563, '0DEE6806', '2026-01-03 23:57:05'),
(564, '0DEE6806', '2026-01-03 23:57:08'),
(565, '0DEE6806', '2026-01-03 23:57:11'),
(566, '0DEE6806', '2026-01-04 06:44:31'),
(567, '0DEE6806', '2026-01-04 06:53:05'),
(568, '0DEE6806', '2026-01-04 06:53:09'),
(569, '0DEE6806', '2026-01-04 06:54:05'),
(570, '0DEE6806', '2026-01-04 06:55:49'),
(571, '0DEE6806', '2026-01-04 06:55:51'),
(572, '0DEE6806', '2026-01-04 07:15:05'),
(573, '0DEE6806', '2026-01-04 07:15:08'),
(574, '0DEE6806', '2026-01-04 07:15:48'),
(575, '0DEE6806', '2026-01-04 07:33:04'),
(576, '0DEE6806', '2026-01-04 07:46:59'),
(577, '0DEE6806', '2026-01-04 07:47:02'),
(578, '0DEE6806', '2026-01-04 07:47:06'),
(579, '0DEE6806', '2026-01-04 07:47:10'),
(580, '0DEE6806', '2026-01-04 07:47:32'),
(581, '0DEE6806', '2026-01-04 07:47:36'),
(582, '0DEE6806', '2026-01-04 07:47:38'),
(583, '0DEE6806', '2026-01-04 07:52:39'),
(584, '0DEE6806', '2026-01-04 07:52:43'),
(585, '0DEE6806', '2026-01-04 07:52:47'),
(586, '0DEE6806', '2026-01-04 07:53:12'),
(587, '0DEE6806', '2026-01-04 07:53:45'),
(588, '0DEE6806', '2026-01-04 07:53:49'),
(589, '0DEE6806', '2026-01-04 07:53:53'),
(590, '0DEE6806', '2026-01-04 07:54:11'),
(591, '0DEE6806', '2026-01-04 07:54:15'),
(592, '0DEE6806', '2026-01-04 07:58:56'),
(593, '0DEE6806', '2026-01-04 07:58:59'),
(594, '0DEE6806', '2026-01-04 07:59:01'),
(595, '0DEE6806', '2026-01-04 07:59:48'),
(683, '0DEE6806', '2026-01-05 10:44:00'),
(684, '0DEE6806', '2026-01-05 10:44:04'),
(685, '0DEE6806', '2026-01-05 10:44:25'),
(244, '11223344', '2025-12-24 13:38:56'),
(4, '121EEC05', '2025-12-18 05:57:32'),
(15, '121EEC05', '2025-12-18 06:01:27'),
(19, '121EEC05', '2025-12-18 06:01:48'),
(20, '121EEC05', '2025-12-18 06:01:51'),
(21, '121EEC05', '2025-12-18 06:02:00'),
(22, '121EEC05', '2025-12-18 06:02:04'),
(24, '121EEC05', '2025-12-18 06:03:01'),
(25, '121EEC05', '2025-12-18 06:03:20'),
(26, '121EEC05', '2025-12-18 06:04:50'),
(29, '121EEC05', '2025-12-18 06:05:02'),
(30, '121EEC05', '2025-12-18 06:05:06'),
(31, '121EEC05', '2025-12-18 06:05:10'),
(34, '121EEC05', '2025-12-18 07:39:17'),
(35, '121EEC05', '2025-12-18 07:39:20'),
(37, '121EEC05', '2025-12-18 07:39:29'),
(51, '121EEC05', '2025-12-18 09:33:49'),
(52, '121EEC05', '2025-12-18 09:34:03'),
(55, '121EEC05', '2025-12-19 06:20:18'),
(56, '121EEC05', '2025-12-19 06:20:28'),
(58, '121EEC05', '2025-12-19 06:20:36'),
(59, '121EEC05', '2025-12-19 06:20:40'),
(61, '121EEC05', '2025-12-19 06:21:18'),
(73, '121EEC05', '2025-12-19 10:39:03'),
(74, '121EEC05', '2025-12-19 10:39:06'),
(75, '121EEC05', '2025-12-19 10:39:10'),
(76, '121EEC05', '2025-12-19 10:39:54'),
(77, '121EEC05', '2025-12-19 10:40:14'),
(83, '121EEC05', '2025-12-19 10:45:58'),
(104, '121EEC05', '2025-12-19 11:14:51'),
(105, '121EEC05', '2025-12-19 11:14:54'),
(106, '121EEC05', '2025-12-19 11:14:58'),
(107, '121EEC05', '2025-12-19 11:17:46'),
(108, '121EEC05', '2025-12-19 11:17:49'),
(111, '121EEC05', '2025-12-19 11:18:35'),
(113, '121EEC05', '2025-12-19 11:21:43'),
(114, '121EEC05', '2025-12-19 11:21:47'),
(115, '121EEC05', '2025-12-19 11:22:07'),
(116, '121EEC05', '2025-12-19 11:22:17'),
(117, '121EEC05', '2025-12-19 11:22:21'),
(118, '121EEC05', '2025-12-19 11:22:26'),
(119, '121EEC05', '2025-12-19 11:23:50'),
(120, '121EEC05', '2025-12-19 11:24:02'),
(121, '121EEC05', '2025-12-19 11:24:20'),
(122, '121EEC05', '2025-12-19 11:50:26'),
(125, '121EEC05', '2025-12-19 13:47:31'),
(128, '121EEC05', '2025-12-19 13:55:59'),
(137, '121EEC05', '2025-12-19 15:16:30'),
(138, '121EEC05', '2025-12-19 15:16:33'),
(139, '121EEC05', '2025-12-19 15:16:39'),
(141, '121EEC05', '2025-12-20 07:58:42'),
(143, '121EEC05', '2025-12-20 07:59:09'),
(156, '121EEC05', '2025-12-20 08:13:03'),
(158, '121EEC05', '2025-12-20 08:13:09'),
(161, '121EEC05', '2025-12-20 08:17:38'),
(163, '121EEC05', '2025-12-20 08:17:43'),
(165, '121EEC05', '2025-12-20 08:17:45'),
(171, '121EEC05', '2025-12-20 09:10:41'),
(172, '121EEC05', '2025-12-20 09:11:11'),
(173, '121EEC05', '2025-12-20 09:11:14'),
(174, '121EEC05', '2025-12-20 09:11:40'),
(175, '121EEC05', '2025-12-20 09:15:00'),
(176, '121EEC05', '2025-12-20 09:15:16'),
(177, '121EEC05', '2025-12-20 09:21:43'),
(180, '121EEC05', '2025-12-20 09:25:00'),
(183, '121EEC05', '2025-12-20 09:28:39'),
(185, '121EEC05', '2025-12-20 09:45:11'),
(191, '121EEC05', '2025-12-21 15:52:13'),
(203, '121EEC05', '2025-12-21 17:44:57'),
(206, '121EEC05', '2025-12-21 17:46:05'),
(216, '121EEC05', '2025-12-21 17:51:32'),
(237, '121EEC05', '2025-12-21 18:20:56'),
(288, '1D6C7D06', '2025-12-29 07:34:41'),
(289, '1D6C7D06', '2025-12-29 07:34:45'),
(446, '1D6C7D06', '2025-12-30 06:25:33'),
(507, '1D6C7D06', '2025-12-30 10:38:19'),
(603, '1D6C7D06', '2026-01-04 12:40:23'),
(622, '1D6C7D06', '2026-01-05 08:03:59'),
(660, '1D6C7D06', '2026-01-05 09:38:08'),
(708, '1D6C7D06', '2026-01-06 07:50:11'),
(752, '1D6C7D06', '2026-01-06 10:43:01'),
(302, '1DA69006', '2025-12-29 07:36:35'),
(303, '1DA69006', '2025-12-29 07:36:38'),
(398, '1DA69006', '2025-12-29 13:20:31'),
(457, '1DA69006', '2025-12-30 06:26:09'),
(309, '1DE47C06', '2025-12-29 07:51:29'),
(310, '1DE47C06', '2025-12-29 07:51:32'),
(394, '1DE47C06', '2025-12-29 13:20:20'),
(463, '1DE47C06', '2025-12-30 06:26:42'),
(494, '1DE47C06', '2025-12-30 10:37:37'),
(782, '1DE47C06', '2026-01-07 09:23:28'),
(783, '1DE47C06', '2026-01-07 09:23:46'),
(784, '1DE47C06', '2026-01-07 09:31:32'),
(785, '1DE47C06', '2026-01-07 09:34:52'),
(786, '1DE47C06', '2026-01-07 09:36:28'),
(787, '1DE47C06', '2026-01-07 09:47:56'),
(788, '1DE47C06', '2026-01-07 09:48:23'),
(311, '2D958306', '2025-12-29 07:51:43'),
(312, '2D958306', '2025-12-29 07:51:46'),
(393, '2D958306', '2025-12-29 13:20:17'),
(464, '2D958306', '2025-12-30 06:26:44'),
(493, '2D958306', '2025-12-30 10:37:36'),
(600, '2D958306', '2026-01-04 12:38:18'),
(354, '2DA18C06', '2025-12-29 08:32:40'),
(355, '2DA18C06', '2025-12-29 08:32:43'),
(386, '2DA18C06', '2025-12-29 13:19:37'),
(471, '2DA18C06', '2025-12-30 06:27:00'),
(486, '2DA18C06', '2025-12-30 10:37:24'),
(638, '2DA18C06', '2026-01-05 08:41:24'),
(639, '2DA18C06', '2026-01-05 08:43:35'),
(731, '2DA18C06', '2026-01-06 08:30:21'),
(764, '2DA18C06', '2026-01-06 10:45:33'),
(307, '2DD47406', '2025-12-29 07:51:16'),
(308, '2DD47406', '2025-12-29 07:51:20'),
(396, '2DD47406', '2025-12-29 13:20:25'),
(530, '2DD47406', '2026-01-03 13:17:17'),
(214, '39071778', '2025-12-21 17:51:19'),
(215, '39071778', '2025-12-21 17:51:27'),
(217, '39071778', '2025-12-21 17:51:35'),
(218, '39071778', '2025-12-21 17:51:39'),
(221, '39071778', '2025-12-21 18:06:34'),
(223, '39071778', '2025-12-21 18:06:40'),
(230, '39071778', '2025-12-21 18:16:11'),
(232, '39071778', '2025-12-21 18:16:16'),
(239, '39071778', '2025-12-22 06:48:42'),
(272, '3D517106', '2025-12-29 07:32:40'),
(273, '3D517106', '2025-12-29 07:32:44'),
(408, '3D517106', '2025-12-29 13:20:58'),
(410, '3D517106', '2025-12-29 13:21:01'),
(513, '3D517106', '2025-12-30 10:38:40'),
(601, '3D517106', '2026-01-04 12:39:31'),
(621, '3D517106', '2026-01-05 08:03:57'),
(661, '3D517106', '2026-01-05 09:38:10'),
(662, '3D517106', '2026-01-05 09:38:12'),
(707, '3D517106', '2026-01-06 07:49:53'),
(750, '3D517106', '2026-01-06 10:42:51'),
(780, '3D517106', '2026-01-07 07:50:25'),
(800, '3D517106', '2026-01-07 12:19:16'),
(313, '3DDE8E06', '2025-12-29 07:51:57'),
(314, '3DDE8E06', '2025-12-29 07:52:00'),
(392, '3DDE8E06', '2025-12-29 13:20:15'),
(465, '3DDE8E06', '2025-12-30 06:26:47'),
(492, '3DDE8E06', '2025-12-30 10:37:34'),
(609, '3DDE8E06', '2026-01-05 07:53:29'),
(1, '40AC7A61', '2025-12-17 22:35:38'),
(2, '40AC7A61', '2025-12-17 22:37:05'),
(3, '40AC7A61', '2025-12-18 05:56:43'),
(5, '40AC7A61', '2025-12-18 05:57:36'),
(6, '40AC7A61', '2025-12-18 05:57:40'),
(7, '40AC7A61', '2025-12-18 05:58:29'),
(8, '40AC7A61', '2025-12-18 05:58:41'),
(9, '40AC7A61', '2025-12-18 05:58:46'),
(10, '40AC7A61', '2025-12-18 05:58:50'),
(11, '40AC7A61', '2025-12-18 05:58:53'),
(12, '40AC7A61', '2025-12-18 05:59:36'),
(13, '40AC7A61', '2025-12-18 05:59:41'),
(14, '40AC7A61', '2025-12-18 05:59:44'),
(16, '40AC7A61', '2025-12-18 06:01:30'),
(17, '40AC7A61', '2025-12-18 06:01:40'),
(18, '40AC7A61', '2025-12-18 06:01:45'),
(23, '40AC7A61', '2025-12-18 06:02:07'),
(27, '40AC7A61', '2025-12-18 06:04:53'),
(28, '40AC7A61', '2025-12-18 06:04:57'),
(32, '40AC7A61', '2025-12-18 06:05:13'),
(33, '40AC7A61', '2025-12-18 07:39:12'),
(36, '40AC7A61', '2025-12-18 07:39:25'),
(38, '40AC7A61', '2025-12-18 07:40:08'),
(39, '40AC7A61', '2025-12-18 07:40:11'),
(40, '40AC7A61', '2025-12-18 07:40:32'),
(41, '40AC7A61', '2025-12-18 07:40:36'),
(42, '40AC7A61', '2025-12-18 07:41:14'),
(43, '40AC7A61', '2025-12-18 07:41:30'),
(44, '40AC7A61', '2025-12-18 07:42:44'),
(45, '40AC7A61', '2025-12-18 09:25:30'),
(46, '40AC7A61', '2025-12-18 09:25:56'),
(47, '40AC7A61', '2025-12-18 09:26:06'),
(48, '40AC7A61', '2025-12-18 09:26:41'),
(49, '40AC7A61', '2025-12-18 09:30:02'),
(50, '40AC7A61', '2025-12-18 09:33:25'),
(53, '40AC7A61', '2025-12-18 09:34:09'),
(54, '40AC7A61', '2025-12-18 21:18:08'),
(57, '40AC7A61', '2025-12-19 06:20:33'),
(60, '40AC7A61', '2025-12-19 06:21:15'),
(62, '40AC7A61', '2025-12-19 08:40:52'),
(63, '40AC7A61', '2025-12-19 09:51:24'),
(64, '40AC7A61', '2025-12-19 09:51:29'),
(65, '40AC7A61', '2025-12-19 10:24:49'),
(66, '40AC7A61', '2025-12-19 10:25:34'),
(67, '40AC7A61', '2025-12-19 10:37:22'),
(68, '40AC7A61', '2025-12-19 10:37:25'),
(69, '40AC7A61', '2025-12-19 10:37:29'),
(70, '40AC7A61', '2025-12-19 10:38:18'),
(71, '40AC7A61', '2025-12-19 10:38:46'),
(72, '40AC7A61', '2025-12-19 10:38:50'),
(78, '40AC7A61', '2025-12-19 10:40:31'),
(79, '40AC7A61', '2025-12-19 10:41:49'),
(80, '40AC7A61', '2025-12-19 10:42:36'),
(81, '40AC7A61', '2025-12-19 10:42:54'),
(82, '40AC7A61', '2025-12-19 10:45:54'),
(84, '40AC7A61', '2025-12-19 10:53:37'),
(85, '40AC7A61', '2025-12-19 10:53:48'),
(86, '40AC7A61', '2025-12-19 10:54:23'),
(87, '40AC7A61', '2025-12-19 10:54:56'),
(88, '40AC7A61', '2025-12-19 11:03:16'),
(89, '40AC7A61', '2025-12-19 11:03:19'),
(90, '40AC7A61', '2025-12-19 11:03:35'),
(91, '40AC7A61', '2025-12-19 11:03:38'),
(92, '40AC7A61', '2025-12-19 11:03:42'),
(93, '40AC7A61', '2025-12-19 11:04:41'),
(94, '40AC7A61', '2025-12-19 11:04:46'),
(95, '40AC7A61', '2025-12-19 11:04:55'),
(96, '40AC7A61', '2025-12-19 11:07:55'),
(97, '40AC7A61', '2025-12-19 11:10:19'),
(98, '40AC7A61', '2025-12-19 11:10:22'),
(99, '40AC7A61', '2025-12-19 11:10:26'),
(100, '40AC7A61', '2025-12-19 11:11:03'),
(101, '40AC7A61', '2025-12-19 11:13:46'),
(102, '40AC7A61', '2025-12-19 11:14:41'),
(103, '40AC7A61', '2025-12-19 11:14:47'),
(109, '40AC7A61', '2025-12-19 11:17:53'),
(110, '40AC7A61', '2025-12-19 11:18:13'),
(112, '40AC7A61', '2025-12-19 11:21:39'),
(123, '40AC7A61', '2025-12-19 13:45:43'),
(124, '40AC7A61', '2025-12-19 13:45:54'),
(126, '40AC7A61', '2025-12-19 13:55:52'),
(127, '40AC7A61', '2025-12-19 13:55:55'),
(129, '40AC7A61', '2025-12-19 13:56:03'),
(130, '40AC7A61', '2025-12-19 13:56:07'),
(131, '40AC7A61', '2025-12-19 13:56:10'),
(132, '40AC7A61', '2025-12-19 13:56:14'),
(133, '40AC7A61', '2025-12-19 13:56:17'),
(134, '40AC7A61', '2025-12-19 14:30:08'),
(135, '40AC7A61', '2025-12-19 14:30:18'),
(136, '40AC7A61', '2025-12-19 14:31:45'),
(140, '40AC7A61', '2025-12-19 15:17:37'),
(142, '40AC7A61', '2025-12-20 07:59:04'),
(144, '40AC7A61', '2025-12-20 07:59:17'),
(145, '40AC7A61', '2025-12-20 07:59:47'),
(146, '40AC7A61', '2025-12-20 07:59:59'),
(147, '40AC7A61', '2025-12-20 08:00:28'),
(148, '40AC7A61', '2025-12-20 08:00:41'),
(149, '40AC7A61', '2025-12-20 08:03:57'),
(150, '40AC7A61', '2025-12-20 08:08:30'),
(151, '40AC7A61', '2025-12-20 08:08:34'),
(152, '40AC7A61', '2025-12-20 08:10:49'),
(153, '40AC7A61', '2025-12-20 08:10:54'),
(154, '40AC7A61', '2025-12-20 08:10:58'),
(155, '40AC7A61', '2025-12-20 08:13:00'),
(157, '40AC7A61', '2025-12-20 08:13:06'),
(159, '40AC7A61', '2025-12-20 08:17:32'),
(160, '40AC7A61', '2025-12-20 08:17:34'),
(162, '40AC7A61', '2025-12-20 08:17:42'),
(164, '40AC7A61', '2025-12-20 08:17:44'),
(166, '40AC7A61', '2025-12-20 08:40:24'),
(167, '40AC7A61', '2025-12-20 09:10:09'),
(168, '40AC7A61', '2025-12-20 09:10:12'),
(169, '40AC7A61', '2025-12-20 09:10:15'),
(170, '40AC7A61', '2025-12-20 09:10:35'),
(178, '40AC7A61', '2025-12-20 09:23:38'),
(179, '40AC7A61', '2025-12-20 09:23:43'),
(181, '40AC7A61', '2025-12-20 09:25:33'),
(182, '40AC7A61', '2025-12-20 09:28:35'),
(184, '40AC7A61', '2025-12-20 09:28:43'),
(186, '40AC7A61', '2025-12-20 09:45:13'),
(187, '40AC7A61', '2025-12-21 15:51:26'),
(188, '40AC7A61', '2025-12-21 15:51:31'),
(189, '40AC7A61', '2025-12-21 15:51:47'),
(190, '40AC7A61', '2025-12-21 15:52:09'),
(192, '40AC7A61', '2025-12-21 16:15:03'),
(193, '40AC7A61', '2025-12-21 16:15:14'),
(194, '40AC7A61', '2025-12-21 16:21:06'),
(195, '40AC7A61', '2025-12-21 16:21:11'),
(196, '40AC7A61', '2025-12-21 16:21:25'),
(197, '40AC7A61', '2025-12-21 17:12:14'),
(198, '40AC7A61', '2025-12-21 17:12:21'),
(199, '40AC7A61', '2025-12-21 17:42:49'),
(200, '40AC7A61', '2025-12-21 17:42:57'),
(201, '40AC7A61', '2025-12-21 17:44:09'),
(202, '40AC7A61', '2025-12-21 17:44:46'),
(204, '40AC7A61', '2025-12-21 17:44:59'),
(205, '40AC7A61', '2025-12-21 17:45:58'),
(207, '40AC7A61', '2025-12-21 17:46:08'),
(208, '40AC7A61', '2025-12-21 17:48:05'),
(213, '40AC7A61', '2025-12-21 17:51:15'),
(219, '40AC7A61', '2025-12-21 17:51:43'),
(222, '40AC7A61', '2025-12-21 18:06:36'),
(224, '40AC7A61', '2025-12-21 18:15:31'),
(225, '40AC7A61', '2025-12-21 18:15:34'),
(226, '40AC7A61', '2025-12-21 18:15:59'),
(227, '40AC7A61', '2025-12-21 18:16:03'),
(228, '40AC7A61', '2025-12-21 18:16:06'),
(235, '40AC7A61', '2025-12-21 18:20:48'),
(338, '4D3C7E06', '2025-12-29 08:29:57'),
(339, '4D3C7E06', '2025-12-29 08:30:00'),
(422, '4D3C7E06', '2025-12-29 13:21:39'),
(436, '4D3C7E06', '2025-12-30 06:24:29'),
(501, '4D3C7E06', '2025-12-30 10:38:08'),
(607, '4D3C7E06', '2026-01-04 12:41:25'),
(619, '4D3C7E06', '2026-01-05 08:01:37'),
(672, '4D3C7E06', '2026-01-05 09:38:39'),
(673, '4D3C7E06', '2026-01-05 09:38:40'),
(699, '4D3C7E06', '2026-01-06 07:38:58'),
(700, '4D3C7E06', '2026-01-06 07:39:01'),
(754, '4D3C7E06', '2026-01-06 10:43:22'),
(755, '4D3C7E06', '2026-01-06 10:43:24'),
(772, '4D3C7E06', '2026-01-07 07:44:02'),
(796, '4D3C7E06', '2026-01-07 12:19:06'),
(686, '4D6A8B06', '2026-01-06 07:30:36'),
(687, '4D6A8B06', '2026-01-06 07:30:39'),
(344, '4D9D8506', '2025-12-29 08:30:40'),
(345, '4D9D8506', '2025-12-29 08:30:43'),
(419, '4D9D8506', '2025-12-29 13:21:24'),
(439, '4D9D8506', '2025-12-30 06:24:42'),
(482, '4D9D8506', '2025-12-30 10:37:18'),
(610, '4D9D8506', '2026-01-05 07:53:47'),
(724, '4D9D8506', '2026-01-06 08:06:41'),
(725, '4D9D8506', '2026-01-06 08:06:44'),
(751, '4D9D8506', '2026-01-06 10:42:55'),
(271, '4DBF8A06', '2025-12-29 07:31:34'),
(409, '4DBF8A06', '2025-12-29 13:21:00'),
(411, '4DBF8A06', '2025-12-29 13:21:07'),
(712, '4DBF8A06', '2026-01-06 07:53:38'),
(713, '4DBF8A06', '2026-01-06 07:53:52'),
(714, '4DBF8A06', '2026-01-06 07:53:55'),
(719, '4DBF8A06', '2026-01-06 07:54:23'),
(720, '4DBF8A06', '2026-01-06 07:55:41'),
(721, '4DBF8A06', '2026-01-06 07:56:00'),
(722, '4DBF8A06', '2026-01-06 07:56:05'),
(729, '4DBF8A06', '2026-01-06 08:26:03'),
(741, '4DBF8A06', '2026-01-06 10:42:14'),
(774, '4DBF8A06', '2026-01-07 07:45:04'),
(793, '4DBF8A06', '2026-01-07 12:18:57'),
(334, '4DE98206', '2025-12-29 08:29:27'),
(335, '4DE98206', '2025-12-29 08:29:31'),
(424, '4DE98206', '2025-12-29 13:21:43'),
(434, '4DE98206', '2025-12-30 06:24:17'),
(508, '4DE98206', '2025-12-30 10:38:31'),
(296, '5D799406', '2025-12-29 07:35:49'),
(297, '5D799406', '2025-12-29 07:35:52'),
(401, '5D799406', '2025-12-29 13:20:38'),
(454, '5D799406', '2025-12-30 06:25:56'),
(624, '5D799406', '2026-01-05 08:04:03'),
(626, '5D799406', '2026-01-05 08:38:22'),
(627, '5D799406', '2026-01-05 08:38:26'),
(628, '5D799406', '2026-01-05 08:39:07'),
(629, '5D799406', '2026-01-05 08:39:10'),
(630, '5D799406', '2026-01-05 08:39:16'),
(631, '5D799406', '2026-01-05 08:39:47'),
(640, '5D799406', '2026-01-05 08:44:41'),
(666, '5D799406', '2026-01-05 09:38:21'),
(701, '5D799406', '2026-01-06 07:43:33'),
(704, '5D799406', '2026-01-06 07:43:51'),
(705, '5D799406', '2026-01-06 07:43:54'),
(748, '5D799406', '2026-01-06 10:42:35'),
(778, '5D799406', '2026-01-07 07:50:16'),
(805, '5D799406', '2026-01-07 12:22:17'),
(806, '5D799406', '2026-01-07 12:22:20'),
(356, '6D6E8306', '2025-12-29 08:32:56'),
(357, '6D6E8306', '2025-12-29 08:32:58'),
(381, '6D6E8306', '2025-12-29 13:19:19'),
(383, '6D6E8306', '2025-12-29 13:19:30'),
(459, '6D6E8306', '2025-12-30 06:26:31'),
(497, '6D6E8306', '2025-12-30 10:37:47'),
(636, '6D6E8306', '2026-01-05 08:40:55'),
(261, '7D1C7606', '2025-12-29 07:29:51'),
(264, '7D1C7606', '2025-12-29 07:30:17'),
(415, '7D1C7606', '2025-12-29 13:21:16'),
(443, '7D1C7606', '2025-12-30 06:24:57'),
(478, '7D1C7606', '2025-12-30 10:37:09'),
(523, '7D1C7606', '2026-01-03 12:55:39'),
(524, '7D1C7606', '2026-01-03 12:55:48'),
(262, '7D248506', '2025-12-29 07:30:09'),
(263, '7D248506', '2025-12-29 07:30:12'),
(416, '7D248506', '2025-12-29 13:21:18'),
(442, '7D248506', '2025-12-30 06:24:54'),
(479, '7D248506', '2025-12-30 10:37:11'),
(520, '7D248506', '2026-01-03 08:50:06'),
(522, '7D248506', '2026-01-03 12:54:06'),
(260, '7D368E06', '2025-12-29 07:25:43'),
(265, '7D368E06', '2025-12-29 07:30:20'),
(414, '7D368E06', '2025-12-29 13:21:15'),
(444, '7D368E06', '2025-12-30 06:25:00'),
(477, '7D368E06', '2025-12-30 10:37:07'),
(515, '7D368E06', '2026-01-01 09:56:39'),
(516, '7D368E06', '2026-01-01 09:56:52'),
(290, '7DC88506', '2025-12-29 07:34:57'),
(291, '7DC88506', '2025-12-29 07:35:00'),
(404, '7DC88506', '2025-12-29 13:20:44'),
(429, '7DC88506', '2025-12-30 06:23:52'),
(473, '7DC88506', '2025-12-30 06:49:50'),
(475, '7DC88506', '2025-12-30 10:37:03'),
(519, '7DC88506', '2026-01-03 08:39:44'),
(340, '7DE38706', '2025-12-29 08:30:12'),
(341, '7DE38706', '2025-12-29 08:30:14'),
(421, '7DE38706', '2025-12-29 13:21:37'),
(437, '7DE38706', '2025-12-30 06:24:33'),
(500, '7DE38706', '2025-12-30 10:38:06'),
(534, '7DE38706', '2026-01-03 13:19:57'),
(613, '7DE38706', '2026-01-05 07:59:20'),
(674, '7DE38706', '2026-01-05 09:38:42'),
(675, '7DE38706', '2026-01-05 09:38:44'),
(771, '7DE38706', '2026-01-07 07:43:46'),
(797, '7DE38706', '2026-01-07 12:19:07'),
(257, '8D7E8F06', '2025-12-28 17:27:41'),
(258, '8D7E8F06', '2025-12-28 17:27:53'),
(259, '8D7E8F06', '2025-12-28 17:28:11'),
(360, '8D7E8F06', '2025-12-29 08:33:43'),
(366, '8D7E8F06', '2025-12-29 09:36:36'),
(369, '8D7E8F06', '2025-12-29 09:39:35'),
(372, '8D7E8F06', '2025-12-29 11:40:21'),
(377, '8D7E8F06', '2025-12-29 13:16:37'),
(378, '8D7E8F06', '2025-12-29 13:16:50'),
(389, '8D7E8F06', '2025-12-29 13:19:44'),
(468, '8D7E8F06', '2025-12-30 06:26:53'),
(489, '8D7E8F06', '2025-12-30 10:37:29'),
(274, '8D8B7F06', '2025-12-29 07:32:56'),
(275, '8D8B7F06', '2025-12-29 07:33:00'),
(407, '8D8B7F06', '2025-12-29 13:20:56'),
(645, '8D8B7F06', '2026-01-05 09:04:02'),
(647, '8D8B7F06', '2026-01-05 09:04:22'),
(648, '8D8B7F06', '2026-01-05 09:04:25'),
(663, '8D8B7F06', '2026-01-05 09:38:13'),
(664, '8D8B7F06', '2026-01-05 09:38:15'),
(305, '9D1A9406', '2025-12-29 07:37:06'),
(306, '9D1A9406', '2025-12-29 07:37:12'),
(395, '9D1A9406', '2025-12-29 13:20:23'),
(462, '9D1A9406', '2025-12-30 06:26:40'),
(495, '9D1A9406', '2025-12-30 10:37:40'),
(608, '9D1A9406', '2026-01-05 07:53:14'),
(317, '9D807106', '2025-12-29 07:53:07'),
(318, '9D807106', '2025-12-29 07:53:10'),
(374, '9D807106', '2025-12-29 11:41:23'),
(375, '9D807106', '2025-12-29 11:41:32'),
(390, '9D807106', '2025-12-29 13:20:09'),
(467, '9D807106', '2025-12-30 06:26:51'),
(490, '9D807106', '2025-12-30 10:37:30'),
(598, '9D807106', '2026-01-04 12:37:16'),
(321, '9D947C06', '2025-12-29 08:26:48'),
(322, '9D947C06', '2025-12-29 08:26:50'),
(427, '9D947C06', '2025-12-29 13:21:48'),
(431, '9D947C06', '2025-12-30 06:24:07'),
(510, '9D947C06', '2025-12-30 10:38:34'),
(535, '9D947C06', '2026-01-03 13:20:32'),
(536, '9D947C06', '2026-01-03 13:20:35'),
(617, '9D947C06', '2026-01-05 07:59:51'),
(678, '9D947C06', '2026-01-05 09:38:50'),
(679, '9D947C06', '2026-01-05 09:38:54'),
(690, '9D947C06', '2026-01-06 07:33:15'),
(757, '9D947C06', '2026-01-06 10:43:48'),
(758, '9D947C06', '2026-01-06 10:43:50'),
(765, '9D947C06', '2026-01-07 07:25:23'),
(795, '9D947C06', '2026-01-07 12:19:03'),
(292, '9DC88B06', '2025-12-29 07:35:19'),
(293, '9DC88B06', '2025-12-29 07:35:23'),
(403, '9DC88B06', '2025-12-29 13:20:42'),
(460, '9DC88B06', '2025-12-30 06:26:36'),
(605, '9DC88B06', '2026-01-04 12:40:56'),
(618, '9DC88B06', '2026-01-05 08:01:29'),
(671, '9DC88B06', '2026-01-05 09:38:35'),
(727, '9DC88B06', '2026-01-06 08:09:15'),
(746, '9DC88B06', '2026-01-06 10:42:29'),
(280, '9DD08E06', '2025-12-29 07:33:36'),
(281, '9DD08E06', '2025-12-29 07:33:45'),
(282, '9DD08E06', '2025-12-29 07:33:48'),
(461, '9DD08E06', '2025-12-30 06:26:38'),
(496, '9DD08E06', '2025-12-30 10:37:43'),
(716, '9DD08E06', '2026-01-06 07:54:15'),
(717, '9DD08E06', '2026-01-06 07:54:17'),
(730, '9DD08E06', '2026-01-06 08:26:17'),
(743, '9DD08E06', '2026-01-06 10:42:19'),
(745, '9DD08E06', '2026-01-06 10:42:23'),
(319, '9DD47406', '2025-12-29 07:53:28'),
(320, '9DD47406', '2025-12-29 07:53:32'),
(417, '9DD47406', '2025-12-29 13:21:21'),
(441, '9DD47406', '2025-12-30 06:24:50'),
(480, '9DD47406', '2025-12-30 10:37:13'),
(538, '9DD47406', '2026-01-03 13:21:43'),
(545, '9DD47406', '2026-01-03 20:28:31'),
(546, '9DD47406', '2026-01-03 20:28:36'),
(596, '9DD47406', '2026-01-04 08:00:04'),
(315, '9DDF7A06', '2025-12-29 07:52:52'),
(316, '9DDF7A06', '2025-12-29 07:52:55'),
(391, '9DDF7A06', '2025-12-29 13:20:13'),
(466, '9DDF7A06', '2025-12-30 06:26:49'),
(491, '9DDF7A06', '2025-12-30 10:37:32'),
(599, '9DDF7A06', '2026-01-04 12:37:59'),
(209, '9FC0A7CC', '2025-12-21 17:48:20'),
(210, '9FC0A7CC', '2025-12-21 17:48:29'),
(211, '9FC0A7CC', '2025-12-21 17:48:33'),
(212, '9FC0A7CC', '2025-12-21 17:51:11'),
(220, '9FC0A7CC', '2025-12-21 17:51:46'),
(229, '9FC0A7CC', '2025-12-21 18:16:09'),
(231, '9FC0A7CC', '2025-12-21 18:16:14'),
(233, '9FC0A7CC', '2025-12-21 18:18:07'),
(234, '9FC0A7CC', '2025-12-21 18:18:11'),
(236, '9FC0A7CC', '2025-12-21 18:20:51'),
(238, '9FC0A7CC', '2025-12-21 18:20:59'),
(240, '9FC0A7CC', '2025-12-22 06:48:56'),
(241, '9FC0A7CC', '2025-12-22 06:49:23'),
(242, '9FC0A7CC', '2025-12-22 06:50:47'),
(243, '9FC0A7CC', '2025-12-22 19:27:15'),
(245, 'ABCD1234', '2025-12-28 08:09:32'),
(286, 'AD3F6D06', '2025-12-29 07:34:20'),
(287, 'AD3F6D06', '2025-12-29 07:34:24'),
(447, 'AD3F6D06', '2025-12-30 06:25:37'),
(506, 'AD3F6D06', '2025-12-30 10:38:18'),
(298, 'AD598806', '2025-12-29 07:36:05'),
(299, 'AD598806', '2025-12-29 07:36:09'),
(400, 'AD598806', '2025-12-29 13:20:36'),
(455, 'AD598806', '2025-12-30 06:25:59'),
(499, 'AD598806', '2025-12-30 10:37:54'),
(531, 'AD598806', '2026-01-03 13:17:44'),
(646, 'AD598806', '2026-01-05 09:04:04'),
(667, 'AD598806', '2026-01-05 09:38:24'),
(668, 'AD598806', '2026-01-05 09:38:27'),
(702, 'AD598806', '2026-01-06 07:43:42'),
(703, 'AD598806', '2026-01-06 07:43:46'),
(706, 'AD598806', '2026-01-06 07:43:57'),
(747, 'AD598806', '2026-01-06 10:42:32'),
(779, 'AD598806', '2026-01-07 07:50:21'),
(804, 'AD598806', '2026-01-07 12:22:14'),
(330, 'AD679606', '2025-12-29 08:28:01'),
(331, 'AD679606', '2025-12-29 08:28:55'),
(426, 'AD679606', '2025-12-29 13:21:46'),
(432, 'AD679606', '2025-12-30 06:24:10'),
(625, 'AD679606', '2026-01-05 08:04:26'),
(633, 'AD679606', '2026-01-05 08:40:23'),
(641, 'AD679606', '2026-01-05 08:45:13'),
(654, 'AD679606', '2026-01-05 09:37:51'),
(655, 'AD679606', '2026-01-05 09:37:54'),
(693, 'AD679606', '2026-01-06 07:33:41'),
(694, 'AD679606', '2026-01-06 07:33:43'),
(762, 'AD679606', '2026-01-06 10:44:33'),
(770, 'AD679606', '2026-01-07 07:39:39'),
(266, 'AD807A06', '2025-12-29 07:30:31'),
(267, 'AD807A06', '2025-12-29 07:30:40'),
(413, 'AD807A06', '2025-12-29 13:21:12'),
(445, 'AD807A06', '2025-12-30 06:25:03'),
(476, 'AD807A06', '2025-12-30 10:37:05'),
(517, 'AD807A06', '2026-01-02 17:56:12'),
(518, 'AD807A06', '2026-01-02 17:56:41'),
(528, 'AD807A06', '2026-01-03 13:11:41'),
(529, 'AD807A06', '2026-01-03 13:11:45'),
(328, 'ADC67506', '2025-12-29 08:27:44'),
(329, 'ADC67506', '2025-12-29 08:27:47'),
(449, 'ADC67506', '2025-12-30 06:25:41'),
(634, 'ADC67506', '2026-01-05 08:40:33'),
(715, 'ADC67506', '2026-01-06 07:53:56'),
(718, 'ADC67506', '2026-01-06 07:54:20'),
(723, 'ADC67506', '2026-01-06 08:05:59'),
(728, 'ADC67506', '2026-01-06 08:24:08'),
(744, 'ADC67506', '2026-01-06 10:42:21'),
(776, 'ADC67506', '2026-01-07 07:45:53'),
(777, 'ADC67506', '2026-01-07 07:45:56'),
(802, 'ADC67506', '2026-01-07 12:19:26'),
(346, 'BD5B8906', '2025-12-29 08:30:55'),
(347, 'BD5B8906', '2025-12-29 08:30:58'),
(418, 'BD5B8906', '2025-12-29 13:21:22'),
(440, 'BD5B8906', '2025-12-30 06:24:46'),
(481, 'BD5B8906', '2025-12-30 10:37:16'),
(527, 'BD5B8906', '2026-01-03 13:09:32'),
(614, 'BD5B8906', '2026-01-05 07:59:26'),
(680, 'BD5B8906', '2026-01-05 09:38:56'),
(681, 'BD5B8906', '2026-01-05 09:38:58'),
(695, 'BD5B8906', '2026-01-06 07:33:50'),
(696, 'BD5B8906', '2026-01-06 07:33:54'),
(759, 'BD5B8906', '2026-01-06 10:43:52'),
(769, 'BD5B8906', '2026-01-07 07:34:15'),
(798, 'BD5B8906', '2026-01-07 12:19:10'),
(348, 'BDA97506', '2025-12-29 08:31:41'),
(349, 'BDA97506', '2025-12-29 08:31:50'),
(370, 'BDA97506', '2025-12-29 09:39:46'),
(371, 'BDA97506', '2025-12-29 09:39:55'),
(373, 'BDA97506', '2025-12-29 11:40:32'),
(388, 'BDA97506', '2025-12-29 13:19:41'),
(469, 'BDA97506', '2025-12-30 06:26:55'),
(488, 'BDA97506', '2025-12-30 10:37:28'),
(521, 'BDA97506', '2026-01-03 08:50:33'),
(620, 'BDA97506', '2026-01-05 08:01:44'),
(676, 'BDA97506', '2026-01-05 09:38:46'),
(677, 'BDA97506', '2026-01-05 09:38:48'),
(697, 'BDA97506', '2026-01-06 07:35:24'),
(698, 'BDA97506', '2026-01-06 07:35:27'),
(756, 'BDA97506', '2026-01-06 10:43:44'),
(768, 'BDA97506', '2026-01-07 07:33:19'),
(799, 'BDA97506', '2026-01-07 12:19:13'),
(294, 'BDD48D06', '2025-12-29 07:35:33'),
(295, 'BDD48D06', '2025-12-29 07:35:36'),
(402, 'BDD48D06', '2025-12-29 13:20:40'),
(453, 'BDD48D06', '2025-12-30 06:25:52'),
(502, 'BDD48D06', '2025-12-30 10:38:11'),
(606, 'BDD48D06', '2026-01-04 12:41:04'),
(623, 'BDD48D06', '2026-01-05 08:04:01'),
(669, 'BDD48D06', '2026-01-05 09:38:30'),
(670, 'BDD48D06', '2026-01-05 09:38:33'),
(688, 'BDD48D06', '2026-01-06 07:31:13'),
(689, 'BDD48D06', '2026-01-06 07:31:17'),
(753, 'BDD48D06', '2026-01-06 10:43:11'),
(352, 'BDD67906', '2025-12-29 08:32:24'),
(353, 'BDD67906', '2025-12-29 08:32:28'),
(367, 'BDD67906', '2025-12-29 09:39:22'),
(368, 'BDD67906', '2025-12-29 09:39:29'),
(379, 'BDD67906', '2025-12-29 13:16:55'),
(387, 'BDD67906', '2025-12-29 13:19:39'),
(470, 'BDD67906', '2025-12-30 06:26:57'),
(487, 'BDD67906', '2025-12-30 10:37:26'),
(525, 'BDD67906', '2026-01-03 12:58:28'),
(616, 'BDD67906', '2026-01-05 07:59:32'),
(656, 'BDD67906', '2026-01-05 09:37:56'),
(323, 'CD505D06', '2025-12-29 08:27:04'),
(324, 'CD505D06', '2025-12-29 08:27:06'),
(428, 'CD505D06', '2025-12-29 13:21:50'),
(430, 'CD505D06', '2025-12-30 06:23:58'),
(511, 'CD505D06', '2025-12-30 10:38:35'),
(632, 'CD505D06', '2026-01-05 08:40:13'),
(643, 'CD505D06', '2026-01-05 08:45:16'),
(659, 'CD505D06', '2026-01-05 09:38:05'),
(709, 'CD505D06', '2026-01-06 07:51:06'),
(710, 'CD505D06', '2026-01-06 07:51:08'),
(738, 'CD505D06', '2026-01-06 10:41:51'),
(740, 'CD505D06', '2026-01-06 10:41:58'),
(773, 'CD505D06', '2026-01-07 07:44:56'),
(792, 'CD505D06', '2026-01-07 12:18:54'),
(332, 'CD8A8406', '2025-12-29 08:29:08'),
(333, 'CD8A8406', '2025-12-29 08:29:19'),
(425, 'CD8A8406', '2025-12-29 13:21:45'),
(433, 'CD8A8406', '2025-12-30 06:24:13'),
(509, 'CD8A8406', '2025-12-30 10:38:32'),
(537, 'CD8A8406', '2026-01-03 13:20:47'),
(358, 'DD099406', '2025-12-29 08:33:15'),
(359, 'DD099406', '2025-12-29 08:33:18'),
(380, 'DD099406', '2025-12-29 13:19:18'),
(385, 'DD099406', '2025-12-29 13:19:35'),
(472, 'DD099406', '2025-12-30 06:27:02'),
(485, 'DD099406', '2025-12-30 10:37:22'),
(597, 'DD099406', '2026-01-04 12:36:48'),
(611, 'DD099406', '2026-01-05 07:57:41'),
(682, 'DD099406', '2026-01-05 09:38:59'),
(726, 'DD099406', '2026-01-06 08:09:01'),
(749, 'DD099406', '2026-01-06 10:42:48'),
(781, 'DD099406', '2026-01-07 07:59:13'),
(803, 'DD099406', '2026-01-07 12:19:35'),
(336, 'DD618B06', '2025-12-29 08:29:41'),
(337, 'DD618B06', '2025-12-29 08:29:43'),
(423, 'DD618B06', '2025-12-29 13:21:41'),
(435, 'DD618B06', '2025-12-30 06:24:21'),
(283, 'DD776806', '2025-12-29 07:33:59'),
(284, 'DD776806', '2025-12-29 07:34:02'),
(285, 'DD776806', '2025-12-29 07:34:06'),
(450, 'DD776806', '2025-12-30 06:25:44'),
(504, 'DD776806', '2025-12-30 10:38:14'),
(254, 'DDE26E06', '2025-12-28 17:26:47'),
(255, 'DDE26E06', '2025-12-28 17:26:51'),
(361, 'DDE26E06', '2025-12-29 08:34:21'),
(362, 'DDE26E06', '2025-12-29 09:32:45'),
(363, 'DDE26E06', '2025-12-29 09:32:47'),
(364, 'DDE26E06', '2025-12-29 09:32:55'),
(365, 'DDE26E06', '2025-12-29 09:32:57'),
(382, 'DDE26E06', '2025-12-29 13:19:26'),
(474, 'DDE26E06', '2025-12-30 10:36:49'),
(651, 'DDE26E06', '2026-01-05 09:32:13'),
(652, 'DDE26E06', '2026-01-05 09:37:42'),
(735, 'DDE26E06', '2026-01-06 08:32:12'),
(736, 'DDE26E06', '2026-01-06 10:41:23'),
(737, 'DDE26E06', '2026-01-06 10:41:32'),
(739, 'DDE26E06', '2026-01-06 10:41:54'),
(790, 'DDE26E06', '2026-01-07 12:18:49'),
(326, 'ED439906', '2025-12-29 08:27:30'),
(327, 'ED439906', '2025-12-29 08:27:32'),
(448, 'ED439906', '2025-12-30 06:25:39'),
(505, 'ED439906', '2025-12-30 10:38:16'),
(532, 'ED439906', '2026-01-03 13:18:11'),
(612, 'ED439906', '2026-01-05 07:59:09'),
(657, 'ED439906', '2026-01-05 09:37:59'),
(692, 'ED439906', '2026-01-06 07:33:35'),
(761, 'ED439906', '2026-01-06 10:44:28'),
(767, 'ED439906', '2026-01-07 07:29:19'),
(794, 'ED439906', '2026-01-07 12:19:00'),
(342, 'ED7B8106', '2025-12-29 08:30:26'),
(343, 'ED7B8106', '2025-12-29 08:30:29'),
(420, 'ED7B8106', '2025-12-29 13:21:26'),
(438, 'ED7B8106', '2025-12-30 06:24:37'),
(483, 'ED7B8106', '2025-12-30 10:37:19'),
(637, 'ED7B8106', '2026-01-05 08:41:05'),
(732, 'ED7B8106', '2026-01-06 08:31:03'),
(733, 'ED7B8106', '2026-01-06 08:31:12'),
(734, 'ED7B8106', '2026-01-06 08:31:18'),
(763, 'ED7B8106', '2026-01-06 10:45:15'),
(278, 'EDB58A06', '2025-12-29 07:33:28'),
(279, 'EDB58A06', '2025-12-29 07:33:31'),
(405, 'EDB58A06', '2025-12-29 13:20:52'),
(452, 'EDB58A06', '2025-12-30 06:25:49'),
(325, 'EDCE6E06', '2025-12-29 08:27:19'),
(451, 'EDCE6E06', '2025-12-30 06:25:46'),
(503, 'EDCE6E06', '2025-12-30 10:38:13'),
(635, 'EDCE6E06', '2026-01-05 08:40:44'),
(642, 'EDCE6E06', '2026-01-05 08:45:15'),
(658, 'EDCE6E06', '2026-01-05 09:38:01'),
(711, 'EDCE6E06', '2026-01-06 07:51:29'),
(742, 'EDCE6E06', '2026-01-06 10:42:18'),
(775, 'EDCE6E06', '2026-01-07 07:45:14'),
(801, 'EDCE6E06', '2026-01-07 12:19:21'),
(246, 'FD0B9106', '2025-12-28 17:23:24'),
(247, 'FD0B9106', '2025-12-28 17:23:26'),
(248, 'FD0B9106', '2025-12-28 17:23:45'),
(249, 'FD0B9106', '2025-12-28 17:23:50'),
(250, 'FD0B9106', '2025-12-28 17:25:22'),
(251, 'FD0B9106', '2025-12-28 17:25:49'),
(252, 'FD0B9106', '2025-12-28 17:26:01'),
(253, 'FD0B9106', '2025-12-28 17:26:06'),
(256, 'FD0B9106', '2025-12-28 17:26:55'),
(304, 'FD0B9106', '2025-12-29 07:36:52'),
(397, 'FD0B9106', '2025-12-29 13:20:28'),
(458, 'FD0B9106', '2025-12-30 06:26:12'),
(498, 'FD0B9106', '2025-12-30 10:37:48'),
(533, 'FD0B9106', '2026-01-03 13:19:15'),
(276, 'FD348A06', '2025-12-29 07:33:14'),
(277, 'FD348A06', '2025-12-29 07:33:17'),
(406, 'FD348A06', '2025-12-29 13:20:54'),
(514, 'FD348A06', '2025-12-30 10:38:44'),
(644, 'FD348A06', '2026-01-05 09:03:59'),
(649, 'FD348A06', '2026-01-05 09:04:34'),
(650, 'FD348A06', '2026-01-05 09:04:37'),
(665, 'FD348A06', '2026-01-05 09:38:16'),
(350, 'FDEC8306', '2025-12-29 08:32:03'),
(351, 'FDEC8306', '2025-12-29 08:32:06'),
(376, 'FDEC8306', '2025-12-29 11:41:41'),
(384, 'FDEC8306', '2025-12-29 13:19:33'),
(484, 'FDEC8306', '2025-12-30 10:37:21'),
(526, 'FDEC8306', '2026-01-03 13:09:13'),
(615, 'FDEC8306', '2026-01-05 07:59:30'),
(653, 'FDEC8306', '2026-01-05 09:37:45'),
(691, 'FDEC8306', '2026-01-06 07:33:30'),
(760, 'FDEC8306', '2026-01-06 10:44:25'),
(766, 'FDEC8306', '2026-01-07 07:29:02'),
(789, 'FDEC8306', '2026-01-07 12:18:36'),
(791, 'FDEC8306', '2026-01-07 12:18:51');

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
('KgyKJ6r36Gz6JFak88KxKW4Ex6l6XyoBqfO26u5D', 1, '192.168.1.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoidzBvMnZvMXVPV2hOWTBaQU1ka1RkOFZ2VDNOSkxadVBNVkR1N2hRZSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM5OiJodHRwOi8vMTkyLjE2OC4xLjE5Ny9hYnNlbi9wdWJsaWMvc2lzd2EiO3M6NToicm91dGUiO3M6MTE6InNpc3dhLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1767311274);

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('absence_check_period_days', '7', '2026-01-06 00:36:00'),
('absence_notification_enabled', 'true', '2026-01-06 00:36:00'),
('absence_threshold_days', '3', '2026-01-06 00:36:00'),
('alamat_sekolah', 'Jalan Murnijaya, Desa Murnijaya, Kec. Tumijajar, Kab. Tulang Bawang Barat, Lampung', '2025-12-26 13:33:53'),
('alamat_ttd', 'Tumijajar', '2025-12-26 13:33:53'),
('checkout_tolerance_minutes', '15', '2025-12-28 05:00:17'),
('enable_checkout_attendance', 'false', '2026-01-10 14:03:14'),
('last_daily_process_date', '2026-01-07', '2026-01-07 06:15:05'),
('last_daily_report_date', '2026-01-07', '2026-01-07 02:00:06'),
('nama_sekolah', 'SMK Assuniyah Tumijajar', '2025-12-26 13:32:12'),
('report_target_jid', '6281369368296-1504440561@g.us', '2026-01-05 01:58:28'),
('schedule_backup_db', '14:00', '2026-01-06 00:35:59'),
('schedule_check_abnormal', '16:00', '2026-01-10 14:01:24'),
('schedule_daily_report', '09:00', '2026-01-05 00:03:08'),
('schedule_process_daily', '13:15', '2026-01-05 00:03:08'),
('schedule_send_teacher_schedule', '07:30', '2026-01-05 00:03:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `nis` varchar(50) NOT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `wa_ortu` varchar(20) DEFAULT NULL,
  `uid_rfid` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `enroll_status` varchar(20) DEFAULT NULL,
  `id_finger` int(11) DEFAULT NULL,
  `enroll_finger_status` varchar(20) DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `user_id`, `nama`, `nis`, `tgl_lahir`, `kelas_id`, `no_wa`, `wa_ortu`, `uid_rfid`, `created_at`, `updated_at`, `enroll_status`, `id_finger`, `enroll_finger_status`) VALUES
(324, 221, 'Aditya Rusliano Akbar', '2501001', '2009-04-20', 1, '6285809366808', NULL, '4DBF8A06', '2026-01-02 07:49:46', '2026-01-07 00:50:57', 'done', NULL, 'none'),
(325, 222, 'Ahmad Muzaki', '2501002', '2009-05-15', 1, NULL, '6285383827211', '3D517106', '2026-01-02 07:49:46', '2026-01-07 09:53:42', 'done', NULL, 'none'),
(326, 223, 'Arta Kusuma', '2501003', '2009-12-08', 1, NULL, NULL, '8D8B7F06', '2026-01-02 07:49:46', '2026-01-05 09:04:22', 'done', NULL, 'none'),
(327, 224, 'Brenda Zaskia R', '2502004', '2009-10-01', 2, '62895322150672', NULL, '5D799406', '2026-01-02 07:49:47', '2026-01-07 01:21:59', 'done', NULL, 'none'),
(328, 225, 'Indra Aprianto', '2501007', '2010-04-05', 1, NULL, NULL, 'FD348A06', '2026-01-02 07:49:47', '2026-01-05 09:04:35', 'done', NULL, 'none'),
(329, 226, 'Nanda Dwi Andi Aritama', '2501008', '2009-08-06', 1, NULL, NULL, NULL, '2026-01-02 07:49:48', '2026-01-02 07:49:48', NULL, NULL, 'none'),
(330, 227, 'Rilly Meilana Wijaya', '2501011', '2010-05-14', 1, '6285384962358', NULL, '9DD08E06', '2026-01-02 07:49:48', '2026-01-06 22:41:03', 'done', NULL, 'none'),
(331, 228, 'Rina Arzeti', '2502012', '2010-03-15', 2, '6285805110953', NULL, 'BDD48D06', '2026-01-02 07:49:48', '2026-01-04 12:41:04', 'done', NULL, 'none'),
(332, 229, 'Tiara Indriyani Sabela', '2502013', '2009-02-21', 2, '6285809285042', NULL, 'AD598806', '2026-01-02 07:49:49', '2026-01-03 13:17:44', 'done', NULL, 'none'),
(333, 230, 'Vanisaul Khoiroh', '2502014', '2010-08-06', 2, NULL, '6285368086407', 'DD099406', '2026-01-02 07:49:49', '2026-01-07 09:52:55', 'done', NULL, 'none'),
(334, 231, 'Viko Afriyan Arbi', '2501015', '2009-06-04', 1, NULL, NULL, '1D6C7D06', '2026-01-02 07:49:50', '2026-01-04 12:40:23', 'done', NULL, 'none'),
(335, 232, 'Adi Abdurachman', '2401001', '2008-05-27', 3, '6281273592988', NULL, '7DC88506', '2026-01-02 07:49:50', '2026-01-03 08:39:45', 'done', NULL, 'none'),
(336, 233, 'Ahmad', '2401002', '2009-07-27', 3, NULL, NULL, NULL, '2026-01-02 07:49:50', '2026-01-07 09:21:35', NULL, NULL, 'none'),
(337, 234, 'Ahmad Agus Salim', '2401003', '2007-08-21', 3, '6285835445980', NULL, '2DD47406', '2026-01-02 07:49:51', '2026-01-03 13:17:17', 'done', NULL, 'none'),
(338, 235, 'Arbai Soliqin', '2401004', '2008-05-29', 3, NULL, NULL, NULL, '2026-01-02 07:49:51', '2026-01-02 19:46:24', 'none', NULL, 'none'),
(339, 236, 'Arista Danu Ansa', '2401005', '2007-02-26', 3, NULL, NULL, NULL, '2026-01-02 07:49:51', '2026-01-03 13:18:50', 'none', NULL, 'none'),
(340, 237, 'Bagus Hermawan', '2401006', '2006-11-25', 3, '6282381393815', NULL, '9D1A9406', '2026-01-02 07:49:51', '2026-01-05 07:53:14', 'done', NULL, 'none'),
(341, 238, 'Deni', '2401007', '2008-09-27', 3, NULL, NULL, NULL, '2026-01-02 07:49:52', '2026-01-02 07:49:52', NULL, NULL, 'none'),
(342, 239, 'Denis Kurniawan', '2401008', '2009-12-13', 3, NULL, NULL, '0DEA9206', '2026-01-02 07:49:52', '2026-01-04 12:40:35', 'done', NULL, 'none'),
(343, 240, 'Dhani Alan Maulana', '2401009', '2009-06-12', 3, '6285809812949', NULL, '0DBE7C06', '2026-01-02 07:49:52', '2026-01-04 12:39:47', 'done', NULL, 'none'),
(344, 241, 'Dhani Muhamad Reza', '2401010', '2007-06-15', 3, '6285702877736', NULL, '9DD47406', '2026-01-02 07:49:53', '2026-01-03 13:21:44', 'done', NULL, 'none'),
(345, 242, 'Dinda Amalia', '2402011', '2009-04-02', 4, NULL, NULL, '9D807106', '2026-01-02 07:49:53', '2026-01-04 12:37:16', 'done', NULL, 'none'),
(346, 243, 'Dwi Sampurna Jaya', '2401012', '2009-09-22', 3, '6285185130325', NULL, 'FD0B9106', '2026-01-02 07:49:53', '2026-01-03 13:19:15', 'done', NULL, 'none'),
(347, 244, 'Fariz Kurniawan', '2401014', '2009-09-26', 3, NULL, NULL, NULL, '2026-01-02 07:49:54', '2026-01-02 07:49:54', NULL, NULL, 'none'),
(348, 245, 'Haris Vino Agusthaan', '2401015', '2008-08-07', 3, NULL, NULL, NULL, '2026-01-02 07:49:54', '2026-01-02 13:37:48', NULL, NULL, 'none'),
(349, 246, 'Junia Sari', '2402016', '2009-06-12', 4, '6287867034856', NULL, '3DDE8E06', '2026-01-02 07:49:54', '2026-01-05 07:53:29', 'done', NULL, 'none'),
(350, 247, 'Keyla Biyan Ramadhani', '2402017', '2008-09-23', 4, NULL, NULL, '9DDF7A06', '2026-01-02 07:49:55', '2026-01-04 12:37:59', 'done', NULL, 'none'),
(351, 248, 'M. Maulana Eri Fernando', '2401018', '2008-03-08', 3, '6285658346895', NULL, '7D1C7606', '2026-01-02 07:49:55', '2026-01-03 12:55:48', 'done', NULL, 'none'),
(352, 249, 'Muhamad Deni Setiawan', '2401019', '2008-07-10', 3, NULL, NULL, NULL, '2026-01-02 07:49:55', '2026-01-02 07:49:55', NULL, NULL, 'none'),
(353, 250, 'Muhammad Haris Ashrori', '2401020', '2008-10-05', 3, NULL, NULL, NULL, '2026-01-02 07:49:56', '2026-01-02 07:49:56', NULL, NULL, 'none'),
(354, 251, 'Muhammad Irsyadul A\'la', '2401021', '2009-03-23', 3, '6285832843310', NULL, NULL, '2026-01-02 07:49:56', '2026-01-06 14:15:51', NULL, NULL, 'none'),
(355, 252, 'Musa', '2401022', '2006-09-06', 3, NULL, NULL, NULL, '2026-01-02 07:49:56', '2026-01-02 07:49:57', NULL, NULL, 'none'),
(356, 253, 'Panji Setia Wardana', '2401023', '2009-06-24', 3, '6285767052478', NULL, 'CD8A8406', '2026-01-02 07:49:57', '2026-01-03 13:20:47', 'done', NULL, 'none'),
(357, 254, 'Ririn Mardiana', '2402024', '2009-03-20', 4, NULL, NULL, '2D958306', '2026-01-02 07:49:57', '2026-01-05 07:55:59', 'done', NULL, 'none'),
(358, 271, 'Adnan Nur Rohim', '2301001', '2007-12-20', 5, '6285960139964', NULL, 'AD679606', '2026-01-02 07:49:57', '2026-01-05 03:53:31', 'done', NULL, 'none'),
(359, 255, 'Aji Irawan', '2301002', '2007-07-03', 5, '6285692153273', NULL, 'ADC67506', '2026-01-02 07:49:58', '2026-01-06 08:24:09', 'done', NULL, 'none'),
(360, 256, 'Akhmad Afandi', '2301003', '2007-12-27', 5, '6287760741578', NULL, 'ED439906', '2026-01-02 07:49:58', '2026-01-03 13:18:11', 'done', NULL, 'none'),
(361, 257, 'Andre Marcel', '2301005', '2007-03-10', 5, '6285761230905', NULL, 'EDCE6E06', '2026-01-02 07:49:58', '2026-01-07 00:48:15', 'done', NULL, 'none'),
(362, 258, 'Arphanca Kun Nugroho', '2301007', '2007-08-28', 5, '6285789525079', NULL, 'CD505D06', '2026-01-02 07:49:59', '2026-01-06 03:55:50', 'done', NULL, 'none'),
(363, 259, 'Ayu Vera Velinia', '2302008', '2008-05-18', 6, '6283847353783', NULL, '9D947C06', '2026-01-02 07:49:59', '2026-01-03 13:20:35', 'done', NULL, 'none'),
(364, 260, 'Davit Mubaidilah', '2301010', '2008-06-16', 5, NULL, NULL, '2DA18C06', '2026-01-02 07:49:59', '2026-01-05 08:43:36', 'done', NULL, 'none'),
(365, 261, 'Dhika Hanafi Rantau', '2301011', '2008-05-01', 5, NULL, NULL, '6D6E8306', '2026-01-02 07:50:00', '2026-01-05 08:40:55', 'done', NULL, 'none'),
(366, 262, 'Fadli Ardiansyah', '2301012', '2008-07-17', 5, '6282294454635', NULL, 'BDD67906', '2026-01-02 07:50:00', '2026-01-03 12:58:28', 'done', NULL, 'none'),
(367, 263, 'Firnando', '2301013', '2008-05-29', 5, '6282280108536', NULL, 'FDEC8306', '2026-01-02 07:50:00', '2026-01-03 13:09:13', 'done', NULL, 'none'),
(368, 264, 'Hanif Dwi Cahyono', '2301014', '2005-04-28', 5, '6281271360260', NULL, '4D9D8506', '2026-01-02 07:50:01', '2026-01-05 07:53:47', 'done', NULL, 'none'),
(369, 265, 'Indah Laras Putri', '2302015', '2007-01-27', 6, '6287768963763', NULL, 'BD5B8906', '2026-01-02 07:50:01', '2026-01-03 13:09:32', 'done', NULL, 'none'),
(370, 266, 'Jepri Maulana', '2301016', '2007-05-05', 5, NULL, NULL, 'ED7B8106', '2026-01-02 07:50:02', '2026-01-06 08:31:15', 'none', NULL, 'none'),
(371, 267, 'Meliana Dwi Irianti', '2302018', '2008-05-11', 6, '6285609555390', NULL, 'BDA97506', '2026-01-02 07:50:02', '2026-01-03 08:50:33', 'done', NULL, 'none'),
(372, 268, 'Novita Dwi Wijayanti', '2302019', '2008-11-14', 6, '6281991814008', NULL, '7DE38706', '2026-01-02 07:50:02', '2026-01-03 13:19:58', 'done', NULL, 'none'),
(373, 269, 'Selviana', '2302020', '2009-10-06', 6, '6285839358970', NULL, '4D3C7E06', '2026-01-02 07:50:03', '2026-01-04 12:41:25', 'done', NULL, 'none'),
(374, 270, 'Zulfi Aulia', '2302021', '2008-03-27', 6, NULL, NULL, '9DC88B06', '2026-01-02 07:50:03', '2026-01-04 12:40:56', 'done', NULL, 'none');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa_fingerprints`
--

CREATE TABLE `siswa_fingerprints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `device_id` bigint(20) UNSIGNED NOT NULL,
  `finger_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa_fingerprints`
--

INSERT INTO `siswa_fingerprints` (`id`, `student_id`, `device_id`, `finger_id`, `created_at`, `updated_at`) VALUES
(2, 180, 7, 90, '2025-12-28 04:46:23', '2025-12-28 04:46:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `teacher_checkout_sessions`
--

CREATE TABLE `teacher_checkout_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `teacher_name` varchar(255) NOT NULL,
  `uid_rfid` varchar(20) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `teacher_checkout_sessions`
--

INSERT INTO `teacher_checkout_sessions` (`id`, `teacher_id`, `teacher_name`, `uid_rfid`, `status`, `created_at`, `expires_at`) VALUES
(25, 11, 'Ahmad Daqiqi Syahrulloh, S.H.', 'DDE26E06', 'open', '2026-01-07 12:18:49', '2026-01-07 12:33:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') DEFAULT 'student',
  `full_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `full_name`, `username`, `created_at`, `reset_token`, `reset_expires`, `updated_at`) VALUES
(1, 'kangdaqiq@gmail.com', '$2y$12$7hwps9Z6OTVQ37DSqgK.c.yGO7gMUigAythjZaGpH07XYnT5WH0ZC', 'admin', 'Ahmad Daqiqi', NULL, '2025-11-17 12:23:27', NULL, NULL, '2025-12-28 23:39:58'),
(221, '2501001@siswa.smkassuniyah.sch.id', '$2y$12$okravwYMnwAGyKee34NYOeablXdu2GMnwp3SZBWb9QzWbtZF/Mr1q', 'student', 'Aditya Rusliano Akbar', '2501001', '2025-12-29 07:19:29', NULL, NULL, '2025-12-29 07:19:29'),
(222, '2501002@siswa.smkassuniyah.sch.id', '$2y$12$YSRHOeEM09QVQ5Sk8STPKOvE0PQREupEQieEQPYfEf5Z.tVOk.4P2', 'student', 'Ahmad Muzaki', '2501002', '2025-12-29 07:19:30', NULL, NULL, '2025-12-29 07:19:30'),
(223, '2501003@siswa.smkassuniyah.sch.id', '$2y$12$MIYjNUA54xzJVuc24Pd0tObsI6rlQ2xMsv935vXLc3HAZB19G5mH.', 'student', 'Arta Kusuma', '2501003', '2025-12-29 07:19:31', NULL, NULL, '2025-12-29 07:19:31'),
(224, '2502004@siswa.smkassuniyah.sch.id', '$2y$12$0tMpMf0h2TmcuSH9q2IvLOPdf1vL852.OttM0syV1OIlJ1TIwFRre', 'student', 'Brenda Zaskia R', '2502004', '2025-12-29 07:19:33', NULL, NULL, '2025-12-29 07:19:33'),
(225, '2501007@siswa.smkassuniyah.sch.id', '$2y$12$8H9yFAB2AiOK3j8uMKfDBeT4NJXhCrBRvjIA1jKGo0/2BIF2Iuw8m', 'student', 'Indra Aprianto', '2501007', '2025-12-29 07:19:34', NULL, NULL, '2025-12-29 07:19:34'),
(226, '2501008@siswa.smkassuniyah.sch.id', '$2y$12$yFLDzr2AECn3k9TX0cFsMuRK/VXOwhKdomJn8nA6OCBNikyVntRBC', 'student', 'Nanda Dwi Andi Aritama', '2501008', '2025-12-29 07:19:35', NULL, NULL, '2025-12-29 07:19:35'),
(227, '2501011@siswa.smkassuniyah.sch.id', '$2y$12$x46k6vvz/ONQtnR0WaroeOmjRkjuU7Q05yR3t07Swa.r2EUKaUxgu', 'student', 'Rilly Meilana Wijaya', '2501011', '2025-12-29 07:19:36', NULL, NULL, '2025-12-29 07:19:36'),
(228, '2502012@siswa.smkassuniyah.sch.id', '$2y$12$7CtCGl4YTj3LEco2hxlSqe2tIzkitt1O4LxsOzobEhU7RgFWupP06', 'student', 'Rina Arzeti', '2502012', '2025-12-29 07:19:37', NULL, NULL, '2025-12-29 07:19:37'),
(229, '2502013@siswa.smkassuniyah.sch.id', '$2y$12$AW1THtIBbZhfFTyL5WobWuTWod7AxG4S3yD2AH70k99T.7guRRddy', 'student', 'Tiara Indriyani Sabela', '2502013', '2025-12-29 07:19:39', NULL, NULL, '2025-12-29 07:19:39'),
(230, '2502014@siswa.smkassuniyah.sch.id', '$2y$12$9mmFjGuYMoOSCcu5VlqMg.2e5swqVacpTDxEFrnEBTTF2WkDYd7aO', 'student', 'Vanisaul Khoiroh', '2502014', '2025-12-29 07:19:40', NULL, NULL, '2025-12-29 07:19:40'),
(231, '2501015@siswa.smkassuniyah.sch.id', '$2y$12$kJxFMKwD2W7.TWw2mVtjq.JmpYw8bLqojMWZby9C0.Wl.K8cgLzPO', 'student', 'Viko Afriyan Arbi', '2501015', '2025-12-29 07:19:41', NULL, NULL, '2025-12-29 07:19:41'),
(232, '2401001@siswa.smkassuniyah.sch.id', '$2y$12$d03jAlgcfjLPLmouE40Gy.4uuwXlPQwc4QfN7uJeuiJWbu0PuK/N6', 'student', 'Adi Abdurachman', '2401001', '2025-12-29 07:19:42', NULL, NULL, '2025-12-29 07:19:42'),
(233, '2401002@siswa.smkassuniyah.sch.id', '$2y$12$ej1lDcbT1LSdVkQeLSU1Punpml0FaSptFGBRxBCUIaAmv0IG2FMd2', 'student', 'Ahmad', '2401002', '2025-12-29 07:19:43', NULL, NULL, '2025-12-29 07:19:43'),
(234, '2401003@siswa.smkassuniyah.sch.id', '$2y$12$sTKg5pB8I4glu3B4eteQp.yXGA5qNnBqdkpSzYKDHMPLKlzwi9IDW', 'student', 'Ahmad Agus Salim', '2401003', '2025-12-29 07:19:44', NULL, NULL, '2025-12-29 07:19:44'),
(235, '2401004@siswa.smkassuniyah.sch.id', '$2y$12$HEbMcl2xvOpP/.ZK7OdTV.b50rxxvA6d0OVczr3fDIXrABOquE.ZK', 'student', 'Arbai Soliqin', '2401004', '2025-12-29 07:19:46', NULL, NULL, '2025-12-29 07:19:46'),
(236, '2401005@siswa.smkassuniyah.sch.id', '$2y$12$WoTpIaFKP0JBbIOn/ptc7u9qxRYNVHlPDa/6prJm08EeSwCa.S1fy', 'student', 'Arista Danu Ansa', '2401005', '2025-12-29 07:19:47', NULL, NULL, '2025-12-29 07:19:47'),
(237, '2401006@siswa.smkassuniyah.sch.id', '$2y$12$JrGTdsx31CeMjYMaPq/WleKEQ6GURE0Gnfs2dfYOr/XqYjSm1h9P6', 'student', 'Bagus Hermawan', '2401006', '2025-12-29 07:19:48', NULL, NULL, '2025-12-29 07:19:48'),
(238, '2401007@siswa.smkassuniyah.sch.id', '$2y$12$LQKLP2YnSb82TtGIqjH1rOBtoOX3fTO.vyIHIOnfX9HAvd4BcaS8S', 'student', 'Deni', '2401007', '2025-12-29 07:19:49', NULL, NULL, '2025-12-29 07:19:49'),
(239, '2401008@siswa.smkassuniyah.sch.id', '$2y$12$DTxXG6BgdkcU.kTtvCdlou4AjqOnSkg7G1MSYbqZq2owvHxKyGDDS', 'student', 'Denis Kurniawan', '2401008', '2025-12-29 07:19:50', NULL, NULL, '2025-12-29 07:19:50'),
(240, '2401009@siswa.smkassuniyah.sch.id', '$2y$12$/0noMjPt2wPn1moXi2vwg.KsEg9k5XJZArs/gK48RKIjaRce.BYwu', 'student', 'Dhani Alan Maulana', '2401009', '2025-12-29 07:19:51', NULL, NULL, '2025-12-29 07:19:51'),
(241, '2401010@siswa.smkassuniyah.sch.id', '$2y$12$r436y7BMTbWm6tuYhU68uOstyllLHLzycjZjXIQ5zW6bvJaYrx3SO', 'student', 'Dhani Muhamad Reza', '2401010', '2025-12-29 07:19:53', NULL, NULL, '2025-12-29 07:19:53'),
(242, '2402011@siswa.smkassuniyah.sch.id', '$2y$12$llHpZEKpCtwyvTaBz5Ql1ekKBt9wA2l5yrz2Le5uTMKWootVzP.BC', 'student', 'Dinda Amalia', '2402011', '2025-12-29 07:19:54', NULL, NULL, '2025-12-29 07:19:54'),
(243, '2401012@siswa.smkassuniyah.sch.id', '$2y$12$s1ZfR8xUj5J5/5eHCFgcjOPd6WuvCnlmIO3/bOCM5paU4orsA0faC', 'student', 'Dwi Sampurna Jaya', '2401012', '2025-12-29 07:19:55', NULL, NULL, '2025-12-29 07:19:55'),
(244, '2401014@siswa.smkassuniyah.sch.id', '$2y$12$3fLRWIiZt.VUr24rkEx7dOhvr5G6.doWb085OA/6Hnna3T18knBQa', 'student', 'Fariz Kurniawan', '2401014', '2025-12-29 07:19:56', NULL, NULL, '2025-12-29 07:19:56'),
(245, '2401015@siswa.smkassuniyah.sch.id', '$2y$12$tgTb/de2iSpGq.mMPWaDVu9wZ7usMobhgXOG/GYe8JXA7SLHUH5SO', 'student', 'Haris Vino Agusthaan', '2401015', '2025-12-29 07:19:57', NULL, NULL, '2025-12-29 07:19:57'),
(246, '2402016@siswa.smkassuniyah.sch.id', '$2y$12$SA0l9EYgGR7lGZwZyl2Im.zKo1lpBXSvq671cuhuDxca8reekLJoW', 'student', 'Junia Sari', '2402016', '2025-12-29 07:19:59', NULL, NULL, '2025-12-29 07:19:59'),
(247, '2402017@siswa.smkassuniyah.sch.id', '$2y$12$sCP8pmd0.PtxsO.kWuQYB.qJquv3yAFf874QiUjyp623kNO3960Mm', 'student', 'Keyla Biyan Ramadhani', '2402017', '2025-12-29 07:20:00', NULL, NULL, '2025-12-29 07:20:00'),
(248, '2401018@siswa.smkassuniyah.sch.id', '$2y$12$j8SU20gbECccH.dNDg0hxOdZmuKBwgSvleKOjRw1kzDXnY2p6s.qq', 'student', 'M. Maulana Eri Fernando', '2401018', '2025-12-29 07:20:01', NULL, NULL, '2025-12-29 07:20:01'),
(249, '2401019@siswa.smkassuniyah.sch.id', '$2y$12$OMahBoGj3U2M9ocBuUDyj.HKKM9fMZ4.JguadmEZBydTgs7kvghzy', 'student', 'Muhamad Deni Setiawan', '2401019', '2025-12-29 07:20:02', NULL, NULL, '2025-12-29 07:20:02'),
(250, '2401020@siswa.smkassuniyah.sch.id', '$2y$12$qNhKXWj5GMwqRgpMo2W.HuMOzxnJ4Wxj/xQvDBv54l3WRAxRZuhBa', 'student', 'Muhammad Haris Ashrori', '2401020', '2025-12-29 07:20:04', NULL, NULL, '2025-12-29 07:20:04'),
(251, '2401021@siswa.smkassuniyah.sch.id', '$2y$12$ear/VcevrwbPQejVd8Xzlemj0sLhVVJb95muapPxaCS7hv5B3J2mW', 'student', 'Muhammad Irsyadul A\'la', '2401021', '2025-12-29 07:20:05', NULL, NULL, '2025-12-29 07:20:05'),
(252, '2401022@siswa.smkassuniyah.sch.id', '$2y$12$sYSPs.XGnrQQKm4xIuu9ue8CfkMKbKKGPVYBfqw1X9XBTzFtmZ2Le', 'student', 'Musa', '2401022', '2025-12-29 07:20:06', NULL, NULL, '2025-12-29 07:20:06'),
(253, '2401023@siswa.smkassuniyah.sch.id', '$2y$12$Kz645IUKhNqABC71gZ5GQu93cYxthw6DRY/V/tw/7V5fAF3SXzJle', 'student', 'Panji Setia Wardana', '2401023', '2025-12-29 07:20:07', NULL, NULL, '2025-12-29 07:20:07'),
(254, '2402024@siswa.smkassuniyah.sch.id', '$2y$12$8mQe4a0sQ20YdEaqJW8exOxRw9GW2YG1vd2Pw8ls5HGfvhHTQA6ki', 'student', 'Ririn Mardiana', '2402024', '2025-12-29 07:20:09', NULL, NULL, '2025-12-29 07:20:09'),
(255, '2301002@siswa.smkassuniyah.sch.id', '$2y$12$4lLrVjN38ZeiGYRc/kEi1uh8mvrYPejin.C4hFtiZVTb79rrxY4bK', 'student', 'Aji Irawan', '2301002', '2025-12-29 07:20:10', NULL, NULL, '2025-12-29 07:20:10'),
(256, '2301003@siswa.smkassuniyah.sch.id', '$2y$12$HAz.08lEhaQsLHPPy//i7.bHkWiBatMqFy7cY9p6VgEa.5Pz71wf.', 'student', 'Akhmad Afandi', '2301003', '2025-12-29 07:20:11', NULL, NULL, '2025-12-29 07:20:11'),
(257, '2301005@siswa.smkassuniyah.sch.id', '$2y$12$RUJIldf4q.m6sSJrbgXkEeakn8.eLKN3ZaxRKwB/gMQedJ/wn3FCu', 'student', 'Andre Marcel', '2301005', '2025-12-29 07:20:12', NULL, NULL, '2025-12-29 07:20:12'),
(258, '2301007@siswa.smkassuniyah.sch.id', '$2y$12$/piOuIxDedqb0qhSa/XUmOe4A3ZZBFgTRvuNDMPb/Z/A3LfoXkTXm', 'student', 'Arphanca Kun Nugroho', '2301007', '2025-12-29 07:20:13', NULL, NULL, '2025-12-29 07:20:13'),
(259, '2302008@siswa.smkassuniyah.sch.id', '$2y$12$pB/ez7gPIkDRyeFRO6Z/YuEdYsPthrOJ12JEpkqw56JXjAe4986oW', 'student', 'Ayu Vera Velinia', '2302008', '2025-12-29 07:20:14', NULL, NULL, '2025-12-29 07:20:14'),
(260, '2301010@siswa.smkassuniyah.sch.id', '$2y$12$vB87KrsvQ/1PyxposxKJg.ro.hx0X.LYjqKkUjPPEC4BqKVlcATfi', 'student', 'Davit Mubaidilah', '2301010', '2025-12-29 07:20:15', NULL, NULL, '2025-12-29 07:20:15'),
(261, '2301011@siswa.smkassuniyah.sch.id', '$2y$12$3R8MS3VK8e7jcT.CTYmru.AypJIlw7OgdguSUmWR0TDacWce5nFbO', 'student', 'Dhika Hanafi Rantau', '2301011', '2025-12-29 07:20:17', NULL, NULL, '2025-12-29 07:20:17'),
(262, '2301012@siswa.smkassuniyah.sch.id', '$2y$12$QfPg/PUSEgPEIwKQBl2mh.z5zRZtYUUlQYwXo2XaE5ELW2kO9EvAu', 'student', 'Fadli Ardiansyah', '2301012', '2025-12-29 07:20:18', NULL, NULL, '2025-12-29 07:20:18'),
(263, '2301013@siswa.smkassuniyah.sch.id', '$2y$12$HskAKJByt8vRZZ2e3eae8ukOmfSDhugzCAm83KrB/hekmaT/wfOwa', 'student', 'Firnando', '2301013', '2025-12-29 07:20:19', NULL, NULL, '2025-12-29 07:20:19'),
(264, '2301014@siswa.smkassuniyah.sch.id', '$2y$12$pQI4Icn3liiJaiMD19rKlOlrFC.VjFHBVS.kk7VGGOkb8fzjniTnq', 'student', 'Hanif Dwi Cahyono', '2301014', '2025-12-29 07:20:20', NULL, NULL, '2025-12-29 07:20:20'),
(265, '2302015@siswa.smkassuniyah.sch.id', '$2y$12$S1ugHyhhpcwQtZfgQ7ObweV7ymGMc2Q0v4uCjO.LqVTnnlDRB/bza', 'student', 'Indah Laras Putri', '2302015', '2025-12-29 07:20:21', NULL, NULL, '2025-12-29 07:20:21'),
(266, '2301016@siswa.smkassuniyah.sch.id', '$2y$12$mQmHy2PvHMcuu2PIf/k2z.2jA7h5vJfK4VHfy8tVVktHG8DEc0hrG', 'student', 'Jepri Maulana', '2301016', '2025-12-29 07:20:22', NULL, NULL, '2025-12-29 07:20:22'),
(267, '2302018@siswa.smkassuniyah.sch.id', '$2y$12$F/P7ZUk2VJggOyeIqJ5tKe.M.As6Gik27Ic4xkl7CFq6aJR2hPIw.', 'student', 'Meliana Dwi Irianti', '2302018', '2025-12-29 07:20:23', NULL, NULL, '2025-12-29 07:20:23'),
(268, '2302019@siswa.smkassuniyah.sch.id', '$2y$12$QMblk2CQhBan0guMEWef/uByP0FJJ57s78DNGJvl8U9oVyrX6qM4u', 'student', 'Novita Dwi Wijayanti', '2302019', '2025-12-29 07:20:25', NULL, NULL, '2025-12-29 07:20:25'),
(269, '2302020@siswa.smkassuniyah.sch.id', '$2y$12$U05l5pVjLpXpDCIWyyvSXe5TmGsFDA7eRZB9vtbhHiE6.eLHHyGse', 'student', 'Selviana', '2302020', '2025-12-29 07:20:26', NULL, NULL, '2025-12-29 07:20:26'),
(270, '2302021@siswa.smkassuniyah.sch.id', '$2y$12$04uNivjbD0ily/xurm4eBuwzHoPw4Dg0wQculHyd../gelAaxeJGO', 'student', 'Zulfi Aulia', '2302021', '2025-12-29 07:20:27', NULL, NULL, '2025-12-29 07:20:27'),
(271, '2301001@siswa.smkassuniyah.sch.id', '$2y$12$l7RkbSy6gfvqPZvBnPQgHOk3BJqPFoQvC7cjXDqlazQ8yh6XLV3Di', 'student', 'Adnan Nur Rohim', '2301001', '2025-12-29 08:28:47', NULL, NULL, '2025-12-29 08:28:47'),
(272, '24010021@siswa.smkassuniyah.sch.id', '$2y$12$wv.e4Jfmibn8j5YGDQbM2eYILeekxxeV.fjz8DknqTnWlGLy/zBZa', 'student', 'Andi Wijaya', '24010021', '2026-01-07 09:22:03', NULL, NULL, '2026-01-07 09:22:03');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `absensi_guru_jadwal_pelajaran_id_tanggal_unique` (`jadwal_pelajaran_id`,`tanggal`),
  ADD KEY `absensi_guru_guru_id_foreign` (`guru_id`);

--
-- Indeks untuk tabel `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`);

--
-- Indeks untuk tabel `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_api_key_time` (`api_key`,`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indeks untuk tabel `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_date` (`student_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_status` (`status`);

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
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indeks untuk tabel `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_guru_uid` (`uid_rfid`);

--
-- Indeks untuk tabel `guru_fingerprints`
--
ALTER TABLE `guru_fingerprints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guru_fingerprints_device_id_finger_id_unique` (`device_id`,`finger_id`),
  ADD KEY `guru_fingerprints_guru_id_index` (`guru_id`),
  ADD KEY `guru_fingerprints_device_id_index` (`device_id`);

--
-- Indeks untuk tabel `hari_libur`
--
ALTER TABLE `hari_libur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hari_libur_tanggal_unique` (`tanggal`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwal_pelajaran_guru_id_foreign` (`guru_id`),
  ADD KEY `jadwal_pelajaran_kelas_id_foreign` (`kelas_id`),
  ADD KEY `jadwal_pelajaran_mapel_id_foreign` (`mapel_id`);

--
-- Indeks untuk tabel `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `kelas_wali_kelas_id_foreign` (`wali_kelas_id`);

--
-- Indeks untuk tabel `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `message_queues`
--
ALTER TABLE `message_queues`
  ADD PRIMARY KEY (`id`);

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
-- Indeks untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indeks untuk tabel `report_groups`
--
ALTER TABLE `report_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `scan_history`
--
ALTER TABLE `scan_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid_time` (`uid`,`created_at`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_user_id_index` (`user_id`);

--
-- Indeks untuk tabel `siswa_fingerprints`
--
ALTER TABLE `siswa_fingerprints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fingerprint` (`student_id`,`device_id`,`finger_id`);

--
-- Indeks untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_teacher` (`teacher_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi_guru`
--
ALTER TABLE `absensi_guru`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=832;

--
-- AUTO_INCREMENT untuk tabel `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=435;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `guru`
--
ALTER TABLE `guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `guru_fingerprints`
--
ALTER TABLE `guru_fingerprints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `hari_libur`
--
ALTER TABLE `hari_libur`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT untuk tabel `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `message_queues`
--
ALTER TABLE `message_queues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `report_groups`
--
ALTER TABLE `report_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `scan_history`
--
ALTER TABLE `scan_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=807;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=376;

--
-- AUTO_INCREMENT untuk tabel `siswa_fingerprints`
--
ALTER TABLE `siswa_fingerprints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD CONSTRAINT `absensi_guru_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_guru_jadwal_pelajaran_id_foreign` FOREIGN KEY (`jadwal_pelajaran_id`) REFERENCES `jadwal_pelajaran` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  ADD CONSTRAINT `jadwal_pelajaran_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_pelajaran_kelas_id_foreign` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_pelajaran_mapel_id_foreign` FOREIGN KEY (`mapel_id`) REFERENCES `mapel` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_wali_kelas_id_foreign` FOREIGN KEY (`wali_kelas_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  ADD CONSTRAINT `teacher_checkout_sessions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
