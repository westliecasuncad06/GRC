<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$student_id = $_SESSION['user_id'];
$class_code = $_POST['class_code'] ?? '';

if (empty($class_code)) {
    echo json_encode(['success' => false, 'message' => 'Class code is required']);
    exit();
}

try {
    // Get class ID from class code
    $stmt = $pdo->prepare("SELECT class_id FROM classes WHERE class_code = ?");
    $stmt->execute([$class_code]);
    $class = $stmt->fetch();

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Invalid class code']);
        exit();
    }

    $class_id = $class['class_id'];

    // Check if student is already enrolled in the class
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    $enrolled = $stmt->fetch()['count'];

    if ($enrolled > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this class']);
        exit();
    }

    // Insert student directly into the class
    $stmt = $pdo->prepare("INSERT INTO student_classes (student_id, class_id, enrolled_at) VALUES (?, ?, NOW())");
    $stmt->execute([$student_id, $class_id]);

    echo json_encode(['success' => true, 'message' => 'Successfully enrolled in the class']);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation
        echo json_encode(['success' => false, 'message' => 'You already have a pending or accepted enrollment request for this class']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
