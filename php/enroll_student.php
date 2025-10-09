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

    // Check if student has a pending unenrollment request for this class, cancel it
    $stmt = $pdo->prepare("SELECT request_id FROM unenrollment_requests WHERE student_id = ? AND class_id = ? AND status = 'pending'");
    $stmt->execute([$student_id, $class_id]);
    $pending_unenrollment = $stmt->fetch();

    if ($pending_unenrollment) {
        // Cancel the pending unenrollment request by deleting it
        $stmt = $pdo->prepare("DELETE FROM unenrollment_requests WHERE request_id = ?");
        $stmt->execute([$pending_unenrollment['request_id']]);
    }

    // Check if student already has a request for this class
    $stmt = $pdo->prepare("SELECT status FROM enrollment_requests WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    $existing_request = $stmt->fetch();

    if ($existing_request) {
        if ($existing_request['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'You already have a pending enrollment request for this class']);
            exit();
        } elseif ($existing_request['status'] === 'accepted') {
            // Check if still enrolled
            if ($enrolled > 0) {
                echo json_encode(['success' => false, 'message' => 'You are already enrolled in this class']);
                exit();
            } else {
                // Was unenrolled, allow re-enrollment by updating to pending
                $stmt = $pdo->prepare("UPDATE enrollment_requests SET status = 'pending', requested_at = NOW(), handled_at = NULL, handled_by = NULL, processed_at = NULL, processed_by = NULL WHERE student_id = ? AND class_id = ?");
                $stmt->execute([$student_id, $class_id]);
            }
        } elseif ($existing_request['status'] === 'rejected') {
            // Update the rejected request to pending
            $stmt = $pdo->prepare("UPDATE enrollment_requests SET status = 'pending', requested_at = NOW(), handled_at = NULL, handled_by = NULL, processed_at = NULL, processed_by = NULL WHERE student_id = ? AND class_id = ?");
            $stmt->execute([$student_id, $class_id]);
        }
    } else {
        // Insert new enrollment request
        $stmt = $pdo->prepare("INSERT INTO enrollment_requests (student_id, class_id, status, requested_at) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$student_id, $class_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Enrollment request submitted successfully. Please wait for professor approval.']);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation
        echo json_encode(['success' => false, 'message' => 'You already have a pending or accepted enrollment request for this class']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
