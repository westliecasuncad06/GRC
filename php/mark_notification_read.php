<?php
session_start();
require_once 'db.php';
require_once 'notifications.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$notification_id = trim($_POST['notification_id'] ?? '');

if (empty($notification_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['role'];

    // Initialize notification manager
    $notificationManager = new NotificationManager($pdo);

    // Mark notification as read
    $success = $notificationManager->markAsRead($notification_id, $user_id, $user_type);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
