<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

// Function to generate unique class code
function generateUniqueClassCode($pdo) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxAttempts = 10;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $code = '';
        for ($j = 0; $j < 8; $j++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes WHERE class_code = ?");
        $stmt->execute([$code]);
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            return $code;
        }
    }
    
    // If all attempts fail, use timestamp-based code
    return 'CLASS' . time();
}

// Handle schedule actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add_subject':
                $subject_code = trim($_POST['subject_code']);
                $subject_name = trim($_POST['subject_name']);
                $professor_id = $_POST['professor_id'];
                $schedule = trim($_POST['schedule']);
                $room = trim($_POST['room']);
                $school_year = trim($_POST['school_year']);

                $pdo->beginTransaction();

                $subject_id = 'SUB' . time();
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_id, subject_name, subject_code, credits, created_at, updated_at)
                                      VALUES (?, ?, ?, 3, NOW(), NOW())");
                $stmt->execute([$subject_id, $subject_name, $subject_code]);

                $class_code = generateUniqueClassCode($pdo);
                $class_id = 'CLASS' . time();
                $stmt = $pdo->prepare("INSERT INTO classes (class_id, class_name, class_code, subject_id, professor_id, schedule, room, school_year, created_at, updated_at)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$class_id, $subject_name . ' Class', $class_code, $subject_id, $professor_id, $schedule, $room, $school_year]);

                $pdo->commit();
                $success = "Subject and class added successfully!";
                break;
                
            case 'edit_subject':
                $subject_id = $_POST['subject_id'];
                $subject_code = trim($_POST['subject_code']);
                $subject_name = trim($_POST['subject_name']);
                $professor_id = $_POST['professor_id'];
                $class_code = trim($_POST['class_code']);
                $schedule = trim($_POST['schedule']);
                $room = trim($_POST['room']);
                $school_year = trim($_POST['school_year']);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, updated_at = NOW() WHERE subject_id = ?");
                $stmt->execute([$subject_code, $subject_name, $subject_id]);

                $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, professor_id = ?, schedule = ?, room = ?, school_year = ?, updated_at = NOW() WHERE subject_id = ?");
                $stmt->execute([$class_code, $professor_id, $schedule, $room, $school_year, $subject_id]);

                $pdo->commit();
                $success = "Subject updated successfully!";
                break;
                
            case 'delete_subject':
                $subject_id = $_POST['subject_id'];
                
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("DELETE FROM classes WHERE subject_id = ?");
                $stmt->execute([$subject_id]);
                
                $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
                $stmt->execute([$subject_id]);
                
                $pdo->commit();
                $success = "Subject deleted successfully!";
                break;
                
            case 'assign_professor':
                $subject_id = $_POST['subject_id'];
                $professor_id = $_POST['professor_id'];
                
                $stmt = $pdo->prepare("UPDATE classes SET professor_id = ?, updated_at = NOW() WHERE subject_id = ?");
                $stmt->execute([$professor_id, $subject_id]);
                $success = "Professor assigned successfully!";
                break;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error processing request: " . $e->getMessage();
    }
}

