<?php
// Migration script to add school_year_semester table and missing columns
// Run this once to fix the enrolled subjects display issue

require_once 'db.php';

try {
    echo "Starting migration...\n";

    // Create school_year_semester table
    $sql = "CREATE TABLE IF NOT EXISTS `school_year_semester` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `school_year` varchar(20) NOT NULL,
        `semester` varchar(20) NOT NULL,
        `status` enum('Active','Archived') NOT NULL DEFAULT 'Active',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_year_semester` (`school_year`,`semester`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $pdo->exec($sql);
    echo "✓ Created school_year_semester table\n";

    // Check if school_year_semester_id column exists
    $stmt = $pdo->query("DESCRIBE classes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('school_year_semester_id', $columns)) {
        $pdo->exec("ALTER TABLE `classes` ADD COLUMN `school_year_semester_id` int(11) DEFAULT NULL AFTER `section`");
        echo "✓ Added school_year_semester_id column to classes table\n";
    } else {
        echo "✓ school_year_semester_id column already exists\n";
    }

    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE `classes` ADD COLUMN `status` enum('active','archived') NOT NULL DEFAULT 'active' AFTER `school_year_semester_id`");
        echo "✓ Added status column to classes table\n";
    } else {
        echo "✓ status column already exists\n";
    }

    // Add foreign key constraint (only if it doesn't exist)
    try {
        $pdo->exec("ALTER TABLE `classes` ADD CONSTRAINT `fk_classes_school_year_semester` FOREIGN KEY (`school_year_semester_id`) REFERENCES `school_year_semester` (`id`)");
        echo "✓ Added foreign key constraint\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
        echo "✓ Foreign key constraint already exists\n";
    }

    // Insert current school year and semester if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM school_year_semester WHERE school_year = ? AND semester = ?");
    $stmt->execute(['2025-2026', '1st Semester']);
    $exists = $stmt->fetch()['count'];

    if ($exists == 0) {
        $stmt = $pdo->prepare("INSERT INTO `school_year_semester` (`school_year`, `semester`, `status`) VALUES (?, ?, 'Active')");
        $stmt->execute(['2025-2026', '1st Semester']);
        echo "✓ Inserted current school year and semester\n";
    } else {
        echo "✓ Current school year and semester already exists\n";
    }

    // Update existing classes to use the current school year semester
    $stmt = $pdo->prepare("UPDATE `classes` SET `school_year_semester_id` = (SELECT `id` FROM `school_year_semester` WHERE `school_year` = ? AND `semester` = ? LIMIT 1) WHERE `school_year_semester_id` IS NULL");
    $stmt->execute(['2025-2026', '1st Semester']);
    echo "✓ Updated existing classes with school_year_semester_id\n";

    echo "\nMigration completed successfully!\n";
    echo "Enrolled subjects should now display properly in the Student portal after professor approval.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
