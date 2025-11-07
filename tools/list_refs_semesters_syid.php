<?php
require_once __DIR__ . '/../db.php';
if (!$pdo) { echo "No DB connection.\n"; exit(1);} 
try {
    $sql = "SELECT kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.REFERENCED_TABLE_NAME, GROUP_CONCAT(kcu.REFERENCED_COLUMN_NAME ORDER BY kcu.POSITION_IN_UNIQUE_CONSTRAINT) as ref_cols
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
            WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
              AND kcu.REFERENCED_TABLE_NAME = 'semesters'
            GROUP BY kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.REFERENCED_TABLE_NAME";
    foreach ($pdo->query($sql) as $row) {
        echo $row['TABLE_NAME'].'.'.$row['CONSTRAINT_NAME'].' -> semesters('.$row['ref_cols'].")\n";
    }
} catch (PDOException $e) {
    echo "Error: ".$e->getMessage()."\n";
}
