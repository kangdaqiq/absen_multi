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

 check the manual that corresponds to your MariaDB server version for the right syntax to use near \'FROM siswa \r\n            WHERE uid_rfid = ? \r\n            LIMIT 1\' at line 2', '192.168.100.42', 'ESP8266HTTPClient', '2025-12-18 06:03:20'),
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

 Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 20:20:34'),
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

', 2082858644);

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

 Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoidzBvMnZvMXVPV2hOWTBaQU1ka1RkOFZ2VDNOSkxadVBNVkR1N2hRZSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM5OiJodHRwOi8vMTkyLjE2OC4xLjE5Ny9hYnNlbi9wdWJsaWMvc2lzd2EiO3M6NToicm91dGUiO3M6MTE6InNpc3dhLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1767311274);

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
