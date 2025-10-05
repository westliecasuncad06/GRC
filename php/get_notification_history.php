<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get enrollment request history
    $stmt = $pdo->prepare("
        SELECT
            er.request_id,
            er.status,
            er.requested_at,
            er.processed_at,
            c.class_name,
            c.class_code,
            s.subject_name,
            p.first_name as prof_first_name,
            p.last_name as prof_last_name,
            'enrollment' as request_type
        FROM enrollment_requests er
        JOIN classes c ON er.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN professors p ON er.processed_by = p.professor_id
        WHERE er.student_id = ?
        ORDER BY er.requested_at DESC
    ");
    $stmt->execute([$user_id]);
    $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unenrollment request history
    $stmt = $pdo->prepare("
        SELECT
            ur.request_id,
            ur.status,
            ur.requested_at,
            ur.processed_at,
            c.class_name,
            c.class_code,
            s.subject_name,
            p.first_name as prof_first_name,
            p.last_name as prof_last_name,
            'unenrollment' as request_type
        FROM unenrollment_requests ur
        JOIN classes c ON ur.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN professors p ON ur.processed_by = p.professor_id
        WHERE ur.student_id = ?
        ORDER BY ur.requested_at DESC
    ");
    $stmt->execute([$user_id]);
    $unenrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine and sort all requests
    $all_requests = array_merge($enrollment_requests, $unenrollment_requests);
    usort($all_requests, function($a, $b) {
        return strtotime($b['requested_at']) - strtotime($a['requested_at']);
    });

    // Format the data for frontend
    $history = array_map(function($request) {
        return [
            'request_id' => $request['request_id'],
            'request_type' => $request['request_type'],
            'status' => $request['status'],
            'subject_name' => $request['subject_name'],
            'class_name' => $request['class_name'],
            'class_code' => $request['class_code'],
            'professor_name' => $request['prof_first_name'] && $request['prof_last_name'] ? $request['prof_first_name'] . ' ' . $request['prof_last_name'] : 'N/A',
            'created_at' => $request['requested_at'],
            'processed_at' => $request['processed_at']
        ];
    }, $all_requests);

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
