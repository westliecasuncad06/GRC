<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

// Fetch professor data using session values
$professor_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name, email, mobile
FROM professors
WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

// Populate session fields only if values are available in DB
if ($professor) {
    $_SESSION['first_name'] = $professor['first_name'];
    $_SESSION['last_name'] = $professor['last_name'];
    $_SESSION['email'] = $professor['email'];
    $_SESSION['mobile'] = $professor['mobile'];
}

// Get professor's subjects (from attendance_reports.php)
$query = "SELECT s.*, c.class_id, c.class_code, c.schedule, c.room, c.section
          FROM subjects s
          JOIN classes c ON s.subject_id = c.subject_id
          WHERE c.professor_id = ? AND c.status = 'active'
          ORDER BY s.subject_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$subjects = $stmt->fetchAll();

try {
    // Check if 'requested_at' column exists in enrollment_requests table
    $stmt = $pdo->query("DESCRIBE enrollment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('requested_at', $columns)) {
        // Add the requested_at column
        $sql = "ALTER TABLE enrollment_requests ADD COLUMN requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER class_id";
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    // Log or handle error silently to avoid breaking the page
}

// Fetch pending enrollment requests for professor's classes
$pending_requests_query = "
    SELECT er.request_id, er.student_id, er.class_id, er.requested_at,
           st.first_name, st.last_name,
           c.class_code, s.subject_name
    FROM enrollment_requests er
    JOIN students st ON er.student_id = st.student_id
    JOIN classes c ON er.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE er.status = 'pending' AND c.professor_id = ?
    ORDER BY er.requested_at DESC
";
$pending_stmt = $pdo->prepare($pending_requests_query);
$pending_stmt->execute([$professor_id]);
$pending_requests = $pending_stmt->fetchAll();

// Fetch pending unenrollment requests for professor's classes
$pending_unenrollment_requests_query = "
    SELECT ur.request_id, ur.student_id, ur.class_id, ur.requested_at,
           st.first_name, st.last_name,
           c.class_code, s.subject_name
    FROM unenrollment_requests ur
    JOIN students st ON ur.student_id = st.student_id
    JOIN classes c ON ur.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE ur.status = 'pending' AND c.professor_id = ?
    ORDER BY ur.requested_at DESC
";
$pending_unenrollment_stmt = $pdo->prepare($pending_unenrollment_requests_query);
$pending_unenrollment_stmt->execute([$professor_id]);
$pending_unenrollment_requests = $pending_unenrollment_stmt->fetchAll();

// Add pending requests count for notification badge (enrollment + unenrollment)
$pending_requests_count = count($pending_requests) + count($pending_unenrollment_requests);

// Get sections and students for professor's classes
$query = "SELECT DISTINCT c.section, st.student_id, st.first_name, st.last_name, st.email, sc.enrolled_at, c.class_name
          FROM students st
          JOIN student_classes sc ON st.student_id = sc.student_id
          JOIN classes c ON sc.class_id = c.class_id
          JOIN subjects sub ON c.subject_id = sub.subject_id
          WHERE c.professor_id = ?
          ORDER BY c.section, st.last_name, st.first_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$all_students = $stmt->fetchAll();

// The rest of the code remains unchanged

// Group students by section
$students_by_section = [];
foreach ($all_students as $student) {
    $section = $student['section'] ?: 'Unassigned';
    if (!isset($students_by_section[$section])) {
        $students_by_section[$section] = [];
    }
    $students_by_section[$section][] = $student;
}

// Get attendance statistics (from attendance_reports.php)
$attendance_stats = [];
foreach ($subjects as $subject) {
    $query = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused
              FROM attendance 
              WHERE class_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$subject['class_id']]);
    $stats = $stmt->fetch();
    
    $attendance_stats[$subject['class_id']] = $stats;
}

// Get recent attendance dates (from attendance_reports.php)
$recent_dates = [];
foreach ($subjects as $subject) {
    $query = "SELECT DISTINCT date 
              FROM attendance 
              WHERE class_id = ? 
              ORDER BY date DESC 
              LIMIT 2";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$subject['class_id']]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $recent_dates[$subject['class_id']] = $dates;
}

