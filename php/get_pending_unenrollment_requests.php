<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$professor_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("\n        SELECT ur.request_id, ur.requested_at, s.subject_name, c.class_code, st.first_name, st.last_name\n        FROM unenrollment_requests ur\n        JOIN classes c ON ur.class_id = c.class_id\n        JOIN subjects s ON c.subject_id = s.subject_id\n        JOIN students st ON ur.student_id = st.student_id\n        WHERE c.professor_id = ? AND ur.status = 'pending'\n        ORDER BY ur.requested_at DESC\n    ");
    $stmt->execute([$professor_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'requests' => $rows,
        'count' => count($rows)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
