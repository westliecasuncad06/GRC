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

// Pagination params
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4; // default 4 per request
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = max(1, min($limit, 50)); // clamp limit between 1 and 50
$offset = max(0, $offset);

try {
    // Get batch notifications
    $notifications = $notificationManager->getNotifications($user_id, $user_type, $limit, $offset);

    // Get total count for has_more flag
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$user_id, $user_type]);
    $total = (int)($stmt->fetch()['count'] ?? 0);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + count($notifications)) < $total
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
