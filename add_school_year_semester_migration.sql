-- Migration to add school_year_semester table and missing columns to classes table
-- This fixes the issue where enrolled subjects don't show after professor approval

-- Create school_year_semester table
CREATE TABLE IF NOT EXISTS `school_year_semester` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `status` enum('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_year_semester` (`school_year`,`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add school_year_semester_id column to classes table
ALTER TABLE `classes` ADD COLUMN `school_year_semester_id` int(11) DEFAULT NULL AFTER `section`;

-- Add status column to classes table
ALTER TABLE `classes` ADD COLUMN `status` enum('active','archived') NOT NULL DEFAULT 'active' AFTER `school_year_semester_id`;

-- Add foreign key constraint
ALTER TABLE `classes` ADD CONSTRAINT `fk_classes_school_year_semester` FOREIGN KEY (`school_year_semester_id`) REFERENCES `school_year_semester` (`id`);

-- Insert current school year and semester (2025-2026, 1st Semester)
INSERT INTO `school_year_semester` (`school_year`, `semester`, `status`) VALUES ('2025-2026', '1st Semester', 'Active');

-- Update existing classes to use the current school year semester
UPDATE `classes` SET `school_year_semester_id` = (SELECT `id` FROM `school_year_semester` WHERE `school_year` = '2025-2026' AND `semester` = '1st Semester' LIMIT 1) WHERE `school_year_semester_id` IS NULL;
