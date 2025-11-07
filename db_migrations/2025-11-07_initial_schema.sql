-- GRC Student Portal for Attendance Monitoring - Schema Only
-- Generated: 2025-11-07
-- MariaDB/MySQL compatible
-- Purpose: Baseline schema (no data) aligned with current app and latest structure updates.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create and switch to database
CREATE DATABASE IF NOT EXISTS `GRC_STUDENT_PORTAL_FOR_ATTENDANCE_MONITORING` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `GRC_STUDENT_PORTAL_FOR_ATTENDANCE_MONITORING`;

-- Safety: Drop existing tables (order-agnostic)
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `student_classes`;
DROP TABLE IF EXISTS `enrollment_requests`;
DROP TABLE IF EXISTS `unenrollment_requests`;
DROP TABLE IF EXISTS `professor_attendance`;
DROP TABLE IF EXISTS `professor_subjects`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `professors`;
DROP TABLE IF EXISTS `semesters`;
DROP TABLE IF EXISTS `school_year_semester`;
DROP TABLE IF EXISTS `school_years`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `subject_durations`;
DROP TABLE IF EXISTS `administrators`;
SET FOREIGN_KEY_CHECKS=1;

-- Administrators
CREATE TABLE `administrators` (
  `admin_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Departments
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_name` (`department_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Subject durations
CREATE TABLE `subject_durations` (
  `duration_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_duration` varchar(50) NOT NULL,
  PRIMARY KEY (`duration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- School years
CREATE TABLE `school_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year_label` varchar(20) NOT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(20) NULL,
  `updated_by` varchar(20) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_label` (`year_label`),
  KEY `idx_school_year_status` (`status`),
  CONSTRAINT `fk_school_years_created_by` FOREIGN KEY (`created_by`) REFERENCES `administrators` (`admin_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_school_years_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `administrators` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Semesters
CREATE TABLE `semesters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year_id` int(11) NOT NULL,
  `semester_name` enum('1st Semester','2nd Semester','Summer') NOT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(20) NULL,
  `updated_by` varchar(20) NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `idx_semester_status` (`status`),
  CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_semesters_created_by` FOREIGN KEY (`created_by`) REFERENCES `administrators` (`admin_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_semesters_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `administrators` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- School year + semester mapping (flat for convenience in UI)
CREATE TABLE `school_year_semester` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('1st Semester','2nd Semester','Summer') NOT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(20) NULL,
  `updated_by` varchar(20) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_term` (`school_year`,`semester`),
  CONSTRAINT `fk_sys_created_by` FOREIGN KEY (`created_by`) REFERENCES `administrators` (`admin_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sys_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `administrators` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Students
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
  `section` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Subjects
CREATE TABLE `subjects` (
  `subject_id` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) NOT NULL,
  `duration_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_code` (`subject_code`),
  KEY `fk_subjects_duration_id` (`duration_id`),
  KEY `idx_subject_semester` (`semester_id`),
  CONSTRAINT `fk_subjects_duration_id` FOREIGN KEY (`duration_id`) REFERENCES `subject_durations` (`duration_id`) ON DELETE SET NULL,
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professors (keeps legacy `department` and adds `department_id` FK)
CREATE TABLE `professors` (
  `professor_id` varchar(20) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `department_id` int(11) NULL,
  `mobile` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`professor_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_professors_department` (`department_id`),
  CONSTRAINT `fk_professors_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Classes
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
  `school_year_semester_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `class_code` (`class_code`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_classes_professor_id` (`professor_id`),
  KEY `idx_class_semester` (`semester_id`),
  KEY `idx_class_sys` (`school_year_semester_id`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`),
  CONSTRAINT `classes_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_classes_school_year_semester` FOREIGN KEY (`school_year_semester_id`) REFERENCES `school_year_semester` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professor subjects (mapping)
CREATE TABLE `professor_subjects` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `professor_id` varchar(20) DEFAULT NULL,
  `subject_id` varchar(20) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `unique_assignment` (`professor_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `fk_professor_subjects_professor` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_professor_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professor attendance
CREATE TABLE `professor_attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) NOT NULL,
  `professor_id` varchar(20) NOT NULL,
  `subject_id` varchar(20) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_professor_date` (`professor_id`,`date`),
  UNIQUE KEY `uq_prof_attendance_id` (`id`),
  KEY `fk_professor_attendance_subject` (`subject_id`),
  CONSTRAINT `professor_attendance_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`professor_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_professor_attendance_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Student enrollment (current table used by app)
CREATE TABLE `student_classes` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) DEFAULT NULL,
  `class_id` varchar(20) DEFAULT NULL,
  `enrolled_at` datetime NOT NULL,
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`class_id`),
  KEY `idx_student_classes_class_id` (`class_id`),
  KEY `idx_student_classes_student_id` (`student_id`),
  CONSTRAINT `fk_student_classes_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_classes_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Attendance
CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) DEFAULT NULL,
  `class_id` varchar(20) DEFAULT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`attendance_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_id` (`class_id`),
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Enrollment requests
CREATE TABLE `enrollment_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `handled_at` datetime DEFAULT NULL,
  `handled_by` varchar(20) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `unique_request` (`student_id`,`class_id`),
  KEY `idx_enrollment_requests_class` (`class_id`),
  CONSTRAINT `fk_enrollment_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollment_requests_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Unenrollment requests
CREATE TABLE `unenrollment_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `handled_at` datetime DEFAULT NULL,
  `handled_by` varchar(20) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `fk_unenrollment_requests_student` (`student_id`),
  KEY `idx_unenrollment_requests_class` (`class_id`),
  CONSTRAINT `fk_unenrollment_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_unenrollment_requests_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notifications
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `user_type` enum('student','professor','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('enrollment_approved','enrollment_rejected','unenrollment_approved','unenrollment_rejected','info','warning','success') NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `related_request_id` int(11) DEFAULT NULL,
  `related_class_id` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `user_type` (`user_type`),
  KEY `is_read` (`is_read`),
  KEY `type` (`type`)
  -- NOTE: Avoid ambiguous FK to two different request tables in baseline
  -- If needed, create ONE FK based on your usage, e.g.:
  -- ,CONSTRAINT `fk_notifications_enrollment_req` FOREIGN KEY (`related_request_id`) REFERENCES `enrollment_requests` (`request_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- End of schema
