-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 04:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(26, 'STU004', 'CLASS005', '2025-09-02', 'Present', '', '2025-09-02 06:53:25'),
(27, 'STU005', 'CLASS005', '2025-09-02', 'Present', 'DAPAT HINDI KA KASAMA SA DATA NI DENMAR', '2025-09-02 06:53:25'),
(28, 'STU001', 'CLASS005', '2025-09-02', 'Present', 'DAPAT KAY DENMAR KA LANG', '2025-09-02 06:53:25'),
(29, 'STU001', 'CLASS1756767458', '2025-09-02', 'Excused', 'may sakit', '2025-09-07 20:35:48'),
(30, 'STU002', 'CLASS1756767458', '2025-09-02', 'Present', '', '2025-09-07 20:35:48'),
(31, 'STU001', 'CLASS1756900369', '2025-09-03', 'Present', 'PINAKA POGI SA LAHAT (TL)', '2025-09-03 19:54:30'),
(32, 'STU001', 'CLASS1756767458', '2025-09-07', 'Present', 'magaling na', '2025-09-07 20:36:30'),
(33, 'STU002', 'CLASS1756767458', '2025-09-07', 'Absent', '', '2025-09-07 20:36:30');

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
  `school_year_semester_id` int(11) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `semester` enum('1st Semester','2nd Semester') DEFAULT '1st Semester'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `class_code`, `subject_id`, `professor_id`, `schedule`, `room`, `created_at`, `updated_at`, `section`, `school_year_semester_id`, `status`, `semester`) VALUES
