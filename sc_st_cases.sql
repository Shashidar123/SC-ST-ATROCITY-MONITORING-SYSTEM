-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 04:36 AM
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
-- Database: `sc_st_cases`
--

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `case_id` int(11) NOT NULL,
  `victim_name` varchar(100) NOT NULL,
  `victim_address` text NOT NULL,
  `incident_date` date NOT NULL,
  `incident_description` text NOT NULL,
  `case_type` enum('SC','ST') NOT NULL,
  `status` varchar(32) DEFAULT NULL,
  `filed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `victim_age` int(11) DEFAULT NULL,
  `victim_gender` varchar(20) DEFAULT NULL,
  `victim_caste` varchar(20) DEFAULT NULL,
  `victim_contact` varchar(20) DEFAULT NULL,
  `victim_aadhaar` varchar(20) DEFAULT NULL,
  `fir_number` varchar(50) DEFAULT NULL,
  `fir_date` date DEFAULT NULL,
  `police_station` varchar(100) DEFAULT NULL,
  `victim_statement` text DEFAULT NULL,
  `medical_report` text DEFAULT NULL,
  `case_sections` text DEFAULT NULL,
  `investigating_officer` varchar(100) DEFAULT NULL,
  `forward_to_sp` tinyint(1) DEFAULT 1,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `assigned_officer` varchar(100) DEFAULT NULL,
  `io_username` varchar(50) DEFAULT NULL,
  `io_report` text DEFAULT NULL,
  `io_witness_statements` text DEFAULT NULL,
  `io_recommendations` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cases`
--

