<?php
require_once 'db.php';

if (!isset($_GET['class_id'])) {
    echo json_encode(['error' => 'Class ID is required']);
    exit();
}

$class_id = $_GET['class_id'];

try {
    $stmt = $pdo->prepare("SELECT s.subject_name, p.first_name, p.last_name 
                          FROM classes c 
                          JOIN subjects s ON c.subject_id = s.subject_id 
                          LEFT JOIN professors p ON c.professor_id = p.professor_id 
                          WHERE c.class_id = ?");
    $stmt->execute([$class_id]);
    $result = $stmt->fetch();

    if ($result) {
        $response = [
            'subject_name' => $result['subject_name'],
            'professor_name' => $result['first_name'] ? $result['first_name'] . ' ' . $result['last_name'] : 'Not Assigned'
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Class not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