('CLASS001', 'CS101 Section A', '5OK7ZE0C', 'SUB001', 'PROF001', 'MWF 8:00-9:30 AM', 'Room 101', '2025-08-28 07:13:53', '2025-08-29 12:19:16', '301', 3, 'archived', '1st Semester'),
('CLASS002', 'MATH101 Section B', 'MATH101-B', 'SUB002', 'PROF002', 'TTH 10:00-11:30 AM', 'Room 202', '2025-08-28 07:13:53', '2025-08-28 07:13:53', '302', 3, 'active', '1st Semester'),
('CLASS003', 'CS201 Section C', 'NOW0G94U', 'SUB003', 'PROF001', 'MWF 1:00-2:30 PM', 'Room 303', '2025-08-28 07:13:53', '2025-08-30 14:50:30', '301', 3, 'archived', '1st Semester'),
('CLASS004', 'CS301 Section A', 'CS301-A', 'SUB004', 'PROF003', 'TTH 2:00-3:30 PM', 'Room 404', '2025-08-28 07:13:53', '2025-08-28 07:13:53', '302', 3, 'active', '1st Semester'),
('CLASS005', 'ENG101 Section D', '6XL8WS9V', 'SUB005', 'PROF001', 'MWF 3:00-4:30 PM', 'Room 505', '2025-08-28 07:13:53', '2025-09-02 06:47:27', '301', 3, 'archived', '1st Semester'),
('CLASS1756423371', 'Database Management System. Class', '4553218', 'SUB1756423371', 'PF0F004', 'Bahala ka na', 'LAB 3', '2025-08-29 07:22:51', '2025-08-29 07:22:51', '302', 3, 'active', '1st Semester'),
('CLASS1756425193', 'System Architecture Class', 'N2X1QVPI', 'SUB1756425193', 'PROF001', '321354', 'LAB 81', '2025-08-29 07:53:13', '2025-09-02 06:24:46', '301', 3, 'archived', '1st Semester'),
('CLASS1756441963', 'HOW TO BE HOTDOG Class', 'A3U3ZXL6', 'SUB1756441963', 'PROF001', 'ANYTIME', 'ANYWHERE', '2025-08-29 12:32:43', '2025-08-30 14:50:14', '302', 3, 'archived', '1st Semester'),
('CLASS1756494311', 'HOW TO BE POGI Class', 'AS8O992R', 'SUB1756494311', 'PROF001', 'CCF', 'CCF', '2025-08-30 03:05:11', '2025-08-30 03:05:11', '303', 3, 'archived', '1st Semester'),
('CLASS1756542883', 'EWAN Class', 'WLCV0T8N', 'SUB1756542883', 'PROF001', 'ANY', 'SA LABAS', '2025-08-30 16:34:43', '2025-09-02 06:24:30', '301', 3, 'archived', '1st Semester'),
('CLASS1756767458', 'TUMESTING KA Class', 'M4FFPLJT', 'SUB1756767458', 'PROF001', 'Not sure', 'lab 3', '2025-09-02 06:57:38', '2025-09-16 08:58:20', '304', 3, 'archived', '1st Semester'),
('CLASS1756900369', 'POGI Class', '7CGDOSFT', 'SUB1756900369', '111111', 'ANYTIME', 'ANYWHERE', '2025-09-03 19:52:49', '2025-09-03 19:52:49', '305', 3, 'active', '1st Semester'),
('CLASS1757248087', 'Funda Class', '6IXPRD3R', 'SUB1757248087', 'PF0F004', 'SHELL', 'SHELL CAFE', '2025-09-07 20:28:07', '2025-09-07 20:29:31', '306', 3, 'active', '1st Semester'),
('CLASS1758335652', 'NEW Class', 'SF7DJY8L', 'SUB1758335652', 'PROF001', 'NEW', 'NEW', '2025-09-20 10:34:12', '2025-09-20 10:34:12', NULL, NULL, 'active', '1st Semester'),
('CLASSTEST1', 'Test Subject Class', 'BSBJK30I', 'SUBTEST1', 'PROF001', 'MWF 9:00-10:00', 'Room 101', '2025-08-30 15:40:29', '2025-09-02 06:24:37', '306', 3, 'archived', '1st Semester');

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
(8, 'STU001', 'CLASS1758324306', '', '2025-09-20 07:25:12', NULL, NULL, '2025-09-20 07:25:18', 'PROF001'),
(9, 'STU001', 'CLASS1758325422', '', '2025-09-20 07:45:55', NULL, NULL, '2025-09-20 07:46:02', 'PROF001'),
(15, 'STU001', 'CLASS1758326678', '', '2025-09-20 08:04:45', NULL, NULL, '2025-09-20 08:04:52', 'PROF001'),
(16, 'STU001', 'CLASS1758327155', '', '2025-09-20 08:12:45', NULL, NULL, '2025-09-20 08:12:52', 'PROF001'),
(17, 'STU001', 'CLASS1758327842', '', '2025-09-20 08:24:15', NULL, NULL, '2025-09-20 08:24:24', 'PROF001'),
(22, 'STU001', 'CLASS1758327989', '', '2025-09-20 08:26:39', NULL, NULL, '2025-09-20 08:26:46', 'PROF001');

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
('', '', 'John', 'Doe', 'john@example.com', 'password', 'CS', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
('111111', '51525251', 'DERICK1', 'Boado', 'derickboado1@gmail.com', '$2y$10$qqQ2t9z56nD4X16pOKA8/e0zU.2dmK5nvEPvsyxFFZeTvzme9wFMi', 'Sawi Departtmen', '09155004507', '2025-09-03 13:50:59', '2025-09-07 20:24:48'),
('PF0F004', '32165498', 'HATDOG', 'JUMBO', 'g@Gmail.com', '25f9e794323b453885f5181f1b624d0b', 'Unknown', '0999554214', '2025-08-28 13:57:57', '2025-09-02 06:22:59'),
('PROF001', 'EMP001', 'Danhil', 'Baluyot', 'dbaluyot@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'Computer Science', '+639123456789', '2025-08-28 07:13:53', '2025-08-28 08:29:54'),
('PROF002', 'EMP002', 'Maria', 'Santos', 'maria.santos@grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', 'Mathematics', '+639234567890', '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('PROF003', 'EMP003', 'Robert', 'Garcia', 'robert.garcia@grc.edu', '5f4dcc3b5aa765d61d8327deb882cf99', 'Engineering', '+639345678901', '2025-08-28 07:13:53', '2025-08-28 07:13:53');

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
(1, '2024-2025', '1st Semester', 'Archived', '2025-09-18 23:24:22', '2025-09-18 23:24:22'),
(2, '2024-2025', '2nd Semester', 'Archived', '2025-09-18 23:24:22', '2025-09-18 23:24:22'),
(3, '2025-2026', '1st Semester', 'Archived', '2025-09-18 23:24:22', '2025-09-20 14:25:58'),
(4, '2025-2026', '2nd Semester', 'Archived', '2025-09-18 23:24:22', '2025-09-20 03:24:56'),
(5, '2025-2026', 'Summer', 'Archived', '2025-09-18 23:24:22', '2025-09-20 05:25:22'),
(31, '2024-2025', '', 'Archived', '2025-09-19 21:24:23', '2025-09-20 05:25:25');

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
('2017-11547-57', 'KKKK', 'KKKK', 'KKKK', 'KKK@gmail.com', '25f9e794323b453885f5181f1b624d0b', '09123547315', 'ewan', '2025-09-02 06:24:12', '2025-09-02 06:24:12', 'A'),
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
(1, 'STU001', 'CLASS001', '2025-08-28 07:13:53'),
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
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `description`, `credits`, `created_at`, `updated_at`) VALUES
('SUB001', 'Introduction to Programming', 'CS101', 'Fundamentals of programming concepts and logic', 3, '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('SUB002', 'Calculus I', 'MATH101', 'Differential and integral calculus', 4, '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('SUB003', 'Database Systems', 'CS201', 'Relational database design and SQL', 3, '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('SUB004', 'Web Development', 'CS301', 'Front-end and back-end web technologies', 3, '2025-08-28 07:13:53', '2025-08-28 07:13:53'),
('SUB005', 'Engineering Mathematics', 'ENG101', 'Mathematical methods for engineering', 4, '2025-08-28 07:13:53', '2025-09-01 04:44:04'),
('SUB1756423371', 'Database Management System.', 'DBMS', NULL, 3, '2025-08-29 07:22:51', '2025-08-29 07:22:51'),
('SUB1756425193', 'System Architecture', 'SYSARCH', NULL, 3, '2025-08-29 07:53:13', '2025-09-02 06:24:46'),
('SUB1756441963', 'HOW TO BE HOTDOG', 'HD12324', NULL, 3, '2025-08-29 12:32:43', '2025-08-29 12:32:43'),
('SUB1756494311', 'HOW TO BE POGI', 'IT 304', NULL, 3, '2025-08-30 03:05:11', '2025-08-30 03:05:11'),
('SUB1756542883', 'EWAN', '305', NULL, 3, '2025-08-30 16:34:43', '2025-09-02 06:24:30'),
('SUB1756767458', 'TUMESTING KA', 'IT101', NULL, 3, '2025-09-02 06:57:38', '2025-09-07 20:37:31'),
('SUB1756900369', 'POGI', 'IT 301', NULL, 3, '2025-09-03 19:52:49', '2025-09-03 19:52:49'),
('SUB1757248087', 'Funda', 'IT 101', NULL, 3, '2025-09-07 20:28:07', '2025-09-07 20:29:31'),
('SUB1758335652', 'NEW', 'NEW', NULL, 3, '2025-09-20 10:34:12', '2025-09-20 10:34:12'),
('SUBARCH1', 'Archived Subject 2024-2025', 'ARCH2024', NULL, 3, '2025-09-20 08:21:50', '2025-09-20 08:21:50'),
('SUBARCH2', 'Archived Subject 2023-2024', 'ARCH2023', NULL, 3, '2025-09-20 08:21:54', '2025-09-20 08:21:54'),
('SUBTEST1', 'Test Subject', 'TS101', NULL, 3, '2025-08-30 15:35:29', '2025-09-02 06:24:37');

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
(1, 'STU001', 'CLASS1758316848', 'pending', '2025-09-20 05:58:04', NULL, NULL, NULL, NULL),
(2, 'STU001', 'CLASS1758322968', 'pending', '2025-09-20 07:04:11', NULL, NULL, NULL, NULL),
(3, 'STU001', 'CLASS1758323183', 'pending', '2025-09-20 07:12:18', NULL, NULL, NULL, NULL),
(4, 'STU001', 'CLASS1758324306', 'pending', '2025-09-20 07:33:31', NULL, NULL, NULL, NULL),
(5, 'STU001', 'CLASS1758325422', '', '2025-09-20 07:46:22', NULL, NULL, '2025-09-20 07:46:32', 'PROF001'),
(6, 'STU001', 'CLASS1758326678', 'rejected', '2025-09-20 08:05:05', NULL, NULL, '2025-09-20 08:05:14', 'PROF001'),
(10, 'STU001', 'CLASS1758327155', 'rejected', '2025-09-20 08:13:11', NULL, NULL, '2025-09-20 08:13:18', 'PROF001'),
(11, 'STU001', 'CLASS1758327842', '', '2025-09-20 08:24:30', NULL, NULL, '2025-09-20 08:24:37', 'PROF001'),
(12, 'STU001', 'CLASS1756767458', 'rejected', '2025-09-20 08:24:47', NULL, NULL, '2025-09-20 08:25:03', 'PROF001'),
(13, 'STU001', 'CLASS1758335652', '', '2025-09-20 10:54:53', NULL, NULL, '2025-09-20 10:55:20', 'PROF001');

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
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_classes_professor_id` (`professor_id`),
  ADD KEY `fk_classes_school_year_semester` (`school_year_semester_id`);

--
-- Indexes for table `class_enrollments`
--
ALTER TABLE `class_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`class_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `class_professors`
--
ALTER TABLE `class_professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`class_id`,`professor_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_request` (`student_id`,`class_id`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`professor_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `professor_subjects`
--
ALTER TABLE `professor_subjects`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_assignment` (`professor_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `school_year_semester`
--
ALTER TABLE `school_year_semester`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_term` (`school_year`,`semester`);

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
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `unenrollment_requests`
--
ALTER TABLE `unenrollment_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_unenroll_request` (`student_id`,`class_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
-- AUTO_INCREMENT for table `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `professor_subjects`
--
ALTER TABLE `professor_subjects`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_year_semester`
--
ALTER TABLE `school_year_semester`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `student_classes`
--
ALTER TABLE `student_classes`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `unenrollment_requests`
--
ALTER TABLE `unenrollment_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`),
  ADD CONSTRAINT `fk_classes_school_year_semester` FOREIGN KEY (`school_year_semester_id`) REFERENCES `school_year_semester` (`id`);

--
-- Constraints for table `class_enrollments`
--
ALTER TABLE `class_enrollments`
  ADD CONSTRAINT `class_enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_professors`
--
ALTER TABLE `class_professors`
  ADD CONSTRAINT `class_professors_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_professors_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  ADD CONSTRAINT `enrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `professor_subjects`
--
ALTER TABLE `professor_subjects`
  ADD CONSTRAINT `professor_subjects_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`),
  ADD CONSTRAINT `professor_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`);

--
-- Constraints for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD CONSTRAINT `student_classes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `student_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
