<?php
// Migration script to add missing columns to enrollment_requests and unenrollment_requests tables
// Run this to fix the "Column not found" errors

require_once 'db.php';

try {
    echo "Starting migration to add missing columns...\n";

    // Check if enrollment_requests table exists and add missing columns
    $stmt = $pdo->query("DESCRIBE enrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('processed_at', $columns)) {
        $pdo->exec("ALTER TABLE `enrollment_requests` ADD COLUMN `processed_at` datetime DEFAULT NULL");
        echo "✓ Added processed_at column to enrollment_requests table\n";
    } else {
        echo "✓ processed_at column already exists in enrollment_requests\n";
    }

    if (!in_array('processed_by', $columns)) {
        $pdo->exec("ALTER TABLE `enrollment_requests` ADD COLUMN `processed_by` varchar(20) DEFAULT NULL");
        echo "✓ Added processed_by column to enrollment_requests table\n";
    } else {
        echo "✓ processed_by column already exists in enrollment_requests\n";
    }

    // Check if unenrollment_requests table exists and add missing columns
    $stmt = $pdo->query("DESCRIBE unenrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('processed_at', $columns)) {
        $pdo->exec("ALTER TABLE `unenrollment_requests` ADD COLUMN `processed_at` datetime DEFAULT NULL");
        echo "✓ Added processed_at column to unenrollment_requests table\n";
    } else {
        echo "✓ processed_at column already exists in unenrollment_requests\n";
    }

    if (!in_array('processed_by', $columns)) {
        $pdo->exec("ALTER TABLE `unenrollment_requests` ADD COLUMN `processed_by` varchar(20) DEFAULT NULL");
        echo "✓ Added processed_by column to unenrollment_requests table\n";
    } else {
        echo "✓ processed_by column already exists in unenrollment_requests\n";
    }

    echo "\nMigration completed successfully!\n";
    echo "Missing columns have been added to both request tables.\n";
    echo "The enrollment and unenrollment request handlers should now work properly.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
