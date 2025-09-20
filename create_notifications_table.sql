-- Migration to create notifications table
-- This table will store notifications for students about enrollment/unenrollment request status

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `user_type` enum('student','professor','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('enrollment_approved','enrollment_rejected','unenrollment_approved','unenrollment_rejected','info','warning','success') NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `related_request_id` int(11) DEFAULT NULL,
  `related_class_id` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `user_type` (`user_type`),
  KEY `is_read` (`is_read`),
  KEY `type` (`type`),
  KEY `related_request_id` (`related_request_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`related_request_id`) REFERENCES `enrollment_requests` (`request_id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_request_id`) REFERENCES `unenrollment_requests` (`request_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
