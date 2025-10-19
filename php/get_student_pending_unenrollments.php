<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$student_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT class_id FROM unenrollment_requests WHERE student_id = ? AND status = 'pending'");
    $stmt->execute([$student_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $classIds = array_map(function($r) { return (string)$r['class_id']; }, $rows);

    echo json_encode([
        'success' => true,
        'pending_class_ids' => $classIds,
        'count' => count($classIds)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
