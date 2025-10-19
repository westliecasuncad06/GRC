<?php
session_start();
require_once 'db.php';
require_once 'notifications.php';

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
    // Get class ID, professor ID, and subject details from class code
    $stmt = $pdo->prepare("SELECT c.class_id, c.professor_id, s.subject_name, c.class_code FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_code = ?");
    $stmt->execute([$class_code]);
    $class = $stmt->fetch();

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Invalid class code']);
        exit();
    }

    $class_id = $class['class_id'];
    $professor_id = $class['professor_id'];
    $subject_name = $class['subject_name'];
    $class_code_display = $class['class_code'];

    // Check if student is already enrolled in the class
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    $enrolled = $stmt->fetch()['count'];

    if ($enrolled > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this class']);
        exit();
    }

    // Check if student has a pending unenrollment request for this class, cancel it
    $stmt = $pdo->prepare("SELECT request_id FROM unenrollment_requests WHERE student_id = ? AND class_id = ? AND status = 'pending'");
    $stmt->execute([$student_id, $class_id]);
    $pending_unenrollment = $stmt->fetch();

    if ($pending_unenrollment) {
        // Cancel the pending unenrollment request by deleting it
        $stmt = $pdo->prepare("DELETE FROM unenrollment_requests WHERE request_id = ?");
        $stmt->execute([$pending_unenrollment['request_id']]);
    }

    // Insert directly into student_classes for instant enrollment
    $stmt = $pdo->prepare("INSERT INTO student_classes (student_id, class_id, enrolled_at) VALUES (?, ?, NOW())");
    $stmt->execute([$student_id, $class_id]);

    // Get student name for notifications
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    $student_name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Student';

    // Create notification for the student
    $student_notification_title = 'Enrollment Successful';
    $student_notification_message = "You have successfully enrolled in {$subject_name} ({$class_code_display}).";
    $notificationManager->createNotification(
        $student_id,
        'student',
        $student_notification_title,
        $student_notification_message,
        'enrollment_success',
        null,
        $class_id
    );

    // Create notification for the professor
    $enrollment_date = date('M j, Y, g:i a');
    $professor_notification_title = 'New Student Enrollment';
    $professor_notification_message = "A new student has enrolled in {$subject_name} ({$class_code_display}).\nDate: {$enrollment_date}";
    $notificationManager->createNotification(
        $professor_id,
        'professor',
        $professor_notification_title,
        $professor_notification_message,
        'student_enrolled',
        null,
        $class_id
    );

    echo json_encode(['success' => true, 'message' => 'Successfully enrolled in the class!']);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this class']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
