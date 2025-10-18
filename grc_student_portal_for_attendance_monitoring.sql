-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2025 at 02:05 AM
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
-- Database: `grc_student_portal_for_attendance_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

CREATE TABLE `administrators` (
  `admin_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrators`
--

INSERT INTO `administrators` (`admin_id`, `first_name`, `last_name`, `email`, `password`, `created_at`, `updated_at`) VALUES
('ADM001', 'Westlie', 'Casuncad', 'west@gmail.com', '25f9e794323b453885f5181f1b624d0b', '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('ADM002', 'Sarah', 'Johnson', 'sarah.johnson@grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', '2025-08-28 07:13:53', '2025-08-28 07:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `class_id` varchar(20) DEFAULT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `class_id`, `date`, `status`, `remarks`, `created_at`) VALUES
(1, 'STU001', 'CLASS001', '2024-01-15', 'Present', 'On time', '2025-08-28 07:13:53'),
(2, 'STU001', 'CLASS001', '2024-01-17', 'Present', 'On time', '2025-08-28 07:13:53'),
(3, 'STU001', 'CLASS002', '2024-01-16', 'Late', 'Arrived 10 minutes late', '2025-08-28 07:13:53'),
(4, 'STU002', 'CLASS001', '2024-01-15', 'Present', 'On time', '2025-08-28 07:13:53'),
(5, 'STU002', 'CLASS001', '2024-01-17', 'Absent', 'Sick leave', '2025-08-28 07:13:53'),
(6, 'STU002', 'CLASS003', '2024-01-16', 'Present', 'On time', '2025-08-28 07:13:53'),
(7, 'STU003', 'CLASS002', '2024-01-16', 'Present', 'On time', '2025-08-28 07:13:53'),
(8, 'STU003', 'CLASS004', '2024-01-18', 'Present', 'On time', '2025-08-28 07:13:53'),
(9, 'STU004', 'CLASS003', '2024-01-16', 'Late', 'Arrived 5 minutes late', '2025-08-28 07:13:53'),
(10, 'STU004', 'CLASS005', '2024-01-15', 'Present', 'On time', '2025-09-02 06:43:58'),
(11, 'STU005', 'CLASS004', '2024-01-18', 'Present', 'On time', '2025-08-28 07:13:53'),
(12, 'STU005', 'CLASS005', '2024-01-15', 'Excused', 'Family emergency', '2025-09-02 06:43:58'),
(15, 'STU002', 'CLASS003', '2025-08-30', 'Present', '', '2025-09-14 16:54:48'),
(16, 'STU004', 'CLASS003', '2025-08-30', 'Present', '', '2025-09-14 16:54:48'),
(17, 'STU001', 'CLASS1756441963', '2025-08-29', 'Absent', 'Pogi mo po', '2025-08-31 00:04:33'),
(18, 'STU001', 'CLASSTEST1', '2025-08-30', 'Absent', '', '2025-08-30 16:06:04'),
(19, 'STU001', 'CLASSTEST1', '2025-08-31', 'Absent', '', '2025-08-30 16:06:33'),
(20, 'STU001', 'CLASS1756542883', '2025-08-30', 'Absent', '', '2025-08-30 16:36:06'),
(23, 'STU001', 'CLASS001', '2023-08-30', 'Present', '', '2025-08-30 17:06:39'),
(24, 'STU001', 'CLASS1756441963', '2025-08-31', 'Present', 'Congrats pumasok din', '2025-09-02 05:39:32'),
(25, 'STU001', 'CLASS005', '2024-01-15', 'Present', '', '2025-09-02 06:43:58'),
(26, 'STU004', 'CLASS005', '2025-09-02', 'Present', '', '2025-10-04 11:44:01'),
(27, 'STU005', 'CLASS005', '2025-09-02', 'Absent', 'DAPAT HINDI KA KASAMA SA DATA NI DENMAR', '2025-10-04 11:44:01'),
(28, 'STU001', 'CLASS005', '2025-09-02', 'Present', 'DAPAT KAY DENMAR KA LANG', '2025-10-04 11:44:01'),
(29, 'STU001', 'CLASS1756767458', '2025-09-02', 'Excused', 'may sakit', '2025-09-07 20:35:48'),
(30, 'STU002', 'CLASS1756767458', '2025-09-02', 'Present', '', '2025-09-07 20:35:48'),
(31, 'STU001', 'CLASS1756900369', '2025-09-03', 'Present', 'PINAKA POGI SA LAHAT (TL)', '2025-09-03 19:54:30'),
(32, 'STU001', 'CLASS1756767458', '2025-09-07', 'Present', 'magaling na', '2025-09-07 20:36:30'),
(33, 'STU002', 'CLASS1756767458', '2025-09-07', 'Absent', '', '2025-09-07 20:36:30'),
(36, 'STU004', 'CLASS005', '2025-10-04', 'Late', '', '2025-10-04 11:45:47'),
(37, 'STU005', 'CLASS005', '2025-10-04', 'Absent', '', '2025-10-04 11:45:47'),
(38, 'STU001', 'CLASS005', '2025-10-04', 'Excused', 'May sakit', '2025-10-04 11:45:47'),
(39, 'STU001', 'CLASS1760514979', '2025-10-15', 'Present', '10 points', '2025-10-15 16:11:01'),
(40, 'STU001', 'CLASS1760514979', '2025-10-18', 'Present', '', '2025-10-18 03:56:47'),
(41, 'STU001', 'CLASS1760514979', '2025-10-17', 'Absent', '', '2025-10-18 03:42:34'),
(42, 'STU001', 'CLASS1760514979', '2025-10-19', 'Absent', '', '2025-10-18 04:07:36'),
(43, 'STU002', 'CLASS003', '2025-10-18', 'Absent', '', '2025-10-18 04:15:31'),
(44, 'STU004', 'CLASS003', '2025-10-18', 'Absent', '', '2025-10-18 04:15:31'),
(45, 'STU004', 'CLASS005', '2025-10-18', 'Absent', '', '2025-10-18 04:18:20'),
(46, 'STU005', 'CLASS005', '2025-10-18', 'Absent', '', '2025-10-18 04:18:20'),
(47, 'STU001', 'CLASS005', '2025-10-18', 'Absent', '', '2025-10-18 04:18:20');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` varchar(20) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `subject_id` varchar(20) DEFAULT NULL,
  `professor_id` varchar(20) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `school_year_semester_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `class_code`, `subject_id`, `professor_id`, `schedule`, `room`, `created_at`, `updated_at`, `section`, `semester_id`, `status`, `school_year_semester_id`) VALUES
('CLASS001', 'CS101 Section A', '5OK7ZE0C', 'SUB001', 'PROF001', 'MWF 8:00-9:30 AM', 'Room 101', '2025-08-28 07:13:53', '2025-08-29 12:19:16', '301', 3, 'active', 4),
('CLASS002', 'MATH101 Section B', 'MATH101-B', 'SUB002', 'PROF002', 'TTH 10:00-11:30 AM', 'Room 202', '2025-08-28 07:13:53', '2025-08-28 07:13:53', '302', 3, 'active', 4),
('CLASS003', 'CS201 Section C', 'NOW0G94U', 'SUB003', 'PROF001', 'MWF 1:00-2:30 PM', 'Room 303', '2025-08-28 07:13:53', '2025-08-30 14:50:30', '301', 3, 'active', 4),
('CLASS004', 'CS301 Section A', 'CS301-A', 'SUB004', 'PROF003', 'TTH 2:00-3:30 PM', 'Room 404', '2025-08-28 07:13:53', '2025-08-28 07:13:53', '302', 3, 'active', 4),
('CLASS005', 'ENG101 Section D', '6XL8WS9V', 'SUB005', 'PROF001', 'MWF 3:00-4:30 PM', 'Room 505', '2025-08-28 07:13:53', '2025-09-02 06:47:27', '301', 3, 'active', 4),
('CLASS1756423371', 'Database Management System. Class', '4553218', 'SUB1756423371', 'PF0F004', 'Bahala ka na', 'LAB 3', '2025-08-29 07:22:51', '2025-08-29 07:22:51', '302', 3, 'active', 4),
('CLASS1756425193', 'System Architecture Class', 'N2X1QVPI', 'SUB1756425193', 'PROF001', '321354', 'LAB 81', '2025-08-29 07:53:13', '2025-09-02 06:24:46', '301', 3, 'active', 4),
('CLASS1756441963', 'HOW TO BE HOTDOG Class', 'A3U3ZXL6', 'SUB1756441963', 'PROF001', 'ANYTIME', 'ANYWHERE', '2025-08-29 12:32:43', '2025-08-30 14:50:14', '302', 3, 'active', 4),
('CLASS1756494311', 'HOW TO BE POGI Class', 'AS8O992R', 'SUB1756494311', 'PROF001', 'CCF', 'CCF', '2025-08-30 03:05:11', '2025-08-30 03:05:11', '303', 3, 'active', 4),
('CLASS1756542883', 'EWAN Class', 'WLCV0T8N', 'SUB1756542883', 'PROF001', 'ANY', 'SA LABAS', '2025-08-30 16:34:43', '2025-09-02 06:24:30', '301', 3, 'active', 4),
('CLASS1756767458', 'TUMESTING KA Class', 'M4FFPLJT', 'SUB1756767458', 'PROF001', 'Not sure', 'lab 3', '2025-09-02 06:57:38', '2025-10-04 04:42:50', '304', 3, 'active', 4),
('CLASS1756900369', 'POGI Class', '7CGDOSFT', 'SUB1756900369', '111111', 'ANYTIME', 'ANYWHERE', '2025-09-03 19:52:49', '2025-09-03 19:52:49', '305', 3, 'active', 4),
('CLASS1757248087', 'Funda Class', '6IXPRD3R', 'SUB1757248087', 'PF0F004', 'SHELL', 'SHELL CAFE', '2025-09-07 20:28:07', '2025-09-07 20:29:31', '306', 3, 'active', 4),
('CLASS1759971443', '2 Class', 'WJR7ZESJ', 'SUB1759971443', 'PROF001', '2', '2', '2025-10-09 08:57:23', '2025-10-09 08:57:23', NULL, 3, 'active', 4),
('CLASS1760512359', 'lknsdf;glnk Class', '16P84Y8J', 'SUB1760512359', 'PROF001', ';\'lkdnfg;lkn', '\';lkdnfg;\'lkn', '2025-10-15 15:12:39', '2025-10-15 15:12:39', NULL, NULL, 'active', 3),
('CLASS1760514979', '4 Class', '68RS6M1R', 'SUB1760514979', 'PROF001', '4', '4', '2025-10-15 15:56:19', '2025-10-18 00:39:06', NULL, NULL, 'active', 1),
('CLASSTEST1', 'Test Subject Class', 'BSBJK30I', 'SUBTEST1', 'PROF001', 'MWF 9:00-10:00', 'Room 101', '2025-08-30 15:40:29', '2025-09-02 06:24:37', '306', 3, 'active', 4);

-- --------------------------------------------------------

--
-- Table structure for table `class_enrollments`
--

CREATE TABLE `class_enrollments` (
  `id` int(11) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `enrollment_status` enum('Enrolled','Dropped','Completed') DEFAULT 'Enrolled',
  `grade` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_enrollments`
