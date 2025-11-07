<?php
require_once __DIR__ . '/../db.php';
if (!$pdo) { echo "No DB connection.\n"; exit(1);} 
try {
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo "DB: {$dbName}\n";
    $col = (int)$pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='semesters' AND COLUMN_NAME='school_year_id'")->fetchColumn();
    echo "Before: semesters.school_year_id present? ".$col."\n";
    if ($col) {
        echo "Indexes referencing school_year_id before drop:\n";
        $stmt = $pdo->query("SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='semesters' AND COLUMN_NAME='school_year_id'");
        $idxs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($idxs as $idx) {
            echo " - Attempting to drop index $idx... ";
            try {
                $pdo->exec("ALTER TABLE `semesters` DROP INDEX `".$idx."`");
                echo "dropped.\n";
            } catch (PDOException $e) {
                echo "error: ".$e->getMessage()."\n";
            }
        }
        echo "Now dropping column school_year_id...\n";
        $pdo->exec("ALTER TABLE `semesters` DROP COLUMN `school_year_id`");
        echo "Dropped column.\n";
    } else {
        echo "Column already absent.\n";
    }
} catch (PDOException $e) {
    echo "Error: ".$e->getMessage()."\n";
    exit(1);
}
