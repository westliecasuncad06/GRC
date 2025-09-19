<?php
require_once 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS enrollment_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        class_id VARCHAR(20) NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        handled_at DATETIME DEFAULT NULL,
        handled_by VARCHAR(20) DEFAULT NULL,
        UNIQUE KEY unique_request (student_id, class_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    echo "Table enrollment_requests created or already exists.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
