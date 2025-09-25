<?php
require_once 'php/db.php';

try {
    $sql = "ALTER TABLE classes ADD COLUMN school_year VARCHAR(20) DEFAULT NULL";
    $pdo->exec($sql);
    echo "Migration successful: school_year column added to classes table.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
