<?php
require_once 'db.php';

try {
    // Delete all entries with semester 'Summer' from school_year_semester table
    $stmt = $pdo->prepare("DELETE FROM school_year_semester WHERE semester = ?");
    $stmt->execute(['Summer']);

    echo "All 'Summer' entries have been deleted from the school_year_semester table.";
} catch (PDOException $e) {
    echo "Error deleting Summer entries: " . $e->getMessage();
}
?>
