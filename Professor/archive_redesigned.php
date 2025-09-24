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
                 sys.school_year, sys.semester, sys.status as term_status
          FROM subjects s
          JOIN classes c ON s.subject_id = c.subject_id
          JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
          WHERE c.professor_id = ?
          ORDER BY sys.status DESC, sys.school_year DESC, sys.semester DESC, s.subject_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$classes = $stmt->fetchAll();

// Insert sample archive subjects for years 2024-2025 and 2023-2024 if not exist
$sample_subjects = [
    ['subject_id' => 'SUBARCH1', 'subject_name' => 'Archived Subject 2024-2025', 'subject_code' => 'ARCH2024'],
    ['subject_id' => 'SUBARCH2', 'subject_name' => 'Archived Subject 2023-2024', 'subject_code' => 'ARCH2023']
];

foreach ($sample_subjects as $sample) {
    // Check if subject exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subjects WHERE subject_id = ?");
    $stmt->execute([$sample['subject_id']]);
    $count = $stmt->fetch()['count'];

    if ($count == 0) {
        // Insert subject
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_id, subject_name, subject_code, credits, created_at, updated_at) VALUES (?, ?, ?, 3, NOW(), NOW())");
        $stmt->execute([$sample['subject_id'], $sample['subject_name'], $sample['subject_code']]);

        // Insert class for professor with archived status and school year
        $class_id = 'CLASS' . time() . rand(1000, 9999);
        $class_code = 'ARCH' . rand(1000, 9999);
        $school_year = $sample['subject_code'] == 'ARCH2024' ? '2024-2025' : '2023-2024';

        $stmt = $pdo->prepare("INSERT INTO classes (class_id, class_name, class_code, subject_id, professor_id, schedule, room, status, school_year, created_at, updated_at, section) VALUES (?, ?, ?, ?, ?, ?, ?, 'archived', ?, NOW(), NOW(), 'A')");
        $stmt->execute([$class_id, $sample['subject_name'] . ' Class', $class_code, $sample['subject_id'], $professor_id, 'TBA', 'TBA', $school_year]);

        // Insert into student_classes to register the subject for the professor as a student (archive)
        $stmt = $pdo->prepare("INSERT INTO student_classes (student_id, class_id, enrolled_at) VALUES (?, ?, NOW())");
        $stmt->execute([$professor_id, $class_id]);
    }
}

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Header Section */
        .archive-hero {
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

        .archive-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .archive-hero-content {
            position: relative;
            z-index: 1;
        }

        .archive-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .archive-hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            background: white;
            padding: 0.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .tab-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(247, 82, 112, 0.1), transparent);
            transition: left 0.5s;
        }

        .tab-btn:hover::before {
            left: 100%;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 82, 112, 0.3);
            transform: translateY(-2px);
        }

        .tab-btn:hover:not(.active) {
            color: var(--primary);
            transform: translateY(-1px);
        }

        /* Content Container */
        .archive-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Class Cards */
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .class-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(247, 82, 112, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            transform: scaleY(0);
            transition: transform 0.3s ease;
            transform-origin: bottom;
        }

        .class-card:hover::before {
            transform: scaleY(1);
            transform-origin: top;
        }

        .class-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(247, 82, 112, 0.15);
            border-color: rgba(247, 82, 112, 0.2);
        }

        .class-header {
            margin-bottom: 1.5rem;
        }

        .class-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .class-title i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .class-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--light);
            border-radius: 12px;
            font-size: 0.9rem;
            color: var(--dark);
            transition: all 0.2s ease;
        }

        .detail-item:hover {
            background: var(--primary-light);
            transform: translateX(4px);
        }

        .detail-item i {
            color: var(--primary);
            width: 16px;
            font-size: 0.9rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: rgba(247, 202, 201, 0.2);
            color: var(--primary);
            border: 1px solid rgba(247, 82, 112, 0.2);
        }

        .status-archived {
            background: rgba(253, 235, 208, 0.2);
            color: var(--warning);
            border: 1px solid rgba(253, 235, 208, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 1.5rem;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 82, 112, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(247, 82, 112, 0.4);
        }

        .btn-success {
            background: var(--success);
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(247, 202, 201, 0.3);
        }

        .btn-success:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(247, 202, 201, 0.4);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--dark);
            box-shadow: 0 4px 15px rgba(253, 235, 208, 0.3);
        }

        .btn-warning:hover {
            background: #f5d4a3;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(253, 235, 208, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Bulk Actions */
        .bulk-actions {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            text-align: center;
        }

        .bulk-actions h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-state-icon {
            font-size: 5rem;
            color: var(--light-gray);
            margin-bottom: 2rem;
            opacity: 0.6;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1rem;
            color: var(--gray);
        }

        /* Archive Groups */
        .archive-group {
            background: white;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .archive-group-header {
            background: linear-gradient(135deg, var(--light) 0%, var(--warning) 100%);
            padding: 1.5rem 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .archive-group-header:hover {
            background: linear-gradient(135deg, var(--warning) 0%, var(--primary-light) 100%);
        }

        .archive-group-header h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .archive-group-header h4 i {
            color: var(--primary);
        }

        .archive-group-header .toggle-icon {
            transition: transform 0.3s ease;
            color: var(--primary);
        }

        .archive-group-header.collapsed .toggle-icon {
            transform: rotate(180deg);
        }

        .archive-group-content {
            padding: 2rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .archive-group-content.expanded {
            max-height: 1000px;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(52, 58, 64, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
        }

        .modal-overlay.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-container {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9) translateY(20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-overlay.show .modal-container {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .modal-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid rgba(247, 82, 112, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title i {
            color: var(--primary);
        }

        .modal-close {
            background: var(--light);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-text {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-actions .btn {
            min-width: 120px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .class-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .class-card {
                padding: 1.5rem;
            }

            .class-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .tab-navigation {
                margin: 0 1rem 2rem;
            }

            .archive-hero {
                padding: 2rem 1rem;
                margin: 0 1rem 2rem;
            }

            .archive-hero h1 {
                font-size: 2rem;
            }

            .bulk-actions {
                margin: 0 1rem 2rem;
                padding: 1.5rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-up {
            animation: slideUp 0.3s ease forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--gray);
        }

        .loading i {
            margin-right: 0.5rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_professor.php'; ?>
    <?php include '../includes/sidebar_professor.php'; ?>

    <main class="main-content">
        <div class="archive-hero">
            <div class="archive-hero-content">
                <h1><i class="fas fa-archive"></i> Archive Management</h1>
                <p>Manage and organize your class records with ease</p>
            </div>
        </div>

        <div class="archive-container">
            <div class="tab-navigation">
                <button class="tab-btn active" onclick="showTab('active')">
                    <i class="fas fa-play-circle"></i> Active Classes
                </button>
                <button class="tab-btn" onclick="showTab('archived')">
                    <i class="fas fa-archive"></i> Archived Classes
                </button>
            </div>

            <div id="active-tab" class="tab-content fade-in">
                <div class="bulk-actions">
                    <h3><i class="fas fa-boxes"></i> Bulk Archive</h3>
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="archive_all_2025_1st">
                        <button type="button" class="btn btn-warning" onclick="showArchiveConfirmModal()">
                            <i class="fas fa-archive"></i> Archive All 2025-2026 1st Semester Classes
                        </button>
                    </form>
                </div>

                <?php
                $active_classes = array_filter($classes, function($class) {
                    return $class['status'] === 'active';
                });

                if (!empty($active_classes)):
                ?>
                    <div class="class-grid">
                        <?php foreach ($active_classes as $class): ?>
                            <div class="class-card slide-up">
                                <div class="class-header">
                                    <h3 class="class-title">
                                        <i class="fas fa-book"></i>
                                        <?php echo htmlspecialchars($class['subject_name']); ?>
                                    </h3>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-circle"></i> Active
                                    </span>
                                </div>

                                <div class="class-details">
                                    <div class="detail-item">
                                        <i class="fas fa-hashtag"></i>
                                        <strong>Code:</strong> <?php echo htmlspecialchars($class['class_code']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <strong>Schedule:</strong> <?php echo htmlspecialchars($class['schedule']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <strong>Room:</strong> <?php echo htmlspecialchars($class['room']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                        <input type="hidden" name="action" value="archive">
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to archive this class?')">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    </form>
                                    <a href="professor_dashboard.php" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>No Active Classes</h3>
                        <p>You don't have any active classes to archive at the moment.</p>
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
                    <div class="archive-group">
                        <div class="archive-group-header" onclick="toggleArchiveGroup(this)">
                            <h4>
                                <i class="fas fa-folder"></i>
                                <?php echo htmlspecialchars($year); ?> - <?php echo htmlspecialchars($semester); ?> Semester
                            </h4>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="archive-group-content">
                            <?php if ($year == '2025-2026' && $semester == '1st Semester'): ?>
                                <div class="bulk-actions" style="margin-bottom: 1.5rem;">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="unarchive_all_1st">
                                        <button type="button" class="btn btn-success" onclick="showUnarchiveConfirmModal()">
                                            <i class="fas fa-undo"></i> Unarchive All Classes
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <div class="class-grid">
                                <?php foreach ($classes_group as $class): ?>
                                    <div class="class-card slide-up">
                                        <div class="class-header">
                                            <h3 class="class-title">
                                                <i class="fas fa-archive"></i>
                                                <?php echo htmlspecialchars($class['subject_name']); ?>
                                            </h3>
                                            <span class="status-badge status-archived">
                                                <i class="fas fa-circle"></i> Archived
                                            </span>
                                        </div>

                                        <div class="class-details">
                                            <div class="detail-item">
                                                <i class="fas fa-hashtag"></i>
                                                <strong>Code:</strong> <?php echo htmlspecialchars($class['class_code']); ?>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <strong>Schedule:</strong> <?php echo htmlspecialchars($class['schedule']); ?>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <strong>Room:</strong> <?php echo htmlspecialchars($class['room']); ?>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-users"></i>
                                                <strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?>
                                            </div>
                                        </div>

                                        <div class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="action" value="unarchive">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to unarchive this class?')">
                                                    <i class="fas fa-undo"></i> Unarchive
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php
                        endforeach;
                    endforeach;
                else:
                ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-archive"></i>
                        </div>
                        <h3>No Archived Classes</h3>
                        <p>You don't have any archived classes yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Archive Confirmation Modal -->
    <div id="archiveConfirmModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm Archive
                </h3>
                <button class="modal-close" onclick="closeArchiveConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-text">
                    Are you sure you want to archive all classes for the 2025-2026 1st Semester? This action will move all active classes to the archived section.
                </div>
                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="closeArchiveConfirmModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-warning" onclick="confirmArchiveAll()">
                        <i class="fas fa-archive"></i> Archive All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unarchive Confirmation Modal -->
    <div id="unarchiveConfirmModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-undo"></i>
                    Confirm Unarchive
                </h3>
                <button class="modal-close" onclick="closeUnarchiveConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-text">
                    Are you sure you want to unarchive all classes for the 2025-2026 1st Semester? This action will restore all archived classes to active status.
                </div>
                <div class="modal-actions">
                    <button class
