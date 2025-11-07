<?php
require_once __DIR__ . '/db.php';

function ensureColumn(PDO $pdo, string $table, string $column, string $definition, string $after = null): void {
    $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    $exists = (int)$stmt->fetchColumn() > 0;
    if (!$exists) {
        $alter = "ALTER TABLE `$table` ADD COLUMN `$column` $definition" . ($after ? " AFTER `$after`" : "");
        $pdo->exec($alter);
        echo "Added column $table.$column\n";
    }
}

try {
    // 0) Ensure columns exist (in case migration step was skipped/not supported)
    // Note: DDL in MySQL auto-commits implicitly, so avoid using explicit transactions here.
    ensureColumn($pdo, 'school_years', 'created_by', 'VARCHAR(20) NULL', 'status');
    ensureColumn($pdo, 'school_years', 'updated_by', 'VARCHAR(20) NULL', 'created_at');
    ensureColumn($pdo, 'semesters', 'created_by', 'VARCHAR(20) NULL', 'status');
    ensureColumn($pdo, 'semesters', 'updated_by', 'VARCHAR(20) NULL', 'created_at');
    ensureColumn($pdo, 'school_year_semester', 'created_by', 'VARCHAR(20) NULL', 'status');
    ensureColumn($pdo, 'school_year_semester', 'updated_by', 'VARCHAR(20) NULL', 'created_at');

    // 1) Pick a default admin to attribute historical rows to
    $stmt = $pdo->query("SELECT admin_id FROM administrators ORDER BY created_at ASC LIMIT 1");
    $defaultAdmin = $stmt->fetchColumn();
    if (!$defaultAdmin) {
        throw new Exception('No administrators found to backfill created_by/updated_by.');
    }

    // 2) Backfill created_by/updated_by for school_years
    $stmt = $pdo->prepare("UPDATE school_years SET created_by = COALESCE(created_by, ?), updated_by = COALESCE(updated_by, ?) WHERE created_by IS NULL OR updated_by IS NULL");
    $stmt->execute([$defaultAdmin, $defaultAdmin]);

    // 3) Backfill created_by/updated_by for semesters
    $stmt = $pdo->prepare("UPDATE semesters SET created_by = COALESCE(created_by, ?), updated_by = COALESCE(updated_by, ?) WHERE created_by IS NULL OR updated_by IS NULL");
    $stmt->execute([$defaultAdmin, $defaultAdmin]);

    // 4) Backfill created_by/updated_by for school_year_semester
    $stmt = $pdo->prepare("UPDATE school_year_semester SET created_by = COALESCE(created_by, ?), updated_by = COALESCE(updated_by, ?) WHERE created_by IS NULL OR updated_by IS NULL");
    $stmt->execute([$defaultAdmin, $defaultAdmin]);

    // 5) Drop legacy/unused tables if they still exist
    $pdo->exec("DROP TABLE IF EXISTS class_enrollments");

    // 6) Remove orphaned request rows that would block FK creation
    //    Any request whose class_id no longer exists in classes will be deleted.
    $deletedUnenroll = $pdo->exec("DELETE ur FROM unenrollment_requests ur LEFT JOIN classes c ON ur.class_id = c.class_id WHERE c.class_id IS NULL");
    $deletedEnroll = $pdo->exec("DELETE er FROM enrollment_requests er LEFT JOIN classes c ON er.class_id = c.class_id WHERE c.class_id IS NULL");
    echo "Removed orphan rows -> unenrollment_requests: {$deletedUnenroll}, enrollment_requests: {$deletedEnroll}\n";

    echo "Maintenance complete. Default admin used: {$defaultAdmin}\n";
} catch (Throwable $e) {
    echo "Maintenance failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
