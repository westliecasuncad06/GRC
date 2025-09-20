<?php
// Migration script to create unenrollment_requests table
// Run this once to fix the pending unenrollment requests display issue

require_once 'db.php';

try {
    echo "Starting unenrollment requests migration...\n";

    // Check if unenrollment_requests table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'unenrollment_requests'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        // Create unenrollment_requests table
        $sql = "CREATE TABLE `unenrollment_requests` (
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

        // Add foreign key constraints
        try {
            $pdo->exec("ALTER TABLE `unenrollment_requests` ADD CONSTRAINT `unenrollment_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)");
            $pdo->exec("ALTER TABLE `unenrollment_requests` ADD CONSTRAINT `unenrollment_requests_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)");
            $pdo->exec("ALTER TABLE `unenrollment_requests` ADD CONSTRAINT `unenrollment_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `professors` (`professor_id`)");
            echo "✓ Added foreign key constraints\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
            echo "✓ Foreign key constraints already exist\n";
        }

    } else {
        echo "✓ unenrollment_requests table already exists\n";
    }

    echo "\nMigration completed successfully!\n";
    echo "Pending unenrollment requests should now display properly in the Professor dashboard.\n";
    echo "Students can now submit unenrollment requests and professors can see them in their notifications.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
