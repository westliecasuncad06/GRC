<?php
require_once __DIR__ . '/../db.php';

if (!$pdo) {
    echo "DB connection not available. Check db.php settings.\n";
    exit(1);
}

try {
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

    echo "Database: {$dbName}\n";
    echo "\nSemesters FKs for column school_year_id:\n";
    $stmt = $pdo->prepare("SELECT tc.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
          ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
         AND tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
         AND tc.TABLE_NAME = kcu.TABLE_NAME
       WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
         AND tc.TABLE_NAME = 'semesters'
         AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
         AND kcu.COLUMN_NAME = 'school_year_id'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "  - No FK found on semesters.school_year_id (as expected).\n";
    } else {
        foreach ($rows as $r) {
            echo "  - {$r['CONSTRAINT_NAME']} -> {$r['REFERENCED_TABLE_NAME']}({$r['REFERENCED_COLUMN_NAME']})\n";
        }
    }

    // Check if semesters.school_year_id column still exists
    echo "\nSemesters column presence check:\n";
    $hasCol = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'semesters' AND COLUMN_NAME = 'school_year_id'")->fetchColumn();
    if ((int)$hasCol === 0) {
        echo "  - Column semesters.school_year_id: NOT FOUND (dropped).\n";
    } else {
        echo "  - Column semesters.school_year_id: PRESENT.\n";
    }

    echo "\nSubjects FK for column school_year_id:\n";
    $stmt2 = $pdo->prepare("SELECT tc.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
          ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
         AND tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
         AND tc.TABLE_NAME = kcu.TABLE_NAME
       WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
         AND tc.TABLE_NAME = 'subjects'
         AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
         AND kcu.COLUMN_NAME = 'school_year_id'");
    $stmt2->execute();
    $rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows2) {
        echo "  - No FK found on subjects.school_year_id.\n";
    } else {
        foreach ($rows2 as $r) {
            echo "  - {$r['CONSTRAINT_NAME']} -> {$r['REFERENCED_TABLE_NAME']}({$r['REFERENCED_COLUMN_NAME']})\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
