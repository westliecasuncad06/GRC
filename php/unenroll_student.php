<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$student_id = $_SESSION['user_id'];
$class_id = trim($_POST['class_id'] ?? '');

if (empty($class_id)) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

try {
    // Check if student is enrolled in this class
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    $enrollment_count = $stmt->fetch()['count'];

    if ($enrollment_count == 0) {
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this class']);
        exit();
    }

    // Check if student already has a pending unenrollment request for this class
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM unenrollment_requests WHERE student_id = ? AND class_id = ? AND status = 'pending'");
    $stmt->execute([$student_id, $class_id]);
    $pending_count = $stmt->fetch()['count'];

    if ($pending_count > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending unenrollment request for this class']);
        exit();
    }

    // Insert unenrollment request
    $stmt = $pdo->prepare("INSERT INTO unenrollment_requests (student_id, class_id, status, requested_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$student_id, $class_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Unenrollment request submitted successfully. Waiting for professor approval.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
