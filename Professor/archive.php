<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

// Fetch professor data
$professor_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name FROM professors WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

// Get professor's classes (both active and archived) with school year and semester from school_year_semester table
$query = "SELECT s.*, c.class_id, c.class_code, c.schedule, c.room, c.section, c.status,
                 sys.school_year,
                 sys.semester,
                 sys.status as term_status
          FROM subjects s
          JOIN classes c ON s.subject_id = c.subject_id
          LEFT JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
          WHERE c.professor_id = ?
          ORDER BY COALESCE(sys.status, 'Active') DESC, sys.school_year DESC, sys.semester DESC, s.subject_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$classes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'archive_all_2025_1st') {
        // Get the current school year and semester
        $stmt = $pdo->prepare("SELECT id, school_year, semester FROM school_year_semester WHERE status = 'Active'");
        $stmt->execute();
        $active_term = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($active_term) {
            $school_year_semester_id = $active_term['id'];
            $school_year = $active_term['school_year'];
            $semester = $active_term['semester'];

            // Archive all classes for the active school year and semester for this professor
            // First update the school_year_semester status to 'Archived'
            $stmt = $pdo->prepare("UPDATE school_year_semester SET status = 'Archived' WHERE id = ?");
            $stmt->execute([$school_year_semester_id]);

            // Then update the classes.status for backward compatibility
            $archiveQuery = "UPDATE classes c
                             SET c.status = 'archived'
                             WHERE c.professor_id = ? AND c.school_year_semester_id = ?";
            $stmt = $pdo->prepare($archiveQuery);
            $stmt->execute([$professor_id, $school_year_semester_id]);

            header('Location: archive.php');
            exit();
        } else {
            // Handle the case where no active school year and semester are found
            echo "No active school year and semester found.";
            exit();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'unarchive_all_1st') {
        // Unarchive all classes for 1st semester 2025-2026 for this professor
        // First get the school_year_semester_id for 2025-2026 1st Semester
        $stmt = $pdo->prepare("SELECT id FROM school_year_semester WHERE school_year = '2025-2026' AND semester = '1st Semester'");
        $stmt->execute();
        $term = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($term) {
            $school_year_semester_id = $term['id'];

            // Update the school_year_semester status to 'Active'
            $stmt = $pdo->prepare("UPDATE school_year_semester SET status = 'Active' WHERE id = ?");
            $stmt->execute([$school_year_semester_id]);

            // Then update the classes.status for backward compatibility
            $unarchiveQuery = "UPDATE classes c
                             SET c.status = 'active'
                             WHERE c.professor_id = ? AND c.school_year_semester_id = ?";
            $stmt = $pdo->prepare($unarchiveQuery);
            $stmt->execute([$professor_id, $school_year_semester_id]);
        }

        header('Location: archive.php');
        exit();
    } elseif (isset($_POST['action']) && isset($_POST['class_id'])) {
        $class_id = $_POST['class_id'];
        $action = $_POST['action'];

        if ($action === 'archive') {
            // Only update the status of the specific class
            $stmt = $pdo->prepare("UPDATE classes SET status = 'archived' WHERE class_id = ? AND professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);
        } elseif ($action === 'unarchive') {
            // Only update the status of the specific class
            $stmt = $pdo->prepare("UPDATE classes SET status = 'active' WHERE class_id = ? AND professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);
        }

        header('Location: archive.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Global Reciprocal College</title>
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
            --success: #F7CAC9;
            --warning: #FDEBD0;
            --danger: #DC143C;
            --info: #F75270;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Enhanced Header Design */
        .archive-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 2rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px rgba(247, 82, 112, 0.3);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .archive-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .archive-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 1rem 0;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .archive-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0;
            position: relative;
            z-index: 2;
            font-weight: 400;
        }

        /* Enhanced Container */
        .archive-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Modern Tab Design */
        .tabs {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0;
            margin-bottom: 3rem;
            background: white;
            border-radius: 16px;
            padding: 0.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .tab-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--gray);
            position: relative;
            min-width: 180px;
        }

        .tab-btn:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 8px 20px rgba(247, 82, 112, 0.4);
            transform: translateY(-2px);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Enhanced Class Cards */
        .class-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(247, 82, 112, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .class-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            transition: width 0.3s ease;
        }

        .class-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(247, 82, 112, 0.15);
        }

        .class-card:hover::before {
            width: 6px;
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .class-info h3 {
            margin: 0 0 1rem 0;
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .class-info h3 i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .class-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            color: var(--gray);
            padding: 0.75rem;
            background: var(--light);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateX(5px);
        }

        .detail-item i {
            width: 20px;
            color: var(--primary);
            font-size: 1rem;
        }

        /* Enhanced Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-active {
            background: linear-gradient(135deg, var(--success) 0%, var(--primary-light) 100%);
            color: var(--primary-dark);
            box-shadow: 0 4px 15px rgba(247, 82, 112, 0.2);
        }

        .status-archived {
            background: linear-gradient(135deg, var(--light) 0%, var(--warning) 100%);
            color: var(--dark);
            box-shadow: 0 4px 15px rgba(253, 235, 208, 0.3);
        }

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-archive {
            background: linear-gradient(135deg, var(--warning) 0%, var(--light) 100%);
            color: var(--dark);
        }

        .btn-archive:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--warning) 100%);
            color: var(--primary-dark);
        }

        .btn-unarchive {
            background: linear-gradient(135deg, var(--success) 0%, var(--primary-light) 100%);
            color: var(--primary-dark);
        }

        .btn-unarchive:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--success) 100%);
            color: white;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-3px) scale(1.05);
        }

        /* Enhanced Empty States */
        .no-classes {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 2px dashed var(--primary-light);
        }

        .no-classes-icon {
            font-size: 5rem;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        /* Enhanced Collapse Buttons */
        .collapse-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            justify-content: space-between;
            box-shadow: 0 8px 25px rgba(247, 82, 112, 0.3);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .collapse-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(247, 82, 112, 0.4);
        }

        .collapse-btn i {
            transition: transform 0.3s ease;
            font-size: 1rem;
        }

        .collapse-btn.collapsed i {
            transform: rotate(180deg);
        }

        .collapse-content {
            margin-top: 1rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; max-height: 0; }
            to { opacity: 1; max-height: 1000px; }
        }



        /* Enhanced Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--primary-light);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(247, 82, 112, 0.1);
        }

        /* Enhanced Alert/Notification Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInAlert 0.3s ease;
        }

        @keyframes slideInAlert {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: linear-gradient(135deg, var(--success) 0%, var(--primary-light) 100%);
            color: var(--primary-dark);
            border-color: var(--primary);
        }

        .alert-warning {
            background: linear-gradient(135deg, var(--warning) 0%, var(--light) 100%);
            color: var(--dark);
            border-color: var(--primary-light);
        }

        .alert-danger {
            background: linear-gradient(135deg, var(--danger) 0%, var(--primary-light) 100%);
            color: var(--danger);
            border-color: var(--danger);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .class-header {
                flex-direction: column;
                gap: 1.5rem;
            }

            .action-buttons {
                justify-content: center;
                width: 100%;
            }

            .tabs {
                width: 100%;
                margin: 0 0 2rem 0;
            }

            .tab-btn {
                flex: 1;
                min-width: auto;
            }

            .class-details {
                grid-template-columns: 1fr;
            }

            .archive-title {
                font-size: 2rem;
            }

            .modal-content {
                width: 95%;
                margin: 1rem;
            }
        }

        /* Mobile Responsiveness for 414px and below */
        @media (max-width: 414px) {
            .main-content {
                margin-left: 0;
                padding: 1rem 0.5rem;
            }

            .archive-header {
                padding: 2rem 1rem;
                margin-bottom: 2rem;
            }

            .archive-title {
                font-size: 1.8rem;
            }

            .archive-subtitle {
                font-size: 1rem;
            }

            .tabs {
                padding: 0.25rem;
                margin-bottom: 2rem;
            }

            .tab-btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
                min-width: 120px;
            }

            .class-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }

            .class-info h3 {
                font-size: 1.3rem;
            }

            .class-details {
                gap: 0.75rem;
            }

            .detail-item {
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .btn {
                padding: 0.875rem 1.25rem;
                font-size: 0.9rem;
                width: 100%;
                justify-content: center;
                min-height: 44px; /* Touch-friendly */
            }

            .status-badge {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem;
            }

            .collapse-btn {
                padding: 0.875rem 1rem;
                font-size: 1rem;
                min-height: 44px;
            }

            .no-classes {
                padding: 2rem 1rem;
            }

            .no-classes-icon {
                font-size: 3rem;
            }

            .modal-content {
                width: 95%;
                max-width: none;
                margin: 0.5rem;
            }

            .modal-header {
                padding: 1.5rem;
            }

            .modal-title {
                font-size: 1.3rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .form-control {
                padding: 0.875rem;
                font-size: 1rem;
            }
        }

        /* Extra Small Mobile (375px and below) */
        @media (max-width: 375px) {
            .main-content {
                padding: 0.75rem 0.25rem;
            }

            .archive-header {
                padding: 1.5rem 0.75rem;
                margin-bottom: 1.5rem;
            }

            .archive-title {
                font-size: 1.5rem;
            }

            .archive-subtitle {
                font-size: 0.9rem;
            }

            .tabs {
                padding: 0.125rem;
                margin-bottom: 1.5rem;
            }

            .tab-btn {
                padding: 0.625rem 0.75rem;
                font-size: 0.85rem;
                min-width: 100px;
            }

            .class-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }

            .class-info h3 {
                font-size: 1.2rem;
            }

            .class-details {
                gap: 0.5rem;
            }

            .detail-item {
                padding: 0.4rem;
                font-size: 0.85rem;
            }

            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
                min-height: 40px;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }

            .collapse-btn {
                padding: 0.75rem 0.875rem;
                font-size: 0.95rem;
                min-height: 40px;
            }

            .no-classes {
                padding: 1.5rem 0.75rem;
            }

            .no-classes-icon {
                font-size: 2.5rem;
            }

            .modal-content {
                width: 98%;
                margin: 0.25rem;
            }

            .modal-header {
                padding: 1.25rem;
            }

            .modal-title {
                font-size: 1.2rem;
            }

            .modal-body {
                padding: 1.25rem;
            }

            .form-control {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .alert {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--primary-light);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Enhanced Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
        /* Modal overlay and content - ensure modals behave as overlays and only close on overlay click */
        .modal {
            display: none;
            position: fixed;
            inset: 0; /* top:0; right:0; bottom:0; left:0; */
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.45);
            z-index: 9999;
            padding: 1rem;
        }

        .modal.show {
            display: flex;
        }

        .modal .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 1100px;
            width: 100%;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

    </style>
</head>
<body>
    <?php include '../includes/navbar_professor.php'; ?>
    <?php include '../includes/sidebar_professor.php'; ?>

    <main class="main-content">
        <div class="archive-header">
            <h1 class="archive-title"><i class="fas fa-archive" style="margin-right: 10px;"></i>Archive Management</h1>
            <p class="archive-subtitle">Manage and archive your class records</p>
        </div>

        <div class="archive-container">
        <div class="tabs">
            <div style="display: flex; gap: 0.5rem;">
                <button class="tab-btn active" onclick="showTab('active', event)">Active Classes</button>
                <button class="tab-btn" onclick="showTab('archived', event)">Archived Classes</button>
            </div>
        </div>

            <div id="active-tab" class="tab-content active">
                <?php
                $active_classes = array_filter($classes, function($class) {
                    return $class['status'] === 'active';
                });

                if (!empty($active_classes)):
                    foreach ($active_classes as $class):
                ?>
                    <div class="class-card">
                        <div class="class-header">
                            <div class="class-info">
                                <h3><?php echo htmlspecialchars($class['subject_name']); ?></h3>
                                <div class="class-details">
                                    <div class="detail-item">
                                        <i class="fas fa-code"></i>
                                        <?php echo htmlspecialchars($class['class_code']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo htmlspecialchars($class['schedule']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($class['room']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        Section <?php echo htmlspecialchars($class['section']); ?>
                                    </div>
                                </div>

                            </div>
                            <div class="action-buttons">
                                <span class="status-badge status-active">Active</span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                    <input type="hidden" name="action" value="archive">
                                    <button type="submit" class="btn btn-archive" onclick="return confirm('Are you sure you want to archive this class?')">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                </form>
                                <button type="button" class="btn btn-view" onclick="toggleAttendanceModal('<?php echo $class['class_id']; ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </div>
                        </div>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="no-classes">
                        <div class="no-classes-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>No Active Classes</h3>
                        <p>You don't have any active classes to archive.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="archived-tab" class="tab-content">
                <?php
                // Group archived classes by school_year and semester
                $grouped_archived = [];
                foreach ($classes as $class) {
                    if ($class['status'] === 'archived') {
                        $year = $class['school_year'] ?? 'Unknown Year';
                        $semester = $class['semester'] ?? 'Unknown Semester';
                        $grouped_archived[$year][$semester][] = $class;
                    }
                }

                if (!empty($grouped_archived)):
                    foreach ($grouped_archived as $year => $semesters):
                        foreach ($semesters as $semester => $classes_group):
                ?>
                    <div class="archive-group" style="width: 100%; max-width: 900px; margin: 0 auto;">
                        <button class="collapse-btn" onclick="toggleCollapse(this)">
                            Year <?php echo htmlspecialchars($year); ?> - <?php echo htmlspecialchars($semester); ?> sem
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="collapse-content" style="display:none; margin-top: 1rem;">

                            <?php foreach ($classes_group as $class): ?>
                                <div class="class-card" onclick="openModal('<?php echo $class['class_id']; ?>')">
                                    <div class="class-header">
                                        <div class="class-info">
                                            <h3><?php echo htmlspecialchars($class['subject_name']); ?></h3>
                                            <div class="class-details">
                                                <div class="detail-item">
                                                    <i class="fas fa-code"></i>
                                                    <?php echo htmlspecialchars($class['class_code']); ?>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo htmlspecialchars($class['schedule']); ?>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($class['room']); ?>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-users"></i>
                                                    Section <?php echo htmlspecialchars($class['section']); ?>
                                                </div>
                                            </div>
                                            <!-- Removed enrolled students section as per user request -->
                                            <!--
                                            <div class="class-students">
                                                <h4>Enrolled Students</h4>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT st.first_name, st.last_name, st.email FROM student_classes sc JOIN students st ON sc.student_id = st.student_id WHERE sc.class_id = ?");
                                                $stmt->execute([$class['class_id']]);
                                                $students = $stmt->fetchAll();
                                                if ($students):
                                                ?>
                                                <ul>
                                                    <?php foreach ($students as $student): ?>
                                                        <li><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['email'] . ')'); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php else: ?>
                                                    <p>No students enrolled.</p>
                                                <?php endif; ?>
                                            </div>
                                            -->
                                        </div>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-view" onclick="toggleAttendanceModal('<?php echo $class['class_id']; ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="action" value="unarchive">
                                                <button type="submit" class="btn btn-unarchive" onclick="return confirm('Are you sure you want to unarchive this class?')">
                                                    <i class="fas fa-undo"></i> Unarchive
                                                </button>
                                            </form>
                                            <!-- Add Download Attendance button for this archived class -->
                                            <a href="../php/download_attendance.php?subject_id=<?php echo htmlspecialchars($class['subject_id']); ?>" class="btn btn-sm btn-primary" title="Download Attendance">
                                                <i class="fas fa-file-download"></i> Download Attendance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                        endforeach;
                    endforeach;
                else:
                ?>
                    <div class="no-classes" style="width: 100%; max-width: 900px; margin: 0 auto; text-align: center;">
                        <div class="no-classes-icon">
                            <i class="fas fa-archive"></i>
                        </div>
                        <h3>No Archived Classes</h3>
                        <p>You don't have any archived classes.</p>
                    </div>
                <?php endif; ?>
            </div>
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

    <!-- Archive Confirmation Modal -->
    <div id="archiveConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px; padding: 2rem; text-align: center;">
            <h3 style="margin: 0 0 1rem 0; color: var(--dark); font-size: 1.5rem;">Confirm Archive</h3>
            <p style="margin: 0 0 2rem 0; color: var(--gray); font-size: 1rem;">Are you sure you want to archive all classes for 2025-2026 1st Semester?</p>
            <div style="display: flex; justify-content: center; gap: 1rem;">
                <button class="btn" style="background: #f8f9fa; color: var(--dark); padding: 0.75rem 1.5rem;" onclick="closeArchiveConfirmModal()">Cancel</button>
                <button class="btn btn-archive" style="padding: 0.75rem 1.5rem;" onclick="confirmArchiveAll()">OK</button>
            </div>
        </div>
    </div>

    <!-- Unarchive Confirmation Modal -->
    <div id="unarchiveConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px; padding: 2rem; text-align: center;">
            <h3 style="margin: 0 0 1rem 0; color: var(--dark); font-size: 1.5rem;">Confirm Unarchive</h3>
            <p style="margin: 0 0 2rem 0; color: var(--gray); font-size: 1rem;">Are you sure you want to unarchive all classes for 2025-2026 1st Semester?</p>
            <div style="display: flex; justify-content: center; gap: 1rem;">
                <button class="btn" style="background: #f8f9fa; color: var(--dark); padding: 0.75rem 1.5rem;" onclick="closeUnarchiveConfirmModal()">Cancel</button>
                <button class="btn btn-unarchive" style="padding: 0.75rem 1.5rem;" onclick="confirmUnarchiveAll()">OK</button>
            </div>
        </div>
    </div>

    <!-- Archive Details Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>

    <script>
        function showTab(tabName, evt) {
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
            if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
        }

        // Hamburger menu toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('show');
                    if (window.innerWidth <= 900) {
                        document.body.classList.toggle('sidebar-open');
                    }
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

        // Modal functions
        function openModal(classId) {
            fetch('archive_details.php?class_id=' + classId + '&modal=1')
                .then(response => response.text())
                .then(data => {
                    const modal = document.getElementById('archiveModal');
                    modal.querySelector('.modal-content').innerHTML = data;
                    modal.classList.add('show');
                })
                .catch(error => console.error('Error loading modal content:', error));
        }

        function closeModal() {
            const modal = document.getElementById('archiveModal');
            modal.classList.remove('show');
        }

        // Collapse toggle function
        function toggleCollapse(button) {
            const content = button.nextElementSibling;
            if (!content) return;
            const isHidden = content.style.display === 'none' || getComputedStyle(content).display === 'none';
            if (isHidden) {
                content.style.display = 'block';
                button.classList.add('collapsed');
            } else {
                content.style.display = 'none';
                button.classList.remove('collapsed');
            }
        }

        // Archive confirmation modal functions
        function showArchiveConfirmModal() {
            document.getElementById('archiveConfirmModal').classList.add('show');
        }

        function closeArchiveConfirmModal() {
            document.getElementById('archiveConfirmModal').classList.remove('show');
        }

        function confirmArchiveAll() {
            // Create and submit a small form to archive all
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const input = document.createElement('input');
            input.name = 'action';
            input.value = 'archive_all_2025_1st';
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Unarchive confirmation modal functions
        function showUnarchiveConfirmModal() {
            document.getElementById('unarchiveConfirmModal').classList.add('show');
        }

        function closeUnarchiveConfirmModal() {
            document.getElementById('unarchiveConfirmModal').classList.remove('show');
        }

        function confirmUnarchiveAll() {
            // Create and submit a small form to unarchive all
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const input = document.createElement('input');
            input.name = 'action';
            input.value = 'unarchive_all_1st';
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Attendance modal functions
        function openAttendanceModal(classId) {
            fetch('archive_attendance.php?class_id=' + classId)
                .then(response => response.text())
                .then(data => {
                    const modal = document.getElementById('attendanceModal');
                    modal.querySelector('.modal-content').innerHTML = data;
                    // Ensure all date sections are collapsed by default
                    modal.querySelectorAll('.date-content').forEach(dc => {
                        dc.style.display = 'none';
                    });
                    modal.querySelectorAll('.collapse-icon').forEach(ci => {
                        ci.textContent = '▼';
                    });

                    // Attach a single delegated click handler for date-header to support dynamic content
                    const contentContainer = modal.querySelector('.modal-content');
                    // Remove previous handler if present to avoid duplicates
                    if (modal._dateHeaderHandler && contentContainer) {
                        contentContainer.removeEventListener('click', modal._dateHeaderHandler);
                        modal._dateHeaderHandler = null;
                    }
                    if (contentContainer) {
                        const handler = function(e) {
                            const header = e.target.closest('.date-header');
                            if (header && contentContainer.contains(header)) {
                                // prevent accidental other handlers
                                e.preventDefault();
                                toggleDateCollapse(header);
                            }
                        };
                        contentContainer.addEventListener('click', handler);
                        modal._dateHeaderHandler = handler;
                    }

                    modal.classList.add('show');
                })
                .catch(error => console.error('Error loading attendance modal content:', error));
        }

        function closeAttendanceModal() {
            const modal = document.getElementById('attendanceModal');
            modal.classList.remove('show');
        }

        function toggleAttendanceModal(classId) {
            const modal = document.getElementById('attendanceModal');
            if (modal.classList.contains('show')) {
                closeAttendanceModal();
            } else {
                openAttendanceModal(classId);
            }
        }

        // Provide a global toggleDateCollapse so HTML loaded via fetch (innerHTML)
        // can call it. Script tags inside fetched HTML won't execute automatically.
        function toggleDateCollapse(header) {
            if (!header) return;
            // Prevent the click from bubbling to other handlers
            if (event && typeof event.stopPropagation === 'function') event.stopPropagation();

            // Prefer finding the .date-content within the same .date-card
            const card = header.closest('.date-card');
            let content = null;
            if (card) {
                content = card.querySelector('.date-content');
            }
            // Fallback to nextElementSibling
            if (!content) content = header.nextElementSibling;

            const icon = header.querySelector('.collapse-icon');
            if (!content) return;
            const isHidden = getComputedStyle(content).display === 'none';
            if (isHidden) {
                content.style.display = 'block';
                if (icon) icon.textContent = '▲';
                header.setAttribute('aria-expanded', 'true');
            } else {
                content.style.display = 'none';
                if (icon) icon.textContent = '▼';
                header.setAttribute('aria-expanded', 'false');
            }
        }

        // Close modals when clicking outside their content (single consolidated listener)
        document.addEventListener('click', function(e) {
            ['archiveModal', 'attendanceModal', 'archiveConfirmModal', 'unarchiveConfirmModal'].forEach(id => {
                const modal = document.getElementById(id);
                if (!modal) return;
                if (modal.classList.contains('show') && e.target === modal) {
                    // Close the modal when clicking the overlay
                    modal.classList.remove('show');
                }
            });
        });
    </script>
    <?php include '../includes/footbar.php'; ?>
</body>
</html>
