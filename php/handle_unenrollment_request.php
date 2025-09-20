<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$professor_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'accept' or 'reject'

if (empty($request_id) || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

try {
    // Get the unenrollment request and verify it belongs to professor's class
    $stmt = $pdo->prepare("
        SELECT ur.*, c.professor_id, s.subject_name, st.first_name as student_first, st.last_name as student_last
        FROM unenrollment_requests ur
        JOIN classes c ON ur.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN students st ON ur.student_id = st.student_id
        WHERE ur.request_id = ? AND ur.status = 'pending'
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        exit();
    }

    if ($request['professor_id'] !== $professor_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized to handle this request']);
        exit();
    }

    if ($action === 'accept') {
        // Check if student is still enrolled
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$request['student_id'], $request['class_id']]);
        $enrolled = $stmt->fetch()['count'];

        if ($enrolled == 0) {
            echo json_encode(['success' => false, 'message' => 'Student is not enrolled in this class']);
            exit();
        }

        // Remove from student_classes
        $stmt = $pdo->prepare("DELETE FROM student_classes WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$request['student_id'], $request['class_id']]);

        // Update request status
        $stmt = $pdo->prepare("UPDATE unenrollment_requests SET status = 'accepted', handled_at = NOW(), handled_by = ? WHERE request_id = ?");
        $stmt->execute([$professor_id, $request_id]);

        $message = 'Student has been unenrolled from your subject.';

    } elseif ($action === 'reject') {
        // Update request status
        $stmt = $pdo->prepare("UPDATE unenrollment_requests SET status = 'rejected', handled_at = NOW(), handled_by = ? WHERE request_id = ?");
        $stmt->execute([$professor_id, $request_id]);

        $message = 'Unenrollment request rejected for ' . $request['student_first'] . ' ' . $request['student_last'] . ' in ' . $request['subject_name'];
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