INSERT INTO `cases` (`case_id`, `victim_name`, `victim_address`, `incident_date`, `incident_description`, `case_type`, `status`, `filed_by`, `created_at`, `victim_age`, `victim_gender`, `victim_caste`, `victim_contact`, `victim_aadhaar`, `fir_number`, `fir_date`, `police_station`, `victim_statement`, `medical_report`, `case_sections`, `investigating_officer`, `forward_to_sp`, `priority`, `assigned_officer`, `io_username`, `io_report`, `io_witness_statements`, `io_recommendations`, `updated_at`) VALUES
(15, 'Rishi', 'Dwaraka nagar', '0000-00-00', '', 'SC', 'collector_allotted', 1, '2025-07-18 15:33:00', 25, 'male', 'sc', '9898989898', '987654321234', '999', '2025-05-11', 'anantapur', 'i had been affected by the case so much', 'this case is based on caste discrimination', 'ipc 354 sc/st section 296', 'sashi', 1, 'high', 'sashi', 'io_user1', 'in investigation i had found out that the case victim is innocent !', 'witness approved that he is affected by the case', 'i throughly gn ethorugh investigations', '2025-07-19 15:31:48'),
(16, 'sashi', 'Dwaraka nagar', '0000-00-00', '', 'SC', 'collector_approved', 1, '2025-07-18 15:34:35', 25, 'male', 'sc', '9898989898', '987654321234', '9987', '2025-06-12', 'anantapur', 'ihad been affected by this case !!', 'this case is about the caste discrimination ', 'ipc 354 sc/st case atrocity', 'rishi', 1, 'high', 'rishi', 'io_user1', 'in investigation i had found out that the case victim is innocent !', 'witness approved that he is affected by the case', 'i throughly gn ethorugh investigations', '2025-07-19 15:31:34'),
(17, 'sai', 'Dwaraka nagar', '0000-00-00', '', 'ST', 'collector_review', 1, '2025-07-21 11:49:38', 25, 'male', 'st', '9898989898', '987654321234', '0026420', '2025-02-12', 'atp', 'i have no wordss', 'no words', 'ipc 354', 'rishi', 1, 'medium', 'gg', 'io_user1', 'svwqdvwv', 'wvwv', 'wqv', '2025-07-21 12:07:52'),
(18, 'navi', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'ST', 'collector_allotted', 1, '2025-07-21 12:32:08', 25, 'male', 'st', '9898989898', '987654321234', '0026420', '2025-02-12', 'atp', 'ugwvcuvwd', 'WDDC', 'SC ST 354', 'sashi', 1, 'medium', 'sashi', 'io_user1', 'sadfgasfg', 'afafg', 'afagfg', '2025-07-21 12:41:08'),
(19, 'eshwar', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'ST', 'collector_reverify', 1, '2025-07-21 12:33:39', 25, 'male', 'st', '9898989898', '987654321234', '0026420', '2025-02-12', 'atp', 'dbcjhwdbc', 'SDCJHBDC', 'sc st 354', 'sashi', 1, 'medium', 'gg', 'io_user1', 'dfvadfv', 'svafdsv', 'asdvasd', '2025-07-21 12:41:17'),
(20, 'Rishi', 'Dwaraka nagar', '0000-00-00', '', 'SC', 'sp_review_from_io', 1, '2025-07-21 15:57:14', 44, 'male', 'sc', '9898989898', '987654321234', '9987', '2025-02-11', '3 town , atp', 'ygyg', '', 'ipc 354', 'sashi', 0, 'low', NULL, 'io_user1', 'ygygc', 'k jhh', 'h huhv', '2025-07-21 16:30:43'),
(21, 'koushik', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'ST', 'sp_review_from_io', 1, '2025-07-21 16:42:52', 22, 'male', 'st', '9898989898', '987654321234', '0026420', '2025-02-11', '3 town , atp', 'qvqwrqrv', '', 'ipc 354', 'sashi', 0, 'medium', NULL, 'io_user1', 'qvqwv', 'wewv', 'wdvqvw', '2025-07-21 16:48:09'),
(22, 'gg', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'SC', 'sp_review_from_io', 1, '2025-07-21 16:49:57', 22, 'other', 'sc', '9898989898', '987654321234', '0026420', '2025-02-11', 'atp', ' yg  yg  yg ', '', 'ipc 354', 'sashi', 0, 'medium', NULL, 'io_user1', 'uvygvy', ' huvgvygv', ' bubuvuyv', '2025-07-21 16:51:34'),
(23, 'santu', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'SC', 'collector_allotted', 1, '2025-07-21 16:59:40', 20, 'transgender', 'sc', '9898989898', '987654321234', '0026420', '2025-02-11', '3 town , atp', 'qqdqwde', '', 'ipc 354', 'sp chandra shekhar', 0, 'medium', NULL, 'io_user1', 'b hb h ', 'bhg gcf', 'kjhbuhb', '2025-07-21 17:04:50'),
(24, 'venky', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'ST', 'collector_approved', 1, '2025-07-22 06:05:31', 22, 'male', 'st', '9898989898', '987654321234', '0026420', '2025-02-11', '3 town , atp', 'hvuvuv', '', 'ipc 354', 'rishi', 0, 'medium', NULL, 'io_user1', 'zgbsgafv', 'vhghgc', 'bjuhvujv', '2025-07-22 06:14:25'),
(25, 'chiru', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'ST', 'collector_reverify', 1, '2025-07-22 06:11:09', 22, 'male', 'st', '9898989898', '987654321234', '0026420', '2025-02-12', '3 town , atp', ' jvhgv', '', 'ipc 354', 'sashi', 0, 'medium', NULL, 'io_user1', 'asvchgvcs', 'sc hgsc hqgsc', 'ascn jsch ', '2025-07-22 06:14:37'),
(26, 'gowri', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'SC', 'from_social_welfare', 1, '2025-07-22 06:50:08', 22, 'male', 'sc', '9898989898', '987654321234', '0026420', '0025-02-12', '3 town , atp', 'no commets', '', 'ipc 354 ', 'sashi', 0, 'high', NULL, 'io_user1', 'The incident occurred at the reported location and time. Evidence and statements confirm a caste-based offense under the SC/ST Act.', 'Witnesses consistently support the victim’s claims, confirming the accused’s presence and abusive behavior.', 'Recommend filing charges under SC/ST Act and forwarding the case for legal action.', '2025-07-22 07:11:14'),
(27, 'koushik', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'ST', 'collector_approved', 1, '2025-07-22 06:51:40', 22, 'male', 'st', '9898989898', '987654321234', '9987', '2025-02-12', '3 town , atp', 'no comments', '', 'pc 354', 'rishi', 0, 'medium', NULL, 'io_user1', 'The incident occurred at the reported location and time. Evidence and statements confirm a caste-based offense under the SC/ST Act.', 'Witnesses consistently support the victim’s claims, confirming the accused’s presence and abusive behavior.', 'Recommend filing charges under SC/ST Act and forwarding the case for legal action.', '2025-07-22 07:06:05'),
(28, 'yash', 'DWARAKA NAGAR ANANTAPUR 1-335', '0000-00-00', '', 'SC', 'collector_reverify', 1, '2025-07-23 06:31:17', 20, 'male', 'sc', '9898989898', '987654321234', '9987', '2025-07-22', 'anantapur 3 town', '6trvbfgkbbtgvlktfktgfbtygbbfbvvfcfkwvtrbjfltvfg', '', 'ipc 354', 'sp chandra shekhar', 0, 'high', NULL, 'io_user1', 'wertghj', 'qwert', 'qwert', '2025-07-23 06:41:16');

-- --------------------------------------------------------

--
-- Table structure for table `case_documents`
--

