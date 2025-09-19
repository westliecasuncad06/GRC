<?php
require_once 'db.php';

try {
    // Check if requested_at column exists
    $stmt = $pdo->query("DESCRIBE enrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('requested_at', $columns)) {
        // Add the requested_at column
        $sql = "ALTER TABLE enrollment_requests ADD COLUMN requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER class_id";
        $pdo->exec($sql);
        echo "Column 'requested_at' added successfully to enrollment_requests table.";
    } else {
        echo "Column 'requested_at' already exists in enrollment_requests table.";
    }

    // Also check if there are any existing rows without requested_at and update them
    $stmt = $pdo->query("SELECT COUNT(*) FROM enrollment_requests WHERE requested_at IS NULL");
    $nullCount = $stmt->fetchColumn();

    if ($nullCount > 0) {
        $pdo->exec("UPDATE enrollment_requests SET requested_at = NOW() WHERE requested_at IS NULL");
        echo " Updated $nullCount existing rows with current timestamp.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
