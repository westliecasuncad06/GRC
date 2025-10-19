<?php
session_start();
require_once 'db.php';
require_once 'notifications.php';

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
    $request_id = $pdo->lastInsertId();

    // Fetch details for notifications (professor id, subject, class code)
    $stmt = $pdo->prepare("SELECT c.professor_id, s.subject_name, c.class_code FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();

    // Fetch student name
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    $student_name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Student';

    $subject_name = $class['subject_name'] ?? 'Subject';
    $class_code = $class['class_code'] ?? '';
    $professor_id = $class['professor_id'] ?? null;
    $now_display = date('M j, Y, g:i a');

    // Notify student that request was submitted
    $notificationManager->createNotification(
        $student_id,
        'student',
        'Unenrollment Request Submitted',
        "Your request to unenroll from {$subject_name} ({$class_code}) was submitted on {$now_display}. Waiting for professor approval.",
        'info',
        $request_id,
        $class_id
    );

    // Notify professor about the new unenrollment request
    if ($professor_id) {
        $notificationManager->createNotification(
            $professor_id,
            'professor',
            'Unenrollment Request',
            "{$student_name} requested to unenroll from {$subject_name} ({$class_code}).\nDate: {$now_display}",
            'info',
            $request_id,
            $class_id
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Unenrollment request submitted successfully. Waiting for professor approval.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