CREATE TABLE `case_documents` (
  `document_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_documents`
--

INSERT INTO `case_documents` (`document_id`, `case_id`, `document_type`, `file_path`, `uploaded_by`, `created_at`) VALUES
(44, 15, 'fir', 'fir_687a692c754c1.png', 1, '2025-07-18 15:33:00'),
(45, 15, 'medical', 'medical_687a692c76308.png', 1, '2025-07-18 15:33:00'),
(46, 16, 'fir', 'fir_687a698bcac67.png', 1, '2025-07-18 15:34:35'),
(47, 16, 'medical', 'medical_687a698bcbaa6.png', 1, '2025-07-18 15:34:35'),
(48, 16, 'evidence', 'uploads/evidence_687a6a7570415.log', 8, '2025-07-18 15:38:29'),
(49, 16, 'evidence', 'uploads/evidence_687a6a89132c6.log', 8, '2025-07-18 15:38:49'),
(50, 16, 'evidence', 'uploads/evidence_687a6a9990b04.log', 8, '2025-07-18 15:39:05'),
(51, 15, 'evidence', 'uploads/evidence_687a6b184d050.log', 8, '2025-07-18 15:41:12'),
(52, 15, 'evidence', 'uploads/evidence_687a6b2464633.log', 8, '2025-07-18 15:41:24'),
(53, 15, 'evidence', 'uploads/evidence_687a6b30049e5.log', 8, '2025-07-18 15:41:36'),
(54, 16, 'digital_signature', 'digital_signature_687a6d273851a.png', 3, '2025-07-18 15:49:59'),
(55, 16, 'digital_signature', 'digital_signature_687a6d2dd9c84.png', 3, '2025-07-18 15:50:05'),
(56, 15, 'digital_signature', 'digital_signature_687a6de2b2500.png', 3, '2025-07-18 15:53:06'),
(57, 15, 'evidence', 'uploads/evidence_687bceed932fd.png', 8, '2025-07-19 16:59:25'),
(58, 17, 'fir', 'fir_687e29520d1e4.png', 1, '2025-07-21 11:49:38'),
(59, 17, 'medical', 'medical_687e29520dbab.png', 1, '2025-07-21 11:49:38'),
(60, 17, 'evidence', 'evidence_687e29520e069.png', 1, '2025-07-21 11:49:38'),
(61, 17, 'evidence', 'uploads/evidence_687e29c5edd85.log', 8, '2025-07-21 11:51:33'),
(62, 17, 'evidence', 'uploads/evidence_687e29d27386a.log', 8, '2025-07-21 11:51:46'),
(63, 17, 'evidence', 'uploads/evidence_687e29df89b15.log', 8, '2025-07-21 11:51:59'),
(64, 17, 'evidence', 'uploads/evidence_687e29f39e41d.log', 8, '2025-07-21 11:52:19'),
(65, 17, 'digital_signature', 'digital_signature_687e2a5e25b91.png', 3, '2025-07-21 11:54:06'),
(66, 17, 'digital_signature', 'digital_signature_687e2a65319d7.png', 3, '2025-07-21 11:54:13'),
(67, 17, 'digital_signature', 'digital_signature_687e2c3c20097.png', 3, '2025-07-21 12:02:04'),
(68, 17, 'digital_signature', 'digital_signature_687e2cd47bfd2.png', 3, '2025-07-21 12:04:36'),
(69, 18, 'fir', 'fir_687e33484ae42.png', 1, '2025-07-21 12:32:08'),
(70, 18, 'medical', 'medical_687e33484bcca.png', 1, '2025-07-21 12:32:08'),
(71, 18, 'evidence', 'evidence_687e33484c43e.png', 1, '2025-07-21 12:32:08'),
(72, 19, 'fir', 'fir_687e33a3dc318.png', 1, '2025-07-21 12:33:39'),
(73, 19, 'medical', 'medical_687e33a3dc95c.png', 1, '2025-07-21 12:33:39'),
(74, 19, 'evidence', 'evidence_687e33a3ddb90.png', 1, '2025-07-21 12:33:39'),
(75, 18, 'evidence', 'uploads/evidence_687e341482462.log', 8, '2025-07-21 12:35:32'),
(76, 18, 'evidence', 'uploads/evidence_687e342061a2d.log', 8, '2025-07-21 12:35:44'),
(77, 18, 'evidence', 'uploads/evidence_687e342b5262b.log', 8, '2025-07-21 12:35:55'),
(78, 19, 'evidence', 'uploads/evidence_687e345838650.log', 8, '2025-07-21 12:36:40'),
(79, 19, 'evidence', 'uploads/evidence_687e3464a7b6f.log', 8, '2025-07-21 12:36:52'),
(80, 19, 'evidence', 'uploads/evidence_687e3470452f5.log', 8, '2025-07-21 12:37:04'),
(81, 19, 'digital_signature', 'digital_signature_687e35047fcc2.png', 3, '2025-07-21 12:39:32'),
(82, 18, 'digital_signature', 'digital_signature_687e351b6c430.png', 3, '2025-07-21 12:39:55'),
(83, 20, 'fir', 'fir_687e635a4d635.png', 1, '2025-07-21 15:57:14'),
(84, 20, 'evidence', 'uploads/evidence_687e6a8c8065f.log', 8, '2025-07-21 16:27:56'),
(85, 20, 'evidence', 'uploads/evidence_687e6a97644fe.log', 8, '2025-07-21 16:28:07'),
(86, 20, 'evidence', 'uploads/evidence_687e6aa26c39d.log', 8, '2025-07-21 16:28:18'),
(87, 20, 'evidence', 'uploads/evidence_687e6b15279b9.log', 8, '2025-07-21 16:30:13'),
(88, 20, 'evidence', 'uploads/evidence_687e6b2259ec9.log', 8, '2025-07-21 16:30:26'),
(89, 20, 'evidence', 'uploads/evidence_687e6b2db7a0f.log', 8, '2025-07-21 16:30:37'),
(90, 21, 'fir', 'fir_687e6e0ce81f0.png', 1, '2025-07-21 16:42:52'),
(91, 21, 'evidence', 'uploads/evidence_687e6e513d549.png', 8, '2025-07-21 16:44:01'),
(92, 21, 'evidence', 'uploads/evidence_687e6e5fa0bd0.png', 8, '2025-07-21 16:44:15'),
(93, 21, 'evidence', 'uploads/evidence_687e6e6bb21aa.png', 8, '2025-07-21 16:44:27'),
(94, 22, 'fir', 'fir_687e6fb53389e.png', 1, '2025-07-21 16:49:57'),
(95, 22, 'evidence', 'evidence_687e6ff0e1cfc.png', 8, '2025-07-21 16:50:56'),
(96, 22, 'evidence', 'evidence_687e6ffe1cfe7.png', 8, '2025-07-21 16:51:10'),
(97, 22, 'evidence', 'evidence_687e700a0fd5d.png', 8, '2025-07-21 16:51:22'),
(98, 23, 'fir', 'fir_687e71fc64c4d.png', 1, '2025-07-21 16:59:40'),
(99, 23, 'medical', 'evidence_687e728684e0b.png', 8, '2025-07-21 17:01:58'),
(100, 23, 'cctv', 'evidence_687e729f49e76.png', 8, '2025-07-21 17:02:23'),
(101, 23, 'photo', 'evidence_687e72ac380b8.png', 8, '2025-07-21 17:02:36'),
(102, 23, 'evidence', 'evidence_687e72b93845f.png', 8, '2025-07-21 17:02:49'),
(103, 23, 'digital_signature', 'digital_signature_687e72f39decb.pdf', 3, '2025-07-21 17:03:47'),
(104, 24, 'fir', 'fir_687f2a2bd9097.png', 1, '2025-07-22 06:05:31'),
(105, 24, 'medical', 'evidence_687f2a9d84d3f.png', 8, '2025-07-22 06:07:25'),
(106, 24, 'cctv', 'evidence_687f2aa8c9b58.png', 8, '2025-07-22 06:07:36'),
(107, 24, 'photo', 'evidence_687f2ab432e23.png', 8, '2025-07-22 06:07:48'),
(108, 24, 'digital_signature', 'digital_signature_687f2b3315f2a.png', 3, '2025-07-22 06:09:55'),
(109, 25, 'fir', 'fir_687f2b7d479d1.png', 1, '2025-07-22 06:11:09'),
(110, 25, 'medical', 'evidence_687f2bb979b37.png', 8, '2025-07-22 06:12:09'),
(111, 25, 'photo', 'evidence_687f2bc729059.png', 8, '2025-07-22 06:12:23'),
(112, 25, 'digital_signature', 'digital_signature_687f2c0722a53.png', 3, '2025-07-22 06:13:27'),
(113, 26, 'fir', 'fir_687f34a0e87f5.png', 1, '2025-07-22 06:50:08'),
(114, 27, 'fir', 'fir_687f34fcc9970.png', 1, '2025-07-22 06:51:40'),
(115, 26, 'medical', 'evidence_687f361784523.png', 8, '2025-07-22 06:56:23'),
(116, 26, 'cctv', 'evidence_687f362567e43.png', 8, '2025-07-22 06:56:37'),
(117, 26, 'photo', 'evidence_687f363133ce9.png', 8, '2025-07-22 06:56:49'),
(118, 27, 'medical', 'evidence_687f366c038b4.png', 8, '2025-07-22 06:57:48'),
(119, 27, 'photo', 'evidence_687f36765e548.png', 8, '2025-07-22 06:57:58'),
(120, 27, 'digital_signature', 'digital_signature_687f37f2a00df.png', 3, '2025-07-22 07:04:18'),
(121, 26, 'digital_signature', 'digital_signature_687f395d19342.png', 3, '2025-07-22 07:10:21'),
(122, 28, 'fir', 'fir_688081b5209ab.png', 1, '2025-07-23 06:31:17'),
(123, 28, 'medical', 'evidence_6880826b61464.png', 8, '2025-07-23 06:34:19'),
(124, 28, 'photo', 'evidence_6880828b63f87.png', 8, '2025-07-23 06:34:51'),
(125, 28, 'digital_signature', 'digital_signature_68808379ad1e1.png', 3, '2025-07-23 06:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `case_status`
--

CREATE TABLE `case_status` (
  `status_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `status` varchar(32) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_status`
--

INSERT INTO `case_status` (`status_id`, `case_id`, `status`, `comments`, `updated_by`, `created_at`) VALUES
(94, 15, 'dcr_review', NULL, 1, '2025-07-18 15:33:00'),
(95, 16, 'dcr_review', NULL, 1, '2025-07-18 15:34:35'),
(96, 16, 'sp_review', 'ihave gone through case detauls and it is correct as per my investigation', 7, '2025-07-18 15:35:56'),
(97, 15, 'sp_review', 'i had verified this case', 7, '2025-07-18 15:36:13'),
(98, 15, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-18 15:36:49'),
(99, 15, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-18 15:37:00'),
(100, 16, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-18 15:37:47'),
(101, 16, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-18 15:40:36'),
(102, 16, 'sp_review_from_io', '', 8, '2025-07-18 15:40:36'),
(103, 15, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-18 15:42:23'),
(104, 15, 'sp_review_from_io', '', 8, '2025-07-18 15:42:23'),
(105, 16, 'c_section_review', 'checked case', 2, '2025-07-18 15:48:25'),
(106, 15, 'c_section_review', 'checked case', 2, '2025-07-18 15:48:40'),
(107, 16, 'collector_review', 'compensation is approved', 3, '2025-07-18 15:49:59'),
(108, 16, 'collector_review', 'compensation is approved', 3, '2025-07-18 15:50:05'),
(109, 15, 'social_welfare_review', 'no remarks', 3, '2025-07-18 15:53:06'),
(110, 15, 'from_social_welfare', 'job allocation done', 5, '2025-07-18 15:55:09'),
(111, 15, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-19 15:08:49'),
(112, 15, 'sp_review_from_io', '', 8, '2025-07-19 15:08:49'),
(113, 15, 'c_section_review', 'no remarks', 2, '2025-07-19 15:09:13'),
(114, 15, 'social_welfare_review', 'Forwarded to Social Welfare by C Section', 3, '2025-07-19 15:10:06'),
(115, 15, 'from_social_welfare', 'checked the details ', 5, '2025-07-19 15:10:48'),
(116, 15, 'collector_review', 'no remakrks', 3, '2025-07-19 15:14:27'),
(117, 15, 'collector_allotted', 'yes allot it', 4, '2025-07-19 15:14:59'),
(118, 16, 'collector_reverify', 'noo', 4, '2025-07-19 15:15:14'),
(119, 16, 'collector_approved', 'yess', 4, '2025-07-19 15:31:34'),
(120, 15, 'collector_allotted', 'yess', 4, '2025-07-19 15:31:48'),
(121, 17, 'dcr_review', NULL, 1, '2025-07-21 11:49:38'),
(122, 17, 'sp_review', 'no remarks', 7, '2025-07-21 11:50:24'),
(123, 17, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 11:51:07'),
(124, 17, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 11:52:33'),
(125, 17, 'sp_review_from_io', '', 8, '2025-07-21 11:52:34'),
(126, 17, 'c_section_review', 'no remarks', 2, '2025-07-21 11:53:09'),
(127, 17, 'collector_review', 'no remarks', 3, '2025-07-21 11:54:06'),
(128, 17, 'collector_review', 'no remarks', 3, '2025-07-21 11:54:13'),
(129, 17, 'collector_review', 'no remarksjhugvugv', 3, '2025-07-21 12:02:04'),
(130, 17, 'collector_review', 'fevqefv', 3, '2025-07-21 12:04:36'),
(131, 17, 'collector_approved', 'uhbgu', 4, '2025-07-21 12:05:01'),
(132, 17, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 12:06:02'),
(133, 17, 'sp_review_from_io', '', 8, '2025-07-21 12:06:02'),
(134, 17, 'c_section_review', 'n uhuhb', 2, '2025-07-21 12:06:26'),
(135, 17, 'social_welfare_review', 'sdvv', 3, '2025-07-21 12:07:10'),
(136, 17, 'from_social_welfare', 'hh h ', 5, '2025-07-21 12:07:34'),
(137, 17, 'collector_review', ' huhu uhbu', 3, '2025-07-21 12:07:52'),
(138, 18, 'dcr_review', NULL, 1, '2025-07-21 12:32:08'),
(139, 19, 'dcr_review', NULL, 1, '2025-07-21 12:33:39'),
(140, 19, 'sp_review', 'asdvqwef', 7, '2025-07-21 12:34:03'),
(141, 18, 'sp_review', 'hbqsuvd', 7, '2025-07-21 12:34:18'),
(142, 18, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 12:34:47'),
(143, 19, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 12:35:01'),
(144, 18, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 12:36:12'),
(145, 18, 'sp_review_from_io', '', 8, '2025-07-21 12:36:12'),
(146, 19, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 12:37:15'),
(147, 19, 'sp_review_from_io', '', 8, '2025-07-21 12:37:15'),
(148, 19, 'c_section_review', 'vgvyuvg', 2, '2025-07-21 12:37:39'),
(149, 18, 'c_section_review', 'huvuhv', 2, '2025-07-21 12:37:49'),
(150, 19, 'collector_review', 'hvuvuy', 3, '2025-07-21 12:39:32'),
(151, 18, 'social_welfare_review', 'gvygytyct', 3, '2025-07-21 12:39:55'),
(152, 18, 'from_social_welfare', 'jvygcytc', 5, '2025-07-21 12:40:22'),
(153, 18, 'collector_review', 'vytytc', 3, '2025-07-21 12:40:44'),
(154, 18, 'collector_allotted', 'gcyfcyfc', 4, '2025-07-21 12:41:08'),
(155, 19, 'collector_reverify', 'vgvygcy', 4, '2025-07-21 12:41:17'),
(156, 20, 'dcr_review', NULL, 1, '2025-07-21 15:57:14'),
(157, 20, 'sp_review', 'uyugvgvyv', 7, '2025-07-21 16:04:43'),
(158, 20, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 16:21:30'),
(159, 20, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 16:28:33'),
(160, 20, 'sp_review_from_io', '', 8, '2025-07-21 16:28:33'),
(161, 20, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 16:30:43'),
(162, 20, 'sp_review_from_io', '', 8, '2025-07-21 16:30:43'),
(163, 21, 'dcr_review', NULL, 1, '2025-07-21 16:42:52'),
(164, 21, 'sp_review', 'qwdcwef', 7, '2025-07-21 16:43:09'),
(165, 21, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 16:43:30'),
(166, 21, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 16:44:40'),
(167, 21, 'sp_review_from_io', '', 8, '2025-07-21 16:44:40'),
(168, 21, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 16:48:09'),
(169, 21, 'sp_review_from_io', '', 8, '2025-07-21 16:48:09'),
(170, 22, 'dcr_review', NULL, 1, '2025-07-21 16:49:57'),
(171, 22, 'sp_review', 'juhu  u', 7, '2025-07-21 16:50:11'),
(172, 22, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 16:50:30'),
(173, 22, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 16:51:34'),
(174, 22, 'sp_review_from_io', '', 8, '2025-07-21 16:51:34'),
(175, 23, 'dcr_review', NULL, 1, '2025-07-21 16:59:40'),
(176, 23, 'sp_review', 'wdnibwdubhd', 7, '2025-07-21 17:00:06'),
(177, 23, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-21 17:01:23'),
(178, 23, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-21 17:03:00'),
(179, 23, 'sp_review_from_io', '', 8, '2025-07-21 17:03:00'),
(180, 23, 'c_section_review', 'hvuvuv', 2, '2025-07-21 17:03:25'),
(181, 23, 'social_welfare_review', 'Forwarded to Social Welfare by C Section', 3, '2025-07-21 17:03:47'),
(182, 23, 'from_social_welfare', 'bhuh', 5, '2025-07-21 17:04:08'),
(183, 23, 'collector_review', ',ihbuhv', 3, '2025-07-21 17:04:23'),
(184, 23, 'collector_allotted', 'uhygvyg', 4, '2025-07-21 17:04:50'),
(185, 24, 'dcr_review', NULL, 1, '2025-07-22 06:05:31'),
(186, 24, 'sp_review', 'jgvgv', 7, '2025-07-22 06:06:07'),
(187, 24, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-22 06:06:58'),
(188, 24, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-22 06:08:03'),
(189, 24, 'sp_review_from_io', '', 8, '2025-07-22 06:08:03'),
(190, 24, 'c_section_review', 'hgvyg', 2, '2025-07-22 06:08:45'),
(191, 24, 'collector_review', 'vhghg', 3, '2025-07-22 06:09:55'),
(192, 25, 'dcr_review', NULL, 1, '2025-07-22 06:11:09'),
(193, 25, 'sp_review', 'n jvj', 7, '2025-07-22 06:11:20'),
(194, 25, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-22 06:11:34'),
(195, 25, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-22 06:12:34'),
(196, 25, 'sp_review_from_io', '', 8, '2025-07-22 06:12:34'),
(197, 25, 'c_section_review', 'jv hg', 2, '2025-07-22 06:13:00'),
(198, 25, 'social_welfare_review', 'hgchchgc', 3, '2025-07-22 06:13:27'),
(199, 25, 'from_social_welfare', 'b hgchg', 5, '2025-07-22 06:13:49'),
(200, 25, 'collector_review', 'vhghgc', 3, '2025-07-22 06:14:04'),
(201, 24, 'collector_approved', 'b hghg hg ', 4, '2025-07-22 06:14:25'),
(202, 25, 'collector_reverify', ' hghg ', 4, '2025-07-22 06:14:37'),
(203, 26, 'dcr_review', NULL, 1, '2025-07-22 06:50:08'),
(204, 27, 'dcr_review', NULL, 1, '2025-07-22 06:51:40'),
(205, 27, 'sp_review', 'no remarks', 7, '2025-07-22 06:53:46'),
(206, 26, 'sp_review', 'no comments', 7, '2025-07-22 06:54:02'),
(207, 26, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-22 06:55:09'),
(208, 27, 'io_investigation', 'Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-22 06:55:22'),
(209, 26, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-22 06:57:20'),
(210, 26, 'sp_review_from_io', '', 8, '2025-07-22 06:57:20'),
(211, 27, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-22 06:58:16'),
(212, 27, 'sp_review_from_io', '', 8, '2025-07-22 06:58:16'),
(213, 27, 'c_section_review', 'no remarks', 2, '2025-07-22 06:59:19'),
(214, 26, 'c_section_review', 'no comments', 2, '2025-07-22 07:00:10'),
(215, 27, 'collector_review', 'no remarks ', 3, '2025-07-22 07:04:18'),
(216, 27, 'collector_approved', 'approved ', 4, '2025-07-22 07:06:05'),
(217, 26, 'social_welfare_review', 'no remaeks ', 3, '2025-07-22 07:10:21'),
(218, 26, 'from_social_welfare', 'no comments', 5, '2025-07-22 07:11:14'),
(219, 28, 'dcr_review', NULL, 1, '2025-07-23 06:31:17'),
(220, 28, 'sp_review', 'qgyui', 7, '2025-07-23 06:32:23'),
(221, 28, 'io_investigation', 'Please prioritize this cawertyuiopse due to severity of allegations. Collect all available CCTV footage from the incident location.', 2, '2025-07-23 06:33:18'),
(222, 28, 'sp_review_from_io', 'IO submitted investigation report', 8, '2025-07-23 06:35:06'),
(223, 28, 'sp_review_from_io', '', 8, '2025-07-23 06:35:06'),
(224, 28, 'c_section_review', 'gdxfhjkl;', 2, '2025-07-23 06:35:47'),
(225, 28, 'social_welfare_review', 'dxgf', 3, '2025-07-23 06:38:49'),
(226, 28, 'from_social_welfare', 'qsdfghj', 5, '2025-07-23 06:39:53'),
(227, 28, 'collector_review', 'sdfghk/', 3, '2025-07-23 06:40:31'),
(228, 28, 'collector_reverify', 'ertyuio;\'', 4, '2025-07-23 06:41:16');

-- --------------------------------------------------------

--
-- Table structure for table `compensation`
--

CREATE TABLE `compensation` (
  `compensation_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','disbursed') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensation_approvals`
--

CREATE TABLE `compensation_approvals` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `approved_amount` decimal(10,2) NOT NULL,
  `approved_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensation_breakdown`
--

CREATE TABLE `compensation_breakdown` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensation_recommendations`
--

CREATE TABLE `compensation_recommendations` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `recommended_amount` decimal(10,2) NOT NULL,
  `recommended_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_officer` varchar(100) DEFAULT NULL,
  `verification_remarks` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `disbursement_timeline` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investigation_reports`
--

CREATE TABLE `investigation_reports` (
  `report_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `findings` text DEFAULT NULL,
  `witness_summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('draft','submitted','reviewed') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investigation_reports`
--

INSERT INTO `investigation_reports` (`report_id`, `case_id`, `findings`, `witness_summary`, `recommendations`, `submitted_by`, `submitted_at`, `status`) VALUES
(30, 16, 'in investigation i had found out that the case victim is innocent !', 'witness approved that he is affected by the case', 'i throughly gn ethorugh investigations', 8, '2025-07-18 15:40:36', 'submitted'),
(31, 15, 'in investigation i had found out that the case victim is innocent !', 'witness approved that he is affected by the case', 'i throughly gn ethorugh investigations', 8, '2025-07-18 15:42:23', 'submitted'),
(32, 15, 'in investigation i had found out that the case victim is innocent !', 'witness approved that he is affected by the case', 'i throughly gn ethorugh investigations', 8, '2025-07-19 15:08:49', 'submitted'),
(33, 17, 'svwqdvwv', 'wvwv', 'wqv', 8, '2025-07-21 11:52:33', 'submitted'),
(34, 17, 'svwqdvwv', 'wvwv', 'wqv', 8, '2025-07-21 12:06:02', 'submitted'),
(35, 18, 'sadfgasfg', 'afafg', 'afagfg', 8, '2025-07-21 12:36:09', 'draft'),
(36, 18, 'sadfgasfg', 'afafg', 'afagfg', 8, '2025-07-21 12:36:12', 'submitted'),
(37, 19, 'dfvadfv', 'svafdsv', 'asdvasd', 8, '2025-07-21 12:37:15', 'submitted'),
(38, 20, 'ygygc', 'k jhh', 'h huhv', 8, '2025-07-21 16:28:30', 'draft'),
(39, 20, 'ygygc', 'k jhh', 'h huhv', 8, '2025-07-21 16:28:33', 'submitted'),
(40, 20, 'ygygc', 'k jhh', 'h huhv', 8, '2025-07-21 16:30:43', 'submitted'),
(41, 21, 'qvqwv', 'wewv', 'wdvqvw', 8, '2025-07-21 16:44:40', 'submitted'),
(42, 21, 'qvqwv', 'wewv', 'wdvqvw', 8, '2025-07-21 16:48:09', 'submitted'),
(43, 22, 'uvygvy', ' huvgvygv', ' bubuvuyv', 8, '2025-07-21 16:51:34', 'submitted'),
(44, 23, 'b hb h ', 'bhg gcf', 'kjhbuhb', 8, '2025-07-21 17:03:00', 'submitted'),
(45, 24, 'zgbsgafv', 'vhghgc', 'bjuhvujv', 8, '2025-07-22 06:08:03', 'submitted'),
(46, 25, 'asvchgvcs', 'sc hgsc hqgsc', 'ascn jsch ', 8, '2025-07-22 06:12:34', 'submitted'),
(47, 26, 'The incident occurred at the reported location and time. Evidence and statements confirm a caste-based offense under the SC/ST Act.', 'Witnesses consistently support the victim’s claims, confirming the accused’s presence and abusive behavior.', 'Recommend filing charges under SC/ST Act and forwarding the case for legal action.', 8, '2025-07-22 06:57:20', 'submitted'),
(48, 27, 'The incident occurred at the reported location and time. Evidence and statements confirm a caste-based offense under the SC/ST Act.', 'Witnesses consistently support the victim’s claims, confirming the accused’s presence and abusive behavior.', 'Recommend filing charges under SC/ST Act and forwarding the case for legal action.', 8, '2025-07-22 06:58:16', 'submitted'),
(49, 28, 'wertghj', 'qwert', 'qwert', 8, '2025-07-23 06:35:06', 'submitted');

-- --------------------------------------------------------

--
-- Table structure for table `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `recommendation` varchar(100) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `job_allocated` varchar(20) NOT NULL DEFAULT 'peon'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recommendations`
--

INSERT INTO `recommendations` (`id`, `case_id`, `recommendation`, `comments`, `created_by`, `created_at`, `job_allocated`) VALUES
(2, 15, 'he is elgible for peon post at any category', 'checked the details ', 5, '2025-07-19 20:40:48', 'peon'),
(3, 17, 'he is elgible for peon post at any category', 'hh h ', 5, '2025-07-21 17:37:34', 'typist'),
(4, 18, 'he is elgible for peon post at any category', 'jvygcytc', 5, '2025-07-21 18:10:22', 'peon'),
(5, 23, 'he is elgible for peon post at any category', 'bhuh', 5, '2025-07-21 22:34:08', 'peon'),
(6, 25, 'he is elgible for peon post at any category', 'b hgchg', 5, '2025-07-22 11:43:49', 'typist'),
(7, 26, 'he is elgible for peon post at any category', 'no comments', 5, '2025-07-22 12:41:14', 'peon'),
(8, 28, 'he is elgible for peon post at any category', 'qsdfghj', 5, '2025-07-23 12:09:53', 'clerk');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('police','rdo','c_section','collector','social_welfare','dcr','io') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'police', '$2y$10$3d/YEUDgNlpEb4IwUlZKzup1v41zMcb4ed.akldKpffy//8aC9hFa', 'police', '2025-06-12 07:08:45'),
(2, 'rdo', '$2y$10$LuXzniiMvTccK788KEKREuViVLa1G3EwN0409IMVwfblQywYKSHoy', 'rdo', '2025-06-12 07:08:45'),
(3, 'c_section', '$2y$10$/YK5puW08TMDj8eumY1Sye0TDYCN9.6J7g3gatK8mt7V512sTYJRC', 'c_section', '2025-06-12 07:08:45'),
(4, 'collector', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'collector', '2025-06-12 07:08:45'),
(5, 'social_welfare', '$2y$10$nCegL0gaXqSvpLGKVIxCWefuaKF60pYg6G8TbPuG2QpJ5Fl70ePlO', 'social_welfare', '2025-06-12 07:08:45'),
(7, 'dcr_user', '$2y$10$VwCXqxA/Tbqn3evBLIVvB..uKnP2.uFhxjj.1wK/kWo5T2cnu/nP.', 'dcr', '2025-07-03 06:16:40'),
(8, 'io_user1', '$2y$10$CfE4GzAwJ6IUCywwtdwp..xqxrgbGfUKLEYs1NDaKX2tsLuBX/dgS', 'io', '2025-07-03 09:14:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`case_id`),
  ADD KEY `filed_by` (`filed_by`);

--
-- Indexes for table `case_documents`
--
ALTER TABLE `case_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `case_status`
--
ALTER TABLE `case_status`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `compensation`
--
ALTER TABLE `compensation`
  ADD PRIMARY KEY (`compensation_id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `compensation_approvals`
--
ALTER TABLE `compensation_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `compensation_breakdown`
--
ALTER TABLE `compensation_breakdown`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`);

--
-- Indexes for table `compensation_recommendations`
--
ALTER TABLE `compensation_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `recommended_by` (`recommended_by`);

--
-- Indexes for table `investigation_reports`
--
ALTER TABLE `investigation_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `submitted_by` (`submitted_by`);

--
-- Indexes for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `case_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `case_documents`
--
ALTER TABLE `case_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `case_status`
--
ALTER TABLE `case_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT for table `compensation`
--
ALTER TABLE `compensation`
  MODIFY `compensation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensation_approvals`
--
ALTER TABLE `compensation_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `compensation_breakdown`
--
ALTER TABLE `compensation_breakdown`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensation_recommendations`
--
ALTER TABLE `compensation_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `investigation_reports`
--
ALTER TABLE `investigation_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`filed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `case_documents`
--
ALTER TABLE `case_documents`
  ADD CONSTRAINT `case_documents_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `case_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `case_status`
--
ALTER TABLE `case_status`
  ADD CONSTRAINT `case_status_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `case_status_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `compensation`
--
ALTER TABLE `compensation`
  ADD CONSTRAINT `compensation_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `compensation_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `compensation_approvals`
--
ALTER TABLE `compensation_approvals`
  ADD CONSTRAINT `compensation_approvals_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `compensation_approvals_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `compensation_breakdown`
--
ALTER TABLE `compensation_breakdown`
  ADD CONSTRAINT `compensation_breakdown_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`);

--
-- Constraints for table `compensation_recommendations`
--
ALTER TABLE `compensation_recommendations`
  ADD CONSTRAINT `compensation_recommendations_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `compensation_recommendations_ibfk_2` FOREIGN KEY (`recommended_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `investigation_reports`
--
ALTER TABLE `investigation_reports`
  ADD CONSTRAINT `investigation_reports_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `investigation_reports_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD CONSTRAINT `recommendations_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `recommendations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
