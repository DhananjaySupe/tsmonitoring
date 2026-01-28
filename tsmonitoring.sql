-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 04:04 PM
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
  `swachhagrahi_id` int(11) NOT NULL,
  `inspection_date` date NOT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `questions_answered` int(11) DEFAULT NULL,
  `questions_answers_data` text DEFAULT NULL COMMENT 'JSON',
  `compliance_score` decimal(5,2) DEFAULT NULL,
  `overall_status` enum('COMPLIANT','NON_COMPLIANT','PARTIAL') NOT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `notes` text DEFAULT NULL,
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
  `current_photo_url` varchar(500) DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_type_id` int(11) NOT NULL DEFAULT 0,
  `vendor_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `user_type_permissions`
--

CREATE TABLE `user_type_permissions` (
  `permission_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `permission` enum('SECTOR','CIRCLE') NOT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_read` tinyint(1) DEFAULT 0,
  `can_update` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD UNIQUE KEY `username` (`username`),
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
  MODIFY `asset_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circles`
--
ALTER TABLE `circles`
  MODIFY `circle_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `sector_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_type_permissions`
--
ALTER TABLE `user_type_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
