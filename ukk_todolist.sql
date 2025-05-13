-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2025 at 10:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ukk_todolist`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('create','update','delete','complete','login','share') NOT NULL,
  `entity_type` enum('task','list','user','tag','subtask','comment','attachment') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action_type`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 1, 'create', 'list', 1, 'Membuat daftar Rapat Pimpinan', '2025-04-14 11:17:38'),
(2, 1, 'create', 'task', 1, 'Membuat tugas: Rapat Koordinasi Pimpinan', '2025-04-14 11:17:38'),
(3, 1, 'update', 'task', 3, 'Menyelesaikan tugas: Review Capaian Semester', '2025-04-14 11:17:38'),
(4, 2, 'create', 'list', 5, 'Membuat daftar Kurikulum', '2025-04-14 11:17:38'),
(5, 2, 'create', 'task', 10, 'Membuat tugas: Review Kurikulum Berbasis MBKM', '2025-04-14 11:17:38'),
(6, 1, 'login', 'user', 1, NULL, '2025-04-14 11:20:33'),
(7, 1, 'create', 'list', 20, NULL, '2025-04-14 11:21:17'),
(8, 1, 'create', 'list', 21, NULL, '2025-04-14 11:21:29'),
(9, 2, 'login', 'user', 2, NULL, '2025-04-14 11:24:40'),
(10, 2, 'create', 'task', 20, NULL, '2025-04-14 11:28:20'),
(11, 1, 'login', 'user', 1, NULL, '2025-04-14 11:28:29'),
(12, 1, 'create', 'task', 21, NULL, '2025-04-14 11:29:05'),
(13, 2, 'login', 'user', 2, NULL, '2025-04-14 11:29:16'),
(14, 2, 'create', 'task', 23, NULL, '2025-04-14 11:29:50'),
(15, 2, 'complete', 'task', 23, 'Changed task status to completed', '2025-04-14 11:30:31'),
(16, 2, 'complete', 'task', 20, 'Changed task status to completed', '2025-04-14 11:30:32'),
(17, 2, 'delete', 'task', 20, 'Deleted task', '2025-04-14 11:30:49'),
(18, 2, 'create', 'task', 24, NULL, '2025-04-14 11:40:13'),
(19, 2, 'delete', 'task', 24, 'Deleted task', '2025-04-14 11:40:47'),
(20, 1, 'login', 'user', 1, NULL, '2025-04-14 11:41:17'),
(21, 2, 'login', 'user', 2, NULL, '2025-04-14 11:41:37'),
(22, 2, 'delete', 'task', 23, 'Deleted task', '2025-04-14 11:46:36'),
(23, 2, 'create', 'task', 26, NULL, '2025-04-14 11:58:10'),
(24, 2, 'create', 'comment', 6, 'Added comment to task #26', '2025-04-14 11:58:26'),
(25, 1, 'login', 'user', 1, NULL, '2025-04-14 11:58:34'),
(26, 1, 'create', 'task', 27, NULL, '2025-04-14 11:59:13'),
(27, 1, 'create', 'tag', 15, NULL, '2025-04-14 12:02:22'),
(28, 1, 'delete', 'tag', 15, 'Deleted tag \'tes\'', '2025-04-14 12:02:30'),
(29, 1, 'update', 'tag', 3, 'Updated tag from \'Strategis\' to \'ebew\'', '2025-04-14 12:02:37'),
(30, 1, 'update', 'tag', 3, 'Updated tag from \'ebew\' to \'Jaringan\'', '2025-04-14 12:02:46'),
(31, 1, 'update', 'user', 1, 'Memperbarui informasi profil', '2025-04-14 12:19:14'),
(32, 1, 'update', 'user', 1, 'Memperbarui informasi profil', '2025-04-14 12:19:22'),
(33, 1, 'update', 'user', 1, 'Memperbarui informasi profil', '2025-04-14 12:21:30'),
(34, 9, 'create', 'user', 9, NULL, '2025-04-14 12:54:15'),
(35, 9, 'login', 'user', 9, 'User registered and logged in', '2025-04-14 12:54:15'),
(36, 1, 'login', 'user', 1, NULL, '2025-04-14 12:59:17'),
(37, 1, 'update', '', 0, 'Updated application settings', '2025-04-14 12:59:23'),
(38, 1, 'update', '', 0, 'Updated application settings', '2025-04-14 12:59:27'),
(39, 1, 'update', '', 0, 'Updated application settings', '2025-04-14 12:59:29'),
(40, 1, 'update', '', 0, 'Updated application settings', '2025-04-14 12:59:48'),
(41, 9, 'login', 'user', 9, NULL, '2025-04-14 13:00:07'),
(42, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:03:19'),
(43, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:03:19'),
(44, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:03:19'),
(45, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:03:19'),
(46, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:03:19'),
(47, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:03:22'),
(48, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:03:22'),
(49, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:03:22'),
(50, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:03:22'),
(51, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:03:22'),
(52, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:03:28'),
(53, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:03:28'),
(54, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:03:28'),
(55, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:03:28'),
(56, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:03:28'),
(57, 1, 'update', '', 0, 'Updated setting: maintenance_mode', '2025-04-14 13:03:28'),
(58, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:03:41'),
(59, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:03:41'),
(60, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:03:41'),
(61, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:03:41'),
(62, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:03:41'),
(63, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:03:50'),
(64, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:03:50'),
(65, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:03:50'),
(66, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:03:50'),
(67, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:03:50'),
(68, 1, 'update', '', 0, 'Updated setting: maintenance_mode', '2025-04-14 13:03:50'),
(69, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:05:06'),
(70, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:05:06'),
(71, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:05:06'),
(72, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:05:06'),
(73, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:05:06'),
(74, 1, 'update', '', 0, 'Updated setting: maintenance_mode', '2025-04-14 13:05:06'),
(75, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:05:27'),
(76, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:05:27'),
(77, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:05:27'),
(78, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:05:27'),
(79, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:05:27'),
(80, 1, 'update', '', 0, 'Updated setting: maintenance_mode', '2025-04-14 13:05:27'),
(81, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:05:32'),
(82, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:05:32'),
(83, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:05:32'),
(84, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:05:32'),
(85, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:05:32'),
(86, 1, 'update', '', 0, 'Updated setting: app_name', '2025-04-14 13:05:35'),
(87, 1, 'update', '', 0, 'Updated setting: base_url', '2025-04-14 13:05:35'),
(88, 1, 'update', '', 0, 'Updated setting: timezone', '2025-04-14 13:05:35'),
(89, 1, 'update', '', 0, 'Updated setting: upload_max_size', '2025-04-14 13:05:35'),
(90, 1, 'update', '', 0, 'Updated setting: allowed_file_types', '2025-04-14 13:05:35'),
(91, 1, 'update', '', 0, 'Updated setting: app_name = UKK Todolist', '2025-04-14 13:07:06'),
(92, 1, 'update', '', 0, 'Updated setting: base_url = http://localhost:8002', '2025-04-14 13:07:06'),
(93, 1, 'update', '', 0, 'Updated setting: timezone = Asia/Makassar', '2025-04-14 13:07:06'),
(94, 1, 'update', '', 0, 'Updated setting: upload_max_size = 2', '2025-04-14 13:07:06'),
(95, 1, 'update', '', 0, 'Updated setting: allowed_file_types = jpg,jpeg,png,gif,webp', '2025-04-14 13:07:06'),
(96, 1, 'update', '', 0, 'Updated setting: maintenance_message = Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', '2025-04-14 13:07:06'),
(97, 1, 'create', 'comment', 7, 'Added comment to task #5', '2025-04-14 13:27:47'),
(98, 9, 'login', 'user', 9, NULL, '2025-04-14 13:54:07'),
(99, 1, 'login', 'user', 1, NULL, '2025-04-14 13:55:48'),
(100, 1, 'update', '', 0, 'Updated setting: app_name = UKK Todolist', '2025-04-14 13:56:06'),
(101, 1, 'update', '', 0, 'Updated setting: base_url = https://first-strongly-gazelle.ngrok-free.app', '2025-04-14 13:56:06'),
(102, 1, 'update', '', 0, 'Updated setting: timezone = Asia/Makassar', '2025-04-14 13:56:06'),
(103, 1, 'update', '', 0, 'Updated setting: upload_max_size = 2', '2025-04-14 13:56:06'),
(104, 1, 'update', '', 0, 'Updated setting: allowed_file_types = jpg,jpeg,png,gif,webp', '2025-04-14 13:56:06'),
(105, 1, 'update', '', 0, 'Updated setting: maintenance_message = Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', '2025-04-14 13:56:06'),
(106, 9, 'create', 'task', 28, NULL, '2025-04-14 13:57:52'),
(107, 9, 'create', 'comment', 8, 'Added comment to task #28', '2025-04-14 13:58:18'),
(108, 9, 'delete', 'comment', 8, 'Deleted comment from task #28', '2025-04-14 13:58:21'),
(109, 9, 'create', 'comment', 9, 'Added comment to task #28', '2025-04-14 14:03:19'),
(110, 9, 'delete', 'comment', 9, 'Deleted comment from task #28', '2025-04-14 14:03:22'),
(111, 1, 'update', '', 0, 'Updated setting: app_name = UKK Todolist', '2025-04-14 14:08:28'),
(112, 1, 'update', '', 0, 'Updated setting: base_url = localhost:8002', '2025-04-14 14:08:28'),
(113, 1, 'update', '', 0, 'Updated setting: timezone = Asia/Makassar', '2025-04-14 14:08:28'),
(114, 1, 'update', '', 0, 'Updated setting: upload_max_size = 2', '2025-04-14 14:08:28'),
(115, 1, 'update', '', 0, 'Updated setting: allowed_file_types = jpg,jpeg,png,gif,webp', '2025-04-14 14:08:28'),
(116, 1, 'update', '', 0, 'Updated setting: maintenance_message = Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', '2025-04-14 14:08:28'),
(117, 1, 'update', '', 0, 'Updated setting: app_name = UKK Todolist', '2025-04-14 14:09:01'),
(118, 1, 'update', '', 0, 'Updated setting: base_url = http://localhost:8002', '2025-04-14 14:09:01'),
(119, 1, 'update', '', 0, 'Updated setting: timezone = Asia/Makassar', '2025-04-14 14:09:01'),
(120, 1, 'update', '', 0, 'Updated setting: upload_max_size = 2', '2025-04-14 14:09:02'),
(121, 1, 'update', '', 0, 'Updated setting: allowed_file_types = jpg,jpeg,png,gif,webp', '2025-04-14 14:09:02'),
(122, 1, 'update', '', 0, 'Updated setting: maintenance_message = Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', '2025-04-14 14:09:02'),
(123, 1, 'complete', 'task', 1, 'Changed task status to completed', '2025-04-14 22:17:54'),
(124, 1, 'create', 'task', 29, NULL, '2025-04-14 22:18:09'),
(125, 1, 'complete', 'task', 2, 'Changed task status to completed', '2025-04-14 22:18:22'),
(126, 1, 'complete', 'task', 5, 'Changed task status to completed', '2025-04-14 22:18:31'),
(127, 1, 'complete', 'task', 4, 'Changed task status to completed', '2025-04-14 22:18:32'),
(128, 1, 'complete', 'task', 7, 'Changed task status to completed', '2025-04-14 22:18:33'),
(129, 1, 'complete', 'task', 29, 'Changed task status to completed', '2025-04-14 22:18:49'),
(130, 1, 'complete', 'task', 27, 'Changed task status to completed', '2025-04-14 22:18:49'),
(131, 1, 'complete', 'task', 21, 'Changed task status to completed', '2025-04-14 22:18:53'),
(132, 1, 'complete', 'task', 6, 'Changed task status to completed', '2025-04-14 22:18:57'),
(133, 1, 'update', 'task', 1, NULL, '2025-04-14 22:19:27'),
(134, 1, 'update', 'list', 20, NULL, '2025-04-14 22:20:07'),
(135, 1, 'update', 'task', 27, 'Changed task status to pending', '2025-04-14 22:20:13'),
(136, 1, 'complete', 'task', 27, 'Changed task status to completed', '2025-04-14 22:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`, `setting_description`, `updated_at`, `updated_by`) VALUES
('allowed_file_types', 'jpg,jpeg,png,gif,webp', 'Tipe file yang diizinkan untuk upload', '2025-04-14 14:09:02', 1),
('app_name', 'UKK Todolist', 'Nama aplikasi', '2025-04-14 14:09:01', 1),
('app_version', '1.0.0', 'Versi aplikasi', '2025-04-14 13:01:51', NULL),
('base_url', 'http://localhost:8002', 'URL dasar aplikasi', '2025-04-14 14:09:01', 1),
('maintenance_message', 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', 'Pesan maintenance', '2025-04-14 14:09:02', 1),
('maintenance_mode', '0', 'Mode maintenance (0=off, 1=on)', '2025-04-14 13:07:19', 1),
('timezone', 'Asia/Makassar', 'Zona waktu aplikasi', '2025-04-14 14:09:01', 1),
('upload_max_size', '2', 'Ukuran maksimal upload dalam MB', '2025-04-14 14:09:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `attachment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collaboration_requests`
--

CREATE TABLE `collaboration_requests` (
  `request_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `permission` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaboration_requests`
--

INSERT INTO `collaboration_requests` (`request_id`, `list_id`, `sender_id`, `target_user_id`, `permission`, `status`, `created_at`, `updated_at`) VALUES
(1, 21, 1, 2, 'view', 'approved', '2025-04-14 11:24:26', '2025-04-14 11:27:55'),
(2, 20, 1, 2, 'admin', 'approved', '2025-04-14 11:41:29', '2025-04-14 11:41:43');

-- --------------------------------------------------------

--
-- Table structure for table `collaborators`
--

CREATE TABLE `collaborators` (
  `collaboration_id` int(11) NOT NULL,
  `entity_type` enum('list','task') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission` enum('view','edit','admin') DEFAULT 'view',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `task_id`, `user_id`, `content`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Mohon dipersiapkan data capaian kinerja dari masing-masing bidang', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(2, 1, 2, 'Dokumen akademik sudah saya siapkan, Pak Rektor', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(3, 2, 1, 'Undangan sudah ditandatangani dan didistribusikan', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(4, 5, 2, 'Perlu masukan dari semua kaprodi terkait implementasi MBKM', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(5, 8, 3, 'Minta data realisasi anggaran dari masing-masing fakultas', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(6, 26, 2, 'ini tugas nya pak', '2025-04-14 11:58:26', '2025-04-14 11:58:26'),
(7, 5, 1, 'tralalelotralala', '2025-04-14 13:27:47', '2025-04-14 13:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `lists`
--

CREATE TABLE `lists` (
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#3498db',
  `icon` varchar(50) DEFAULT 'list',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lists`
--

INSERT INTO `lists` (`list_id`, `user_id`, `title`, `description`, `color`, `icon`, `is_public`, `created_at`, `updated_at`, `is_deleted`) VALUES
(1, 1, 'Rapat Pimpinan', 'Agenda rapat pimpinan universitas', '#e74c3c', 'university', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(2, 1, 'Kebijakan Strategis', 'Kebijakan dan arah pengembangan universitas', '#3498db', 'book', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(3, 1, 'Kerjasama', 'Kerja sama dengan pihak eksternal', '#2ecc71', 'handshake', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(4, 1, 'Akreditasi', 'Persiapan dan monitoring akreditasi institusi', '#9b59b6', 'certificate', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(5, 2, 'Kurikulum', 'Pengembangan kurikulum semua prodi', '#f39c12', 'book-open', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(6, 2, 'Kalender Akademik', 'Penetapan dan monitoring kalender akademik', '#1abc9c', 'calendar', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(7, 2, 'Penelitian Dosen', 'Monitoring dan evaluasi penelitian dosen', '#34495e', 'microscope', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(8, 3, 'Anggaran', 'Perencanaan dan monitoring anggaran universitas', '#e74c3c', 'money-bill', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(9, 3, 'Aset', 'Pengelolaan aset universitas', '#3498db', 'building', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(10, 3, 'SDM', 'Pengembangan sumber daya manusia', '#2ecc71', 'users', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(11, 4, 'Kegiatan Mahasiswa', 'Monitoring kegiatan kemahasiswaan', '#9b59b6', 'graduation-cap', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(12, 4, 'Beasiswa', 'Pengelolaan program beasiswa', '#f39c12', 'dollar-sign', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(13, 4, 'Alumni', 'Hubungan dengan alumni', '#1abc9c', 'users', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(14, 5, 'Rapat Fakultas', 'Agenda rapat fakultas', '#e74c3c', 'users', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(15, 5, 'Kegiatan Akademik', 'Monitoring kegiatan akademik fakultas', '#3498db', 'book', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(16, 5, 'Lab dan Fasilitas', 'Pengelolaan laboratorium dan fasilitas', '#2ecc71', 'flask', 0, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(20, 1, 'borbamdilo corcodilo', '', '#3498db', 'list', 0, '2025-04-14 11:21:17', '2025-04-14 22:20:07', 0),
(21, 1, 'tralalelotralala', '', '#3498db', 'list', 1, '2025-04-14 11:21:29', '2025-04-14 11:21:29', 0),
(22, 9, 'My Tasks', 'Default task list', '#3498db', 'tasks', 0, '2025-04-14 12:54:15', '2025-04-14 12:54:15', 0);

-- --------------------------------------------------------

--
-- Table structure for table `list_collaborators`
--

CREATE TABLE `list_collaborators` (
  `collaborator_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'accepted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `list_collaborators`
--

INSERT INTO `list_collaborators` (`collaborator_id`, `list_id`, `user_id`, `permission`, `created_at`, `status`) VALUES
(1, 21, 2, 'view', '2025-04-14 11:27:55', 'accepted'),
(2, 20, 2, 'admin', '2025-04-14 11:41:43', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `type` enum('reminder','mention','share','comment','system') NOT NULL,
  `entity_type` enum('task','list','comment','system') NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `is_read`, `type`, `entity_type`, `entity_id`, `created_at`) VALUES
(2, 2, 'Tugas Mendatang', 'Review Kurikulum Berbasis MBKM dalam 5 hari', 0, 'reminder', 'task', 10, '2025-04-14 11:17:38'),
(3, 3, 'Tugas Hari Ini', 'Rapat Anggaran Tahunan dijadwalkan hari ini', 0, 'reminder', 'task', 11, '2025-04-14 11:17:38'),
(4, 5, 'Tugas Besok', 'Rapat Pimpinan Fakultas dijadwalkan besok', 0, 'reminder', 'task', 14, '2025-04-14 11:17:38'),
(6, 2, 'New Task Added', 'A new task \"aku bisa\" has been added to list \"corcodilo\" by rektor', 0, '', 'task', 27, '2025-04-14 11:59:13'),
(7, 9, 'Selamat datang di TodoList!', 'Terima kasih telah mendaftar. Mulailah dengan membuat tugas pertama Anda.', 0, 'system', 'system', NULL, '2025-04-14 12:54:15');

-- --------------------------------------------------------

--
-- Table structure for table `subtasks`
--

CREATE TABLE `subtasks` (
  `subtask_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subtasks`
--

INSERT INTO `subtasks` (`subtask_id`, `task_id`, `title`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Persiapan bahan rapat', 'completed', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(2, 1, 'Koordinasi dengan sekretariat', 'completed', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(3, 1, 'Undangan peserta rapat', 'pending', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(4, 2, 'Persiapan dokumen kebijakan', 'pending', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(5, 2, 'Koordinasi dengan sekretaris senat', 'pending', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(6, 4, 'Pengumpulan bahan dari wakil rektor', 'completed', '2025-04-14 11:17:38', '2025-04-14 11:17:38'),
(7, 4, 'Draft agenda rapat kerja', '', '2025-04-14 11:17:38', '2025-04-14 11:17:38');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT '#3498db',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`tag_id`, `user_id`, `name`, `color`, `created_at`) VALUES
(1, 1, 'Penting', '#e74c3c', '2025-04-14 11:17:37'),
(2, 1, 'Mendesak', '#f39c12', '2025-04-14 11:17:37'),
(3, 1, 'Jaringan', '#000000', '2025-04-14 11:17:37'),
(4, 1, 'Evaluasi', '#2ecc71', '2025-04-14 11:17:37'),
(5, 1, 'Pengembangan', '#9b59b6', '2025-04-14 11:17:37'),
(6, 2, 'Akademik', '#e74c3c', '2025-04-14 11:17:37'),
(7, 2, 'Kurikulum', '#3498db', '2025-04-14 11:17:37'),
(8, 2, 'Penelitian', '#2ecc71', '2025-04-14 11:17:37'),
(9, 3, 'Anggaran', '#e74c3c', '2025-04-14 11:17:37'),
(10, 3, 'Aset', '#3498db', '2025-04-14 11:17:37'),
(11, 3, 'SDM', '#2ecc71', '2025-04-14 11:17:37'),
(12, 4, 'Mahasiswa', '#e74c3c', '2025-04-14 11:17:37'),
(13, 4, 'Beasiswa', '#3498db', '2025-04-14 11:17:37'),
(14, 4, 'Alumni', '#2ecc71', '2025-04-14 11:17:37');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `due_date` datetime DEFAULT NULL,
  `reminder` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `list_id`, `user_id`, `title`, `description`, `priority`, `status`, `due_date`, `reminder`, `created_at`, `updated_at`, `is_deleted`) VALUES
(1, 1, 1, 'Rapat Koordinasi Pimpinan 2', 'Rapat koordinasi dengan semua wakil rektor dan dekan', 'high', 'completed', '2025-04-17 00:00:00', NULL, '2025-04-14 11:17:37', '2025-04-14 22:19:27', 0),
(2, 1, 1, 'Rapat Senat Universitas', 'Pembahasan kebijakan strategis universitas', 'high', 'completed', '2025-04-21 00:00:00', NULL, '2025-04-14 11:17:37', '2025-04-14 22:18:22', 0),
(3, 1, 1, 'Review Capaian Semester', 'Evaluasi capaian kinerja universitas semester lalu', 'medium', 'completed', '2025-04-09 00:00:00', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', 0),
(4, 1, 1, 'Rapat Kerja Tahunan', 'Persiapan rapat kerja tahunan universitas', 'high', 'completed', '2025-04-28 00:00:00', NULL, '2025-04-14 11:17:37', '2025-04-14 22:18:32', 0),
(5, 2, 1, 'Finalisasi Renstra', 'Finalisasi dokumen rencana strategis 5 tahun', 'high', 'completed', '2025-04-24 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 22:18:31', 0),
(6, 2, 1, 'Kebijakan Remunerasi', 'Review dan finalisasi kebijakan remunerasi baru', 'medium', 'completed', '2025-05-04 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 22:18:57', 0),
(7, 2, 1, 'Kebijakan Pengembangan Kampus', 'Finalisasi masterplan pengembangan kampus', 'high', 'completed', '2025-05-14 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 22:18:33', 0),
(8, 5, 2, 'Review Kurikulum Berbasis MBKM', 'Evaluasi implementasi kurikulum Merdeka Belajar Kampus Merdeka', 'high', 'in_progress', '2025-04-19 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(9, 5, 2, 'Workshop OBE', 'Pelaksanaan workshop Outcome-Based Education untuk dosen', 'medium', 'pending', '2025-04-29 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(10, 5, 2, 'Penyusunan Panduan Akademik', 'Finalisasi buku panduan akademik tahun ajaran baru', 'high', 'pending', '2025-04-24 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(11, 8, 3, 'Rapat Anggaran Tahunan', 'Pembahasan RKAT dengan seluruh unit', 'high', 'pending', '2025-04-16 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(12, 8, 3, 'Evaluasi Realisasi Anggaran', 'Evaluasi realisasi anggaran semester berjalan', 'medium', 'in_progress', '2025-04-21 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(13, 8, 3, 'Penyusunan Anggaran Tahun Berikutnya', 'Persiapan penyusunan anggaran tahun depan', 'medium', 'pending', '2025-05-09 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(14, 11, 4, 'Persiapan PKKMB', 'Persiapan Pengenalan Kehidupan Kampus bagi Mahasiswa Baru', 'high', 'in_progress', '2025-05-14 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(15, 11, 4, 'Monitoring UKM', 'Evaluasi kegiatan Unit Kegiatan Mahasiswa semester lalu', 'medium', 'pending', '2025-04-19 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(16, 11, 4, 'Seleksi Beasiswa', 'Pelaksanaan seleksi beasiswa unggulan', 'high', 'pending', '2025-04-28 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(17, 14, 5, 'Rapat Pimpinan Fakultas', 'Koordinasi dengan kaprodi dan sekretaris fakultas', 'high', 'pending', '2025-04-15 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(18, 14, 5, 'Persiapan Akreditasi', 'Persiapan dokumen akreditasi prodi Teknik Informatika', 'high', 'in_progress', '2025-04-24 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(19, 14, 5, 'Evaluasi Dosen', 'Review hasil evaluasi kinerja dosen', 'medium', 'pending', '2025-04-21 00:00:00', NULL, '2025-04-14 11:17:38', '2025-04-14 11:17:38', 0),
(20, 5, 2, 'tra budi', '', 'medium', 'completed', NULL, NULL, '2025-04-14 11:28:19', '2025-04-14 11:30:49', 1),
(21, 21, 1, 'tra ahmad', '', 'medium', 'completed', NULL, NULL, '2025-04-14 11:29:05', '2025-04-14 22:18:53', 0),
(23, 7, 2, 'tra budi jay', '', 'medium', 'completed', NULL, NULL, '2025-04-14 11:29:50', '2025-04-14 11:46:36', 1),
(24, 6, 2, 'tra budi', '', 'medium', 'pending', NULL, NULL, '2025-04-14 11:40:13', '2025-04-14 11:40:47', 1),
(26, 20, 2, 'corcodilo budi', '', 'medium', 'pending', NULL, NULL, '2025-04-14 11:58:10', '2025-04-14 11:58:10', 0),
(27, 20, 1, 'aku bisa', '', 'medium', 'completed', NULL, NULL, '2025-04-14 11:59:13', '2025-04-14 22:20:15', 0),
(28, 22, 9, 'Bajingan', 'Ebew', 'urgent', 'in_progress', NULL, NULL, '2025-04-14 13:57:52', '2025-04-14 13:57:52', 0),
(29, 4, 1, 'bisa mi', 'asa', 'medium', 'completed', NULL, NULL, '2025-04-14 22:18:09', '2025-04-14 22:18:49', 0);

-- --------------------------------------------------------

--
-- Table structure for table `task_tags`
--

CREATE TABLE `task_tags` (
  `task_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_tags`
--

INSERT INTO `task_tags` (`task_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 3),
(4, 3),
(5, 3),
(5, 5),
(8, 9),
(11, 12),
(13, 13),
(26, 7),
(26, 8),
(29, 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `role` enum('admin','regular') DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `profile_picture`, `created_at`, `updated_at`, `last_login`, `is_active`, `role`) VALUES
(1, 'rektor', 'rektor@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Prof. Dr. Ahmad Sudirman, M.Sc.', 'user_1_1744635220_67fd0554b6352.jpg', '2025-04-14 11:17:37', '2025-04-14 13:55:48', '2025-04-14 13:55:48', 1, 'admin'),
(2, 'wr1', 'wr1@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Budi Santoso, M.Pd.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:41:37', '2025-04-14 11:41:37', 1, 'regular'),
(3, 'wr2', 'wr2@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Prof. Dr. Siti Aminah, M.M.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', NULL, 1, 'regular'),
(4, 'wr3', 'wr3@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Hendra Wijaya, M.Kom.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', NULL, 1, 'regular'),
(5, 'dekanfti', 'dekanfti@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Rudi Hartono, M.T.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', NULL, 1, 'regular'),
(6, 'dekanfeb', 'dekanfeb@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Dewi Anggraini, M.Sc.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', NULL, 1, 'regular'),
(7, 'kaprodi_ti', 'kaprodi_ti@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Joko Susilo, M.Kom.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', NULL, 1, 'regular'),
(8, 'kabag_aka', 'kabag_aka@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Indra Maulana, M.M.', NULL, '2025-04-14 11:17:37', '2025-04-14 11:17:37', NULL, 1, 'regular'),
(9, 'alkawsar', 'alkawsar@gmail.com', '$2y$10$uiwixhwTsC6QqI5F1J3Sr.0jRYz7Bl/P9HcRVN1gb5O5XzIOOQJc6', 'Andi Muh Raihan Alkawsar', 'user_9_1744638909_67fd13bd32fcb.jpg', '2025-04-14 11:54:15', '2025-04-14 13:55:09', '2025-04-14 13:54:07', 1, 'regular');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `collaboration_requests`
--
ALTER TABLE `collaboration_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `list_id` (`list_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `target_user_id` (`target_user_id`);

--
-- Indexes for table `collaborators`
--
ALTER TABLE `collaborators`
  ADD PRIMARY KEY (`collaboration_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lists`
--
ALTER TABLE `lists`
  ADD PRIMARY KEY (`list_id`),
  ADD KEY `lists_ibfk_1` (`user_id`);

--
-- Indexes for table `list_collaborators`
--
ALTER TABLE `list_collaborators`
  ADD PRIMARY KEY (`collaborator_id`),
  ADD UNIQUE KEY `unique_collaboration` (`list_id`,`user_id`),
  ADD KEY `list_collaborators_ibfk_2` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD PRIMARY KEY (`subtask_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `list_id` (`list_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_tags`
--
ALTER TABLE `task_tags`
  ADD PRIMARY KEY (`task_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collaboration_requests`
--
ALTER TABLE `collaboration_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `collaborators`
--
ALTER TABLE `collaborators`
  MODIFY `collaboration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lists`
--
ALTER TABLE `lists`
  MODIFY `list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `list_collaborators`
--
ALTER TABLE `list_collaborators`
  MODIFY `collaborator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subtasks`
--
ALTER TABLE `subtasks`
  MODIFY `subtask_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD CONSTRAINT `app_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `collaboration_requests`
--
ALTER TABLE `collaboration_requests`
  ADD CONSTRAINT `collaboration_requests_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`),
  ADD CONSTRAINT `collaboration_requests_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `collaboration_requests_ibfk_3` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `collaborators`
--
ALTER TABLE `collaborators`
  ADD CONSTRAINT `collaborators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `lists`
--
ALTER TABLE `lists`
  ADD CONSTRAINT `lists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `list_collaborators`
--
ALTER TABLE `list_collaborators`
  ADD CONSTRAINT `list_collaborators_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `list_collaborators_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD CONSTRAINT `subtasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE;

--
-- Constraints for table `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`list_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `task_tags`
--
ALTER TABLE `task_tags`
  ADD CONSTRAINT `task_tags_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
