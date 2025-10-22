<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Get enrolled classes
$stmt = $pdo->prepare("SELECT c.class_id, c.class_name, c.class_code, s.subject_name, p.first_name, p.last_name
                     FROM student_classes sc
                     JOIN classes c ON sc.class_id = c.class_id
                     JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN professors p ON c.professor_id = p.professor_id
                     LEFT JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                     WHERE sc.student_id = ? AND c.status != 'archived'
                     ORDER BY sc.enrolled_at DESC");
$stmt->execute([$student_id]);
$enrolled_classes = $stmt->fetchAll();

// No pending enrollment requests since enrollment is instant
$pending_requests = [];

if (empty($enrolled_classes)) {
    // Debug: Log to error log
    error_log("No enrolled classes found for student_id: $student_id");
} else {
    // Debug: Log count of enrolled classes
    error_log("Enrolled classes count for student_id $student_id: " . count($enrolled_classes));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Schedule - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles_fixed.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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

        /* Enhanced Dashboard Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Enhanced Tile Grid Styles */
        .tiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .class-tile {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .class-tile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .class-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .class-code {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .class-subject {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .class-subject-code {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .class-details {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .class-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            color: var(--gray);
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .class-detail i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            color: var(--primary);
        }

        .class-actions {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .view-attendance-btn {
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
            transition: all 0.2s ease;
            width: 100%;
            justify-content: center;
        }

        .view-attendance-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Table Header */
        .table-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title-enhanced {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .table-actions-enhanced {
            display: flex;
            gap: 1rem;
        }



        /* Enhanced Attendance Table */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            table-layout: fixed;
        }

        .attendance-table th:nth-child(1),
        .attendance-table td:nth-child(1) {
            width: 30%;
        }

        .attendance-table th:nth-child(2),
        .attendance-table td:nth-child(2) {
            width: 30%;
        }

        .attendance-table th:nth-child(3),
        .attendance-table td:nth-child(3) {
            width: 40%;
            word-wrap: break-word;
        }

        .attendance-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .attendance-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }

        .attendance-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .attendance-table tr:hover {
            background-color: #e9ecef;
        }

        .attendance-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .attendance-status.Present {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .attendance-status.Absent {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .attendance-status.Late {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .attendance-status.Excused {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .attendance-status.null {
            background: linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%);
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .attendance-status.Accepted {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Enhanced Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        /* Enhanced Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Loading States */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: #333;
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            gap: 1rem;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
            max-width: 400px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            text-align: center;
        }

        .toast.show {
            opacity: 1;
            transform: scale(1);
        }

        .toast.success {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
        }

        .toast.error {
            background: linear-gradient(135deg, var(--danger) 0%, #e74c3c 100%);
        }

        .toast-icon {
            font-size: 1.2rem;
        }

        .toast-message {
            flex: 1;
            font-size: 0.9rem;
        }

        .toast-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-close:hover {
            opacity: 0.7;
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 16px;
            padding: 0;
            width: 90%;
            max-width: 1000px;
            height: auto;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            padding: 0.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 0 0 16px 16px;
        }

        /* Responsive Design */
        @media (max-width: 1440px) {
            .dashboard-container {
                padding: 1.5rem;
            }

            .tiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1.75rem;
            }

            .class-tile {
                padding: 1.75rem;
            }

            .class-subject {
                font-size: 1.15rem;
            }

            .class-subject-code {
                font-size: 0.9rem;
            }

            .class-detail {
                font-size: 0.9rem;
            }

            .view-attendance-btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.9rem;
            }

            .table-header-enhanced {
                padding: 1.75rem;
            }

            .table-title-enhanced {
                font-size: 1.4rem;
            }

            .attendance-table th,
            .attendance-table td {
                padding: 0.9rem;
                font-size: 0.9rem;
            }

            .modal-content {
                max-width: 900px;
            }
        }

        @media (max-width: 1024px) {
            .dashboard-container {
                padding: 1.25rem;
            }

            .tiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
            }

            .class-tile {
                padding: 1.5rem;
            }

            .class-subject {
                font-size: 1.1rem;
            }

            .class-subject-code {
                font-size: 0.85rem;
            }

            .class-detail {
                font-size: 0.85rem;
            }

            .view-attendance-btn {
                padding: 0.65rem 1rem;
                font-size: 0.85rem;
            }

            .table-header-enhanced {
                padding: 1.5rem;
            }

            .table-title-enhanced {
                font-size: 1.3rem;
            }

            .attendance-table th,
            .attendance-table td {
                padding: 0.8rem;
                font-size: 0.85rem;
            }

            .modal-content {
                max-width: 850px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .tiles-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .class-tile {
                padding: 1.5rem;
            }

            .table-header-enhanced {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .table-actions-enhanced {
                width: 100%;
                justify-content: center;
            }

            .attendance-table th,
            .attendance-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .class-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .class-code {
                align-self: flex-start;
            }
        }

        /* Responsive Modal for screens smaller than 722px */
        @media (max-width: 722px) {
            .modal-content {
                width: 95vw;
                max-width: 95vw;
                max-height: 90vh;
                border-radius: 12px;
                margin: 5vh auto; /* Center the modal vertically and horizontally */
            }

            .modal-header {
                padding: 1rem;
                flex-direction: column; /* Stack header elements vertically */
                gap: 1rem; /* Add space between title and close button */
                text-align: center; /* Center align title */
            }

            .modal-title {
                font-size: 1.25rem;
                margin: 0;
            }

            .modal-close {
                width: 35px;
                height: 35px;
                font-size: 1.25rem;
                padding: 0.25rem;
                align-self: flex-end; /* Align close button to the right */
            }

            .modal-body {
                padding: 1rem;
                overflow-x: auto; /* Enable horizontal scrolling for wide content like tables */
            }

            .attendance-table {
                min-width: 100%; /* Ensure table takes full width but allows horizontal scroll if needed */
                table-layout: fixed;
            }

            .attendance-table th:nth-child(1),
            .attendance-table td:nth-child(1) {
                width: 25%; /* Adjust Date column width */
            }

            .attendance-table th:nth-child(2),
            .attendance-table td:nth-child(2) {
                width: 25%; /* Adjust Status column width */
            }

            .attendance-table th:nth-child(3),
            .attendance-table td:nth-child(3) {
                width: 50%; /* Adjust Remarks column width */
                word-wrap: break-word;
            }

            .attendance-table th,
            .attendance-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
                word-wrap: break-word;
            }

            .attendance-status {
                font-size: 0.8rem;
                min-width: 70px;
                padding: 4px 8px;
                display: block; /* Ensure status spans are block for better alignment */
                text-align: center;
            }
        }

        /* Mobile responsive for attendance table */
        @media (max-width: 768px) {
            .attendance-table th:nth-child(3),
            .attendance-table td:nth-child(3) {
                display: none;
            }

            .attendance-table th:nth-child(1),
            .attendance-table td:nth-child(1) {
                width: 50%;
            }

            .attendance-table th:nth-child(2),
            .attendance-table td:nth-child(2) {
                width: 50%;
            }

            .attendance-row {
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .attendance-row:hover {
                background-color: #f8f9fa;
            }
        }

        /* Ensure remarks modal is on top */
        #remarksModal {
            z-index: 1001;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-container">
            <div class="table-header-enhanced">
                <h2 class="table-title-enhanced"><i class="fas fa-book" style="margin-right: 10px;"></i>My Enrolled Classes</h2>
                <div class="table-actions-enhanced">
                    <button class="btn btn-primary" onclick="openEnrollModal()">
                        <i class="fas fa-plus"></i>
                        Enroll in Class
                    </button>
                </div>
            </div>

            <?php if (empty($enrolled_classes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="empty-state-text">You are not enrolled in any classes yet.</div>
                    <button class="btn btn-primary" onclick="openEnrollModal()">
                        <i class="fas fa-plus"></i>
                        Enroll in Your First Class
                    </button>
                </div>
            <?php else: ?>
                <div class="tiles-grid">
                    <?php foreach ($enrolled_classes as $class): ?>
                        <div class="class-tile" tabindex="0" role="button" aria-pressed="false" data-class-id="<?php echo $class['class_id']; ?>">
                            <div class="class-header">
                                <div class="class-code"><?php echo htmlspecialchars($class['class_code']); ?></div>
                            </div>
                            <div class="class-subject"><?php echo htmlspecialchars($class['subject_name']); ?></div>
                            <div class="class-subject-code"><?php echo htmlspecialchars($class['class_name']); ?></div>
                            <div class="class-details">
                                <div class="class-detail">
                                    <i class="fas fa-user-tie"></i>
                                    <?php
                                    $professor_name = (!empty($class['first_name']) && !empty($class['last_name'])) ? 'Prof. ' . htmlspecialchars($class['first_name'] . ' ' . $class['last_name']) : 'N/A';
                                    echo $professor_name;
                                    ?>
                                </div>
                            </div>
                            <div class="class-actions">
                                <button class="view-attendance-btn" data-class-id="<?php echo $class['class_id']; ?>">
                                    <i class="fas fa-eye"></i>
                                    View Attendance
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pending Requests Section -->
            <?php if (!empty($pending_requests)): ?>
                <div class="table-header-enhanced" style="margin-top: 3rem;">
                    <h2 class="table-title-enhanced"><i class="fas fa-clock" style="margin-right: 10px;"></i>Pending Enrollment Requests</h2>
                </div>
                <div class="tiles-grid">
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="class-tile" style="border-left: 4px solid #ffc107;">
                            <div class="class-header">
                                <div class="class-code" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);"><?php echo htmlspecialchars($request['class_code']); ?></div>
                                <div style="font-size: 0.8rem; color: #856404; background: #fff3cd; padding: 4px 8px; border-radius: 4px;">Pending Approval</div>
                            </div>
                            <div class="class-subject"><?php echo htmlspecialchars($request['subject_name']); ?></div>
                            <div class="class-details">
                                <div class="class-detail">
                                    <i class="fas fa-user-tie"></i>
                                    <?php
                                    $professor_name = (!empty($request['first_name']) && !empty($request['last_name'])) ? 'Prof. ' . htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) : 'N/A';
                                    echo $professor_name;
                                    ?>
                                </div>
                                <div class="class-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    Requested: <?php echo date('M j, Y', strtotime($request['requested_at'])); ?>
                                </div>
                            </div>
                            <div class="class-actions">
                                <div style="font-size: 0.9rem; color: var(--gray); text-align: center;">
                                    <i class="fas fa-info-circle"></i> Waiting for professor approval
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="attendanceModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="attendanceModalTitle" class="modal-title">Attendance and Remarks</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="attendanceModalBody">
                <p>Loading attendance data...</p>
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div id="enrollModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="enrollModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="enrollModalTitle" class="modal-title">Enroll in Class</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeEnrollModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 1rem; color: var(--gray);">Enter the class code provided by your professor to enroll in a class.</p>
                <form id="enrollForm">
                    <div class="form-group">
                        <label for="class_code" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Class Code</label>
                        <input type="text" id="class_code" name="class_code" required
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--light-gray); border-radius: 6px; font-size: 1rem;"
                               placeholder="Enter class code (e.g., ABC123)">
                    </div>
                    <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeEnrollModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="enrollBtn">
                            <span id="enrollBtnText">Enroll</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Remarks Modal -->
    <div id="remarksModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="remarksModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="remarksModalTitle" class="modal-title">Full Remarks</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeRemarksModal()">&times;</button>
            </div>
            <div class="modal-body" id="remarksModalBody">
                <p>Loading remarks...</p>
            </div>
        </div>
    </div>

    <script>
        const studentId = '<?php echo $student_id; ?>';

        // Modal open/close functions
        function openModal() {
            const modal = document.getElementById('attendanceModal');
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        }
        function closeModal() {
            const modal = document.getElementById('attendanceModal');
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('attendanceModalBody').innerHTML = '<p>Loading attendance data...</p>';
        }

        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} toast-icon"></i>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Fetch attendance for a student in a class
        async function fetchAttendanceForStudent(classId) {
            const response = await fetch(`../php/get_attendance_for_date.php?class_id=${classId}&student_id=${studentId}`);
            if (!response.ok) throw new Error('Failed to fetch attendance');
            return await response.json();
        }

        // Render attendance modal content
        async function renderAttendanceModal(classId) {
            const modalBody = document.getElementById('attendanceModalBody');
            modalBody.innerHTML = '<p>Loading attendance data...</p>';

            try {
                const attendanceRecords = await fetchAttendanceForStudent(classId);
                if (attendanceRecords.length === 0) {
                    modalBody.innerHTML = '<p>No attendance records found for this class.</p>';
                    return;
                }

                const table = document.createElement('table');
                table.className = 'attendance-table';

                const thead = document.createElement('thead');
                const headerRow = document.createElement('tr');
                const thDate = document.createElement('th');
                thDate.textContent = 'Date';
                const thStatus = document.createElement('th');
                thStatus.textContent = 'Status';
                const thRemarks = document.createElement('th');
                thRemarks.textContent = 'Remarks';
                headerRow.appendChild(thDate);
                headerRow.appendChild(thStatus);
                headerRow.appendChild(thRemarks);
                thead.appendChild(headerRow);
                table.appendChild(thead);

                const tbody = document.createElement('tbody');
                for (const record of attendanceRecords) {
                    const row = document.createElement('tr');
                    row.className = 'attendance-row';

                    const tdDate = document.createElement('td');
                    tdDate.textContent = record.date;

                    const tdStatus = document.createElement('td');
                    const statusSpan = document.createElement('span');
                    statusSpan.textContent = record.status || 'No status';
                    statusSpan.className = 'attendance-status ' + (record.status || '');
                    tdStatus.appendChild(statusSpan);

                    const tdRemarks = document.createElement('td');
                    const remarksText = record.remarks || 'None';
                    if (remarksText.length > 20) {
                        tdRemarks.textContent = remarksText.substring(0, 20) + '...';
                        tdRemarks.style.cursor = 'pointer';
                        tdRemarks.style.color = 'var(--primary)';
                        tdRemarks.style.textDecoration = 'underline';
                        tdRemarks.title = 'Click to view full remarks';

                        // Add click event to open remarks modal
                        tdRemarks.addEventListener('click', (e) => {
                            e.stopPropagation();
                            openRemarksModal(remarksText);
                        });
                    } else {
                        tdRemarks.textContent = remarksText;
                    }

                    row.appendChild(tdDate);
                    row.appendChild(tdStatus);
                    row.appendChild(tdRemarks);
                    tbody.appendChild(row);
                }
                table.appendChild(tbody);

                modalBody.innerHTML = '';
                modalBody.appendChild(table);
            } catch (error) {
                modalBody.innerHTML = '<p>Error loading attendance data.</p>';
                console.error(error);
            }
        }

        // Add click event listeners to view attendance buttons
        document.querySelectorAll('.view-attendance-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent tile click
                const classId = btn.getAttribute('data-class-id');
                renderAttendanceModal(classId);
                openModal();
            });
        });



        // Add click event listeners to class tiles (for accessibility)
        document.querySelectorAll('.class-tile').forEach(tile => {
            tile.addEventListener('click', () => {
                const classId = tile.getAttribute('data-class-id');
                renderAttendanceModal(classId);
                openModal();
            });
            tile.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    tile.click();
                }
            });
        });



        // Close modal when clicking outside modal content
        document.getElementById('attendanceModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });

        // Enrollment modal functions
        function openEnrollModal() {
            document.getElementById('enrollModal').classList.add('show');
            document.getElementById('enrollModal').setAttribute('aria-hidden', 'false');
            document.getElementById('class_code').focus();
        }

        function closeEnrollModal() {
            document.getElementById('enrollModal').classList.remove('show');
            document.getElementById('enrollModal').setAttribute('aria-hidden', 'true');
            document.getElementById('enrollForm').reset();
            document.getElementById('enrollBtnText').textContent = 'Enroll';
            document.getElementById('enrollBtn').disabled = false;
        }

        // Handle enrollment form submission
        document.getElementById('enrollForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const classCode = document.getElementById('class_code').value.trim();
            if (!classCode) {
                showToast('Please enter a class code', 'error');
                return;
            }

            // Show loading state
            const enrollBtn = document.getElementById('enrollBtn');
            const enrollBtnText = document.getElementById('enrollBtnText');
            enrollBtnText.textContent = 'Enrolling...';
            enrollBtn.disabled = true;

            // Create form data
            const formData = new FormData();
            formData.append('class_code', classCode);

            // Send enrollment request
            fetch('../php/enroll_student.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Successfully enrolled in the class!', 'success');
                    closeEnrollModal();
                    // Refresh the page to show the updated status
                    location.reload();
                } else {
                    if (data.message === 'You are already enrolled in this class.' ||
                        data.message.includes('pending or accepted enrollment request')) {
                        alert(data.message);
                    } else {
                        showToast(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while enrolling. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                enrollBtnText.textContent = 'Enroll';
                enrollBtn.disabled = false;
            });
        });



        // Close enrollment modal when clicking outside
        document.getElementById('enrollModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEnrollModal();
            }
        });

        // Remarks modal functions
        function openRemarksModal(remarksText) {
            const modal = document.getElementById('remarksModal');
            const modalBody = document.getElementById('remarksModalBody');
            modalBody.innerHTML = `<p>${remarksText}</p>`;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeRemarksModal() {
            const modal = document.getElementById('remarksModal');
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('remarksModalBody').innerHTML = '<p>Loading remarks...</p>';
        }

        // Close remarks modal when clicking outside
        document.getElementById('remarksModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeRemarksModal();
            }
        });

    </script>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>
    <?php include '../includes/footbar.php'; ?>
</body>
</html>