// Fetch data for display
$subjects = $pdo->query("SELECT s.*, p.first_name, p.last_name, c.class_id, c.class_code, c.professor_id, c.schedule, c.room, sys.school_year
                        FROM subjects s
                        JOIN classes c ON s.subject_id = c.subject_id
                        LEFT JOIN professors p ON c.professor_id = p.professor_id
                        LEFT JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                        ORDER BY s.created_at DESC")->fetchAll();

$professors = $pdo->query("SELECT * FROM professors ORDER BY first_name, last_name")->fetchAll();

$enrollment_counts = [];
foreach ($subjects as $subject) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_classes sc 
                          JOIN classes c ON sc.class_id = c.class_id 
                          WHERE c.subject_id = ?");
    $stmt->execute([$subject['subject_id']]);
    $enrollment_counts[$subject['subject_id']] = $stmt->fetch()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Schedule - Global Reciprocal Colleges</title>
    <link rel="stylesheet" href="../css/styles_fixed.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
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
            border: 2px solid var(--primary);
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
            background-color: var(--light);
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(247, 82, 112, 0.2);
            background-color: white;
        }
        .search-icon {
            position: absolute;
            left: 11px;
            top: 65%;
            transform: translateY(-50%);
            color: var(--primary);
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
            box-shadow: 0 6px 16px rgba(247, 82, 112, 0.3);
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(247, 82, 112, 0.5);
        }
        .add-professor-btn {
            background: var(--primary);
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
            box-shadow: 0 6px 16px rgba(247, 82, 112, 0.3);
        }
        .add-professor-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(247, 82, 112, 0.5);
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
            background: var(--danger);
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
        .main-content {
            display: flex;
            flex-direction: column;
            /* Remove align-items center to avoid centering all content */
            /* align-items: center; */
            width: 100%;
            padding: 0 2rem;
            box-sizing: border-box;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        @media (max-width: 768px) {
            .enhanced-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            .header-actions {
                width: 100%;
                justify-content: center;
            }
            .search-container {
                width: 100%;
                max-width: 300px;
            }
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            .modal-body {
                padding: 1rem;
            }
            .action-buttons {
                justify-content: center;
                flex-wrap: wrap;
            }
            .table-container {
                overflow-x: auto;
            }
            .table {
                min-width: 600px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
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

        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 60px; /* Assuming navbar height */
            width: 250px;
            height: calc(100vh - 60px);
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
        }
        .sidebar-header i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .sidebar-header span {
            display: block;
            font-weight: 600;
        }
        .sidebar-nav {
            padding: 0 1rem;
        }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .nav-item i {
            margin-right: 10px;
            width: 20px;
        }
        .nav-item span {
            font-weight: 500;
        }

        /* Adjust main content for sidebar */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 60px;
                top: auto;
                padding: 0;
                display: flex;
                justify-content: space-around;
                align-items: center;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            }
            .sidebar-header {
                display: none;
            }
            .sidebar-nav {
                display: flex;
                width: 100%;
                padding: 0;
                justify-content: space-around;
            }
            .nav-item {
                flex: 1;
                text-align: center;
                padding: 0.5rem 0;
                margin: 0;
                flex-direction: column;
                border-radius: 0;
            }
            .nav-item i {
                margin-right: 0;
                margin-bottom: 0.25rem;
                font-size: 1.2rem;
            }
            .nav-item span {
                font-size: 0.75rem;
                display: block;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-bottom: 60px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt"></i>
            <span>Admin Panel</span>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_manage_students.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
            <a href="admin_manage_professors.php" class="nav-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Professors</span>
            </a>
            <a href="admin_manage_schedule.php" class="nav-item active">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedule</span>
            </a>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="main-content" role="main" tabindex="-1">
        <div class="enhanced-header fade-in">
            <h1 class="header-title"><i class="fas fa-calendar-alt" style="margin-right: 15px;"></i>Manage Schedule & Subjects</h1>
            <div class="header-actions">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search subjects..." onkeyup="filterSubjects()">
                </div>
                <button class="add-professor-btn" onclick="openModal('addSubjectModal')">
                    <i class="fas fa-plus"></i>
                    Add Subject
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
            <div class="stats-card fade-in">
                <i class="fas fa-book stats-icon"></i>
                <div class="stats-number"><?php echo count($subjects); ?></div>
                <div class="stats-label">Total Subjects</div>
            </div>
            <div class="stats-card fade-in">
                <i class="fas fa-chalkboard-teacher stats-icon"></i>
                <div class="stats-number"><?php echo count($professors); ?></div>
                <div class="stats-label">Active Professors</div>
            </div>
            <div class="stats-card fade-in">
                <i class="fas fa-users stats-icon"></i>
                <div class="stats-number"><?php echo array_sum($enrollment_counts); ?></div>
                <div class="stats-label">Total Enrollments</div>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Class Code</th>
                        <th>Professor</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>School Year</th>
                        <th>Enrolled</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?php echo $subject['subject_code']; ?></td>
                        <td><?php echo $subject['subject_name']; ?></td>
                        <td><?php echo $subject['class_code']; ?></td>
                        <td>
                            <?php if ($subject['first_name']): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-user-tie" style="color: var(--primary);"></i>
                                    <?php echo $subject['first_name'] . ' ' . $subject['last_name']; ?>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="status-badge badge-unassigned">Not Assigned</span>
                                    <button class="btn btn-sm btn-primary" onclick="assignProfessor(<?php echo $subject['subject_id']; ?>, '')">
                                        <i class="fas fa-user-plus"></i> Assign
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $subject['schedule']; ?></td>
                        <td><?php echo $subject['room']; ?></td>
                        <td><?php echo $subject['school_year']; ?></td>
                        <td><?php echo $enrollment_counts[$subject['subject_id']] ?? 0; ?> students</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editSubject(<?php echo htmlspecialchars(json_encode($subject)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-info" onclick="viewEnrolledStudents('<?php echo $subject['class_id']; ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subject?')">
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

        <!-- Add Subject Modal -->
        <div id="addSubjectModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New Subject</h3>
                    <button class="modal-close" onclick="closeModal('addSubjectModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add_subject">
                        <div class="form-group">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Name</label>
                            <input type="text" name="subject_name" required>
                        </div>
                        <div class="form-group">
                            <label>Professor</label>
                            <select name="professor_id" required>
                                <option value="">Select Professor</option>
                                <?php foreach ($professors as $professor): ?>
                                <option value="<?php echo $professor['professor_id']; ?>">
                                    <?php echo $professor['first_name'] . ' ' . $professor['last_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Schedule</label>
                            <input type="text" name="schedule" placeholder="e.g., MWF 9:00-10:30" required>
                        </div>
                        <div class="form-group">
                            <label>Room</label>
                            <input type="text" name="room" required>
                        </div>
                        <div class="form-group">
                            <label>School Year</label>
                            <input type="text" name="school_year" placeholder="e.g., 2023-2024" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('addSubjectModal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Subject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Subject Modal -->
        <div id="editSubjectModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Subject</h3>
                    <button class="modal-close" onclick="closeModal('editSubjectModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="edit_subject">
                        <input type="hidden" name="subject_id" id="edit_subject_id">
                        <div class="form-group">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" id="edit_subject_code" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Name</label>
                            <input type="text" name="subject_name" id="edit_subject_name" required>
                        </div>
                        <div class="form-group">
                            <label>Professor</label>
                            <select name="professor_id" id="edit_professor_id" required>
                                <option value="">Select Professor</option>
                                <?php foreach ($professors as $professor): ?>
                                <option value="<?php echo $professor['professor_id']; ?>">
                                    <?php echo $professor['first_name'] . ' ' . $professor['last_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Class Code</label>
                            <input type="text" name="class_code" id="edit_class_code" required>
                        </div>
                        <div class="form-group">
                            <label>Schedule</label>
                            <input type="text" name="schedule" id="edit_schedule" required>
                        </div>
                        <div class="form-group">
                            <label>Room</label>
                            <input type="text" name="room" id="edit_room" required>
                        </div>
                        <div class="form-group">
                            <label>School Year</label>
                            <input type="text" name="school_year" id="edit_school_year" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editSubjectModal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Subject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assign Professor Modal -->
        <div id="assignProfessorModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign Professor</h3>
                    <button class="modal-close" onclick="closeModal('assignProfessorModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="assign_professor">
                        <input type="hidden" name="subject_id" id="assign_subject_id">
                        <div class="form-group">
                            <label>Current Professor: <span id="current_professor">Not assigned</span></label>
                        </div>
                        <div class="form-group">
                            <label>Select New Professor</label>
                            <select name="professor_id" required>
                                <option value="">Select Professor</option>
                                <?php foreach ($professors as $professor): ?>
                                <option value="<?php echo $professor['professor_id']; ?>">
                                    <?php echo $professor['first_name'] . ' ' . $professor['last_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('assignProfessorModal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Assign Professor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>



        <!-- View Enrolled Students Modal -->
        <div id="viewStudentsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Enrolled Students</h3>
                    <button class="modal-close" onclick="closeModal('viewStudentsModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="enrolledStudentsList"></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function filterSubjects() {
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

        function editSubject(subject) {
            document.getElementById('edit_subject_id').value = subject.subject_id;
            document.getElementById('edit_subject_code').value = subject.subject_code;
            document.getElementById('edit_subject_name').value = subject.subject_name;
            document.getElementById('edit_professor_id').value = subject.professor_id || '';
            document.getElementById('edit_class_code').value = subject.class_code;
            document.getElementById('edit_schedule').value = subject.schedule;
            document.getElementById('edit_room').value = subject.room;
            document.getElementById('edit_school_year').value = subject.school_year;
            openModal('editSubjectModal');
        }

        function assignProfessor(subjectId, currentProfessor) {
            document.getElementById('assign_subject_id').value = subjectId;
            document.getElementById('current_professor').textContent = currentProfessor || 'Not assigned';
            openModal('assignProfessorModal');
        }

        function viewEnrolledStudents(classId) {
            document.getElementById('enrolledStudentsList').innerHTML = '<p>Loading enrolled students...</p>';
            openModal('viewStudentsModal');

            fetch('../php/get_class_students.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('enrolledStudentsList').innerHTML = '<p>Error: ' + data.error + '</p>';
                        return;
                    }
                    if (data.length === 0) {
                        document.getElementById('enrolledStudentsList').innerHTML = '<p>No students enrolled in this class.</p>';
                        return;
                    }
                    let html = '<table class="table"><thead><tr><th>Student Name</th><th>Email</th></tr></thead><tbody>';
                    data.forEach(student => {
                        html += '<tr><td>' + student.first_name + ' ' + student.last_name + '</td><td>' + student.email + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    document.getElementById('enrolledStudentsList').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('enrolledStudentsList').innerHTML = '<p>Error loading students: ' + error.message + '</p>';
                });
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
    </script>
    <?php include '../includes/footbar.php'; ?>

</body>
</html>
