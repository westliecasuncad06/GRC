<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

$professor_id = $_SESSION['user_id'];

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_student':
                $student_id = $_POST['student_id'];
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $middle_name = $_POST['middle_name'];
                $email = $_POST['email'];
                $password = md5($_POST['password']);
                $mobile = $_POST['mobile'];
                $address = $_POST['address'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, middle_name, email, password, mobile, address, created_at, updated_at) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$student_id, $first_name, $last_name, $middle_name, $email, $password, $mobile, $address]);
                    $success = "Student added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding student: " . $e->getMessage();
                }
                break;
                
            case 'edit_student':
                $student_id = $_POST['student_id'];
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $middle_name = $_POST['middle_name'];
                $email = $_POST['email'];
                $mobile = $_POST['mobile'];
                $address = $_POST['address'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, middle_name = ?, email = ?, mobile = ?, address = ?, updated_at = NOW() 
                                          WHERE student_id = ?");
                    $stmt->execute([$first_name, $last_name, $middle_name, $email, $mobile, $address, $student_id]);
                    $success = "Student updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating student: " . $e->getMessage();
                }
                break;
                
            case 'delete_student':
                $student_id = $_POST['student_id'];
                
                try {
                    // Check if student has attendance records or class enrollments
                    $check_attendance = $pdo->prepare("SELECT COUNT(*) as attendance_count FROM attendance WHERE student_id = ?");
                    $check_attendance->execute([$student_id]);
                    $attendance_count = $check_attendance->fetch()['attendance_count'];
                    
                    $check_enrollment = $pdo->prepare("SELECT COUNT(*) as enrollment_count FROM student_classes WHERE student_id = ?");
                    $check_enrollment->execute([$student_id]);
                    $enrollment_count = $check_enrollment->fetch()['enrollment_count'];
                    
                    if ($attendance_count > 0 || $enrollment_count > 0) {
                        $error = "Cannot delete student: Student has $attendance_count attendance records and $enrollment_count class enrollments. Please remove these records first.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                        $stmt->execute([$student_id]);
                        $success = "Student deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting student: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get students enrolled in professor's classes
$query = "SELECT DISTINCT s.*
          FROM students s
          JOIN student_classes sc ON s.student_id = sc.student_id
          JOIN classes c ON sc.class_id = c.class_id
          WHERE c.professor_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Students - Global Reciprocal College</title>
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

        .table-actions-enhanced {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .search-input-enhanced {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            width: 300px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .search-input-enhanced:focus {
            outline: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .table-container-enhanced {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-enhanced {
            width: 100%;
            border-collapse: collapse;
        }

        .table-enhanced th {
            background: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .table-enhanced td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            font-size: 0.9rem;
        }

        .table-enhanced tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table-enhanced tr:hover {
            background-color: #e9ecef;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm-enhanced {
            padding: 0.375rem 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s;
        }

        .btn-primary-enhanced {
            background: var(--primary);
            color: white;
        }

        .btn-primary-enhanced:hover {
            background: var(--primary-dark);
        }

        .btn-warning-enhanced {
            background: var(--warning);
            color: white;
        }

        .btn-warning-enhanced:hover {
            background: var(--warning-dark);
        }

        .btn-danger-enhanced {
            background: var(--danger);
            color: white;
        }

        .btn-danger-enhanced:hover {
            background: var(--danger-dark);
        }

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

        .form-group {
            margin-bottom: 2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label i {
            color: var(--primary);
            font-size: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1), 0 4px 16px rgba(0, 123, 255, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #adb5bd;
            font-weight: 400;
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

        .btn-primary-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(0, 123, 255, 0.2);
        }

        .btn-primary-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.3);
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

        .success-message,
        .error-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 3rem;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-height: 90vh;
                margin: 1rem;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1.5rem;
            }

            .modal-title {
                font-size: 1.25rem;
            }

            .table-actions-enhanced {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input-enhanced {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .stat-card-enhanced {
                padding: 1.5rem;
            }

            .btn-enhanced {
                width: 100%;
                justify-content: center;
            }
        }

        .password-container {
            position: relative;
        }

        .password-container input {
            padding-right: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            font-size: 1.2rem;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_professor.php'; ?>
    <?php include '../includes/sidebar_professor.php'; ?>

    <main class="main-content">
        <div class="dashboard-container">
            <?php if (isset($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-users"></i>My Students</h2>
            <div class="table-actions-enhanced">
                <input type="search" id="searchInput" class="search-input-enhanced" placeholder="Search students..." aria-label="Search students" onkeyup="filterStudents()">
                <button class="stat-primary-btn" type="button" onclick="openModal('addStudentModal')" aria-haspopup="dialog">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card-enhanced fade-in">
                <div class="stat-header-enhanced">
                    <div class="stat-icon-enhanced"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info-enhanced">
                        <h3 class="stat-title-enhanced">Total Students</h3>
                        <p class="stat-subtitle-enhanced">Enrolled in your classes</p>
                    </div>
                </div>
                <div class="stat-main-metric"><?php echo count($students); ?></div>
            </div>
            <div class="stat-card-enhanced fade-in">
                <div class="stat-header-enhanced">
                    <div class="stat-icon-enhanced"><i class="fas fa-user-check"></i></div>
                    <div class="stat-info-enhanced">
                        <h3 class="stat-title-enhanced">New This Month</h3>
                        <p class="stat-subtitle-enhanced">Students added in last 30 days</p>
                    </div>
                </div>
                <div class="stat-main-metric"><?php echo count(array_filter($students, function($s) { return strtotime($s['created_at']) > strtotime('-30 days'); })); ?></div>
            </div>
        </div>

        <div class="table-container-enhanced">
            <table class="table-enhanced">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($student['address']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-sm-enhanced btn-primary-enhanced" onclick="viewStudentDetails('<?php echo htmlspecialchars($student['student_id']); ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn-sm-enhanced btn-warning-enhanced" onclick='editStudent(<?php echo json_encode($student); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_student" />
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" />
                                    <button type="submit" class="btn-sm-enhanced btn-danger-enhanced" onclick="return confirm('Are you sure you want to delete this student?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Student Details Modal -->
        <div id="studentDetailsModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="studentDetailsTitle" tabindex="-1">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="studentDetailsTitle">Student Details</h3>
                    <button class="modal-close" aria-label="Close" onclick="closeModal('studentDetailsModal')">&times;</button>
                </div>
                <div class="modal-body" id="studentDetailsContent">
                    <!-- Student details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary-enhanced" onclick="closeModal('studentDetailsModal')">Close</button>
                </div>
            </div>
        </div>

        <!-- Add Student Modal -->
        <div id="addStudentModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addStudentTitle" tabindex="-1">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="addStudentTitle">Add New Student</h3>
                    <button class="modal-close" aria-label="Close" onclick="closeModal('addStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="modal-form" action="" method="POST" id="addStudentForm">
                        <input type="hidden" name="action" value="add_student" />
                        <div class="form-group">
                            <label for="add_student_id">Student ID</label>
                            <input type="text" id="add_student_id" name="student_id" required />
                        </div>
                        <div class="form-group">
                            <label for="add_first_name">First Name</label>
                            <input type="text" id="add_first_name" name="first_name" required />
                        </div>
                        <div class="form-group">
                            <label for="add_middle_name">Middle Name</label>
                            <input type="text" id="add_middle_name" name="middle_name" />
                        </div>
                        <div class="form-group">
                            <label for="add_last_name">Last Name</label>
                            <input type="text" id="add_last_name" name="last_name" required />
                        </div>
                        <div class="form-group">
                            <label for="add_email">Email</label>
                            <input type="email" id="add_email" name="email" required />
                        </div>
                        <div class="form-group">
                            <label for="add_password">Password</label>
                            <input type="password" id="add_password" name="password" required />
                        </div>
                        <div class="form-group">
                            <label for="add_mobile">Mobile Number</label>
                            <input type="tel" id="add_mobile" name="mobile" required />
                        </div>
                        <div class="form-group">
                            <label for="add_address">Address</label>
                            <textarea id="add_address" name="address" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary-enhanced" onclick="closeModal('addStudentModal')">Cancel</button>
                            <button type="submit" class="btn-primary-enhanced">Add Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Student Modal -->
        <div id="editStudentModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editStudentTitle" tabindex="-1">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="editStudentTitle">Edit Student</h3>
                    <button class="modal-close" aria-label="Close" onclick="closeModal('editStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="modal-form" action="" method="POST" id="editStudentForm">
                        <input type="hidden" name="action" value="edit_student" />
                        <input type="hidden" name="student_id" id="edit_student_id" />
                        <div class="form-group">
                            <label for="edit_first_name">First Name</label>
                            <input type="text" id="edit_first_name" name="first_name" required />
                        </div>
                        <div class="form-group">
                            <label for="edit_middle_name">Middle Name</label>
                            <input type="text" id="edit_middle_name" name="middle_name" />
                        </div>
                        <div class="form-group">
                            <label for="edit_last_name">Last Name</label>
                            <input type="text" id="edit_last_name" name="last_name" required />
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" required />
                        </div>
                        <div class="form-group">
                            <label for="edit_mobile">Mobile Number</label>
                            <input type="tel" id="edit_mobile" name="mobile" required />
                        </div>
                        <div class="form-group">
                            <label for="edit_address">Address</label>
                            <textarea id="edit_address" name="address" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary-enhanced" onclick="closeModal('editStudentModal')">Cancel</button>
                            <button type="submit" class="btn-primary-enhanced">Update Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function filterStudents() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const tbody = document.querySelector('.table tbody');
            const rows = tbody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(query)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.getElementById(modalId).focus();
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function viewStudentDetails(studentId) {
            fetch('../php/get_student_details.php?student_id=' + encodeURIComponent(studentId))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const student = data.student;
                        const classes = data.classes;
                        const attendance = data.attendance;
                        let classesHtml = '';
                        if (classes.length > 0) {
                            classesHtml = classes.map(cls => `<p>${cls.class_name} (${cls.subject_name}) - ${cls.schedule}</p>`).join('');
                        } else {
                            classesHtml = '<p>No classes enrolled</p>';
                        }
                        const content = `
                            <div class="student-info">
                                <h4>Student Information</h4>
                                <p><strong>ID:</strong> ${student.student_id}</p>
                                <p><strong>Name:</strong> ${student.first_name} ${student.middle_name || ''} ${student.last_name}</p>
                                <p><strong>Email:</strong> ${student.email}</p>
                                <p><strong>Mobile:</strong> ${student.mobile}</p>
                                <p><strong>Address:</strong> ${student.address}</p>
                            </div>
                            <div class="student-classes">
                                <h4>Enrolled Classes</h4>
                                ${classesHtml}
                            </div>
                            <div class="student-attendance">
                                <h4>Attendance Summary</h4>
                                <p><strong>Total Present:</strong> ${attendance.present}</p>
                                <p><strong>Total Absent:</strong> ${attendance.absent}</p>
                                <p><strong>Total Late:</strong> ${attendance.late}</p>
                            </div>
                        `;
                        document.getElementById('studentDetailsContent').innerHTML = content;
                        openModal('studentDetailsModal');
                    } else {
                        alert('Error loading student details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading student details');
                });
        }

        function editStudent(student) {
            document.getElementById('edit_student_id').value = student.student_id;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_middle_name').value = student.middle_name || '';
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_mobile').value = student.mobile;
            document.getElementById('edit_address').value = student.address;
            openModal('editStudentModal');
        }

        // Add student form submission
        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            // Allow default form submission to PHP handler
        });

        // Edit student form submission
        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
            // Allow default form submission to PHP handler
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        });



        // Hamburger menu toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
            if (window.innerWidth <= 900) {
                document.body.classList.toggle('sidebar-open');
            }
        });

        // Optional: Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('sidebarToggle');
            if (window.innerWidth <= 900 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
    </script>
</body>
</html>
