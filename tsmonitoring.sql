-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2026 at 10:16 AM
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
-- Database: `tsmonitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `asset_location_history`
--

CREATE TABLE `asset_location_history` (
  `history_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `previous_latitude` decimal(10,8) DEFAULT NULL,
  `previous_longitude` decimal(11,8) DEFAULT NULL,
  `previous_sector_id` int(11) DEFAULT NULL,
  `previous_circle_id` int(11) DEFAULT NULL,
  `new_latitude` decimal(10,8) NOT NULL,
  `new_longitude` decimal(11,8) NOT NULL,
  `new_sector_id` int(11) NOT NULL,
  `new_circle_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `change_reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_types`
--

CREATE TABLE `asset_types` (
  `asset_type_id` int(11) NOT NULL,
  `type` enum('SANITATION','') NOT NULL DEFAULT 'SANITATION',
  `name` varchar(60) NOT NULL,
  `description` varchar(200) NOT NULL,
  `questions` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `asset_types`
--

INSERT INTO `asset_types` (`asset_type_id`, `type`, `name`, `description`, `questions`, `status`) VALUES
(1, 'SANITATION', 'Type-1 FRP Septic Tank', 'Fiber Reinforced Plastic (FRP) Toilets (with Septic Tank)', '1,2,3,4,5,6,7,8,9,10,11,12,13,14', 1),
(2, 'SANITATION', 'Type-2 FRP Soak Pit', 'Fiber Reinforced Plastic (FRP) Toilets (with Soak Pit)', '1,2,15,4,5,6,7,8,9,10,11,12,13,14', 1),
(3, 'SANITATION', 'Type-3 FRP Urinals', 'Fiber Reinforced Plastic (FRP) Urinals (with Septic Tank / Soak Pit)', '3,16,17,9', 1),
(4, 'SANITATION', 'Type-4 Prefab Steel Septic Tank', 'Prefabricated Steel Toilets with Septic Tank', '1,2,3,4,5,6,7,8,9,10,11,12,13,14', 1),
(5, 'SANITATION', 'Type-5 Prefab Steel Soak Pit', 'Prefabricated Steel Toilets (with soak pit)', '1,2,15,4,5,6,7,8,9,10,11,12,13,14', 1),
(6, 'SANITATION', 'Type-6 Kanath Soak Pit', 'Tentage / Kanath Toilets (with soak pit) – Integrated Structure (Sub Structure & Super Structure)', '5,10,11,12,13,14,15,16', 1),
(8, 'SANITATION', 'Type-8 Govt Cemented Toilets', 'Govt Cemented Toilets 4x4 and 8x8', '3,4,5,6,7,8,10,12,13,14,15', 1),
(9, 'SANITATION', 'Type-9 Vehicle Mounted Mobile Toilets', 'Vehicle mounted mobile toilets –10 toilets/ unit', '0', 1),
(10, 'SANITATION', 'Type-10 Special Toilets – VIP', 'Special Toilets – Specially Designed Structures VIP', '0', 1);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `swachhagrahi_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` timestamp NULL DEFAULT NULL,
  `check_out_time` timestamp NULL DEFAULT NULL,
  `attendance_status` enum('PRESENT','ABSENT','LATE','HALF_DAY','LEAVE') DEFAULT 'ABSENT',
  `location_latitude` decimal(10,8) DEFAULT NULL,
  `location_longitude` decimal(11,8) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `circles`
--

CREATE TABLE `circles` (
  `circle_id` int(11) NOT NULL,
  `circle_name` varchar(100) NOT NULL,
  `circle_code` varchar(50) NOT NULL,
  `sector_id` int(11) NOT NULL,
  `boundary_coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boundary_coordinates`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `circles`
--

INSERT INTO `circles` (`circle_id`, `circle_name`, `circle_code`, `sector_id`, `boundary_coordinates`, `created_at`) VALUES
(1, 'Circle A', 'CIRC001', 1, NULL, '2026-01-24 08:13:23'),
(2, 'Circle B', 'CIRC002', 1, NULL, '2026-01-24 08:13:23'),
(3, 'Circle C', 'CIRC003', 2, NULL, '2026-01-24 08:13:23'),
(4, 'Circle D', 'CIRC004', 3, NULL, '2026-01-24 08:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ci_sessions`
--

INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES
('vepr483q1kpns74bjlcvdge0vis9tg00', '127.0.0.1', 4294967295, 0x5f5f63695f6c6173745f726567656e65726174657c693a313736383831383239303b667461646d696e5f6c6f67696e5f63737266746f6b656e7c733a33323a226335343333336431303530376431633038393464323131613939363732313036223b5f63695f70726576696f75735f75726c7c733a33323a22687474703a2f2f656d692e6c6f632f652d6d616e646174652d7061636b616765223b757365725f69647c733a313a2231223b73657373696f6e5f746f6b656e7c733a33323a226630623366613538643039666534373935663564383763306536633939393564223b);

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `incident_code` varchar(50) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
  `description` text DEFAULT NULL,
  `incident_status` enum('OPEN','ASSIGNED','IN_PROGRESS','RESOLVED','CLOSED','REOPENED') DEFAULT 'OPEN',
  `due_date` datetime DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_history`
--

CREATE TABLE `incident_history` (
  `history_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `inspection_id` int(11) NOT NULL,
  `allocation_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `swachhagrahi_id` int(11) NOT NULL,
  `inspection_date` date NOT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `questions_answered` int(11) DEFAULT NULL,
  `questions_answers_data` text DEFAULT NULL COMMENT 'JSON',
  `compliance_score` decimal(5,2) DEFAULT NULL,
  `overall_status` enum('COMPLIANT','NON_COMPLIANT','PARTIAL') NOT NULL,
  `notes` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('INCIDENT_ASSIGNED','INCIDENT_RESOLVED','SHIFT_ASSIGNED','ATTENDANCE_REMINDER','ASSET_ALLOCATED','INSPECTION_DUE') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `related_entity_type` enum('INCIDENT','INSPECTION','ASSET','ATTENDANCE') DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('LOW','MEDIUM','HIGH') DEFAULT 'MEDIUM',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `log_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `delivery_status` enum('SENT','DELIVERED','FAILED','READ') NOT NULL,
  `delivery_channel` enum('PUSH','SMS','EMAIL','IN_APP') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('YES_NO','RATING','TEXT','NUMBER','MULTIPLE_CHOICE') NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `expected_answer` varchar(500) DEFAULT NULL,
  `condition_type` enum('EQUALS','NOT_EQUALS','GREATER_THAN','LESS_THAN','CONTAINS') DEFAULT NULL,
  `condition_value` varchar(500) DEFAULT NULL,
  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM',
  `is_mandatory` tinyint(1) DEFAULT 1,
  `is_photo_mandatory` tinyint(4) NOT NULL DEFAULT 0,
  `sequence` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `question_text`, `question_type`, `options`, `expected_answer`, `condition_type`, `condition_value`, `severity`, `is_mandatory`, `is_photo_mandatory`, `sequence`, `is_active`) VALUES
