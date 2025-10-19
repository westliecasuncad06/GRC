<?php
session_start();
require_once 'db.php';
require_once 'notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['role'];
$pending = 0;

try {
    $count = $notificationManager->getUnreadCount($user_id, $user_type);

    // For professors, also consider pending unenrollment requests as actionable alerts
    if ($user_type === 'professor') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM unenrollment_requests ur
            JOIN classes c ON ur.class_id = c.class_id
            WHERE c.professor_id = ? AND ur.status = 'pending'
        ");
        $stmt->execute([$user_id]);
            $pending = (int)($stmt->fetch()['cnt'] ?? 0);
        // Use max to avoid under-reporting in case notifications were not created yet for these requests
        $count = max((int)$count, $pending);
    }

        echo json_encode(['success' => true, 'count' => (int)$count, 'pending' => (int)$pending]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
