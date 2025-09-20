<?php
// Complete migration script to create both enrollment_requests and unenrollment_requests tables
// Run this once to fix the pending requests display issue in Professor dashboard

require_once 'db.php';

try {
    echo "Starting complete migration for request tables...\n";

    // Create enrollment_requests table
    $sql = "CREATE TABLE IF NOT EXISTS `enrollment_requests` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sql);
    echo "✓ Created enrollment_requests table\n";

    // Create unenrollment_requests table
    $sql = "CREATE TABLE IF NOT EXISTS `unenrollment_requests` (
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
        CONSTRAINT `unenrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
        CONSTRAINT `unenrollment_requests_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
        CONSTRAINT `unenrollment_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `professors` (`professor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sql);
    echo "✓ Created unenrollment_requests table\n";

    // Add foreign key constraints for enrollment_requests
    try {
        $pdo->exec("ALTER TABLE `enrollment_requests` ADD CONSTRAINT `enrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)");
        $pdo->exec("ALTER TABLE `enrollment_requests` ADD CONSTRAINT `enrollment_requests_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)");
        $pdo->exec("ALTER TABLE `enrollment_requests` ADD CONSTRAINT `enrollment_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `professors` (`professor_id`)");
        echo "✓ Added foreign key constraints for enrollment_requests\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
        echo "✓ Foreign key constraints for enrollment_requests already exist\n";
    }

    // Add foreign key constraints for unenrollment_requests
    try {
        $pdo->exec("ALTER TABLE `unenrollment_requests` ADD CONSTRAINT `unenrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)");
        $pdo->exec("ALTER TABLE `unenrollment_requests` ADD CONSTRAINT `unenrollment_requests_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)");
        $pdo->exec("ALTER TABLE `unenrollment_requests` ADD CONSTRAINT `unenrollment_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `professors` (`professor_id`)");
        echo "✓ Added foreign key constraints for unenrollment_requests\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
        echo "✓ Foreign key constraints for unenrollment_requests already exist\n";
    }

    echo "\nMigration completed successfully!\n";
    echo "Both enrollment_requests and unenrollment_requests tables have been created.\n";
    echo "Pending requests should now display properly in the Professor dashboard.\n";
    echo "Students can submit enrollment/unenrollment requests and professors can see them in their notifications.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