--

INSERT INTO `class_enrollments` (`id`, `class_id`, `student_id`, `enrollment_status`, `grade`, `created_at`, `updated_at`) VALUES
(1, 'CLASS001', 'STU001', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(2, 'CLASS001', 'STU002', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(3, 'CLASS003', 'STU002', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(4, 'CLASS002', 'STU003', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(5, 'CLASS004', 'STU003', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(6, 'CLASS003', 'STU004', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(7, 'CLASS005', 'STU004', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(8, 'CLASS004', 'STU005', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(9, 'CLASS005', 'STU005', 'Enrolled', NULL, '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(10, 'CLASS1756441963', 'STU001', 'Enrolled', NULL, '2025-08-29 04:33:12', '2025-09-18 23:24:22'),
(11, 'CLASS1756542883', 'STU001', 'Enrolled', NULL, '2025-08-30 08:35:48', '2025-09-18 23:24:22'),
(12, 'CLASS1756494311', 'STU001', 'Enrolled', NULL, '2025-08-30 16:40:34', '2025-09-18 23:24:22'),
(13, 'CLASS005', 'STU001', 'Enrolled', NULL, '2025-09-01 22:41:39', '2025-09-18 23:24:22'),
(14, 'CLASS1756767458', 'STU001', 'Enrolled', NULL, '2025-09-01 22:57:51', '2025-09-18 23:24:22'),
(15, 'CLASS1756767458', 'STU002', 'Enrolled', NULL, '2025-09-01 22:58:34', '2025-09-18 23:24:22'),
(16, 'CLASS1756900369', 'STU001', 'Enrolled', NULL, '2025-09-03 11:53:48', '2025-09-18 23:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `class_professors`
--

CREATE TABLE `class_professors` (
  `id` int(11) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `professor_id` varchar(20) NOT NULL,
  `role` enum('Main','Co-teacher') DEFAULT 'Main',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_professors`
--

INSERT INTO `class_professors` (`id`, `class_id`, `professor_id`, `role`, `created_at`, `updated_at`) VALUES
(1, 'CLASS001', 'PROF001', 'Main', '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(2, 'CLASS002', 'PROF002', 'Main', '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(3, 'CLASS003', 'PROF001', 'Main', '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(4, 'CLASS004', 'PROF003', 'Main', '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(5, 'CLASS005', 'PROF001', 'Main', '2025-08-27 23:13:53', '2025-09-18 23:24:22'),
(6, 'CLASS1756423371', 'PF0F004', 'Main', '2025-08-28 23:22:51', '2025-09-18 23:24:22'),
(7, 'CLASS1756425193', 'PROF001', 'Main', '2025-08-28 23:53:13', '2025-09-18 23:24:22'),
(8, 'CLASS1756441963', 'PROF001', 'Main', '2025-08-29 04:32:43', '2025-09-18 23:24:22'),
(9, 'CLASS1756494311', 'PROF001', 'Main', '2025-08-29 19:05:11', '2025-09-18 23:24:22'),
(10, 'CLASS1756542883', 'PROF001', 'Main', '2025-08-30 08:34:43', '2025-09-18 23:24:22'),
(11, 'CLASS1756767458', 'PROF001', 'Main', '2025-09-01 22:57:38', '2025-09-18 23:24:22'),
(12, 'CLASS1756900369', '111111', 'Main', '2025-09-03 11:52:49', '2025-09-18 23:24:22'),
(13, 'CLASS1757248087', 'PF0F004', 'Main', '2025-09-07 12:28:07', '2025-09-18 23:24:22'),
(16, 'CLASSTEST1', 'PROF001', 'Main', '2025-08-30 07:40:29', '2025-09-18 23:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `created_at`, `updated_at`) VALUES
(1, 'College of Business Administration', '2025-10-17 20:26:59', '2025-10-18 00:07:04'),
(2, 'College of Entrepreneurship', '2025-10-17 20:26:59', '2025-10-17 20:26:59'),
(3, 'College of Accountancy', '2025-10-17 20:26:59', '2025-10-17 20:26:59'),
(4, 'College of Education', '2025-10-17 20:26:59', '2025-10-17 20:26:59'),
(5, 'College of Computer Studies', '2025-10-17 20:26:59', '2025-10-17 20:26:59');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_requests`
--

CREATE TABLE `enrollment_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `handled_at` datetime DEFAULT NULL,
  `handled_by` varchar(20) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment_requests`
--

INSERT INTO `enrollment_requests` (`request_id`, `student_id`, `class_id`, `status`, `requested_at`, `handled_at`, `handled_by`, `processed_at`, `processed_by`) VALUES
(1, 'STU001', 'CLASS1758315545', 'accepted', '2025-09-20 04:59:25', NULL, NULL, NULL, NULL),
(2, 'STU001', 'CLASS1758316330', 'pending', '2025-09-20 05:12:16', NULL, NULL, NULL, NULL),
(3, 'STU001', 'CLASS1758316848', 'accepted', '2025-09-20 05:48:48', NULL, NULL, NULL, NULL),
(4, 'STU001', 'CLASS1758322968', 'accepted', '2025-09-20 07:02:57', NULL, NULL, NULL, NULL),
(5, 'STU001', 'CLASS1758323183', 'accepted', '2025-09-20 07:06:34', NULL, NULL, NULL, NULL),
(6, 'STU001', 'CLASS1758324051', 'pending', '2025-09-20 07:21:04', NULL, NULL, NULL, NULL),
(7, 'STU001', 'CLASS1758324104', 'pending', '2025-09-20 07:22:02', NULL, NULL, NULL, NULL),
(8, 'STU001', 'CLASS1758324306', 'accepted', '2025-09-20 07:25:12', NULL, NULL, '2025-09-20 07:25:18', 'PROF001'),
(9, 'STU001', 'CLASS1758325422', 'accepted', '2025-09-20 07:45:55', NULL, NULL, '2025-09-20 07:46:02', 'PROF001'),
(15, 'STU001', 'CLASS1758326678', 'accepted', '2025-09-20 08:04:45', NULL, NULL, '2025-09-20 08:04:52', 'PROF001'),
(16, 'STU001', 'CLASS1758327155', 'accepted', '2025-09-20 08:12:45', NULL, NULL, '2025-09-20 08:12:52', 'PROF001'),
(17, 'STU001', 'CLASS1758327842', 'accepted', '2025-09-20 08:24:15', NULL, NULL, '2025-09-20 08:24:24', 'PROF001'),
(22, 'STU001', 'CLASS1758327989', 'accepted', '2025-09-20 08:26:39', NULL, NULL, '2025-09-20 08:26:46', 'PROF001'),
(23, 'STU001', 'CLASS1758336375', 'accepted', '2025-09-20 10:46:29', NULL, NULL, '2025-09-20 10:46:38', 'PROF001'),
(25, 'STU001', 'CLASS1758337263', 'accepted', '2025-09-20 11:01:11', NULL, NULL, '2025-09-20 11:01:19', 'PROF001'),
(26, 'STU001', 'CLASS1758337356', 'rejected', '2025-09-20 11:02:48', NULL, NULL, '2025-09-20 11:05:22', 'PROF001'),
(28, 'STU001', 'CLASS1758337557', 'accepted', '2025-09-20 11:06:09', NULL, NULL, '2025-09-20 11:06:18', 'PROF001'),
(30, 'STU001', 'CLASS1758362678', 'pending', '2025-09-20 18:04:47', NULL, NULL, NULL, NULL),
(31, 'STU001', 'CLASS1758364037', 'accepted', '2025-09-20 18:27:26', NULL, NULL, '2025-09-20 18:27:32', 'PROF001'),
(32, 'STU001', 'CLASS1758379061', 'accepted', '2025-09-20 22:37:52', NULL, NULL, '2025-09-20 22:38:23', 'PROF001'),
(33, 'STU001', 'CLASS1758431306', 'accepted', '2025-09-21 13:08:35', NULL, NULL, '2025-09-21 13:14:14', 'PROF001'),
(34, 'STU001', 'CLASS1758439340', 'accepted', '2025-09-21 15:22:30', NULL, NULL, '2025-09-21 15:22:39', 'PROF001'),
(36, 'STU001', 'CLASS1758439515', 'accepted', '2025-09-21 15:25:21', NULL, NULL, '2025-09-21 15:25:28', 'PROF001'),
(37, 'STU001', 'CLASS1758440607', 'accepted', '2025-09-21 15:44:32', NULL, NULL, '2025-09-21 15:44:46', 'PROF001'),
(38, 'STU001', 'CLASS1758955783', 'rejected', '2025-09-27 14:50:02', NULL, NULL, '2025-09-27 14:50:23', 'PROF001'),
(40, 'STU001', 'CLASS1759024920', 'rejected', '2025-09-28 10:02:26', NULL, NULL, '2025-09-28 10:03:26', 'PROF001'),
(43, 'STU001', 'CLASS1759033268', 'pending', '2025-09-28 12:21:28', NULL, NULL, NULL, NULL),
(44, 'STU001', 'CLASS1759034012', 'pending', '2025-09-28 12:33:48', NULL, NULL, NULL, NULL),
(45, 'STU001', 'CLASS1759034585', 'accepted', '2025-09-28 12:43:22', NULL, NULL, '2025-09-28 13:22:33', 'PROF001'),
(46, 'STU001', 'CLASS1759042465', 'accepted', '2025-09-28 14:54:37', NULL, NULL, '2025-09-28 14:54:59', 'PROF001'),
(47, 'STU001', 'CLASS1759528083', 'pending', '2025-10-04 05:48:17', NULL, NULL, NULL, NULL),
(48, 'STU001', 'CLASS1759530017', 'accepted', '2025-10-04 06:20:39', NULL, NULL, '2025-10-04 06:44:47', 'PROF001'),
(50, 'STU001', 'CLASS1759532102', 'accepted', '2025-10-04 06:55:12', NULL, NULL, '2025-10-04 06:55:51', 'PROF001'),
(51, 'STU001', 'CLASS1759549022', 'accepted', '2025-10-04 11:38:27', NULL, NULL, '2025-10-04 11:38:54', 'PROF001'),
(52, 'STU001', 'CLASS1759963777', 'accepted', '2025-10-09 06:49:47', NULL, NULL, '2025-10-09 06:49:56', 'PROF001'),
(53, 'STU001', 'CLASS1759965189', 'accepted', '2025-10-09 07:25:46', NULL, NULL, '2025-10-09 07:25:50', 'PROF001'),
(56, 'STU001', 'CLASS1759966442', 'accepted', '2025-10-09 07:35:26', NULL, NULL, '2025-10-09 07:35:36', 'PROF001'),
(57, 'STU001', 'CLASS1759966660', 'accepted', '2025-10-09 07:37:45', NULL, NULL, '2025-10-09 07:37:49', 'PROF001'),
(58, 'STU001', 'CLASS1759966921', 'accepted', '2025-10-09 07:42:08', NULL, NULL, '2025-10-09 07:42:17', 'PROF001'),
(59, 'STU001', 'CLASS1759967600', 'accepted', '2025-10-09 08:34:58', NULL, NULL, '2025-10-09 08:35:05', 'PROF001'),
(60, 'STU001', 'CLASS1759971443', 'accepted', '2025-10-13 10:43:46', NULL, NULL, '2025-10-15 12:54:33', 'PROF001'),
(61, 'STU001', 'CLASS1760514979', 'pending', '2025-10-18 07:42:38', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `user_type` enum('student','professor','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('enrollment_approved','enrollment_rejected','unenrollment_approved','unenrollment_rejected','info','warning','success') NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `related_request_id` int(11) DEFAULT NULL,
  `related_class_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `user_type`, `title`, `message`, `type`, `is_read`, `created_at`, `related_request_id`, `related_class_id`) VALUES
(1, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:44:42', NULL, NULL),
(2, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:45:02', NULL, NULL),
(3, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:45:28', NULL, NULL),
(4, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:45:50', NULL, NULL),
(5, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:46:11', NULL, NULL),
(6, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:46:26', NULL, NULL),
(7, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:46:39', NULL, NULL),
(8, '12345', 'student', 'Test Notification', 'This is a test notification to verify the system works.', 'info', 0, '2025-09-28 10:46:52', NULL, NULL),
(15, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 4, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-04 11:38:57', 25, 'CLASS001'),
(16, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 06:41:09', 26, 'CLASS1759549022'),
(17, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 06:46:20', 28, 'CLASS1756494311'),
(20, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 07:02:39', 30, 'CLASS1759963777'),
(21, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 07:02:50', 31, 'CLASS1759963777'),
(22, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 9, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-09 07:03:05', 32, 'CLASS1759963777'),
(26, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 07:29:33', 33, 'CLASS1759965189'),
(27, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 07:30:01', 34, 'CLASS1759965189'),
(31, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 07:36:17', 36, 'CLASS1759966442'),
(32, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 9, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-09 07:36:35', 37, 'CLASS1759966442'),
(34, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 9, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-09 07:38:03', 38, 'CLASS1759966660'),
(38, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 07:56:34', 40, 'CLASS1759967600'),
(48, 'STU001', 'student', 'Unenrollment Request Submitted', 'Your unenrollment request has been submitted and is pending professor approval.', '', 0, '2025-10-09 08:46:08', 43, 'CLASS1759967600'),
(49, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 08:46:23', 43, 'CLASS1759967600'),
(52, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 9, 2025.', 'unenrollment_rejected', 0, '2025-10-09 08:58:13', 44, 'CLASS1759971443'),
(53, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 9, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-09 08:58:35', 45, 'CLASS1759971443'),
(58, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 13, 2025.', 'unenrollment_rejected', 0, '2025-10-13 10:43:08', 46, 'CLASS1759971443'),
(59, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 13, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-13 10:43:25', 47, 'CLASS1759971443'),
(61, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 15, 2025.', 'unenrollment_rejected', 0, '2025-10-15 12:57:22', 48, 'CLASS1759971443'),
(70, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 15, 2025.', 'unenrollment_rejected', 0, '2025-10-15 16:10:11', 50, 'CLASS1760514979'),
(71, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 15, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-15 16:10:22', 51, 'CLASS1760514979'),
(73, 'STU001', 'student', 'Unenrollment Request Rejected', 'Your unenrollment request has been rejected by Professor Danhil Baluyot on Oct 17, 2025.', 'unenrollment_rejected', 0, '2025-10-18 05:34:09', 52, 'CLASS1760514979'),
(74, 'STU001', 'student', 'Unenrollment Request Approved', 'Your unenrollment request has been approved by Professor Danhil Baluyot on Oct 17, 2025. You have been unenrolled from the class.', 'unenrollment_approved', 0, '2025-10-18 05:34:27', 53, 'CLASS1760514979');

-- --------------------------------------------------------

--
-- Table structure for table `professors`
--

CREATE TABLE `professors` (
  `professor_id` varchar(20) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professors`
--

INSERT INTO `professors` (`professor_id`, `employee_id`, `first_name`, `last_name`, `email`, `password`, `department`, `mobile`, `created_at`, `updated_at`) VALUES
('', '', 'John', 'Doe', 'john@example.com', 'password', 'College of Business Administration', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
('111111', '51525251', 'DERICK1', 'Boado', 'derickboado1@gmail.com', '$2y$10$qqQ2t9z56nD4X16pOKA8/e0zU.2dmK5nvEPvsyxFFZeTvzme9wFMi', 'College of Computer Studies', '09155004507', '2025-09-03 13:50:59', '2025-09-07 20:24:48'),
('PF0F004', '32165498', 'HATDOG', 'JUMBO', 'g@Gmail.com', '25f9e794323b453885f5181f1b624d0b', 'College of Education', '0999554214', '2025-08-28 13:57:57', '2025-09-02 06:22:59'),
('PROF001', 'EMP001', 'Danhil', 'Baluyot', 'dbaluyot@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'College of Business Administration', '+639123456789', '2025-08-28 07:13:53', '2025-08-28 08:29:54'),
('PROF002', 'EMP002', 'Maria', 'Santos', 'maria.santos@grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', 'College of Entrepreneurship', '+639234567890', '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('PROF003', 'EMP003', 'Robert', 'Garcia', 'robert.garcia@grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', 'College of Accountancy', '+639345678901', '2025-08-28 07:13:53', '2025-08-28 07:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `professor_attendance`
--

CREATE TABLE `professor_attendance` (
  `attendance_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `professor_id` varchar(20) NOT NULL,
  `subject_id` varchar(20) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professor_subjects`
--

CREATE TABLE `professor_subjects` (
  `assignment_id` int(11) NOT NULL,
  `professor_id` varchar(20) DEFAULT NULL,
  `subject_id` varchar(20) DEFAULT NULL,
  `assigned_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professor_subjects`
--

INSERT INTO `professor_subjects` (`assignment_id`, `professor_id`, `subject_id`, `assigned_at`) VALUES
(1, 'PROF001', 'SUB001', '2025-08-28 07:13:53'),
(2, 'PROF001', 'SUB003', '2025-08-28 07:13:53'),
(3, 'PROF002', 'SUB002', '2025-08-28 07:13:53'),
(4, 'PROF003', 'SUB004', '2025-08-28 07:13:53'),
(5, 'PROF003', 'SUB005', '2025-08-28 07:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `year_label` varchar(20) NOT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `year_label`, `status`, `created_at`, `updated_at`) VALUES
(4, '2024-2025', 'Archived', '2025-09-18 23:24:22', '2025-10-15 06:20:25'),
(5, '2025-2026', 'Archived', '2025-09-18 23:24:22', '2025-10-15 07:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `school_year_semester`
--

CREATE TABLE `school_year_semester` (
  `id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('1st Semester','2nd Semester','Summer') NOT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_year_semester`
--

INSERT INTO `school_year_semester` (`id`, `school_year`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(1, '2024-2025', '1st Semester', 'Active', '2025-09-18 23:24:22', '2025-10-17 17:46:06'),
(2, '2024-2025', '2nd Semester', 'Archived', '2025-09-18 23:24:22', '2025-10-17 17:00:47'),
(3, '2025-2026', '1st Semester', 'Active', '2025-09-18 23:24:22', '2025-10-17 17:46:00'),
(4, '2025-2026', '2nd Semester', 'Active', '2025-09-18 23:24:22', '2025-10-17 17:46:02');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `semester_name` enum('1st Semester','2nd Semester','Summer') NOT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `school_year_id`, `semester_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, '1st Semester', 'Archived', '2025-09-18 23:24:22', '2025-09-18 23:24:22'),
(2, 4, '2nd Semester', 'Archived', '2025-09-18 23:24:22', '2025-09-18 23:24:22'),
(3, 5, '1st Semester', 'Archived', '2025-09-18 23:24:22', '2025-10-15 07:14:30'),
(4, 5, '2nd Semester', 'Archived', '2025-09-18 23:24:22', '2025-10-15 07:14:45');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `middle_name`, `email`, `password`, `mobile`, `address`, `created_at`, `updated_at`, `section`) VALUES
('123123qw1e123', 'asdfasdf', 'awerawef', '', '123@123.com', '$2y$10$mxjD.FOG9/3sJ.12sN.nvuF5/TWz/gJN95lEUN6BLllC70jzz6dnm', '', '1231qwe', '2025-10-17 23:40:55', '2025-10-17 23:40:55', '12312'),
('2017-11547-57', 'KKKK', 'KKKK', 'KKKK', 'KKK@gmail.com', '25f9e794323b453885f5181f1b624d0b', '09123547315', 'ewan', '2025-09-02 06:24:12', '2025-09-02 06:24:12', 'A'),
('ST001', 'Test', 'Student', NULL, 'test@student.com', '5f4dcc3b5aa765d61d8327deb882cf99', '1234567890', 'Test Address', '2025-09-28 11:10:20', '2025-09-28 11:10:20', NULL),
('STU001', 'Denmar', 'Curtivo', 'R', 'dcurtivo@gmail.com', '25f9e794323b453885f5181f1b624d0b', '+639456789012', 'GEDLI LANG', '2025-08-28 07:13:53', '2025-09-14 11:58:13', '301'),
('STU002', 'Jane', 'Smith', 'Anne', 'jane.smith@student.grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', '+639567890123', '456 Oak St, Quezon City', '2025-08-28 07:13:53', '2025-08-28 07:13:53', 'B'),
('STU003', 'David', 'Lee', 'James', 'david.lee@student.grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', '+639678901234', '789 Pine St, Makati', '2025-08-28 07:13:53', '2025-08-28 07:13:53', 'A'),
('STU004', 'Sarah', 'Wilson', 'Marie', 'sarah.wilson@student.grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', '+639789012345', '321 Elm St, Pasig', '2025-08-28 07:13:53', '2025-08-28 07:13:53', 'C'),
('STU005', 'Mike', 'Brown', 'Thomas', 'mike.brown@student.grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', '+639890123456', '654 Maple St, Taguig', '2025-08-28 07:13:53', '2025-08-28 07:13:53', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `student_classes`
--

CREATE TABLE `student_classes` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `class_id` varchar(20) DEFAULT NULL,
  `enrolled_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_classes`
--

INSERT INTO `student_classes` (`enrollment_id`, `student_id`, `class_id`, `enrolled_at`) VALUES
(3, 'STU002', 'CLASS001', '2025-08-28 07:13:53'),
(4, 'STU002', 'CLASS003', '2025-08-28 07:13:53'),
(5, 'STU003', 'CLASS002', '2025-08-28 07:13:53'),
(6, 'STU003', 'CLASS004', '2025-08-28 07:13:53'),
(7, 'STU004', 'CLASS003', '2025-08-28 07:13:53'),
(8, 'STU004', 'CLASS005', '2025-08-28 07:13:53'),
(9, 'STU005', 'CLASS004', '2025-08-28 07:13:53'),
(10, 'STU005', 'CLASS005', '2025-08-28 07:13:53'),
(11, 'STU001', 'CLASS1756441963', '2025-08-29 12:33:12'),
(13, 'STU001', 'CLASS1756542883', '2025-08-30 16:35:48'),
(35, 'STU001', 'CLASS1756494311', '2025-08-31 00:40:34'),
(36, 'STU001', 'CLASS005', '2025-09-02 06:41:39'),
(37, 'STU001', 'CLASS1756767458', '2025-09-02 06:57:51'),
(38, 'STU002', 'CLASS1756767458', '2025-09-02 06:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) NOT NULL,
  `duration_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `semester_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `description`, `credits`, `duration_id`, `created_at`, `updated_at`, `semester_id`) VALUES
('SUB001', 'Introduction to Programming', 'CS101', 'Fundamentals of programming concepts and logic', 3, 1, '2025-08-28 07:13:53', '2025-08-28 07:13:53', 3),
('SUB002', 'Calculus I', 'MATH101', 'Differential and integral calculus', 4, 3, '2025-08-28 07:13:53', '2025-08-28 07:13:53', NULL),
('SUB003', 'Database Systems', 'CS201', 'Relational database design and SQL', 3, 1, '2025-08-28 07:13:53', '2025-08-28 07:13:53', NULL),
('SUB004', 'Web Development', 'CS301', 'Front-end and back-end web technologies', 3, 1, '2025-08-28 07:13:53', '2025-08-28 07:13:53', NULL),
('SUB005', 'Engineering Mathematics', 'ENG101', 'Mathematical methods for engineering', 4, 1, '2025-08-28 07:13:53', '2025-09-01 04:44:04', NULL),
('SUB1756423371', 'Database Management System.', 'DBMS', NULL, 3, 1, '2025-08-29 07:22:51', '2025-08-29 07:22:51', NULL),
('SUB1756425193', 'System Architecture', 'SYSARCH', NULL, 3, 3, '2025-08-29 07:53:13', '2025-09-02 06:24:46', NULL),
('SUB1756441963', 'HOW TO BE HOTDOG', 'HD12324', NULL, 3, 2, '2025-08-29 12:32:43', '2025-08-29 12:32:43', NULL),
('SUB1756494311', 'HOW TO BE POGI', 'IT 304', NULL, 3, 2, '2025-08-30 03:05:11', '2025-08-30 03:05:11', NULL),
('SUB1756542883', 'EWAN', '305', NULL, 3, 2, '2025-08-30 16:34:43', '2025-09-02 06:24:30', NULL),
('SUB1756767458', 'TUMESTING KA', 'IT1011', NULL, 3, 3, '2025-09-02 06:57:38', '2025-10-04 04:42:50', NULL),
('SUB1756900369', 'POGI', 'IT 301', NULL, 3, 3, '2025-09-03 19:52:49', '2025-09-03 19:52:49', NULL),
('SUB1757248087', 'Funda', 'IT 101', NULL, 3, 2, '2025-09-07 20:28:07', '2025-09-07 20:29:31', NULL),
('SUB1758595099', 'new', 'ne', NULL, 3, 3, '2025-09-23 10:38:19', '2025-09-23 10:38:19', NULL),
('SUB1759971443', '2', '2', NULL, 3, 1, '2025-10-09 08:57:23', '2025-10-09 08:57:23', NULL),
('SUB1760509671', 'New', 'New', NULL, 3, 3, '2025-10-15 14:27:51', '2025-10-15 14:27:51', NULL),
('SUB1760510658', '3', '3', NULL, 3, 3, '2025-10-15 14:44:18', '2025-10-15 14:44:18', NULL),
('SUB1760512359', 'lknsdf;glnk', 'sd;fknjs;lk', NULL, 3, 1, '2025-10-15 15:12:39', '2025-10-15 15:12:39', NULL),
('SUB1760514979', '4', '4', NULL, 3, 3, '2025-10-15 15:56:19', '2025-10-18 00:39:06', NULL),
('SUBARCH1', 'Archived Subject 2024-2025', 'ARCH2024', NULL, 3, 1, '2025-09-20 08:21:50', '2025-09-20 08:21:50', NULL),
('SUBARCH2', 'Archived Subject 2023-2024', 'ARCH2023', NULL, 3, 3, '2025-09-20 08:21:54', '2025-09-20 08:21:54', NULL),
('SUBTEST1', 'Test Subject', 'TS101', NULL, 3, 3, '2025-08-30 15:35:29', '2025-09-02 06:24:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_durations`
--

CREATE TABLE `subject_durations` (
  `duration_id` int(11) NOT NULL,
  `subject_duration` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_durations`
--

INSERT INTO `subject_durations` (`duration_id`, `subject_duration`) VALUES
(1, '1 hour 30 minutes'),
(2, '2 hours'),
(3, '3 hours');

-- --------------------------------------------------------

--
-- Table structure for table `unenrollment_requests`
--

CREATE TABLE `unenrollment_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `handled_at` datetime DEFAULT NULL,
  `handled_by` varchar(20) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unenrollment_requests`
--

INSERT INTO `unenrollment_requests` (`request_id`, `student_id`, `class_id`, `status`, `requested_at`, `handled_at`, `handled_by`, `processed_at`, `processed_by`) VALUES
(5, 'STU001', 'CLASS1758325422', 'pending', '2025-09-20 07:46:22', NULL, NULL, '2025-09-20 07:46:32', 'PROF001'),
(6, 'STU001', 'CLASS1758326678', 'rejected', '2025-09-20 08:05:05', NULL, NULL, '2025-09-20 08:05:14', 'PROF001'),
(10, 'STU001', 'CLASS1758327155', 'rejected', '2025-09-20 08:13:11', NULL, NULL, '2025-09-20 08:13:18', 'PROF001'),
(11, 'STU001', 'CLASS1758327842', 'pending', '2025-09-20 08:24:30', NULL, NULL, '2025-09-20 08:24:37', 'PROF001'),
(12, 'STU001', 'CLASS1756767458', 'rejected', '2025-09-20 08:24:47', NULL, NULL, '2025-09-20 08:25:03', 'PROF001'),
(13, 'STU001', 'CLASS1758336375', 'pending', '2025-09-20 10:49:03', NULL, NULL, '2025-09-20 10:52:16', 'PROF001'),
(14, 'STU001', 'CLASS1758337263', 'rejected', '2025-09-20 11:01:30', NULL, NULL, '2025-09-20 11:01:40', 'PROF001'),
(16, 'STU001', 'CLASS1758337557', 'pending', '2025-09-20 11:06:26', NULL, NULL, '2025-09-20 11:06:48', 'PROF001'),
(17, 'STU001', 'CLASS1758431306', 'pending', '2025-09-21 13:28:29', NULL, NULL, '2025-09-21 13:28:42', 'PROF001'),
(18, 'STU001', 'CLASS1758439340', 'pending', '2025-09-21 15:22:48', NULL, NULL, '2025-09-21 15:24:29', 'PROF001'),
(19, 'STU001', 'CLASS1758439515', 'rejected', '2025-09-21 15:26:13', NULL, NULL, '2025-09-21 15:40:43', 'PROF001'),
(21, 'STU001', 'CLASS1758440607', 'pending', '2025-09-21 15:45:16', NULL, NULL, '2025-09-21 15:47:05', 'PROF001'),
(22, 'STU001', 'CLASS1756542883', 'rejected', '2025-09-21 15:50:49', NULL, NULL, '2025-09-21 15:51:32', 'PROF001'),
(23, 'STU001', 'CLASS1756441963', 'rejected', '2025-09-21 15:51:14', NULL, NULL, '2025-09-21 15:51:30', 'PROF001'),
(24, 'STU001', 'CLASS1759530017', 'pending', '2025-10-04 06:46:48', NULL, NULL, '2025-10-04 06:47:04', 'PROF001'),
(25, 'STU001', 'CLASS001', 'accepted', '2025-10-04 11:34:08', NULL, NULL, '2025-10-04 11:38:57', 'PROF001'),
(26, 'STU001', 'CLASS1759549022', 'rejected', '2025-10-09 06:40:52', NULL, NULL, '2025-10-09 06:41:09', 'PROF001'),
(28, 'STU001', 'CLASS1756494311', 'rejected', '2025-10-09 06:46:14', NULL, NULL, '2025-10-09 06:46:20', 'PROF001'),
(29, 'STU001', 'CLASS1759963777', 'rejected', '2025-10-09 06:50:24', NULL, NULL, '2025-10-09 06:50:31', 'PROF001'),
(30, 'STU001', 'CLASS1759963777', 'rejected', '2025-10-09 07:02:35', NULL, NULL, '2025-10-09 07:02:39', 'PROF001'),
(31, 'STU001', 'CLASS1759963777', 'rejected', '2025-10-09 07:02:45', NULL, NULL, '2025-10-09 07:02:50', 'PROF001'),
(32, 'STU001', 'CLASS1759963777', 'accepted', '2025-10-09 07:02:57', NULL, NULL, '2025-10-09 07:03:05', 'PROF001'),
(33, 'STU001', 'CLASS1759965189', 'rejected', '2025-10-09 07:29:25', NULL, NULL, '2025-10-09 07:29:33', 'PROF001'),
(34, 'STU001', 'CLASS1759965189', 'rejected', '2025-10-09 07:29:52', NULL, NULL, '2025-10-09 07:30:01', 'PROF001'),
(35, 'STU001', 'CLASS1759965189', 'accepted', '2025-10-09 07:30:07', NULL, NULL, '2025-10-09 07:30:12', 'PROF001'),
(36, 'STU001', 'CLASS1759966442', 'rejected', '2025-10-09 07:36:06', NULL, NULL, '2025-10-09 07:36:17', 'PROF001'),
(37, 'STU001', 'CLASS1759966442', 'accepted', '2025-10-09 07:36:28', NULL, NULL, '2025-10-09 07:36:35', 'PROF001'),
(38, 'STU001', 'CLASS1759966660', 'accepted', '2025-10-09 07:38:00', NULL, NULL, '2025-10-09 07:38:03', 'PROF001'),
(39, 'STU001', 'CLASS1759966921', 'accepted', '2025-10-09 07:42:23', NULL, NULL, '2025-10-09 07:42:28', 'PROF001'),
(40, 'STU001', 'CLASS1759967600', 'rejected', '2025-10-09 07:56:22', NULL, NULL, '2025-10-09 07:56:34', 'PROF001'),
(41, 'STU001', 'CLASS1759967600', 'accepted', '2025-10-09 07:56:52', NULL, NULL, '2025-10-09 07:57:00', 'PROF001'),
(42, 'STU001', 'CLASS1759967600', 'accepted', '2025-10-09 07:59:14', NULL, NULL, '2025-10-09 07:59:20', 'PROF001'),
(43, 'STU001', 'CLASS1759967600', 'rejected', '2025-10-09 08:46:08', NULL, NULL, '2025-10-09 08:46:23', 'PROF001'),
(44, 'STU001', 'CLASS1759971443', 'rejected', '2025-10-09 08:58:08', NULL, NULL, '2025-10-09 08:58:13', 'PROF001'),
(45, 'STU001', 'CLASS1759971443', 'accepted', '2025-10-09 08:58:19', NULL, NULL, '2025-10-09 08:58:35', 'PROF001'),
(46, 'STU001', 'CLASS1759971443', 'rejected', '2025-10-13 10:42:58', NULL, NULL, '2025-10-13 10:43:08', 'PROF001'),
(47, 'STU001', 'CLASS1759971443', 'accepted', '2025-10-13 10:43:16', NULL, NULL, '2025-10-13 10:43:25', 'PROF001'),
(48, 'STU001', 'CLASS1759971443', 'rejected', '2025-10-15 12:57:18', NULL, NULL, '2025-10-15 12:57:22', 'PROF001'),
(49, 'STU001', 'CLASS1759971443', 'accepted', '2025-10-15 12:57:29', NULL, NULL, '2025-10-15 12:57:36', 'PROF001'),
(50, 'STU001', 'CLASS1760514979', 'rejected', '2025-10-15 16:10:06', NULL, NULL, '2025-10-15 16:10:11', 'PROF001'),
(51, 'STU001', 'CLASS1760514979', 'accepted', '2025-10-15 16:10:17', NULL, NULL, '2025-10-15 16:10:22', 'PROF001'),
(52, 'STU001', 'CLASS1760514979', 'rejected', '2025-10-18 05:33:45', NULL, NULL, '2025-10-18 05:34:09', 'PROF001'),
(53, 'STU001', 'CLASS1760514979', 'accepted', '2025-10-18 05:34:18', NULL, NULL, '2025-10-18 05:34:27', 'PROF001'),
(54, 'STU001', 'CLASS1760514979', 'accepted', '2025-10-18 07:40:30', NULL, NULL, '2025-10-18 07:42:25', 'PROF001');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_class_id` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_classes_professor_id` (`professor_id`),
  ADD KEY `fk_classes_school_year_semester` (`semester_id`),
  ADD KEY `idx_class_professor` (`professor_id`),
  ADD KEY `idx_class_semester` (`semester_id`),
  ADD KEY `idx_professor_id` (`professor_id`);

--
-- Indexes for table `class_enrollments`
--
ALTER TABLE `class_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`class_id`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_enrollment_student` (`student_id`),
  ADD KEY `idx_enrollment_class` (`class_id`);

--
-- Indexes for table `class_professors`
--
ALTER TABLE `class_professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`class_id`,`professor_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_request` (`student_id`,`class_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `type` (`type`),
  ADD KEY `related_request_id` (`related_request_id`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`professor_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `professor_attendance`
--
ALTER TABLE `professor_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_professor_date` (`professor_id`,`date`),
  ADD UNIQUE KEY `uq_prof_attendance_id` (`id`),
  ADD KEY `fk_professor_attendance_subject` (`subject_id`);

--
-- Indexes for table `professor_subjects`
--
ALTER TABLE `professor_subjects`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_assignment` (`professor_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_label` (`year_label`),
  ADD KEY `idx_school_year_status` (`status`);

--
-- Indexes for table `school_year_semester`
--
ALTER TABLE `school_year_semester`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_term` (`school_year`,`semester`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_year_id` (`school_year_id`),
  ADD KEY `idx_semester_status` (`status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`class_id`),
  ADD KEY `idx_student_classes_class_id` (`class_id`),
  ADD KEY `idx_student_classes_student_id` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_subject_semester` (`semester_id`),
  ADD KEY `fk_subjects_duration_id` (`duration_id`);

--
-- Indexes for table `subject_durations`
--
ALTER TABLE `subject_durations`
  ADD PRIMARY KEY (`duration_id`);

--
-- Indexes for table `unenrollment_requests`
--
ALTER TABLE `unenrollment_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_unenrollment_requests_student` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `class_enrollments`
--
ALTER TABLE `class_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `class_professors`
--
ALTER TABLE `class_professors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `professor_attendance`
--
ALTER TABLE `professor_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `professor_subjects`
--
ALTER TABLE `professor_subjects`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `school_year_semester`
--
ALTER TABLE `school_year_semester`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_classes`
--
ALTER TABLE `student_classes`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `subject_durations`
--
ALTER TABLE `subject_durations`
  MODIFY `duration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `unenrollment_requests`
--
ALTER TABLE `unenrollment_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `fk_attendance_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`),
  ADD CONSTRAINT `classes_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_enrollments`
--
ALTER TABLE `class_enrollments`
  ADD CONSTRAINT `class_enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_enrollments_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_professors`
--
ALTER TABLE `class_professors`
  ADD CONSTRAINT `class_professors_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_professors_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_professors_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_professors_professor` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  ADD CONSTRAINT `enrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_enrollment_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`related_request_id`) REFERENCES `enrollment_requests` (`request_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_request_id`) REFERENCES `unenrollment_requests` (`request_id`) ON DELETE SET NULL;

--
-- Constraints for table `professor_attendance`
--
ALTER TABLE `professor_attendance`
  ADD CONSTRAINT `fk_professor_attendance_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `professor_attendance_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE;

--
-- Constraints for table `professor_subjects`
--
ALTER TABLE `professor_subjects`
  ADD CONSTRAINT `fk_professor_subjects_professor` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_professor_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professor_subjects_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`),
  ADD CONSTRAINT `professor_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`);

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD CONSTRAINT `fk_student_classes_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student_classes_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_classes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `student_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_duration_id` FOREIGN KEY (`duration_id`) REFERENCES `subject_durations` (`duration_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `unenrollment_requests`
--
ALTER TABLE `unenrollment_requests`
  ADD CONSTRAINT `fk_unenrollment_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
