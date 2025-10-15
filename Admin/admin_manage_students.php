<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

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
                $section = $_POST['section'];

                try {
                    $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, middle_name, email, password, mobile, address, section, created_at, updated_at)
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$student_id, $first_name, $last_name, $middle_name, $email, $password, $mobile, $address, $section]);
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
                $section = $_POST['section'];

                try {
                    $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, middle_name = ?, email = ?, mobile = ?, address = ?, section = ?, updated_at = NOW()
                                          WHERE student_id = ?");
                    $stmt->execute([$first_name, $last_name, $middle_name, $email, $mobile, $address, $section, $student_id]);
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

// Get all students with semester info
$query = "
    SELECT s.*, sem.semester_name, y.year_label
    FROM students s
    LEFT JOIN student_classes sc ON s.student_id = sc.student_id
    LEFT JOIN classes c ON sc.class_id = c.class_id
    LEFT JOIN semesters sem ON c.semester_id = sem.id
    LEFT JOIN school_years y ON sem.school_year_id = y.id
    GROUP BY s.student_id
    ORDER BY s.last_name, s.first_name
";
$students = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Global Reciprocal Colleges</title>
    <link rel="stylesheet" href="../css/styles_fixed.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .dashboard-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .dashboard-title-enhanced {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .dashboard-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid var(--primary);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .stats-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        .stats-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stats-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        .fade-in-delayed {
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .enhanced-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
            max-width: 600px;
        }
        .search-container {
            padding-top: 1rem;
            position: relative;
            flex-grow: 1;
            min-width: 200px;
            max-width: 400px;
            display: flex;
            align-items: center;
        }
        .search-input {
            width: 100%;
            height: 40px;
            padding: 10px 16px 10px 40px;
            border: 2px solid #dc3545;
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
            background-color: #fef2f2;
        }
        .search-input:focus {
            outline: none;
            border-color: #c82333;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
            background-color: white;
        }
        .search-icon {
            position: absolute;
            left: 11px;
            top: 65%;
            transform: translateY(-50%);
            color: #dc3545;
            font-size: 1.1rem;
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
        }
        .btn-primary {
            background: #dc3545;
            color: white;
        }
        .btn-primary:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.5);
        }
        .add-professor-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 28px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            height: 40px;
            box-sizing: border-box;
            transition: background 0.3s, transform 0.3s ease;
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
        }
        .add-professor-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.5);
        }
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
        }
        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .table tbody tr:hover {
            background-color: #e3f2fd;
            transition: background-color 0.3s ease;
        }
        .table tbody tr {
            cursor: pointer;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-primary:hover, .btn-danger:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.4s ease-out;
            backdrop-filter: blur(5px);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            width: 90%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.4s ease-out;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transform: scale(0.9);
            transition: transform 0.3s ease-out;
        }
        .modal.show .modal-content {
            transform: scale(1);
        }
        .modal-header {
            padding: 2rem 2.5rem;
            border-bottom: 2px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }
        .modal-title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            padding: 8px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        .modal-body {
            padding: 2.5rem;
        }
        .modal-footer {
            padding: 2rem 2.5rem;
            border-top: 2px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            background: #f8f9fa;
            border-radius: 0 0 16px 16px;
        }
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .password-container {
            position: relative;
        }

        .password-container input {
            padding: 12px 50px 12px 16px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            font-size: 1.2rem;
            transition: color 0.3s ease;
            padding: 5px;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .toggle-password:focus {
            outline: none;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .fade-in {
            animation: fadeInUp 0.5s ease-out;
        }
        .fade-out {
            animation: fadeOut 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }
            .dashboard-container {
                padding: 0;
            }
            .enhanced-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.5rem 1rem;
            }
            .header-title {
                font-size: 1.8rem;
            }
            .header-actions {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
                max-width: none;
            }
            .search-container {
                width: 100%;
                max-width: none;
                padding-top: 0;
            }
            .search-input {
                height: 44px;
                font-size: 1rem;
            }
            .add-professor-btn {
                width: 100%;
                justify-content: center;
                height: 44px;
                font-size: 1rem;
            }
            .stats-grid {
                display: none;
            }
            .table-container {
                padding: 1rem;
                border-radius: 16px;
            }
            .table {
                font-size: 0.9rem;
            }
            .table th, .table td {
                padding: 0.75rem 0.5rem;
            }
            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }
            .btn-sm {
                width: 100%;
                justify-content: center;
                padding: 10px;
                font-size: 0.9rem;
            }
            .table th:nth-child(1), .table td:nth-child(1) { display: none; } /* Student ID */
            .table th:nth-child(4), .table td:nth-child(4) { display: none; } /* Mobile */
            .table th:nth-child(5), .table td:nth-child(5) { display: none; } /* Address */
            .table th:nth-child(6), .table td:nth-child(6) { display: none; } /* Section */
            .table th:nth-child(7), .table td:nth-child(7) { display: none; } /* Actions */
            .modal-content {
                width: 95%;
                margin: 10px;
                max-height: 95vh;
            }
            .modal-header {
                padding: 1.5rem 1rem;
            }
            .modal-title {
                font-size: 1.5rem;
            }
            .modal-body {
                padding: 1.5rem 1rem;
            }
            .modal-footer {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            .modal-footer .btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
            .form-group input, .form-group select {
                padding: 14px 16px;
                font-size: 1rem;
            }
            .alert {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
        @media (max-width: 480px) {
            .main-content {
                padding: 0 0.5rem;
            }
            .enhanced-header {
                padding: 1rem 0.5rem;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .stats-card {
                padding: 1.5rem 1rem;
            }
            .stats-icon {
                font-size: 3.5rem;
                margin-bottom: 1rem;
            }
            .stats-number {
                font-size: 3.5rem;
                margin-bottom: 0.5rem;
            }
            .stats-label {
                font-size: 1.1rem;
            }
            .table-container {
                padding: 0.5rem;
            }
            .table {
                font-size: 0.8rem;
            }
            .table th, .table td {
                padding: 0.5rem 0.25rem;
            }
            .modal-content {
                width: 98%;
                margin: 5px;
            }
            .modal-header {
                padding: 1rem 0.5rem;
            }
            .modal-title {
                font-size: 1.3rem;
            }
            .modal-body {
                padding: 1rem 0.5rem;
            }
            .modal-footer {
                padding: 0.5rem;
            }
            .form-group input, .form-group select {
                padding: 12px 14px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>

    <?php include '../includes/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <main class="main-content" role="main" tabindex="-1">
        <div class="dashboard-container">
            <div class="enhanced-header fade-in">
                <h1 class="header-title"><i class="fas fa-users" style="margin-right: 15px;"></i>Manage Students</h1>
                <div class="header-actions">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search students..." onkeyup="filterStudents()">
                    </div>
                    <button class="add-professor-btn" onclick="openModal('addStudentModal')">
                        <i class="fas fa-plus"></i>
                        Add Student
                    </button>
                </div>
            </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success fade show fade-in" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger fade show fade-in" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stats-card fade-in" tabindex="0">
                <i class="fas fa-user-graduate" style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                <div class="stats-number"><?php echo count($students); ?></div>
                <div class="stats-label">Total Students</div>
            </div>
            <div class="stats-card fade-in" tabindex="0">
                <i class="fas fa-user-check" style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                <div class="stats-number"><?php echo count(array_filter($students, function($s) { return strtotime($s['created_at']) > strtotime('-30 days'); })); ?></div>
                <div class="stats-label">New This Month</div>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Address</th>
                <th>Section</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr onclick="viewStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                <td><?php echo htmlspecialchars($student['address']); ?></td>
                <td><?php echo htmlspecialchars($student['section']); ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="" method="POST" style="display:inline;" onclick="event.stopPropagation();">
                            <input type="hidden" name="action" value="delete_student">
                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
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

        <!-- Add Student Modal -->
        <div id="addStudentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New Student</h3>
                    <button class="modal-close" onclick="closeModal('addStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add_student">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" name="student_id" required>
                        </div>
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="password-container">
                                <input type="password" name="password" required>
                                <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mobile Number</label>
                            <input type="tel" name="mobile" required>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" required>
                        </div>
                        <div class="form-group">
                            <label>Section</label>
                            <input type="text" name="section" placeholder="e.g., A">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Student Modal -->
        <div id="editStudentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Student</h3>
                    <button class="modal-close" onclick="closeModal('editStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="edit_student">
                        <input type="hidden" name="student_id" id="edit_student_id">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="edit_last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" id="edit_middle_name">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label>Mobile Number</label>
                            <input type="tel" name="mobile" id="edit_mobile" required>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" id="edit_address" required>
                        </div>
                        <div class="form-group">
                            <label>Section</label>
                            <input type="text" name="section" id="edit_section" placeholder="e.g., A">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Student Modal -->
        <div id="viewStudentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">View Student</h3>
                    <button class="modal-close" onclick="closeModal('viewStudentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" id="view_student_id" readonly>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="view_first_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="view_last_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" id="view_middle_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="view_email" readonly>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="tel" id="view_mobile" readonly>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" id="view_address" readonly>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" id="view_section" readonly>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="editStudentFromView()">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_student">
                            <input type="hidden" name="student_id" id="view_delete_student_id">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
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
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function editStudent(student) {
            document.getElementById('edit_student_id').value = student.student_id;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_middle_name').value = student.middle_name || '';
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_mobile').value = student.mobile;
            document.getElementById('edit_address').value = student.address;
            document.getElementById('edit_section').value = student.section || '';
            openModal('editStudentModal');
        }

        function viewStudent(student) {
            document.getElementById('view_student_id').value = student.student_id;
            document.getElementById('view_first_name').value = student.first_name;
            document.getElementById('view_last_name').value = student.last_name;
            document.getElementById('view_middle_name').value = student.middle_name || '';
            document.getElementById('view_email').value = student.email;
            document.getElementById('view_mobile').value = student.mobile;
            document.getElementById('view_address').value = student.address;
            document.getElementById('view_section').value = student.section || '';
            document.getElementById('view_delete_student_id').value = student.student_id;
            openModal('viewStudentModal');
        }

        function editStudentFromView() {
            const student = {
                student_id: document.getElementById('view_student_id').value,
                first_name: document.getElementById('view_first_name').value,
                last_name: document.getElementById('view_last_name').value,
                middle_name: document.getElementById('view_middle_name').value,
                email: document.getElementById('view_email').value,
                mobile: document.getElementById('view_mobile').value,
                address: document.getElementById('view_address').value,
                section: document.getElementById('view_section').value
            };
            closeModal('viewStudentModal');
            editStudent(student);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });


    </script>
    <script>
        // Auto dismiss alerts with slide out animation
        document.addEventListener('DOMContentLoaded', function () {
            const alertList = document.querySelectorAll('.alert');
            alertList.forEach(function (alert) {
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        alert.classList.remove('show');
                        alert.remove();
                    }, 500); // match animation duration
                }, 3000); // show alert for 3 seconds
            });
        });

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.querySelector('input[name="password"]');

            const eyeVisible = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"></circle></svg>`;
            const eyeHidden = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"></circle><line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line></svg>`;

            if (togglePassword && passwordInput) {
                // Set initial icon
                togglePassword.innerHTML = eyeHidden;
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? eyeHidden : eyeVisible;
                });
            }
        });
    </script>
    <?php include '../includes/footbar.php'; ?>

</body>
</html>
