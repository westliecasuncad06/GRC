-- Migration to create enrollment_requests table
-- This fixes the issue where pending enrollment requests don't show in Professor dashboard

CREATE TABLE IF NOT EXISTS `enrollment_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`),
  KEY `status` (`status`),
  CONSTRAINT `enrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  CONSTRAINT `enrollment_requests_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  CONSTRAINT `enrollment_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `professors` (`professor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