// Get detailed attendance statistics (from attendance_analytics.php)
$detailed_stats = [];
foreach ($subjects as $subject) {
    // Overall statistics
    $query = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused,
                MIN(date) as first_date,
                MAX(date) as last_date
              FROM attendance 
              WHERE class_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$subject['class_id']]);
    $overall = $stmt->fetch();
    
    // Monthly breakdown
    $query = "SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused
              FROM attendance 
              WHERE class_id = ?
              GROUP BY DATE_FORMAT(date, '%Y-%m')
              ORDER BY month DESC
              LIMIT 6";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$subject['class_id']]);
    $monthly = $stmt->fetchAll();
    
    // Student-wise statistics
    $query = "SELECT 
                s.student_id,
                s.first_name,
                s.last_name,
                COUNT(a.student_id) as total_classes,
                SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) as excused
              FROM students s
              JOIN student_classes sc ON s.student_id = sc.student_id
              LEFT JOIN attendance a ON s.student_id = a.student_id AND a.class_id = ?
              WHERE sc.class_id = ?
              GROUP BY s.student_id, s.first_name, s.last_name
              ORDER BY s.last_name, s.first_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$subject['class_id'], $subject['class_id']]);
    $students = $stmt->fetchAll();
    
    $detailed_stats[$subject['class_id']] = [
        'overall' => $overall,
        'monthly' => $monthly,
        'students' => $students
    ];
 }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles_fixed.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/mobile-responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #F75270;
            --primary-dark: #DC143C;
            --primary-light: #F7CAC9;
            --secondary: #F75270;
            --accent: #F7CAC9;
            --light: #FDEBD0;
            --dark: #343a40;
            --gray: #6c757d;
            --light-gray: #F7CAC9;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .stat-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .stat-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-present { background: #d4edda; color: #155724; }
        .badge-absent { background: #f8d7da; color: #721c24; }
        .badge-late { background: #fff3cd; color: #856404; }
        .badge-excused { background: #d1ecf1; color: #0c5460; }

        .recent-dates {
            margin-top: 1rem;
        }

        .date-list {
            list-style: none;
            padding: 0;
        }

        .date-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .date-item:last-child {
            border-bottom: none;
        }

        .view-report-btn {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .view-report-btn:hover {
            background: var(--primary-dark);
        }

        /* Attendance Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 12px;
            padding: 0;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .modal-close:hover {
            background-color: #f8f9fa;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .attendance-record {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .record-header {
            display: grid;
            grid-template-columns: 2fr 1fr 2fr;
            gap: 1rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .record-item {
            display: grid;
            grid-template-columns: 2fr 1fr 2fr;
            gap: 1rem;
            padding: 0.75rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            align-items: center;
            font-size: 0.9rem;
        }

        .attendance-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
        }

        .attendance-status.Present {
            background-color: #d4edda;
            color: #155724;
        }

        .attendance-status.Absent {
            background-color: #f8d7da;
            color: #721c24;
        }

        .attendance-status.Late {
            background-color: #fff3cd;
            color: #856404;
        }

        .attendance-status.Excused {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .attendance-status.null {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .students-table th,
        .students-table td {
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            text-align: left;
        }

        .students-table th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .students-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .attendance-rate {
            font-weight: 600;
        }

        .rate-high { color: #28a745; }
        .rate-medium { color: #ffc107; }
        .rate-low { color: #dc3545; }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 2rem;
            font-style: italic;
        }

        .tab-container {
            margin-bottom: 2rem;
        }

        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            font-weight: 500;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Responsive design for small mobile devices (300px - 420px) */
        @media (max-width: 420px) {
            .dashboard-container {
                padding: 0.5rem;
                max-width: 100%;
                margin: 0 auto;
            }

            .table-header-enhanced {
                padding: 0.75rem;
                margin-bottom: 1rem;
                border-radius: 8px;
            }

            .table-title-enhanced {
                font-size: 1rem;
                text-align: center;
                margin: 0;
            }

            .stat-card-enhanced {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .stat-header-enhanced {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .stat-icon-enhanced {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .stat-info-enhanced {
                text-align: center;
            }

            .stat-title-enhanced {
                font-size: 0.9rem;
                margin: 0;
            }

            .stat-subtitle-enhanced {
                font-size: 0.75rem;
            }

            .stat-metrics-enhanced {
                margin-bottom: 0.75rem;
            }

            .stat-main-metric {
                text-align: center;
                margin-bottom: 0.75rem;
            }

            .stat-value-enhanced {
                font-size: 1.5rem;
                margin-bottom: 0.25rem;
            }

            .stat-label-enhanced {
                font-size: 0.8rem;
            }

            .stat-breakdown-enhanced {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }

            .stat-breakdown-item {
                padding: 0.375rem;
                border-radius: 6px;
                justify-content: center;
                text-align: center;
            }

            .stat-breakdown-value {
                font-size: 0.9rem;
            }

            .stat-breakdown-label {
                font-size: 0.75rem;
            }

            .stat-section-title {
                font-size: 0.8rem;
                text-align: center;
                margin-bottom: 0.5rem;
            }

            .stat-recent-list {
                gap: 0.375rem;
            }

            .stat-recent-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 0.5rem;
                border-radius: 6px;
                gap: 0.375rem;
            }

            .stat-recent-date {
                font-size: 0.75rem;
                justify-content: center;
            }

            .stat-action-btn {
                width: 100%;
                justify-content: center;
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                border-radius: 6px;
            }

            .stat-primary-btn {
                width: 100%;
                justify-content: center;
                padding: 0.625rem 0.75rem;
                font-size: 0.85rem;
                border-radius: 6px;
            }

            .chart-title {
                font-size: 0.9rem;
                text-align: center;
            }

            .students-list {
                grid-template-columns: 1fr !important;
                gap: 0.375rem;
            }

            .student-card {
                padding: 0.5rem;
                font-size: 0.8rem;
                border-radius: 6px;
                text-align: center;
            }

            .section-group h4 {
                font-size: 0.9rem;
                text-align: center;
                margin-bottom: 0.75rem;
            }

            .modal-content {
                width: 95%;
                max-width: none;
                max-height: 85vh;
                border-radius: 8px;
            }

            .modal-header {
                padding: 0.75rem;
                border-radius: 8px 8px 0 0;
            }

            .modal-title {
                font-size: 1.1rem;
                text-align: center;
            }

            .modal-body {
                padding: 0.75rem;
            }

            .record-header,
            .record-item {
                grid-template-columns: 1fr;
                gap: 0.25rem;
                font-size: 0.8rem;
                text-align: center;
            }

            .record-header {
                font-size: 0.75rem;
                padding: 0.5rem;
            }

            .record-item {
                padding: 0.5rem;
            }

            .attendance-status {
                font-size: 0.7rem;
                padding: 2px 4px;
                display: inline-block;
                margin: 0.125rem 0;
            }

            .notification-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 0.5rem;
                padding: 0.75rem;
                border-radius: 8px;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .notification-content {
                width: 100%;
                text-align: center;
            }

            .notification-title {
                font-size: 0.9rem;
            }

            .notification-message {
                font-size: 0.8rem;
            }

            .notification-meta {
                flex-direction: column;
                align-items: center;
                gap: 0.375rem;
                width: 100%;
                text-align: center;
            }

            .notification-actions {
                width: 100%;
                display: flex;
                gap: 0.375rem;
                justify-content: center;
            }

            .btn-enhanced {
                flex: 1;
                padding: 0.625rem 0.75rem;
                font-size: 0.85rem;
                border-radius: 6px;
            }

            .modal-footer {
                padding: 0.75rem;
                flex-direction: column;
                gap: 0.375rem;
            }

            .modal-footer .btn-enhanced {
                width: 100%;
            }

            .students-table {
                font-size: 0.75rem;
            }

            .students-table th,
            .students-table td {
                padding: 0.375rem;
                text-align: center;
            }

            .subject-selector {
                margin-bottom: 0.75rem;
                text-align: center;
            }

            .subject-select {
                width: 100%;
                padding: 0.625rem;
                font-size: 0.85rem;
                border-radius: 6px;
            }

            .no-data {
                padding: 0.75rem;
                font-size: 0.85rem;
                text-align: center;
            }

            .stat-empty-enhanced {
                padding: 1.5rem 0.75rem;
                text-align: center;
            }

            .stat-empty-text {
                font-size: 0.9rem;
            }

            .stat-empty-icon {
                font-size: 1.75rem;
                margin-bottom: 0.75rem;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .chart-container {
                padding: 0.75rem;
            }

            .stat-card {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        /* Dropdown Menu Styles */
        .user-dropdown {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-width: 150px;
            z-index: 1000;
            margin-top: 0.5rem;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: var(--light-gray);
        }

        .dropdown-item:first-child {
            border-radius: 8px 8px 0 0;
        }

        .dropdown-item:last-child {
            border-radius: 0 0 8px 8px;
        }

        /* Enhanced Stat Card Styles */
        .stat-card-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-header-enhanced {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-icon-enhanced {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-info-enhanced {
            flex: 1;
        }

        .stat-title-enhanced {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 0.25rem 0;
        }

        .stat-subtitle-enhanced {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        .stat-metrics-enhanced {
            margin-bottom: 1.5rem;
        }

        .stat-main-metric {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .stat-value-enhanced {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .stat-label-enhanced {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 600;
        }

        .stat-breakdown-enhanced {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-breakdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-breakdown-icon {
            font-size: 1.2rem;
            width: 30px;
            text-align: center;
        }

        .stat-breakdown-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-breakdown-label {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }

        .stat-actions-enhanced {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 1.5rem;
        }

        .stat-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .stat-recent-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .stat-recent-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-recent-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--dark);
            font-weight: 500;
        }

        .stat-action-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: background-color 0.2s;
        }

        .stat-action-btn:hover {
            background: var(--primary-dark);
        }

        .stat-empty-enhanced {
            text-align: center;
            padding: 3rem 1rem;
        }

        .stat-empty-icon {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .stat-empty-text {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .stat-primary-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Table Header */
        .table-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .table-title-enhanced {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Notification Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.6) 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 0;
            width: 90%;
            max-width: 650px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: scale(0.9);
            opacity: 0;
            position: relative;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes modalSlideIn {
            from {
                transform: scale(0.9) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="50" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="50" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .modal-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .modal-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 1.2rem;
            cursor: pointer;
            color: white;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 2;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .modal-body {
            padding: 2.5rem;
            background: white;
        }

        .modal-footer {
            padding: 2rem 2.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            background: #f8f9fa;
            border-radius: 0 0 20px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-enhanced {
            padding: 0.875rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .btn-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-enhanced:hover::before {
            left: 100%;
        }

        .btn-secondary-enhanced {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(108, 117, 125, 0.3);
        }

        /* Notification List Styles */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .notification-message {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--gray);
        }

        .notification-time {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .notification-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-unread {
            background: rgba(247, 82, 112, 0.1);
            color: var(--primary);
        }

        .status-read {
            background: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        .no-notifications {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray);
        }

        .no-notifications-icon {
            font-size: 4rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .no-notifications-text {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .no-notifications-subtext {
            font-size: 0.9rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }


    </style>
</head>
<body class="professor">
    <?php include '../includes/navbar_professor.php'; ?>

    <?php include '../includes/sidebar_professor.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-chart-line" style="margin-right: 10px;"></i>Professor Dashboard - Attendance Reports & Analytics</h2>
        </div>

        <div class="dashboard-container">
            <?php if (empty($subjects)): ?>
                <div class="no-data">
                    <p>No classes found. Please create a class first.</p>
                </div>
            <?php else: ?>
                <!-- Attendance Reports -->
                <div class="reports-container">
                        <div class="stats-grid">
                            <?php foreach ($subjects as $subject): 
                                $stats = $attendance_stats[$subject['class_id']] ?? null;
                                $recent = $recent_dates[$subject['class_id']] ?? [];
                            ?>
                            <div class="stat-card-enhanced">
                                <div class="stat-header-enhanced">
                                    <div class="stat-icon-enhanced">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="stat-info-enhanced">
                                        <h3 class="stat-title-enhanced"><?php echo $subject['subject_name']; ?></h3>
                                        <span class="stat-subtitle-enhanced"><?php echo $subject['class_code']; ?></span>
                                    </div>
                                </div>

                                <?php if ($stats && $stats['total_records'] > 0): ?>
                                    <div class="stat-metrics-enhanced">
                                        <div class="stat-main-metric">
                                            <div class="stat-value-enhanced">
                                                <?php
                                                $attendance_rate = $stats['total_records'] > 0 ?
                                                    (($stats['present'] + $stats['late'] + $stats['excused']) / $stats['total_records']) * 100 : 0;
                                                echo number_format($attendance_rate, 1) . '%';
                                                ?>
                                            </div>
                                            <div class="stat-label-enhanced">Attendance Rate</div>
                                        </div>

                                        <div class="stat-breakdown-enhanced">
                                            <div class="stat-breakdown-item">
                                                <div class="stat-breakdown-icon" style="color: #28a745;">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <div class="stat-breakdown-value"><?php echo $stats['present']; ?></div>
                                                <div class="stat-breakdown-label">Present</div>
                                            </div>
                                            <div class="stat-breakdown-item">
                                                <div class="stat-breakdown-icon" style="color: #dc3545;">
                                                    <i class="fas fa-times-circle"></i>
                                                </div>
                                                <div class="stat-breakdown-value"><?php echo $stats['absent']; ?></div>
                                                <div class="stat-breakdown-label">Absent</div>
                                            </div>
                                            <div class="stat-breakdown-item">
                                                <div class="stat-breakdown-icon" style="color: #ffc107;">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <div class="stat-breakdown-value"><?php echo $stats['late']; ?></div>
                                                <div class="stat-breakdown-label">Late</div>
                                            </div>
                                            <div class="stat-breakdown-item">
                                                <div class="stat-breakdown-icon" style="color: #17a2b8;">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <div class="stat-breakdown-value"><?php echo $stats['excused']; ?></div>
                                                <div class="stat-breakdown-label">Excused</div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($recent)): ?>
                                    <div class="stat-actions-enhanced">
                                        <h4 class="stat-section-title">Recent Sessions</h4>
                                        <div class="stat-recent-list">
                                            <?php foreach ($recent as $date): ?>
                                                <div class="stat-recent-item">
                                                    <div class="stat-recent-date">
                                                        <i class="fas fa-calendar-day"></i>
                                                        <?php echo date('M j, Y', strtotime($date)); ?>
                                                    </div>
                                                    <button class="stat-action-btn" data-class-id="<?php echo $subject['class_id']; ?>" data-date="<?php echo $date; ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <div class="stat-empty-enhanced">
                                        <div class="stat-empty-icon">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="stat-empty-text">No attendance records yet</div>
                                        <button class="stat-primary-btn" onclick="location.href='professor_manage_schedule.php'">
                                            <i class="fas fa-plus"></i> Take Attendance
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sections and Students -->
                    <div class="stat-card">
                        <h3 class="chart-title">Sections and Enrolled Students</h3>
                        <?php if (!empty($students_by_section)): ?>
                            <?php foreach ($students_by_section as $section => $students): ?>
                                <div class="section-group" style="margin-bottom: 2rem;">
                                    <h4 style="color: var(--primary); margin-bottom: 1rem; font-size: 1.1rem;">
                                        <i class="fas fa-users"></i> Section <?php echo $section; ?> (<?php echo count($students); ?> students)
                                    </h4>
                                    <div class="students-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 0.75rem;">
                                        <?php foreach ($students as $student): ?>
                                            <div class="student-card" style="background: #f8f9fa; padding: 0.75rem; border-radius: 8px; border: 1px solid #e9ecef;">
                                                <div style="font-weight: 600; color: var(--dark);">
                                                    <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: var(--gray);">
                                                    <?php echo $student['student_id']; ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--gray); margin-top: 0.25rem;">
                                                    Enrolled: <?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No students found in your classes.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Analytics Tab -->
                    <div id="analytics-tab" class="tab-content">
                        <div class="subject-selector">
                            <select class="subject-select" id="subjectSelect" onchange="updateAnalytics()">
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['class_id']; ?>">
                                        <?php echo $subject['subject_name']; ?> (<?php echo $subject['class_code']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php foreach ($subjects as $subject): 
                            $stats = $detailed_stats[$subject['class_id']] ?? null;
                            $show = $subject === reset($subjects) ? '' : 'style="display: none;"';
                        ?>
                        <div id="analytics-<?php echo $subject['class_id']; ?>" class="analytics-content" <?php echo $show; ?>>
                            <?php if ($stats && $stats['overall']['total_records'] > 0): ?>
                                <!-- Overall Statistics -->
                                <div class="stat-card">
                                    <h3 class="chart-title">Overall Attendance Statistics</h3>
                                    <div class="stats-grid">
                                        <div class="stat-item stat-present">
                                            <div class="stat-number"><?php echo $stats['overall']['present']; ?></div>
                                            <div>Present</div>
                                        </div>
                                        <div class="stat-item stat-absent">
                                            <div class="stat-number"><?php echo $stats['overall']['absent']; ?></div>
                                            <div>Absent</div>
                                        </div>
                                        <div class="stat-item stat-late">
                                            <div class="stat-number"><?php echo $stats['overall']['late']; ?></div>
                                            <div>Late</div>
                                        </div>
                                        <div class="stat-item stat-excused">
                                            <div class="stat-number"><?php echo $stats['overall']['excused']; ?></div>
                                            <div>Excused</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="analytics-grid">
                                    <div class="chart-container">
                                        <h3 class="chart-title">Attendance Distribution</h3>
                                        <canvas id="pieChart-<?php echo $subject['class_id']; ?>" width="400" height="400"></canvas>
                                    </div>
                                    
                                    <div class="chart-container">
                                        <h3 class="chart-title">Monthly Trend</h3>
                                        <canvas id="barChart-<?php echo $subject['class_id']; ?>" width="400" height="400"></canvas>
                                    </div>
                                </div>

                                <!-- Student-wise Statistics -->
                                <div class="stat-card">
                                    <h3 class="chart-title">Student Attendance Summary</h3>
                                    <table class="students-table">
                                        <thead>
                                            <tr>
                                                <th>Student Name</th>
                                                <th>Present</th>
                                                <th>Absent</th>
                                                <th>Late</th>
                                                <th>Excused</th>
                                                <th>Attendance Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['students'] as $student): 
                                                $total = $student['total_classes'];
                                                $present = $student['present'] + $student['late'] + $student['excused'];
                                                $rate = $total > 0 ? ($present / $total) * 100 : 0;
                                                $rateClass = $rate >= 80 ? 'rate-high' : ($rate >= 60 ? 'rate-medium' : 'rate-low');
                                            ?>
                                            <tr>
                                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                                <td><?php echo $student['present']; ?></td>
                                                <td><?php echo $student['absent']; ?></td>
                                                <td><?php echo $student['late']; ?></td>
                                                <td><?php echo $student['excused']; ?></td>
                                                <td class="attendance-rate <?php echo $rateClass; ?>">
                                                    <?php echo number_format($rate, 1); ?>%
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php else: ?>
                                <div class="no-data">
                                    <p>No attendance records found for this class</p>
                                    <button class="btn btn-primary" onclick="location.href='professor_manage_schedule.php'">
                                        Take Attendance
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="attendanceModalTitle">Attendance Details</h3>
                <button class="modal-close" onclick="closeAttendanceModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="attendanceContent">
                    <!-- Attendance records will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <h3 class="modal-title">
                        <div class="modal-title-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        Notifications
                    </h3>
                </div>
                <button class="modal-close" onclick="closeNotificationModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="notification-list" id="notificationList">
                    <?php if (empty($pending_requests) && empty($pending_unenrollment_requests)): ?>
                        <div class="no-notifications">
                            <div class="no-notifications-icon">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <div class="no-notifications-text">No new notifications</div>
                            <div class="no-notifications-subtext">You have no pending requests.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="notification-item" data-request-id="<?php echo $request['request_id']; ?>" data-type="enrollment">
                                <div class="notification-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">Enrollment Request</div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> has requested to enroll in
                                        <strong><?php echo htmlspecialchars($request['subject_name']); ?></strong>
                                        (Class Code: <?php echo htmlspecialchars($request['class_code']); ?>).
                                    </div>
                                    <div class="notification-meta">
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M j, Y, g:i a', strtotime($request['requested_at'])); ?>
                                        </div>
                                        <div class="notification-status status-unread">PENDING</div>
                                    </div>
                                    <div class="notification-actions" style="margin-top: 10px;">
                                        <button class="btn-enhanced btn-primary" onclick="handleEnrollmentRequest('<?php echo $request['request_id']; ?>', 'accept')">Accept</button>
                                        <button class="btn-enhanced btn-secondary" onclick="handleEnrollmentRequest('<?php echo $request['request_id']; ?>', 'reject')">Reject</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($pending_unenrollment_requests as $request): ?>
                            <div class="notification-item" data-request-id="<?php echo $request['request_id']; ?>" data-type="unenrollment">
                                <div class="notification-icon">
                                    <i class="fas fa-user-minus"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">Unenrollment Request</div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> has requested to unenroll from
                                        <strong><?php echo htmlspecialchars($request['subject_name']); ?></strong>
                                        (Class Code: <?php echo htmlspecialchars($request['class_code']); ?>).
                                    </div>
                                    <div class="notification-meta">
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M j, Y, g:i a', strtotime($request['requested_at'])); ?>
                                        </div>
                                        <div class="notification-status status-unread">PENDING</div>
                                    </div>
                                    <div class="notification-actions" style="margin-top: 10px;">
                                        <button class="btn-enhanced btn-primary" onclick="handleUnenrollmentRequest('<?php echo $request['request_id']; ?>', 'accept')">Accept</button>
                                        <button class="btn-enhanced btn-secondary" onclick="handleUnenrollmentRequest('<?php echo $request['request_id']; ?>', 'reject')">Reject</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-enhanced btn-secondary-enhanced" onclick="closeNotificationModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global function for modal close button
        function closeAttendanceModal() {
            const modal = document.getElementById('attendanceModal');
            modal.classList.remove('show');
        }

        // Global function for notification modal close button
        function closeNotificationModal() {
            const modal = document.getElementById('notificationModal');
            modal.classList.remove('show');
        }

        // Function to open notification modal
        function openNotificationModal() {
            const modal = document.getElementById('notificationModal');
            modal.classList.add('show');
        }

        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            function showTab(tabName) {
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });

                // Show selected tab
                document.getElementById(tabName + '-tab').classList.add('active');

                // Update active button
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`).classList.add('active');
            }

            function viewDateReport(classId, date) {
                // Load attendance data for the specific date and class
                loadAttendanceData(classId, date);
            }

            function loadAttendanceData(classId, date) {
                const modal = document.getElementById('attendanceModal');
                const modalTitle = document.getElementById('attendanceModalTitle');
                const content = document.getElementById('attendanceContent');

                // Set modal title
                modalTitle.textContent = `Attendance for ${new Date(date).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}`;

                // Show loading state
                content.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="loading-spinner">Loading attendance data...</div></div>';

                // Open modal
                modal.classList.add('show');

                // Fetch attendance data
                fetch(`../php/get_attendance_for_date.php?class_id=${classId}&date=${date}`, { credentials: 'same-origin' })
                    .then(response => response.json())
                    .then(attendanceRecords => {
                        if (attendanceRecords.length === 0) {
                            content.innerHTML = '<div style="text-align: center; color: var(--gray); padding: 2rem;">No attendance records found for this date.</div>';
                            return;
                        }

                        // Create attendance records display
                        const recordsHTML = `
                            <div class="attendance-record">
                                <div class="record-header">
                                    <div>Student Name</div>
                                    <div>Status</div>
                                    <div>Remarks</div>
                                </div>
                                ${attendanceRecords.map(record => {
                                    const status = record.status || 'Not Marked';
                                    const statusClass = record.status || 'null';
                                    return `
                                        <div class="record-item">
                                            <div>${record.first_name} ${record.last_name}</div>
                                            <div><span class="attendance-status ${statusClass}">${status}</span></div>
                                            <div>${record.remarks || 'No remarks'}</div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        `;

                        content.innerHTML = recordsHTML;
                    })
                    .catch(error => {
                        console.error('Error loading attendance data:', error);
                        content.innerHTML = '<div style="text-align: center; color: var(--danger); padding: 2rem;">Error loading attendance data. Please try again.</div>';
                    });
            }

            // Attach event listeners to view report buttons
            document.querySelectorAll('.stat-action-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const classId = this.getAttribute('data-class-id');
                    const date = this.getAttribute('data-date');
                    if (classId && date) {
                        viewDateReport(classId, date);
                    }
                });
            });

            // Close modal when clicking outside
            document.addEventListener('click', function(event) {
                const modal = document.getElementById('attendanceModal');
                if (event.target === modal) {
                    closeAttendanceModal();
                }
            });

            function updateAnalytics() {
                const classId = document.getElementById('subjectSelect').value;
                document.querySelectorAll('.analytics-content').forEach(el => {
                    el.style.display = 'none';
                });
                document.getElementById('analytics-' + classId).style.display = 'block';
            }

            // Initialize charts for each subject
            <?php foreach ($subjects as $subject): 
                $stats = $detailed_stats[$subject['class_id']] ?? null;
                if ($stats && $stats['overall']['total_records'] > 0):
            ?>
            // Pie Chart
            const pieCtx<?php echo $subject['class_id']; ?> = document.getElementById('pieChart-<?php echo $subject['class_id']; ?>').getContext('2d');
            new Chart(pieCtx<?php echo $subject['class_id']; ?>, {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent', 'Late', 'Excused'],
                    datasets: [{
                        data: [
                            <?php echo $stats['overall']['present']; ?>,
                            <?php echo $stats['overall']['absent']; ?>,
                            <?php echo $stats['overall']['late']; ?>,
                            <?php echo $stats['overall']['excused']; ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#ffc107',
                            '#17a2b8'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Bar Chart
            const barCtx<?php echo $subject['class_id']; ?> = document.getElementById('barChart-<?php echo $subject['class_id']; ?>').getContext('2d');
            const monthlyData<?php echo $subject['class_id']; ?> = <?php echo json_encode($stats['monthly']); ?>;
            const months<?php echo $subject['class_id']; ?> = monthlyData<?php echo $subject['class_id']; ?>.map(item => item.month);
            const presentData<?php echo $subject['class_id']; ?> = monthlyData<?php echo $subject['class_id']; ?>.map(item => item.present);
            const absentData<?php echo $subject['class_id']; ?> = monthlyData<?php echo $subject['class_id']; ?>.map(item => item.absent);
            
            new Chart(barCtx<?php echo $subject['class_id']; ?>, {
                type: 'bar',
                data: {
                    labels: months<?php echo $subject['class_id']; ?>,
                    datasets: [
                        {
                            label: 'Present',
                            data: presentData<?php echo $subject['class_id']; ?>,
                            backgroundColor: '#28a745'
                        },
                        {
                            label: 'Absent',
                            data: absentData<?php echo $subject['class_id']; ?>,
                            backgroundColor: '#dc3545'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Number of Records'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
            <?php endif; endforeach; ?>

            // Hamburger menu toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('show');
                    }
                    if (window.innerWidth <= 900) {
                        document.body.classList.toggle('sidebar-open');
                    }
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const toggle = document.getElementById('sidebarToggle');
                if (window.innerWidth <= 900 && sidebar && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(event.target) && (!toggle || !toggle.contains(event.target))) {
                        sidebar.classList.remove('show');
                        document.body.classList.remove('sidebar-open');
                    }
                }
            });

            // Dropdown behaviour is handled in the included navbar script
        });

        // Handle enrollment request accept/reject
        function handleEnrollmentRequest(requestId, action) {
            if (!['accept', 'reject'].includes(action)) return;

            fetch('../php/handle_enrollment_request_with_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `request_id=${encodeURIComponent(requestId)}&action=${encodeURIComponent(action)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);

                    // Remove the notification item from the list
                    const notificationList = document.getElementById('notificationList');
                    const item = notificationList.querySelector(`.notification-item[data-request-id="${requestId}"]`);
                    if (item) {
                        item.remove();
                    }

                    // If no more notifications, show no notifications message
                    if (notificationList.children.length === 0) {
                        notificationList.innerHTML = `
                            <div class="no-notifications">
                                <div class="no-notifications-icon">
                                    <i class="fas fa-bell-slash"></i>
                                </div>
                                <div class="no-notifications-text">No new notifications</div>
                                <div class="no-notifications-subtext">You have no pending requests.</div>
                            </div>
                        `;
                    }

                    // Update notification badge count
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        let count = parseInt(badge.textContent) || 0;
                        count = Math.max(0, count - 1);
                        if (count === 0) {
                            badge.style.display = 'none';
                        } else {
                            badge.textContent = count;
                        }
                    }

                    // If accepted, refresh the enrolled students list by reloading the page or dynamically updating
                    if (action === 'accept') {
                        // For simplicity, reload the page to reflect changes
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error handling enrollment request:', error);
                alert('An error occurred while processing the request.');
            });
        }

        // Handle unenrollment request accept/reject
        function handleUnenrollmentRequest(requestId, action) {
            if (!['accept', 'reject'].includes(action)) return;

            fetch('../php/handle_unenrollment_request_with_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `request_id=${encodeURIComponent(requestId)}&action=${encodeURIComponent(action)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);

                    // Remove the notification item from the list
                    const notificationList = document.getElementById('notificationList');
                    const item = notificationList.querySelector(`.notification-item[data-request-id="${requestId}"]`);
                    if (item) {
                        item.remove();
                    }

                    // If no more notifications, show no notifications message
                    if (notificationList.children.length === 0) {
                        notificationList.innerHTML = `
                            <div class="no-notifications">
                                <div class="no-notifications-icon">
                                    <i class="fas fa-bell-slash"></i>
                                </div>
                                <div class="no-notifications-text">No new notifications</div>
                                <div class="no-notifications-subtext">You have no pending requests.</div>
                            </div>
                        `;
                    }

                    // Update notification badge count
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        let count = parseInt(badge.textContent) || 0;
                        count = Math.max(0, count - 1);
                        if (count === 0) {
                            badge.style.display = 'none';
                        } else {
                            badge.textContent = count;
                        }
                    }

                    // If accepted, refresh the enrolled students list by reloading the page or dynamically updating
                    if (action === 'accept') {
                        // For simplicity, reload the page to reflect changes
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error handling unenrollment request:', error);
                alert('An error occurred while processing the request.');
            });
        }

        // Automatically open notification modal if URL hash is #notifications
        if (window.location.hash === '#notifications') {
            openNotificationModal();
            // Remove the hash from URL without reloading
            history.replaceState(null, null, ' ');
        }
    </script>
</body>
</html>
?>
