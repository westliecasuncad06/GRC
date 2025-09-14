<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'db.php';

if (!isset($_GET['class_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Class ID is required']);
    exit();
}

$class_id = $_GET['class_id'];
$professor_id = $_SESSION['user_id'];
$report_type = isset($_GET['type']) ? $_GET['type'] : 'detailed'; // 'detailed' or 'summary'
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Verify that the class belongs to this professor
$query = "SELECT c.class_id, c.class_name, s.subject_name, c.class_code
          FROM classes c
          JOIN subjects s ON c.subject_id = s.subject_id
          WHERE c.class_id = ? AND c.professor_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$class_id, $professor_id]);
$class_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class_info) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Access denied to this class']);
    exit();
}

try {
    if ($report_type === 'summary') {
        // Generate summary report
        $query = "SELECT
                    s.student_id,
                    s.first_name,
                    s.last_name,
                    COUNT(a.student_id) as total_classes,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) as excused,
                    ROUND((SUM(CASE WHEN a.status IN ('Present', 'Late', 'Excused') THEN 1 ELSE 0 END) / COUNT(a.student_id)) * 100, 2) as attendance_rate
                  FROM students s
                  JOIN student_classes sc ON s.student_id = sc.student_id
                  LEFT JOIN attendance a ON s.student_id = a.student_id AND a.class_id = ?
                  WHERE sc.class_id = ?
                  GROUP BY s.student_id, s.first_name, s.last_name
                  ORDER BY s.last_name, s.first_name";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$class_id, $class_id]);
        $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $class_info['class_code'] . '_attendance_summary_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV headers
        echo "Student ID,First Name,Last Name,Total Classes,Present,Absent,Late,Excused,Attendance Rate (%)\n";

        // Output data
        foreach ($summary_data as $row) {
            echo '"' . $row['student_id'] . '",';
            echo '"' . $row['first_name'] . '",';
            echo '"' . $row['last_name'] . '",';
            echo '"' . $row['total_classes'] . '",';
            echo '"' . $row['present'] . '",';
            echo '"' . $row['absent'] . '",';
            echo '"' . $row['late'] . '",';
            echo '"' . $row['excused'] . '",';
            echo '"' . $row['attendance_rate'] . '"';
            echo "\n";
        }

    } else {
        // Generate detailed report
        $query = "SELECT
                    a.date,
                    s.student_id,
                    s.first_name,
                    s.last_name,
                    a.status,
                    a.remarks
                  FROM attendance a
                  JOIN students s ON a.student_id = s.student_id
                  WHERE a.class_id = ?";

        $params = [$class_id];
        if ($date_from && $date_to) {
            $query .= " AND a.date BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        } elseif ($date_from) {
            $query .= " AND a.date >= ?";
            $params[] = $date_from;
        } elseif ($date_to) {
            $query .= " AND a.date <= ?";
            $params[] = $date_to;
        }

        $query .= " ORDER BY a.date DESC, s.last_name, s.first_name";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $detailed_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for CSV download
        $filename = $class_info['class_code'] . '_attendance_detailed_' . date('Y-m-d');
        if ($date_from || $date_to) {
            $filename .= '_' . ($date_from ?: 'start') . '_to_' . ($date_to ?: 'end');
        }
        $filename .= '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV headers
        echo "Date,Student ID,First Name,Last Name,Status,Remarks\n";

        // Output data
        foreach ($detailed_data as $row) {
            echo '"' . $row['date'] . '",';
            echo '"' . $row['student_id'] . '",';
            echo '"' . $row['first_name'] . '",';
            echo '"' . $row['last_name'] . '",';
            echo '"' . ($row['status'] ?: 'Not Marked') . '",';
            echo '"' . ($row['remarks'] ?: '') . '"';
            echo "\n";
        }
    }

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
