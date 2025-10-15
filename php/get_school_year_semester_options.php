<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT
            CONCAT(y.year_label, ' - ', s.semester_name) AS label,
            s.id AS semester_id,
            y.year_label,
            s.semester_name
        FROM semesters s
        JOIN school_years y ON s.school_year_id = y.id
        WHERE s.status = 'Active' AND y.status = 'Active'
        ORDER BY y.year_label DESC, s.semester_name
    ");
    $stmt->execute();
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
