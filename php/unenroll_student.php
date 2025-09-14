<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

error_log("Unenroll request received");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$class_id = trim($_POST['class_id'] ?? '');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    error_log("Unauthorized access attempt: session=" . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$student_id = $_SESSION['user_id'];
error_log("Session user_id: $student_id, role: " . $_SESSION['role']);

if (empty($class_id)) {
    error_log("Class ID is missing in request");
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

// Check if student is enrolled in this class
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    $enrollment_count = $stmt->fetch()['count'];
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

error_log("Unenroll attempt: student_id=$student_id, class_id=$class_id, enrollment_count=$enrollment_count");

if ($enrollment_count == 0) {
    error_log("Student $student_id is not enrolled in class $class_id");
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this class.']);
    exit();
}

try {
    // Unenroll the student
    $stmt = $pdo->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);

    error_log("Student $student_id successfully unenrolled from class $class_id");

    echo json_encode([
        'success' => true,
        'message' => 'Successfully unenrolled from the class!'
    ]);
} catch (PDOException $e) {
    error_log("Failed to unenroll student $student_id from class $class_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to unenroll: ' . $e->getMessage()]);
}
?>
