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
    // Get the enrollment request and verify it belongs to professor's class
    $stmt = $pdo->prepare("
        SELECT er.*, c.professor_id, s.subject_name, st.first_name as student_first, st.last_name as student_last
        FROM enrollment_requests er
        JOIN classes c ON er.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN students st ON er.student_id = st.student_id
        WHERE er.request_id = ? AND er.status = 'pending'
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
        // Check if student is already enrolled
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$request['student_id'], $request['class_id']]);
        $enrolled = $stmt->fetch()['count'];

        if ($enrolled > 0) {
            echo json_encode(['success' => false, 'message' => 'Student is already enrolled in this class']);
            exit();
        }

        // Insert into student_classes
        $stmt = $pdo->prepare("INSERT INTO student_classes (student_id, class_id, enrolled_at) VALUES (?, ?, NOW())");
        $stmt->execute([$request['student_id'], $request['class_id']]);

        // Update request status
        $stmt = $pdo->prepare("UPDATE enrollment_requests SET status = 'accepted' WHERE request_id = ?");
        $stmt->execute([$request_id]);

        $message = 'Student has been added to your subject.';

    } elseif ($action === 'reject') {
        // Update request status
        $stmt = $pdo->prepare("UPDATE enrollment_requests SET status = 'rejected' WHERE request_id = ?");
        $stmt->execute([$request_id]);

        $message = 'Enrollment request rejected for ' . $request['student_first'] . ' ' . $request['student_last'] . ' in ' . $request['subject_name'];
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
