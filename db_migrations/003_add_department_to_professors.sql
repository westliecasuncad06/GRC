-- Migration: 003_add_department_to_professors.sql
-- Add department_id to professors and link to departments table
-- Idempotent where possible (uses IF NOT EXISTS for column add, safe checks for FK)

-- 1) Add nullable department_id column if not already present
ALTER TABLE `professors` ADD COLUMN IF NOT EXISTS `department_id` INT(11) DEFAULT NULL;

-- 2) Backfill department_id from existing `department` (name) column where possible
UPDATE `professors` p
JOIN `departments` d ON p.`department` = d.`department_name`
SET p.`department_id` = d.`department_id`
WHERE p.`department_id` IS NULL;

-- 3) Add index for department_id (IF NOT EXISTS not supported for indexes prior to MySQL 8.0.13,
-- but adding same index twice will error; we attempt to create it and ignore errors when running manually if needed)
ALTER TABLE `professors` ADD INDEX `idx_professors_department_id` (`department_id`);

-- 4) Create FK constraint only if it does not already exist
-- We use a simple information_schema check and dynamic SQL; executing this file in mysql client should work on modern MySQL/MariaDB
SET @fk_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
  WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
    AND tc.TABLE_NAME = 'professors'
    AND tc.CONSTRAINT_NAME = 'fk_professors_department'
);

SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE `professors` ADD CONSTRAINT `fk_professors_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Note: If your MySQL/MariaDB version does not support some constructs above (for example
-- ADD COLUMN IF NOT EXISTS), run the equivalent ALTER TABLE commands manually or via phpMyAdmin.
-- This migration will not remove the existing `department` text column; you can drop it later
-- after verifying data and updating application code to use `department_id`.
