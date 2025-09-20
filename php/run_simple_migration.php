<?php
// Simple migration script to create both enrollment_requests and unenrollment_requests tables
// Run this once to fix the pending requests display issue in Professor dashboard

require_once 'db.php';

try {
    echo "Starting simple migration for request tables...\n";

    // Create enrollment_requests table without foreign key constraints
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
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sql);
    echo "✓ Created enrollment_requests table\n";

    // Create unenrollment_requests table without foreign key constraints
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
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sql);
    echo "✓ Created unenrollment_requests table\n";

    echo "\nMigration completed successfully!\n";
    echo "Both enrollment_requests and unenrollment_requests tables have been created.\n";
    echo "Pending requests should now display properly in the Professor dashboard.\n";
    echo "Students can submit enrollment/unenrollment requests and professors can see them in their notifications.\n";
    echo "\nNote: Foreign key constraints were not added to avoid potential data integrity issues.\n";
    echo "You can add them manually later if needed.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
