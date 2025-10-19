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
$user_type = $_SESSION['role'] ?? '';
$pending = 0;

try {
    // Optional type filter (e.g., student_enrolled)
    $typeFilter = isset($_GET['type']) ? trim($_GET['type']) : null;
    if ($typeFilter) {
        // Special-case: legacy professor enrollment notifications may have type 'info' but title starts with 'New Student Enrollment'
        if ($user_type === 'professor' && $typeFilter === 'student_enrolled') {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS count FROM notifications
                 WHERE user_id = ? AND user_type = 'professor' AND is_read = 0
                 AND (type = 'student_enrolled' OR title LIKE 'New Student Enrollment%')"
            );
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
            $count = (int)($row['count'] ?? 0);
        } else {
            $count = $notificationManager->getUnreadCountByType($user_id, $user_type, $typeFilter);
        }
    } else {
        $count = $notificationManager->getUnreadCount($user_id, $user_type);
    }

    // For professors, also consider pending unenrollment requests as actionable alerts (separate field)
    if ($user_type === 'professor') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt
            FROM unenrollment_requests ur
            JOIN classes c ON ur.class_id = c.class_id
            WHERE c.professor_id = ? AND ur.status = 'pending'"
        );
        $stmt->execute([$user_id]);
        $pending = (int)($stmt->fetch()['cnt'] ?? 0);
    }

    echo json_encode(['success' => true, 'count' => (int)$count, 'pending' => (int)$pending]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>
