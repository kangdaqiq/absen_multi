-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Des 2025 pada 14.18
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
-- Struktur dari tabel `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `api_key` varchar(64) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `api_keys`
--

INSERT INTO `api_keys` (`id`, `name`, `api_key`, `active`, `last_used_at`, `created_at`) VALUES
(3, 'UTAMA', 'PHwNdH7rAiaha0stusgnp9yP', 1, NULL, '2025-12-17 15:12:26'),
(4, 'Device Pintu Utama', 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a', 1, '2025-12-22 19:27:15', '2025-12-17 22:14:26'),
(5, 'Device Lab Komputer', 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 1, '2025-12-24 13:38:56', '2025-12-17 22:14:26');

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
(244, 'a7a003c9a5379eb098d4ad6b2e96ec4b338c21558d25a6e4ce17c2487ff0e862', 'too_early', '11223344', 0, 'Belum dibuka', '::1', 'unknown', '2025-12-24 13:38:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` datetime DEFAULT NULL,
  `jam_pulang` datetime DEFAULT NULL,
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
(90, 1, '2025-12-17', '2025-12-17 21:39:00', NULL, 0, '', 'Telat 0 jam 39 menit', '2025-12-17 21:39:00', '2025-12-17 21:39:00'),
(91, 5, '2025-12-17', '2025-12-17 21:49:27', NULL, 0, '', 'Telat 0 jam 49 menit', '2025-12-17 21:49:27', '2025-12-17 21:49:27'),
(93, 1, '2025-12-18', '2025-12-18 09:33:25', NULL, 0, 'H', 'Telat 0 jam 33 menit', '2025-12-18 09:33:25', '2025-12-18 09:33:25'),
(94, 5, '2025-12-18', '2025-12-18 09:33:49', NULL, 0, 'H', 'Telat 0 jam 33 menit', '2025-12-18 09:33:49', '2025-12-18 09:33:49'),
(101, 1, '2025-12-19', '2025-12-19 11:18:13', '2025-12-19 11:21:39', 206, 'H', 'Telat 0 jam 18 menit', '2025-12-19 11:18:13', '2025-12-19 11:21:39'),
(102, 5, '2025-12-19', '2025-12-19 09:00:00', '2025-12-19 11:50:26', 7200, 'H', 'Langsung absen pulang', '2025-12-19 11:50:26', '2025-12-19 11:50:26'),
(103, 13, '2025-12-20', '2025-12-20 09:10:09', '2025-12-20 09:28:43', 1114, 'H', 'Telat 2 jam 10 menit', '2025-12-20 09:10:09', '2025-12-20 09:28:43'),
(111, 13, '2025-12-21', '2025-12-21 18:15:59', NULL, 0, 'H', 'Telat 2 jam 15 menit', '2025-12-21 18:15:59', '2025-12-21 18:15:59'),
(112, 14, '2025-12-21', '2025-12-21 18:16:09', '2025-12-21 18:20:59', 290, 'H', 'Telat 2 jam 16 menit', '2025-12-21 18:16:09', '2025-12-21 18:20:59'),
(113, 15, '2025-12-21', '2025-12-21 18:16:11', NULL, 0, 'H', 'Telat 2 jam 16 menit', '2025-12-21 18:16:11', '2025-12-21 18:16:11'),
(115, 13, '2025-12-22', '2025-12-22 06:50:47', NULL, 0, 'H', NULL, '2025-12-22 06:50:47', '2025-12-22 06:50:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `attendance_guru`
--

CREATE TABLE `attendance_guru` (
  `id` int(11) NOT NULL,
  `guru_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` datetime DEFAULT NULL,
  `jam_pulang` datetime DEFAULT NULL,
  `total_seconds` int(11) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `attendance_guru`
--

INSERT INTO `attendance_guru` (`id`, `guru_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `total_seconds`, `keterangan`, `created_at`, `updated_at`) VALUES
(41, 6, '2025-11-28', '2025-11-28 11:46:35', NULL, 0, 'Telat 4 jam 46 menit', '2025-11-28 11:46:35', '2025-11-28 11:46:35'),
(42, 6, '2025-11-30', '2025-11-30 08:53:41', NULL, 0, 'Telat 53 menit', '2025-11-30 08:53:41', '2025-11-30 08:53:41'),
(43, 2, '2025-11-30', '2025-11-30 09:07:05', NULL, 0, 'Telat 4 jam 7 menit', '2025-11-30 09:07:05', '2025-11-30 09:07:05'),
(44, 6, '2025-12-01', '2025-12-01 09:07:43', NULL, 0, 'Telat 1 jam 37 menit', '2025-12-01 09:07:43', '2025-12-01 09:07:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `enroll_log`
--

CREATE TABLE `enroll_log` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `txn` varchar(50) NOT NULL,
  `fid` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `enroll_log`
--

INSERT INTO `enroll_log` (`id`, `siswa_id`, `txn`, `fid`, `status`, `message`, `created_at`) VALUES
(1, 3, '', 3, 'started', 'Starting enroll, place finger', '2025-11-26 14:19:19'),
(2, 3, '', 3, 'fail', 'no_finger_step1', '2025-11-26 14:19:34'),
(3, 11, '', 3, 'started', 'Starting enroll, place finger', '2025-11-26 14:19:40'),
(4, 11, '', 3, 'fail', 'no_finger_step1', '2025-11-26 14:19:55'),
(5, 3, '', 3, 'started', 'Starting enroll, place finger', '2025-11-26 14:23:10'),
(6, 3, '', 3, 'progress', 'image1_captured', '2025-11-26 14:23:18'),
(7, 3, '', 3, 'action', 'remove_finger', '2025-11-26 14:23:18'),
(8, 3, '', 3, 'action', 'place_finger_again', '2025-11-26 14:23:18'),
(9, 3, '', 3, 'progress', 'image2_captured', '2025-11-26 14:23:19'),
(10, 3, '', 3, 'fail', 'createModel_failed', '2025-11-26 14:23:19'),
(11, 1, 'tx12345', 7, 'response', NULL, '2025-11-26 15:59:40'),
(12, 1, 'tx12345', 7, 'response', NULL, '2025-11-26 15:59:40'),
(13, 1, 'tx12345', 7, 'action', 'place_finger', '2025-11-26 15:59:40'),
(14, 1, 'tx12345', 7, 'progress', 'image1_captured', '2025-11-26 15:59:42'),
(15, 1, 'tx12345', 7, 'action', 'remove_finger', '2025-11-26 15:59:43'),
(16, 1, 'tx12345', 7, 'action', 'place_finger_again', '2025-11-26 15:59:43'),
(17, 1, 'tx12345', 7, 'progress', 'image2_captured', '2025-11-26 15:59:45'),
(18, 1, 'tx12345', 7, 'done', 'enroll_success', '2025-11-26 15:59:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `enroll_pending`
--

CREATE TABLE `enroll_pending` (
  `id` int(11) NOT NULL,
  `txn` varchar(64) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `fid` int(11) DEFAULT NULL,
  `status` enum('pending','done','error') DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `enroll_pending`
--

INSERT INTO `enroll_pending` (`id`, `txn`, `siswa_id`, `fid`, `status`, `message`, `created_at`, `updated_at`) VALUES
(1, '95ca6f11368fb23b2119', 3, NULL, 'pending', NULL, '2025-11-26 15:05:16', '2025-11-26 15:05:16'),
(2, 'f1ba6a287f7c274619ca', 3, NULL, 'pending', NULL, '2025-11-26 15:05:40', '2025-11-26 15:05:40'),
(3, 'fba76ef26aa36c5e35d1', 3, NULL, 'pending', NULL, '2025-11-26 15:08:42', '2025-11-26 15:08:42'),
(4, '098521ece3aa3d822558', 3, NULL, 'pending', NULL, '2025-11-26 15:08:47', '2025-11-26 15:08:47'),
(5, 'b860572d5377d3248f81', 3, NULL, 'pending', NULL, '2025-11-26 15:09:27', '2025-11-26 15:09:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `guru`
--

CREATE TABLE `guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `no_wa` varchar(50) NOT NULL,
  `id_finger` varchar(50) NOT NULL,
  `uid_rfid` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `enroll_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `guru`
--

INSERT INTO `guru` (`id`, `nama`, `nip`, `no_wa`, `id_finger`, `uid_rfid`, `created_at`, `enroll_status`) VALUES
(2, 'SIFA ALTA FUNISA', '2483000723', '23424', '91', '121EEC05', '2025-11-18 01:37:26', 'requested'),
(3, 'SOFI NUR HABIBAH', '', '34545345', '', '', '2025-11-18 01:41:07', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `index_hari` tinyint(4) NOT NULL COMMENT '1=Senin ... 7=Minggu',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `toleransi` int(11) NOT NULL DEFAULT 0 COMMENT 'Toleransi keterlambatan (menit)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `hari`, `index_hari`, `is_active`, `jam_masuk`, `jam_pulang`, `toleransi`, `created_at`, `updated_at`) VALUES
(1, 'Senin', 1, 1, '06:48:00', '15:00:00', 5, '2025-11-27 04:55:41', '2025-12-21 23:50:33'),
(2, 'Selasa', 2, 1, '20:00:00', '21:00:00', 5, '2025-11-27 04:55:41', '2025-12-21 23:50:33'),
(3, 'Rabu', 3, 1, '21:00:00', '23:00:00', 5, '2025-11-27 04:55:41', '2025-12-21 23:50:33'),
(4, 'Kamis', 4, 1, '09:00:00', '20:00:00', 5, '2025-11-27 04:55:41', '2025-12-21 23:50:33'),
(5, 'Jumat', 5, 1, '09:00:00', '11:00:00', 10, '2025-11-27 04:55:41', '2025-12-21 23:50:33'),
(6, 'Sabtu', 6, 1, '07:00:00', '12:00:00', 0, '2025-11-27 04:55:41', '2025-12-21 23:50:33'),
(7, 'Minggu', 7, 1, '15:00:00', '18:00:00', 0, '2025-11-27 04:55:41', '2025-12-21 23:50:33');

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
-- Struktur dari tabel `message_queue`
--

CREATE TABLE `message_queue` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','processing','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `message_queue`
--

INSERT INTO `message_queue` (`id`, `phone_number`, `message`, `status`, `attempts`, `last_error`, `created_at`, `updated_at`) VALUES
(1, '628123456789@s.whats', 'Debug Test Message 07:35:54', 'pending', 0, NULL, '2025-12-24 06:35:54', '2025-12-24 06:35:54'),
(2, '120363421672356407@g.us', '📊 *Laporan Absensi Harian*\n📅 Tanggal: 24/12/2025\n---------------------------\n✅ Siswa Masuk: 0\n❌ Siswa Tidak Masuk: 51\n---------------------------\n*Daftar Siswa Tidak Masuk:*\n- Adi Abdurachman (Alpha)\n- Aditya Rusliano Akbar (Alpha)\n- Adnan Nur Rohim (Alpha)\n- Ahmad (Alpha)\n- Ahmad Agus Salim (Alpha)\n- Ahmad Muzaki (Alpha)\n- Aji Irawan (Alpha)\n- Akhmad Afandi (Alpha)\n- Andre Marcel (Alpha)\n- Arbai Soliqin (Alpha)\n- Arista Danu Ansa (Alpha)\n- Arphanca Kun Nugroho (Alpha)\n- Arta Kusuma (Alpha)\n- Ayu Vera Velinia (Alpha)\n- Bagus Hermawan (Alpha)\n- Brenda Zaskia R (Alpha)\n- Davit Mubaidilah (Alpha)\n- Deni (Alpha)\n- Denis Kurniawan (Alpha)\n- Dhani Alan Maulana (Alpha)\n- Dhani Muhamad Reza (Alpha)\n- Dhika Hanafi Rantau (Alpha)\n- Dinda Amalia (Alpha)\n- Dwi Sampurna Jaya (Alpha)\n- Fadli Ardiansyah (Alpha)\n- Fariz Kurniawan (Alpha)\n- Firnando (Alpha)\n- Hanif Dwi Cahyono (Alpha)\n- Haris Vino Agusthaan (Alpha)\n- Indah Laras Putri (Alpha)\n- Indra Aprianto (Alpha)\n- Jepri Maulana (Alpha)\n- Junia Sari (Alpha)\n- Keyla Biyan Ramadhani (Alpha)\n- M. Maulana Eri Fernando (Alpha)\n- Meliana Dwi Irianti (Alpha)\n- Muhamad Deni Setiawan (Alpha)\n- Muhammad Haris Ashrori (Alpha)\n- Muhammad Irsyadul A\'la (Alpha)\n- Musa (Alpha)\n- Nanda Dwi Andi Aritama (Alpha)\n- Novita Dwi Wijayanti (Alpha)\n- Panji Setia Wardana (Alpha)\n- Rilly Meilana Wijaya (Alpha)\n- Rina Arzeti (Alpha)\n- Ririn Mardiana (Alpha)\n- Selviana (Alpha)\n- Tiara Indriyani Sabela (Alpha)\n- Vanisaul Khoiroh (Alpha)\n- Viko Afriyan Arbi (Alpha)\n- Zulfi Aulia (Alpha)\n\n_Generated by System_', 'pending', 0, NULL, '2025-12-24 06:54:25', '2025-12-24 06:54:25');

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
(243, '9FC0A7CC', '2025-12-22 19:27:15');

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
('last_daily_report_date', NULL, '2025-12-24 06:53:10'),
('report_target_jid', '120363421672356407@g.us', '2025-12-24 06:53:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nis` varchar(50) NOT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `uid_rfid` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `enroll_status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `nama`, `nis`, `kelas_id`, `no_wa`, `uid_rfid`, `created_at`, `enroll_status`) VALUES
(169, 'Aditya Rusliano Akbar', '2501001', 1, '62845212211', '', '2025-12-22 04:43:21', NULL),
(170, 'Ahmad Muzaki', '2501002', 1, NULL, '', '2025-12-22 04:43:21', NULL),
(171, 'Arta Kusuma', '2501003', 1, '', '', '2025-12-22 04:43:21', NULL),
(172, 'Brenda Zaskia R', '2502004', 2, '', '', '2025-12-22 04:43:21', NULL),
(173, 'Indra Aprianto', '2501007', 1, '', '', '2025-12-22 04:43:21', NULL),
(174, 'Nanda Dwi Andi Aritama', '2501008', 1, '', '', '2025-12-22 04:43:21', NULL),
(175, 'Rilly Meilana Wijaya', '2501011', 1, '', '', '2025-12-22 04:43:21', NULL),
(176, 'Rina Arzeti', '2502012', 2, '', '', '2025-12-22 04:43:21', NULL),
(177, 'Tiara Indriyani Sabela', '2502013', 2, '', '', '2025-12-22 04:43:21', NULL),
(178, 'Vanisaul Khoiroh', '2502014', 2, '', '', '2025-12-22 04:43:21', NULL),
(179, 'Viko Afriyan Arbi', '2501015', 1, '', '', '2025-12-22 04:43:21', NULL),
(180, 'Adi Abdurachman', '2401001', 3, '6281524824563', '', '2025-12-22 04:43:21', NULL),
(181, 'Ahmad', '2401002', 3, '', '', '2025-12-22 04:43:21', NULL),
(182, 'Ahmad Agus Salim', '2401003', 3, '', '', '2025-12-22 04:43:21', NULL),
(183, 'Arbai Soliqin', '2401004', 3, '', '', '2025-12-22 04:43:21', NULL),
(184, 'Arista Danu Ansa', '2401005', 3, '', '', '2025-12-22 04:43:21', NULL),
(185, 'Bagus Hermawan', '2401006', 3, '', '', '2025-12-22 04:43:21', NULL),
(186, 'Deni', '2401007', 3, '', '', '2025-12-22 04:43:21', NULL),
(187, 'Denis Kurniawan', '2401008', 3, '', '', '2025-12-22 04:43:21', NULL),
(188, 'Dhani Alan Maulana', '2401009', 3, '', '', '2025-12-22 04:43:21', NULL),
(189, 'Dhani Muhamad Reza', '2401010', 3, '', '', '2025-12-22 04:43:21', NULL),
(190, 'Dinda Amalia', '2402011', 4, '', '', '2025-12-22 04:43:21', NULL),
(191, 'Dwi Sampurna Jaya', '2401012', 3, '', '', '2025-12-22 04:43:21', NULL),
(192, 'Fariz Kurniawan', '2401014', 3, '', '', '2025-12-22 04:43:21', NULL),
(193, 'Haris Vino Agusthaan', '2401015', 3, '', '', '2025-12-22 04:43:21', NULL),
(194, 'Junia Sari', '2402016', 4, '', '', '2025-12-22 04:43:21', NULL),
(195, 'Keyla Biyan Ramadhani', '2402017', 4, '', '', '2025-12-22 04:43:21', NULL),
(196, 'M. Maulana Eri Fernando', '2401018', 3, '', '', '2025-12-22 04:43:21', NULL),
(197, 'Muhamad Deni Setiawan', '2401019', 3, '', '', '2025-12-22 04:43:21', NULL),
(198, 'Muhammad Haris Ashrori', '2401020', 3, '', '', '2025-12-22 04:43:21', NULL),
(199, 'Muhammad Irsyadul A\'la', '2401021', 3, '', '', '2025-12-22 04:43:21', NULL),
(200, 'Musa', '2401022', 3, '', '', '2025-12-22 04:43:21', NULL),
(201, 'Panji Setia Wardana', '2401023', 3, '', '', '2025-12-22 04:43:21', NULL),
(202, 'Ririn Mardiana', '2402024', 4, '', '', '2025-12-22 04:43:21', NULL),
(204, 'Aji Irawan', '2301002', 5, '', '', '2025-12-22 04:43:21', NULL),
(205, 'Akhmad Afandi', '2301003', 5, '', '', '2025-12-22 04:43:21', NULL),
(206, 'Andre Marcel', '2301005', 5, '', '', '2025-12-22 04:43:21', NULL),
(207, 'Arphanca Kun Nugroho', '2301007', 5, '', '', '2025-12-22 04:43:21', NULL),
(208, 'Ayu Vera Velinia', '2302008', 5, '', '', '2025-12-22 04:43:21', NULL),
(209, 'Davit Mubaidilah', '2301010', 5, '', '', '2025-12-22 04:43:21', NULL),
(210, 'Dhika Hanafi Rantau', '2301011', 5, '', '', '2025-12-22 04:43:21', NULL),
(211, 'Fadli Ardiansyah', '2301012', 5, '', '', '2025-12-22 04:43:21', NULL),
(212, 'Firnando', '2301013', 5, '', '', '2025-12-22 04:43:21', NULL),
(213, 'Hanif Dwi Cahyono', '2301014', 5, '', '', '2025-12-22 04:43:21', NULL),
(214, 'Indah Laras Putri', '2302015', 6, '', '', '2025-12-22 04:43:21', NULL),
(215, 'Jepri Maulana', '2301016', 5, '', '', '2025-12-22 04:43:21', NULL),
(216, 'Meliana Dwi Irianti', '2302018', 6, '', '', '2025-12-22 04:43:21', NULL),
(217, 'Novita Dwi Wijayanti', '2302019', 6, '', '', '2025-12-22 04:43:21', NULL),
(218, 'Selviana', '2302020', 6, '', '', '2025-12-22 04:43:21', NULL),
(219, 'Zulfi Aulia', '2302021', 6, '', '', '2025-12-22 04:43:21', NULL);

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
(7, 2, 'SIFA ALTA FUNISA', '121EEC05', '2025-12-21 11:20:56', '2025-12-21 11:50:56');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `full_name`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'kangdaqiq@gmail.com', '$2y$10$WGEhleZ7gX8/aoNstPVM.OSC9X1H/IGtyuHvGZ8Ff5ro9QEiXWsmy', 'admin', 'Ahmad Daqiqi', '2025-11-17 12:23:27', NULL, NULL);

--
-- Indexes for dumped tables
--

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
-- Indeks untuk tabel `attendance_guru`
--
ALTER TABLE `attendance_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_guru_date` (`guru_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indeks untuk tabel `enroll_log`
--
ALTER TABLE `enroll_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `enroll_pending`
--
ALTER TABLE `enroll_pending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `txn` (`txn`);

--
-- Indeks untuk tabel `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_guru_uid` (`uid_rfid`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_day` (`index_hari`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

--
-- Indeks untuk tabel `message_queue`
--
ALTER TABLE `message_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status_idx` (`status`);

--
-- Indeks untuk tabel `scan_history`
--
ALTER TABLE `scan_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid_time` (`uid`,`created_at`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT untuk tabel `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT untuk tabel `attendance_guru`
--
ALTER TABLE `attendance_guru`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT untuk tabel `enroll_log`
--
ALTER TABLE `enroll_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `enroll_pending`
--
ALTER TABLE `enroll_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `guru`
--
ALTER TABLE `guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `message_queue`
--
ALTER TABLE `message_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `scan_history`
--
ALTER TABLE `scan_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `teacher_checkout_sessions`
--
ALTER TABLE `teacher_checkout_sessions`
  ADD CONSTRAINT `teacher_checkout_sessions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
