-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2026 at 05:44 PM
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
-- Database: `brgy_ams`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `document_type` varchar(120) NOT NULL,
  `purpose` varchar(200) NOT NULL,
  `appt_date` date NOT NULL,
  `appt_time` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','rescheduled') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `walkin_name` varchar(150) DEFAULT NULL,
  `walkin_phone` varchar(20) DEFAULT NULL,
  `walkin_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `document_type`, `purpose`, `appt_date`, `appt_time`, `notes`, `status`, `admin_note`, `deleted_at`, `created_at`, `walkin_name`, `walkin_phone`, `walkin_address`) VALUES
(6, 11, 'Barangay Clearance', 'School', '2026-04-23', '09:00 AM', 'Purposes', 'approved', 'Bring', NULL, '2026-04-22 07:02:42', NULL, NULL, NULL),
(7, 11, 'Barangay Certificate of Residency', 'Employs', '2026-04-24', '10:00 AM', '1234556', 'approved', 'Bring Valid ID', NULL, '2026-04-22 07:03:01', NULL, NULL, NULL),
(8, 15, 'Certificate of Good Moral Character', 'School', '2026-04-22', '10:00 AM', 'for the school', 'approved', 'sorry', NULL, '2026-04-22 12:51:07', NULL, NULL, NULL),
(10, 16, 'Barangay Indigency Certificate', 'Balay', '2026-04-22', '08:00 AM', 'asd', 'approved', NULL, '2026-04-22 15:27:29', '2026-04-22 13:05:51', NULL, NULL, NULL),
(11, 16, 'Barangay Indigency Certificate', 'Balay', '2026-04-22', '10:00 AM', 'para sayu', 'approved', NULL, NULL, '2026-04-22 13:06:16', NULL, NULL, NULL),
(12, NULL, 'Barangay Clearance', 'Employs', '2026-04-23', '02:00 PM', 'For job', 'approved', NULL, NULL, '2026-04-22 13:17:23', 'Nikko Peublas', '09123456789', 'Mactan'),
(13, NULL, 'Barangay Indigency Certificate', '123', '2026-04-24', '08:00 AM', 'asd', 'approved', NULL, NULL, '2026-04-22 14:30:22', 'Diane', '09456321879', 'LLc');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(5, 11, 'Welcome to Barangay AMS!', 'Your account has been verified.', 1, '2026-04-22 06:03:15'),
(6, 11, 'Appointment Submitted', 'Your appointment for Barangay Clearance on 2026-04-23 at 09:00 AM is now pending review.', 1, '2026-04-22 07:02:42'),
(7, 6, 'New Appointment Request', 'Marlon Villarin has requested Barangay Clearance on 2026-04-23.', 1, '2026-04-22 07:02:42'),
(8, 11, 'Appointment Submitted', 'Your appointment for Barangay Certificate of Residency on 2026-04-24 at 10:00 AM is now pending review.', 1, '2026-04-22 07:03:01'),
(9, 6, 'New Appointment Request', 'Marlon Villarin has requested Barangay Certificate of Residency on 2026-04-24.', 1, '2026-04-22 07:03:01'),
(10, 11, 'Appointment Approved', 'Your appointment for Barangay Clearance on 2026-04-23 has been approved. Note: Bring', 1, '2026-04-22 07:04:23'),
(11, 11, 'Appointment Approved', 'Your appointment for Barangay Certificate of Residency on 2026-04-24 has been approved. Note: Bring Valid ID', 1, '2026-04-22 07:09:00'),
(12, 11, 'Appointment Approved', 'Your appointment for Barangay Certificate of Residency on 2026-04-24 has been approved. Note: Bring Valid ID', 1, '2026-04-22 07:09:10'),
(13, 11, 'Appointment Approved', 'Your appointment for Barangay Certificate of Residency on 2026-04-24 has been approved. Note: Bring Valid ID', 1, '2026-04-22 07:09:38'),
(14, 11, 'Appointment Approved', 'Your appointment for Barangay Certificate of Residency on 2026-04-24 has been approved. Note: Bring Valid ID', 1, '2026-04-22 07:11:47'),
(15, 16, 'Appointment Removed', 'Your appointment #10 has been removed by admin.', 0, '2026-04-22 15:27:29'),
(16, 15, 'Appointment Approved', 'Your appointment for Certificate of Good Moral Character on 2026-04-22 has been approved. Note: sorry', 0, '2026-04-22 15:28:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `role` enum('admin','resident') DEFAULT 'resident',
  `is_verified` tinyint(1) DEFAULT 0,
  `verify_code` varchar(6) DEFAULT NULL,
  `verify_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `is_verified`, `verify_code`, `verify_expires`, `created_at`) VALUES
(6, 'Admin', 'admin@brgy.gov.ph', '$2y$10$x2anpNmEhGCAhInR0ifFp.O3r/64dcuIY8iU76DkXbrUYfEieZ6ky', '09123456789', 'Canada', 'admin', 1, NULL, NULL, '2026-04-21 02:42:27'),
(7, 'Juan Dela Cruz', 'juan@gmail.com', '$2y$10$z.hxeVLU6zgLqS4OQOUOMuABU1ANbw9se2E87ztTg1/blhmMfv6sy', '', '', '', 1, NULL, NULL, '2026-04-21 02:42:27'),
(11, 'Marlon Villarin', 'marlonvillarin69@gmail.com', '$2y$10$RRzM71lpyK82LWM.xqwAqe9HJFFtm/PHXdb9WxDvvB.krc3xIfI/O', '09283464210', 'Lapu Lapu City Cebu', 'resident', 1, NULL, NULL, '2026-04-22 06:02:51'),
(14, 'Marlota Villarin', 'marlonvillarin70@gmail.com', '$2y$10$RL8luuVNF2FNYCS/bRDiw.zuxi2awUq1afce0JJ8VC4kjHqenpgSq', '09132456879', 'Busay', 'resident', 0, '188340', '2026-04-22 08:24:28', '2026-04-22 06:07:33'),
(15, 'Jonnel Villarin', 'walkin_1776862267@local', '', '09123456789', 'Walk-in', 'resident', 1, NULL, NULL, '2026-04-22 12:51:07'),
(16, 'Walk-in User', 'walkin@system.local', '', '', '', '', 1, NULL, NULL, '2026-04-22 12:54:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
