-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Des 2025 pada 00.45
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
-- Database: `absen`
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

--
-- Dumping data untuk tabel `absensi_guru`
--

INSERT INTO `absensi_guru` (`id`, `guru_id`, `jadwal_pelajaran_id`, `tanggal`, `waktu_hadir`, `status`, `created_at`, `updated_at`) VALUES
(3, 3, 4, '2025-12-28', '2025-12-28 08:56:27', 'Hadir', '2025-12-28 01:56:27', '2025-12-28 01:56:27'),
(4, 3, 6, '2025-12-28', '2025-12-28 10:59:07', 'Hadir', '2025-12-28 03:59:07', '2025-12-28 03:59:07');

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
(10, 'S', 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'rfid', 1, '2025-12-28 17:28:11', '2025-12-28 09:38:21');

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
(279, 'L2PdoTrqyUMfMmUXCZTAr7sPjAl3aChVA9FoCZBx5J3PEbujILxTqwm1a2mS', 'gagal', '8D7E8F06', 0, 'Jadwal Kosong', '192.168.1.196', 'ESP8266HTTPClient', '2025-12-28 17:28:11');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `total_seconds`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(90, 1, '2025-12-17', '21:39:00', NULL, 0, '', 'Telat 0 jam 39 menit', '2025-12-17 21:39:00', '2025-12-17 21:39:00'),
(91, 5, '2025-12-17', '21:49:27', NULL, 0, '', 'Telat 0 jam 49 menit', '2025-12-17 21:49:27', '2025-12-17 21:49:27'),
(93, 1, '2025-12-18', '09:33:25', NULL, 0, 'H', 'Telat 0 jam 33 menit', '2025-12-18 09:33:25', '2025-12-18 09:33:25'),
(94, 5, '2025-12-18', '09:33:49', NULL, 0, 'H', 'Telat 0 jam 33 menit', '2025-12-18 09:33:49', '2025-12-18 09:33:49'),
(101, 1, '2025-12-19', '11:18:13', '11:21:39', 206, 'H', 'Telat 0 jam 18 menit', '2025-12-19 11:18:13', '2025-12-19 11:21:39'),
(102, 5, '2025-12-19', '09:00:00', '11:50:26', 7200, 'H', 'Langsung absen pulang', '2025-12-19 11:50:26', '2025-12-19 11:50:26'),
(103, 13, '2025-12-20', '09:10:09', '09:28:43', 1114, 'H', 'Telat 2 jam 10 menit', '2025-12-20 09:10:09', '2025-12-20 09:28:43'),
(111, 13, '2025-12-21', '18:15:59', NULL, 0, 'H', 'Telat 2 jam 15 menit', '2025-12-21 18:15:59', '2025-12-21 18:15:59'),
(112, 14, '2025-12-21', '18:16:09', '18:20:59', 290, 'H', 'Telat 2 jam 16 menit', '2025-12-21 18:16:09', '2025-12-21 18:20:59'),
(113, 15, '2025-12-21', '18:16:11', NULL, 0, 'H', 'Telat 2 jam 16 menit', '2025-12-21 18:16:11', '2025-12-21 18:16:11'),
(115, 13, '2025-12-22', '06:50:47', NULL, 0, 'H', NULL, '2025-12-22 06:50:47', '2025-12-22 06:50:47'),
(116, 180, '2025-12-25', NULL, NULL, 0, 'H', NULL, '2025-12-25 13:48:34', '2025-12-25 13:48:34'),
(117, 180, '2025-12-26', '19:03:00', '20:03:00', 0, 'H', NULL, '2025-12-26 05:04:26', '2025-12-26 11:55:38'),
(118, 191, '2025-12-28', '17:25:49', '17:26:55', -66, 'H', 'Telat 1 jam 25 menit', '2025-12-28 17:25:49', '2025-12-28 17:26:55');

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
  `nip` varchar(50) NOT NULL,
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
(3, 'SOFI NUR HABIBAH', '', '34545345', '3', 'DDE26E06', '2025-11-18 01:41:07', 'done', 'done', '2025-12-28 10:26:47');

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
(1, 'Senin', 1, 1, '07:00:00', '15:00:00', 15, NULL, NULL),
(2, 'Selasa', 2, 1, '07:00:00', '15:00:00', 15, NULL, NULL),
(3, 'Rabu', 3, 1, '07:00:00', '15:00:00', 15, NULL, NULL),
(4, 'Kamis', 4, 1, '07:00:00', '15:00:00', 15, NULL, NULL),
(5, 'Jumat', 5, 1, '07:00:00', '11:30:00', 15, NULL, NULL),
(6, 'Sabtu', 6, 1, '07:00:00', '13:00:00', 15, NULL, NULL),
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

