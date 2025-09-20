<?php
require_once 'db.php';

try {
    $sql = file_get_contents('../create_notifications_table.sql');
    $pdo->exec($sql);
    echo "Notifications table created successfully!";
} catch (PDOException $e) {
    echo "Error creating notifications table: " . $e->getMessage();
}
?>
