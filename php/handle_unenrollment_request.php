<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$request_id = trim($_POST['request_id'] ?? '');
$action = trim($_POST['action'] ?? '');

if (empty($request_id) || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    // Get the unenrollment request details
    $stmt = $pdo->prepare("SELECT student_id, class_id FROM unenrollment_requests WHERE request_id = ? AND status = 'pending'");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        exit();
    }

    $professor_id = $_SESSION['user_id'];
    $student_id = $request['student_id'];
    $class_id = $request['class_id'];

    if ($action === 'accept') {
        // Remove from student_classes table
        $stmt = $pdo->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$student_id, $class_id]);

        $message = 'Unenrollment request approved successfully';
    } else {
        $message = 'Unenrollment request rejected';
    }

    // Update the unenrollment request
    $stmt = $pdo->prepare("UPDATE unenrollment_requests SET status = ?, processed_at = NOW(), processed_by = ? WHERE request_id = ?");
    $stmt->execute([$action === 'accept' ? 'approved' : 'rejected', $professor_id, $request_id]);

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