(1, 'Is the toilet floor clean?', 'YES_NO', NULL, 'YES', 'EQUALS', 'NO', 'MEDIUM', 1, 0, 1, 1),
(2, 'Are the toilet seats clean?', 'YES_NO', NULL, 'YES', 'EQUALS', 'NO', 'MEDIUM', 1, 0, 2, 1),
(3, 'Is water available in the toilet?', 'YES_NO', NULL, 'YES', 'EQUALS', 'NO', 'HIGH', 1, 0, 1, 1),
(4, 'Rate the overall condition of the toilet infrastructure (1-5)', 'RATING', '[1,2,3,4,5]', '4', 'LESS_THAN', '3', 'MEDIUM', 1, 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sanitation_assets`
--

CREATE TABLE `sanitation_assets` (
  `sanitation_asset_id` int(11) NOT NULL,
  `asset_type_id` int(11) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `asset_name` varchar(200) NOT NULL,
  `short_url` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `gender` enum('MALE','FEMALE','UNISEX','OTHER') NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `vendor_asset_code` varchar(100) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','UNDER_MAINTENANCE','DECOMMISSIONED') DEFAULT 'ACTIVE',
  `sector_id` int(11) NOT NULL,
  `circle_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sanitation_asset_allocations`
--

CREATE TABLE `sanitation_asset_allocations` (
  `allocation_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `swachhagrahi_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `allocated_by` int(11) NOT NULL,
  `allocation_date` date NOT NULL,
  `status` enum('ACTIVE','COMPLETED','CANCELLED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `sector_id` int(11) NOT NULL,
  `sector_name` varchar(100) NOT NULL,
  `sector_code` varchar(50) NOT NULL,
  `boundary_coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boundary_coordinates`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`sector_id`, `sector_name`, `sector_code`, `boundary_coordinates`, `created_at`) VALUES
(1, 'Central Zone', 'SECT001', NULL, '2026-01-24 08:13:23'),
(2, 'East Zone', 'SECT002', NULL, '2026-01-24 08:13:23'),
(3, 'West Zone', 'SECT003', NULL, '2026-01-24 08:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `session_token` varchar(200) NOT NULL,
  `logged_in` datetime DEFAULT NULL,
  `logged_out` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session`
--

INSERT INTO `session` (`session_id`, `user_id`, `session_token`, `logged_in`, `logged_out`, `status`) VALUES
(19, 1, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiMSIsImlhdCI6MTc2OTY2ODA1MCwiZXhwIjoxNzY5NjcxNjUwfQ.YO6FKJFKG4IqwrrVsllGjuJLVhrIkn6dkOTpfjx54e8', '2026-01-29 11:57:30', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `shift_id` int(11) NOT NULL,
  `shift_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`shift_id`, `shift_name`, `start_time`, `end_time`, `is_active`) VALUES
(1, 'Morning Shift', '08:00:00', '16:00:00', 1),
(2, 'Evening Shift', '16:00:00', '00:00:00', 1),
(3, 'Night Shift', '00:00:00', '08:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `config_type` enum('STRING','NUMBER','BOOLEAN','JSON','ARRAY') DEFAULT 'STRING',
  `is_active` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`config_id`, `config_key`, `config_value`, `description`, `config_type`, `is_active`, `updated_by`, `updated_at`) VALUES
(1, 'INCIDENT_DUE_HOURS', '24', 'Hours within which incident should be resolved', 'NUMBER', 1, NULL, '2026-01-24 08:13:23'),
(2, 'ATTENDANCE_GEO_FENCE_RADIUS', '100', 'Radius in meters for attendance geo-fencing', 'NUMBER', 1, NULL, '2026-01-24 08:13:23'),
(3, 'COMPLIANCE_THRESHOLD', '80', 'Minimum compliance percentage for assets', 'NUMBER', 1, NULL, '2026-01-24 08:13:23'),
(4, 'NOTIFICATION_ENABLED', 'true', 'Enable/disable notifications', 'BOOLEAN', 1, NULL, '2026-01-24 08:13:23'),
(5, 'SYNC_INTERVAL_MINUTES', '15', 'Mobile sync interval in minutes', 'NUMBER', 1, NULL, '2026-01-24 08:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_type_id` int(11) NOT NULL DEFAULT 0,
  `vendor_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `otp` varchar(6) NOT NULL DEFAULT '0',
  `otp_expiry` datetime DEFAULT NULL,
  `otp_attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `code`, `password_hash`, `email`, `phone`, `full_name`, `user_type_id`, `vendor_id`, `is_active`, `otp`, `otp_expiry`, `otp_attempts`, `created_at`, `updated_at`) VALUES
(1, 'KSH2026012822035279600002', '$2y$10$YRhN3leJLjukd/jbjafuBu/qyPPC6fER5DF/NMN0dHjGF5PYtAS9K', 'admin@example.com', '911234567890', 'Admin User', 1, 0, 1, '0', NULL, 0, '2026-01-24 11:03:49', '2026-01-29 06:22:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `user_type_id` int(11) NOT NULL,
  `user_type` varchar(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `index_no` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`user_type_id`, `user_type`, `index_no`, `status`) VALUES
(1, 'Super Admin', 1, 1),
(2, 'Admin', 2, 1),
(3, 'Mela Adhikari', 3, 1),
(4, 'Additional Mela Adhikari', 4, 1),
(5, 'Incharge Sanitation', 5, 1),
(6, 'Sector Medical Officer (SMO)', 6, 1),
(7, 'Sub Divisional  Magistrate (SDM)', 7, 1),
(8, 'Nayab Tahsildar / Sector Magistrate', 8, 1),
(9, 'Circle Inspector', 9, 1),
(10, 'Gram Panchayat/Vikas Adhikari', 10, 0),
(11, 'Vendor', 11, 1),
(12, 'Vendor Supervisor', 12, 1),
(13, 'Monitoring Agent (GSD)', 13, 1),
(14, 'Supervisor Monitoring Agent (Swachhagrahis)', 14, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_type_permissions`
--

CREATE TABLE `user_type_permissions` (
  `permission_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `permission` enum('users','user-permissions','shift','circle','sector','question','asset-type') NOT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_view` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type_permissions`
--

INSERT INTO `user_type_permissions` (`permission_id`, `user_type_id`, `permission`, `can_create`, `can_view`, `can_edit`, `can_delete`, `created_at`) VALUES
(1, 1, 'user-permissions', 1, 1, 1, 1, '2026-01-29 06:28:34'),
(2, 1, 'users', 1, 1, 1, 1, '2026-01-29 06:28:34');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `vendor_name` varchar(200) NOT NULL,
  `vendor_code` varchar(50) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset_location_history`
--
ALTER TABLE `asset_location_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_asset_loc_history_asset` (`asset_id`),
  ADD KEY `idx_asset_loc_history_date` (`changed_at`);

--
-- Indexes for table `asset_types`
--
ALTER TABLE `asset_types`
  ADD PRIMARY KEY (`asset_type_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `idx_attendance_user_date` (`swachhagrahi_id`,`attendance_date`),
  ADD KEY `idx_attendance_shift_date` (`shift_id`,`attendance_date`),
  ADD KEY `idx_attendance_status` (`attendance_status`);

--
-- Indexes for table `circles`
--
ALTER TABLE `circles`
  ADD PRIMARY KEY (`circle_id`),
  ADD UNIQUE KEY `circle_code` (`circle_code`);

--
-- Indexes for table `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD UNIQUE KEY `incident_code` (`incident_code`),
  ADD KEY `idx_incidents_status` (`incident_status`),
  ADD KEY `idx_incidents_vendor` (`vendor_id`),
  ADD KEY `idx_incidents_reported_by` (`reported_by`),
  ADD KEY `idx_incidents_assigned_to` (`assigned_to`),
  ADD KEY `idx_incidents_asset` (`asset_id`),
  ADD KEY `idx_incidents_severity` (`severity`),
  ADD KEY `idx_incidents_created_date` (`created_at`);

--
-- Indexes for table `incident_history`
--
ALTER TABLE `incident_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_incident_history_incident` (`incident_id`),
  ADD KEY `idx_incident_history_date` (`changed_at`);

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`inspection_id`),
  ADD KEY `idx_inspections_asset_date` (`asset_id`,`inspection_date`),
  ADD KEY `idx_inspections_swachhagrahi` (`swachhagrahi_id`),
  ADD KEY `idx_inspections_allocation` (`allocation_id`),
  ADD KEY `idx_inspections_status` (`overall_status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read_status` (`is_read`),
  ADD KEY `idx_notifications_created` (`created_at`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_questions_active` (`is_active`);

--
-- Indexes for table `sanitation_assets`
--
ALTER TABLE `sanitation_assets`
  ADD PRIMARY KEY (`sanitation_asset_id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `idx_assets_qr_code` (`qr_code`),
  ADD KEY `idx_assets_vendor_id` (`vendor_id`),
  ADD KEY `idx_assets_location` (`sector_id`,`circle_id`),
  ADD KEY `idx_assets_geo` (`latitude`,`longitude`),
  ADD KEY `idx_assets_status` (`status`);

--
-- Indexes for table `sanitation_asset_allocations`
--
ALTER TABLE `sanitation_asset_allocations`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `idx_allocations_swachhagrahi` (`swachhagrahi_id`),
  ADD KEY `idx_allocations_date_status` (`allocation_date`,`status`),
  ADD KEY `idx_allocations_asset` (`asset_id`),
  ADD KEY `idx_allocations_shift` (`shift_id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`sector_id`),
  ADD UNIQUE KEY `sector_code` (`sector_code`);

--
-- Indexes for table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`shift_id`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_user_type` (`user_type_id`),
  ADD KEY `idx_users_vendor_id` (`vendor_id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`user_type_id`);

--
-- Indexes for table `user_type_permissions`
--
ALTER TABLE `user_type_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `unique_permission` (`user_type_id`,`permission`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`),
  ADD UNIQUE KEY `vendor_code` (`vendor_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset_location_history`
--
ALTER TABLE `asset_location_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_types`
--
ALTER TABLE `asset_types`
  MODIFY `asset_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circles`
--
ALTER TABLE `circles`
  MODIFY `circle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_history`
--
ALTER TABLE `incident_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sanitation_assets`
--
ALTER TABLE `sanitation_assets`
  MODIFY `sanitation_asset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sanitation_asset_allocations`
--
ALTER TABLE `sanitation_asset_allocations`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `sector_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `user_type_permissions`
--
ALTER TABLE `user_type_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