--
-- Dumping data untuk tabel `jadwal_pelajaran`
--

INSERT INTO `jadwal_pelajaran` (`id`, `guru_id`, `kelas_id`, `mapel_id`, `hari`, `jam_mulai`, `jam_selesai`, `created_at`, `updated_at`) VALUES
(4, 3, 2, 2, 'Minggu', '08:50:00', '09:56:00', '2025-12-28 01:56:11', '2025-12-28 01:56:11'),
(5, 3, 1, 2, 'Senin', '09:02:00', '10:03:00', '2025-12-28 02:02:53', '2025-12-28 02:02:53'),
(6, 3, 4, 1, 'Minggu', '11:04:00', '12:04:00', '2025-12-28 02:03:09', '2025-12-28 02:03:09');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `created_at`) VALUES
(1, 'X TSM', '2025-11-17 14:09:10'),
(2, 'X TB', '2025-11-17 14:09:10'),
(3, 'XI TSM', '2025-11-17 14:09:10'),
(4, 'XI TB', '2025-11-17 14:09:10'),
(5, 'XII TSM', '2025-12-22 04:35:02'),
(6, 'XII TB', '2025-12-22 04:35:02');

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

--
-- Dumping data untuk tabel `mapel`
--

INSERT INTO `mapel` (`id`, `nama_mapel`, `created_at`, `updated_at`) VALUES
(1, 'TEST-SUBJECT', '2025-12-28 01:03:44', '2025-12-28 01:03:44'),
(2, 'SEP-SUBJECT', '2025-12-28 01:09:32', '2025-12-28 01:09:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `message_queues`
--

CREATE TABLE `message_queues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phone_number` varchar(60) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `message_queues`
--

INSERT INTO `message_queues` (`id`, `phone_number`, `message`, `status`, `attempts`, `created_at`, `updated_at`) VALUES
(1, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'pending', 0, '2025-12-28 00:25:30', '2025-12-28 00:25:30'),
(2, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1 (Device #4)`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'pending', 0, '2025-12-28 00:30:38', '2025-12-28 00:30:38'),
(3, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1 (Device #7)`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'pending', 0, '2025-12-28 00:45:40', '2025-12-28 00:45:40'),
(4, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Sidik Jari Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `ID #1 (Device #7)`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'pending', 0, '2025-12-28 00:55:34', '2025-12-28 00:55:34'),
(5, '6281524824563@s.whatsapp.net', '✅ Absen Masuk Berhasil\n\nHalo *Dwi Sampurna Jaya*,\n\n📅 Tanggal: 28/12/2025\n� Jam Masuk: 17:25\n📊 Status: Terlambat\n📝 Keterangan: Telat 1 jam 25 menit\n\nSelamat belajar! 📚\n\nJangan lupa absen pulang ya!', 'pending', 0, '2025-12-28 10:25:49', '2025-12-28 10:25:49'),
(6, '6234545345@s.whatsapp.net', '✨ *PENDAFTARAN BERHASIL* ✨\n\nHalo *SOFI NUR HABIBAH* 👋,\n\nKartu/Perangkat *Kartu Guru* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n🆔 ID Kartu: `DDE26E06`\n📅 Tanggal: Sunday, 28 December 2025\n\n_Terima kasih telah melakukan registrasi._ 🙏', 'pending', 0, '2025-12-28 10:26:47', '2025-12-28 10:26:47'),
(7, '6281524824563@s.whatsapp.net', '🏠 Absen Pulang Berhasil\n\nHalo *Dwi Sampurna Jaya*,\n\n📍 Jam Masuk: 17:25\n� Jam Pulang: 17:26\n⏱️ Durasi: -1 jam -2 menit\n� Diizinkan oleh: SOFI NUR HABIBAH\n\nTerima kasih telah mengikuti kegiatan hari ini.\n\nHati-hati di jalan! 🙏', 'pending', 0, '2025-12-28 10:26:55', '2025-12-28 10:26:55');

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
(18, '2025_12_28_075753_create_teacher_schedule_tables', 9);

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
(1, 'A', '120363421672356407@g.us', 1, '2025-12-28 06:14:08', '2025-12-28 06:14:08');

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
(214, '39071778', '2025-12-21 17:51:19'),
(215, '39071778', '2025-12-21 17:51:27'),
(217, '39071778', '2025-12-21 17:51:35'),
(218, '39071778', '2025-12-21 17:51:39'),
(221, '39071778', '2025-12-21 18:06:34'),
(223, '39071778', '2025-12-21 18:06:40'),
(230, '39071778', '2025-12-21 18:16:11'),
(232, '39071778', '2025-12-21 18:16:16'),
(239, '39071778', '2025-12-22 06:48:42'),
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
(257, '8D7E8F06', '2025-12-28 17:27:41'),
(258, '8D7E8F06', '2025-12-28 17:27:53'),
(259, '8D7E8F06', '2025-12-28 17:28:11'),
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
(254, 'DDE26E06', '2025-12-28 17:26:47'),
(255, 'DDE26E06', '2025-12-28 17:26:51'),
(246, 'FD0B9106', '2025-12-28 17:23:24'),
(247, 'FD0B9106', '2025-12-28 17:23:26'),
(248, 'FD0B9106', '2025-12-28 17:23:45'),
(249, 'FD0B9106', '2025-12-28 17:23:50'),
(250, 'FD0B9106', '2025-12-28 17:25:22'),
(251, 'FD0B9106', '2025-12-28 17:25:49'),
(252, 'FD0B9106', '2025-12-28 17:26:01'),
(253, 'FD0B9106', '2025-12-28 17:26:06'),
(256, 'FD0B9106', '2025-12-28 17:26:55');

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
('alamat_sekolah', 'Jalan Murnijaya, Desa Murnijaya, Kec. Tumijajar, Kab. Tulang Bawang Barat, Lampung', '2025-12-26 13:33:53'),
('alamat_ttd', 'Tumijajar', '2025-12-26 13:33:53'),
('checkout_tolerance_minutes', '15', '2025-12-28 05:00:17'),
('enable_checkout_attendance', 'true', CURRENT_TIMESTAMP),
('last_daily_report_date', NULL, '2025-12-24 06:53:10'),
('nama_sekolah', 'SMK Assuniyah Tumijajar', '2025-12-26 13:32:12'),
('report_target_jid', '120363421672356407@g.us', '2025-12-24 06:53:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `nis` varchar(50) NOT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
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

INSERT INTO `siswa` (`id`, `user_id`, `nama`, `nis`, `kelas_id`, `no_wa`, `uid_rfid`, `created_at`, `updated_at`, `enroll_status`, `id_finger`, `enroll_finger_status`) VALUES
(169, 103, 'Aditya Rusliano Akbar', '2501001', 1, '62845212211', '', '2025-12-22 04:43:21', '2025-12-28 23:42:16', NULL, NULL, 'none'),
(170, 104, 'Ahmad Muzaki', '2501002', 1, NULL, '', '2025-12-22 04:43:21', '2025-12-28 23:42:16', NULL, NULL, 'none'),
(171, 105, 'Arta Kusuma', '2501003', 1, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:17', NULL, NULL, 'none'),
(172, 106, 'Brenda Zaskia R', '2502004', 2, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:17', NULL, NULL, 'none'),
(173, 107, 'Indra Aprianto', '2501007', 1, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:17', NULL, NULL, 'none'),
(174, 108, 'Nanda Dwi Andi Aritama', '2501008', 1, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:17', NULL, NULL, 'none'),
(175, 109, 'Rilly Meilana Wijaya', '2501011', 1, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:18', NULL, NULL, 'none'),
(176, 110, 'Rina Arzeti', '2502012', 2, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:18', NULL, NULL, 'none'),
(177, 111, 'Tiara Indriyani Sabela', '2502013', 2, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:18', NULL, NULL, 'none'),
(178, 112, 'Vanisaul Khoiroh', '2502014', 2, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:19', NULL, NULL, 'none'),
(179, 113, 'Viko Afriyan Arbi', '2501015', 1, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:19', NULL, NULL, 'none'),
(180, 114, 'Adi Abdurachman', '2401001', 3, '6281524824563', NULL, '2025-12-22 04:43:21', '2025-12-28 23:42:19', NULL, 90, 'done'),
(181, 115, 'Ahmad', '2401002', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:19', NULL, NULL, 'none'),
(182, 116, 'Ahmad Agus Salim', '2401003', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:20', NULL, NULL, 'none'),
(183, 117, 'Arbai Soliqin', '2401004', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:20', NULL, NULL, 'none'),
(184, 118, 'Arista Danu Ansa', '2401005', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:20', NULL, NULL, 'none'),
(185, 119, 'Bagus Hermawan', '2401006', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:21', NULL, NULL, 'none'),
(186, 120, 'Deni', '2401007', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:21', NULL, NULL, 'none'),
(187, 121, 'Denis Kurniawan', '2401008', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:21', NULL, NULL, 'none'),
(188, 122, 'Dhani Alan Maulana', '2401009', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:21', NULL, NULL, 'none'),
(189, 123, 'Dhani Muhamad Reza', '2401010', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:22', NULL, NULL, 'none'),
(190, 124, 'Dinda Amalia', '2402011', 4, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:22', NULL, NULL, 'none'),
(191, 125, 'Dwi Sampurna Jaya', '2401012', 3, '081524824563', 'FD0B9106', '2025-12-22 04:43:21', '2025-12-28 23:42:22', 'done', NULL, 'none'),
(192, 126, 'Fariz Kurniawan', '2401014', 3, '', '8D7E8F06', '2025-12-22 04:43:21', '2025-12-28 23:42:22', 'done', NULL, 'none'),
(193, 127, 'Haris Vino Agusthaan', '2401015', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:23', NULL, NULL, 'none'),
(194, 128, 'Junia Sari', '2402016', 4, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:23', NULL, NULL, 'none'),
(195, 129, 'Keyla Biyan Ramadhani', '2402017', 4, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:23', NULL, NULL, 'none'),
(196, 130, 'M. Maulana Eri Fernando', '2401018', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:24', NULL, NULL, 'none'),
(197, 131, 'Muhamad Deni Setiawan', '2401019', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:24', NULL, NULL, 'none'),
(198, 132, 'Muhammad Haris Ashrori', '2401020', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:24', NULL, NULL, 'none'),
(199, 133, 'Muhammad Irsyadul A\'la', '2401021', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:24', NULL, NULL, 'none'),
(200, 134, 'Musa', '2401022', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:25', NULL, NULL, 'none'),
(201, 135, 'Panji Setia Wardana', '2401023', 3, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:25', NULL, NULL, 'none'),
(202, 136, 'Ririn Mardiana', '2402024', 4, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:25', NULL, NULL, 'none'),
(204, 137, 'Aji Irawan', '2301002', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:26', NULL, NULL, 'none'),
(205, 138, 'Akhmad Afandi', '2301003', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:26', NULL, NULL, 'none'),
(206, 139, 'Andre Marcel', '2301005', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:26', NULL, NULL, 'none'),
(207, 140, 'Arphanca Kun Nugroho', '2301007', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:26', NULL, NULL, 'none'),
(208, 141, 'Ayu Vera Velinia', '2302008', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:27', NULL, NULL, 'none'),
(209, 142, 'Davit Mubaidilah', '2301010', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:27', NULL, NULL, 'none'),
(210, 143, 'Dhika Hanafi Rantau', '2301011', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:27', NULL, NULL, 'none'),
(211, 144, 'Fadli Ardiansyah', '2301012', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:27', NULL, NULL, 'none'),
(212, 145, 'Firnando', '2301013', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:28', NULL, NULL, 'none'),
(213, 146, 'Hanif Dwi Cahyono', '2301014', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:28', NULL, NULL, 'none'),
(214, 147, 'Indah Laras Putri', '2302015', 6, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:28', NULL, NULL, 'none'),
(215, 148, 'Jepri Maulana', '2301016', 5, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:29', NULL, NULL, 'none'),
(216, 149, 'Meliana Dwi Irianti', '2302018', 6, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:29', NULL, NULL, 'none'),
(217, 150, 'Novita Dwi Wijayanti', '2302019', 6, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:29', NULL, NULL, 'none'),
(218, 151, 'Selviana', '2302020', 6, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:29', NULL, NULL, 'none'),
(219, 152, 'Zulfi Aulia', '2302021', 6, '', '', '2025-12-22 04:43:21', '2025-12-28 23:42:30', NULL, NULL, 'none');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `teacher_checkout_sessions`
--

INSERT INTO `teacher_checkout_sessions` (`id`, `teacher_id`, `teacher_name`, `uid_rfid`, `created_at`, `expires_at`) VALUES
(13, 3, 'SOFI NUR HABIBAH', 'DDE26E06', '2025-12-28 10:26:51', '2025-12-28 10:56:51');

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
(103, '2501001@siswa.smkassuniyah.sch.id', '$2y$12$juDCF0IwCDC6etraGEzfhOTkfYw8SgizMhyVbFwpqaD79q7l9F1Ou', 'student', 'Aditya Rusliano Akbar', '2501001', '2025-12-28 23:42:16', NULL, NULL, '2025-12-28 23:42:16'),
(104, '2501002@siswa.smkassuniyah.sch.id', '$2y$12$aju0/lhZ8jKVo3B6zn8fve84XVwDHbByRyBOC3oYRpQkt1FXaHLLS', 'student', 'Ahmad Muzaki', '2501002', '2025-12-28 23:42:16', NULL, NULL, '2025-12-28 23:42:16'),
(105, '2501003@siswa.smkassuniyah.sch.id', '$2y$12$REpGqXVhUKnjE3Xe6m/6D./PtROv8wj4Tiw1aqjExceypL7Y.24qm', 'student', 'Arta Kusuma', '2501003', '2025-12-28 23:42:17', NULL, NULL, '2025-12-28 23:42:17'),
(106, '2502004@siswa.smkassuniyah.sch.id', '$2y$12$PImsBdFPwie7.KWP0k.43eBTGY7XRqTWEVHV297dN8i1Rz72IaEP.', 'student', 'Brenda Zaskia R', '2502004', '2025-12-28 23:42:17', NULL, NULL, '2025-12-28 23:42:17'),
(107, '2501007@siswa.smkassuniyah.sch.id', '$2y$12$Gk/nK7vVw9jRZVdNRB9yMuDQk6u7LMZyQ/AR94zFI/mONnNG.stOW', 'student', 'Indra Aprianto', '2501007', '2025-12-28 23:42:17', NULL, NULL, '2025-12-28 23:42:17'),
(108, '2501008@siswa.smkassuniyah.sch.id', '$2y$12$5XTO0zLbBWqr8bB3.zpKXeZPJduP6YPOUywv53oMMk.Pq3sFA9k3O', 'student', 'Nanda Dwi Andi Aritama', '2501008', '2025-12-28 23:42:17', NULL, NULL, '2025-12-28 23:42:17'),
(109, '2501011@siswa.smkassuniyah.sch.id', '$2y$12$TQJna6QtyiZ3uk9ffF1rxO9WXNQEvyjaHATQhOQXxLVp2AAicHD0O', 'student', 'Rilly Meilana Wijaya', '2501011', '2025-12-28 23:42:18', NULL, NULL, '2025-12-28 23:42:18'),
(110, '2502012@siswa.smkassuniyah.sch.id', '$2y$12$5eX2GFJaP3syMwN/oOG0IuyF5qa8ujYvyf1ZPZTF7iW7iyS34w2cW', 'student', 'Rina Arzeti', '2502012', '2025-12-28 23:42:18', NULL, NULL, '2025-12-28 23:42:18'),
(111, '2502013@siswa.smkassuniyah.sch.id', '$2y$12$M1XpnQYE3r7KaWfspj3.mupmM0Y0ZgSQ/IizlSPdv14M4ZBje91b.', 'student', 'Tiara Indriyani Sabela', '2502013', '2025-12-28 23:42:18', NULL, NULL, '2025-12-28 23:42:18'),
(112, '2502014@siswa.smkassuniyah.sch.id', '$2y$12$.4ZzaIf/FGjyF/dD1EJgNeplzfK.xSb00se7yhAgxd3MhmX8hfB/m', 'student', 'Vanisaul Khoiroh', '2502014', '2025-12-28 23:42:19', NULL, NULL, '2025-12-28 23:42:19'),
(113, '2501015@siswa.smkassuniyah.sch.id', '$2y$12$ShCX9WJU0zpjLgJ9I9ksyeIEqtZUWCrp7u32VUfPED2xLKZ2XNVKG', 'student', 'Viko Afriyan Arbi', '2501015', '2025-12-28 23:42:19', NULL, NULL, '2025-12-28 23:42:19'),
(114, '2401001@siswa.smkassuniyah.sch.id', '$2y$12$/ge7JF5my.bA3bEX0TjbQ.RRr6DuocLBGs3Bf6jm.tDmlzD1j//Ya', 'student', 'Adi Abdurachman', '2401001', '2025-12-28 23:42:19', NULL, NULL, '2025-12-28 23:42:19'),
(115, '2401002@siswa.smkassuniyah.sch.id', '$2y$12$smBuxFioXEB2q7a//cZynO4ddhFYGbzC/kYatp8AVofLvTzSEsupe', 'student', 'Ahmad', '2401002', '2025-12-28 23:42:19', NULL, NULL, '2025-12-28 23:42:19'),
(116, '2401003@siswa.smkassuniyah.sch.id', '$2y$12$Mr2v113LmfMAdQktcU5RzOc4X4aWSn4OxcnZct3Jb5ZxJ/SiIyfMS', 'student', 'Ahmad Agus Salim', '2401003', '2025-12-28 23:42:20', NULL, NULL, '2025-12-28 23:42:20'),
(117, '2401004@siswa.smkassuniyah.sch.id', '$2y$12$RNmMAHcQ.N44bHL33gLOiOuZyMnoLPQ9sEgKKR/4MV1VkbWioJBmC', 'student', 'Arbai Soliqin', '2401004', '2025-12-28 23:42:20', NULL, NULL, '2025-12-28 23:42:20'),
(118, '2401005@siswa.smkassuniyah.sch.id', '$2y$12$as64C0ti1agWIXCOfiz7ZuQZDz.FtOTxloUvWvpzs.Cif3kg.4.tW', 'student', 'Arista Danu Ansa', '2401005', '2025-12-28 23:42:20', NULL, NULL, '2025-12-28 23:42:20'),
(119, '2401006@siswa.smkassuniyah.sch.id', '$2y$12$ziDKDbSzgnv0JbBEQFO.k.xuOnAESjrxOjfGBBVFzaf.SHXcIjfu.', 'student', 'Bagus Hermawan', '2401006', '2025-12-28 23:42:21', NULL, NULL, '2025-12-28 23:42:21'),
(120, '2401007@siswa.smkassuniyah.sch.id', '$2y$12$n4ReeE4BfUbVTUdP1.egVeokjyCTBmtIm.DsFvWFRqdeGYMicE5S.', 'student', 'Deni', '2401007', '2025-12-28 23:42:21', NULL, NULL, '2025-12-28 23:42:21'),
(121, '2401008@siswa.smkassuniyah.sch.id', '$2y$12$iskyap7Eqkne6oJYlY2LR.e/2MQl3G5/81Mi4BnKFCBNChFAwArIW', 'student', 'Denis Kurniawan', '2401008', '2025-12-28 23:42:21', NULL, NULL, '2025-12-28 23:42:21'),
(122, '2401009@siswa.smkassuniyah.sch.id', '$2y$12$I021Mgho/0hTyx2g1OkKTeWRIbh.SUgXM/I4T1wOxR10bjY9qxcWO', 'student', 'Dhani Alan Maulana', '2401009', '2025-12-28 23:42:21', NULL, NULL, '2025-12-28 23:42:21'),
(123, '2401010@siswa.smkassuniyah.sch.id', '$2y$12$DS9HD.047Wtcq/Z5WeBE..z/4Lt.0uxdBhD9xCHskTxBmHoWxqoVi', 'student', 'Dhani Muhamad Reza', '2401010', '2025-12-28 23:42:22', NULL, NULL, '2025-12-28 23:42:22'),
(124, '2402011@siswa.smkassuniyah.sch.id', '$2y$12$5IgIOARmIbyaHvOGyd9sHOBrsjh5dECZq.XKb7gQsK7FDSX6loz3m', 'student', 'Dinda Amalia', '2402011', '2025-12-28 23:42:22', NULL, NULL, '2025-12-28 23:42:22'),
(125, '2401012@siswa.smkassuniyah.sch.id', '$2y$12$NWXKilEBFwyqMLPVnx8tdeCPymTKEOqPalqHDNtyjwd.dBM8surlG', 'student', 'Dwi Sampurna Jaya', '2401012', '2025-12-28 23:42:22', NULL, NULL, '2025-12-28 23:42:22'),
(126, '2401014@siswa.smkassuniyah.sch.id', '$2y$12$10JqPOkJfKkGRByMcYpIgOKySJN8xrnzcYo7kzX3PFt.pK6Zf/ZJy', 'student', 'Fariz Kurniawan', '2401014', '2025-12-28 23:42:22', NULL, NULL, '2025-12-28 23:42:22'),
(127, '2401015@siswa.smkassuniyah.sch.id', '$2y$12$iQUgms9eLCMMmv4xlJRBrOwyUc//EsayIdnf.fNzcZ5y7sfO2CJue', 'student', 'Haris Vino Agusthaan', '2401015', '2025-12-28 23:42:23', NULL, NULL, '2025-12-28 23:42:23'),
(128, '2402016@siswa.smkassuniyah.sch.id', '$2y$12$7QsOQz.Hy5kbZMuJkX0aXuhT57k3Yht7dZHxzenmp10v9.UNmQwCa', 'student', 'Junia Sari', '2402016', '2025-12-28 23:42:23', NULL, NULL, '2025-12-28 23:42:23'),
(129, '2402017@siswa.smkassuniyah.sch.id', '$2y$12$811EMPkHWOrVexvF63IO2.4N6EAiGIx1cLXWEXQlWfrMTR2cVoflu', 'student', 'Keyla Biyan Ramadhani', '2402017', '2025-12-28 23:42:23', NULL, NULL, '2025-12-28 23:42:23'),
(130, '2401018@siswa.smkassuniyah.sch.id', '$2y$12$p74EFvpmtvGwNiHl2iAQZuKbBOfBkWHZE4EXefHYvhhyVisJ.e8yS', 'student', 'M. Maulana Eri Fernando', '2401018', '2025-12-28 23:42:24', NULL, NULL, '2025-12-28 23:42:24'),
(131, '2401019@siswa.smkassuniyah.sch.id', '$2y$12$VgVTDfnL.JB/foocCx6Y3e.lGD51WZ4BqoS/c2M1EJGDWGrhYUaLS', 'student', 'Muhamad Deni Setiawan', '2401019', '2025-12-28 23:42:24', NULL, NULL, '2025-12-28 23:42:24'),
(132, '2401020@siswa.smkassuniyah.sch.id', '$2y$12$0HZY/0fjYzsGE94VkaT4ueD6TcAoOwazzbCCATPSPn/uxe5CKVPf.', 'student', 'Muhammad Haris Ashrori', '2401020', '2025-12-28 23:42:24', NULL, NULL, '2025-12-28 23:42:24'),
(133, '2401021@siswa.smkassuniyah.sch.id', '$2y$12$1fO2A/sXFG9t9r.WmwqU5uF2P8Frkzb4yKjavbMXcr9NoI1T6MfX.', 'student', 'Muhammad Irsyadul A\'la', '2401021', '2025-12-28 23:42:24', NULL, NULL, '2025-12-28 23:42:24'),
(134, '2401022@siswa.smkassuniyah.sch.id', '$2y$12$ATj7btvzydPydDmi1CM2XuS5hrUqX5JZVeaz4S9SPyHZwgkYgVCzq', 'student', 'Musa', '2401022', '2025-12-28 23:42:25', NULL, NULL, '2025-12-28 23:42:25'),
(135, '2401023@siswa.smkassuniyah.sch.id', '$2y$12$JI5Dr8oqMye2CutlBgTnlOLoZ23AP7dWGXybSrZK97eJBPojuYV.y', 'student', 'Panji Setia Wardana', '2401023', '2025-12-28 23:42:25', NULL, NULL, '2025-12-28 23:42:25'),
(136, '2402024@siswa.smkassuniyah.sch.id', '$2y$12$VSumNpO43w89e3Yh5SMnl.Da5eHHQzFcMLN62NanSFv7E84fdE1iO', 'student', 'Ririn Mardiana', '2402024', '2025-12-28 23:42:25', NULL, NULL, '2025-12-28 23:42:25'),
(137, '2301002@siswa.smkassuniyah.sch.id', '$2y$12$.nOnxsmZi8jeoltzXRG9MeaG5VGGSI9jfKj2.Z7lgeFrrfR7CxEpq', 'student', 'Aji Irawan', '2301002', '2025-12-28 23:42:26', NULL, NULL, '2025-12-28 23:42:26'),
(138, '2301003@siswa.smkassuniyah.sch.id', '$2y$12$A4nX7zz/i9Yk4z3mAT734u7UrKY2.zXYZAi368Ud.XiZyJSQO0.Hq', 'student', 'Akhmad Afandi', '2301003', '2025-12-28 23:42:26', NULL, NULL, '2025-12-28 23:42:26'),
(139, '2301005@siswa.smkassuniyah.sch.id', '$2y$12$Ob0Q3yhKGQsju4xLW7ppI.jzRaUGFEaJd3Zr5Fx7TeJFQa6zLXHpy', 'student', 'Andre Marcel', '2301005', '2025-12-28 23:42:26', NULL, NULL, '2025-12-28 23:42:26'),
(140, '2301007@siswa.smkassuniyah.sch.id', '$2y$12$ox4EKruF4fZYK3lWpaOAouUyL4O0e7cuoOvWiS8AK7KTY6It2HwLq', 'student', 'Arphanca Kun Nugroho', '2301007', '2025-12-28 23:42:26', NULL, NULL, '2025-12-28 23:42:26'),
(141, '2302008@siswa.smkassuniyah.sch.id', '$2y$12$Lcv5U9x33rFwUhoxIuHem.dBT4oglx5mvSvu6aPWLf10B6btzQiFC', 'student', 'Ayu Vera Velinia', '2302008', '2025-12-28 23:42:27', NULL, NULL, '2025-12-28 23:42:27'),
(142, '2301010@siswa.smkassuniyah.sch.id', '$2y$12$U7HJUDxc8s.8oHE/azK6/.EzZL1SugDRwnnGK/Qp69QlUFcgKL/uy', 'student', 'Davit Mubaidilah', '2301010', '2025-12-28 23:42:27', NULL, NULL, '2025-12-28 23:42:27'),
(143, '2301011@siswa.smkassuniyah.sch.id', '$2y$12$eV7FAAPtaPhQNFqNTO5qbOTi3tO.PHaBEfvdZxQ0TRpnsUNe5Ydoy', 'student', 'Dhika Hanafi Rantau', '2301011', '2025-12-28 23:42:27', NULL, NULL, '2025-12-28 23:42:27'),
(144, '2301012@siswa.smkassuniyah.sch.id', '$2y$12$sK4M/qoGiSxztuUTFOmvZux5yq4Xc5E5q9HsUCMogy6s.Z6AaofHW', 'student', 'Fadli Ardiansyah', '2301012', '2025-12-28 23:42:27', NULL, NULL, '2025-12-28 23:42:27'),
(145, '2301013@siswa.smkassuniyah.sch.id', '$2y$12$/WTDWP7s6IalUO/fpaPfq.WpTsyojZcCH1tQQovQnPByDa0qy3Mya', 'student', 'Firnando', '2301013', '2025-12-28 23:42:28', NULL, NULL, '2025-12-28 23:42:28'),
(146, '2301014@siswa.smkassuniyah.sch.id', '$2y$12$yrWZriux7uH84uFc5oozwuD/Aos9F16kBbjeMjvYaCIqZU.OTfTB6', 'student', 'Hanif Dwi Cahyono', '2301014', '2025-12-28 23:42:28', NULL, NULL, '2025-12-28 23:42:28'),
(147, '2302015@siswa.smkassuniyah.sch.id', '$2y$12$7ygB217B8NcXvsWXhaisI.arhaz/R/GIeIYUcBFPt3Nk2n3qtOIGG', 'student', 'Indah Laras Putri', '2302015', '2025-12-28 23:42:28', NULL, NULL, '2025-12-28 23:42:28'),
(148, '2301016@siswa.smkassuniyah.sch.id', '$2y$12$414MjS0xCEL29w4QbyaZdObItL1J3M/vQpEiZJhvz7SD10rzouTue', 'student', 'Jepri Maulana', '2301016', '2025-12-28 23:42:29', NULL, NULL, '2025-12-28 23:42:29'),
(149, '2302018@siswa.smkassuniyah.sch.id', '$2y$12$wshnQnHfto98hmVlaXBhMONxliAmtomfTvtjOU2QQxWBA2MqTGq6G', 'student', 'Meliana Dwi Irianti', '2302018', '2025-12-28 23:42:29', NULL, NULL, '2025-12-28 23:42:29'),
(150, '2302019@siswa.smkassuniyah.sch.id', '$2y$12$0zn/g3vybBCJ5YHfAn8DReU.plM0mV23FU5eV0JOZkAt3JE8RDmpa', 'student', 'Novita Dwi Wijayanti', '2302019', '2025-12-28 23:42:29', NULL, NULL, '2025-12-28 23:42:29'),
(151, '2302020@siswa.smkassuniyah.sch.id', '$2y$12$L4vIW4YfE1xhFyBHWsrO8ub4UK2fdPjG2OE2.R4JrzPYtjMQy2tGK', 'student', 'Selviana', '2302020', '2025-12-28 23:42:29', NULL, NULL, '2025-12-28 23:42:29'),
(152, '2302021@siswa.smkassuniyah.sch.id', '$2y$12$fjBFkiuc4TYkonqiYwh9l.VWrug4iTiSdhPpTnkHi1TiBme6D56Y6', 'student', 'Zulfi Aulia', '2302021', '2025-12-28 23:42:30', NULL, NULL, '2025-12-28 23:42:30');

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
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=280;

--
-- AUTO_INCREMENT untuk tabel `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `guru`
--
ALTER TABLE `guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `message_queues`
--
ALTER TABLE `message_queues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `report_groups`
--
ALTER TABLE `report_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `scan_history`
--
ALTER TABLE `scan_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=260;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT untuk tabel `siswa_fingerprints`
--
ALTER TABLE `siswa_fingerprints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

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
-- Ketidakleluasaan untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  ADD CONSTRAINT `teacher_checkout_sessions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
