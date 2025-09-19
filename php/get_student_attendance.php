<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Class ID is required']);
    exit();
}

// Verify that the student is enrolled in the class
$enrollment_check = $pdo->prepare("SELECT COUNT(*) FROM student_classes WHERE student_id = ? AND class_id = ?");
$enrollment_check->execute([$student_id, $class_id]);
if ($enrollment_check->fetchColumn() == 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Not enrolled in this class']);
    exit();
}

// Get attendance records for the student in this class
$query = "SELECT date, status, remarks
          FROM attendance
          WHERE student_id = ? AND class_id = ?
          ORDER BY date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$student_id, $class_id]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($attendance_records);
?>
