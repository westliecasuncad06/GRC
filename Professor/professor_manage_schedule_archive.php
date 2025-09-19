<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

// Handle archive/unarchive action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $professor_id = $_SESSION['user_id'];

    if ($action === 'archive' && isset($_POST['class_id'])) {
        $class_id = $_POST['class_id'];
        try {
            // Archive: Update the school_year_semester status to 'Archived'
            $stmt = $pdo->prepare("UPDATE school_year_semester sys
                                   JOIN classes c ON c.school_year_semester_id = sys.id
                                   SET sys.status = 'Archived'
                                   WHERE c.class_id = ? AND c.professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);

            // Update the classes.status for backward compatibility
            $stmt = $pdo->prepare("UPDATE classes c
                                   JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                                   SET c.status = 'archived'
                                   WHERE c.class_id = ?");
            $stmt->execute([$class_id]);

            $success = "Class archived successfully!";
        } catch (PDOException $e) {
            $error = "Error archiving class: " . $e->getMessage();
        }
    } elseif ($action === 'unarchive' && isset($_POST['class_id'])) {
        $class_id = $_POST['class_id'];
        try {
            // Unarchive: Update the school_year_semester status to 'Active'
            $stmt = $pdo->prepare("UPDATE school_year_semester sys
                                   JOIN classes c ON c.school_year_semester_id = sys.id
                                   SET sys.status = 'Active'
                                   WHERE c.class_id = ? AND c.professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);

            // Update the classes.status for backward compatibility
            $stmt = $pdo->prepare("UPDATE classes c
                                   JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                                   SET c.status = 'active'
                                   WHERE c.class_id = ?");
            $stmt->execute([$class_id]);

            $success = "Class unarchived successfully!";
        } catch (PDOException $e) {
            $error = "Error unarchiving class: " . $e->getMessage();
        }
    }

    // Redirect to refresh the page
    header('Location: professor_manage_schedule_archive.php');
    exit();
}

// Get professor's subjects (both active and archived)
$professor_id = $_SESSION['user_id'];
$query = "SELECT s.*, c.class_id, c.class_code, c.schedule, c.room, c.section, c.status,
                 sys.school_year, sys.semester, sys.status as term_status
          FROM subjects s
          JOIN classes c ON s.subject_id = c.subject_id
          JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
          WHERE c.professor_id = ?
          ORDER BY sys.status ASC, s.subject_name ASC, c.section ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$subjects = $stmt->fetchAll();

// Get enrolled students count for each subject
$enrollment_counts = [];
foreach ($subjects as $subject) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_enrollments ce
                          JOIN classes c ON ce.class_id = c.class_id
                          WHERE c.subject_id = ?");
    $stmt->execute([$subject['subject_id']]);
    $enrollment_counts[$subject['subject_id']] = $stmt->fetch()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Global Reciprocal College</title>
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

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
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

        .stat-details-enhanced {
            margin-bottom: 1.5rem;
        }

        .stat-detail-enhanced {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-detail-enhanced:last-child {
            border-bottom: none;
        }

        .stat-detail-icon {
            font-size: 1.1rem;
            color: var(--primary);
            width: 20px;
        }

        .stat-detail-text {
            font-size: 0.9rem;
            color: var(--dark);
            font-weight: 500;
        }

        .stat-actions-enhanced {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 1.5rem;
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
            margin-right: 0.5rem;
        }

        .stat-action-btn:hover {
            background: var(--primary-dark);
        }

        .stat-action-btn.secondary {
            background: var(--secondary);
        }

        .stat-action-btn.secondary:hover {
            background: var(--secondary-dark);
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
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-input-enhanced {
            padding: 0.75rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            width: 300px;
            transition: all 0.2s ease;
        }

        .search-input-enhanced::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input-enhanced:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
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

        .view-btn {
            margin-right: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .view-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .view-btn.active {
            background: rgba(255, 255, 255, 0.4);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .sections-view, .archive-view {
            display: none;
        }

        .sections-grid, .archive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-card, .archive-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUpCard 0.6s ease-out forwards;
        }

        .section-card:nth-child(1), .archive-card:nth-child(1) { animation-delay: 0.1s; }
        .section-card:nth-child(2), .archive-card:nth-child(2) { animation-delay: 0.2s; }
        .section-card:nth-child(3), .archive-card:nth-child(3) { animation-delay: 0.3s; }
        .section-card:nth-child(4), .archive-card:nth-child(4) { animation-delay: 0.4s; }
        .section-card:nth-child(5), .archive-card:nth-child(5) { animation-delay: 0.5s; }
        .section-card:nth-child(6), .archive-card:nth-child(6) { animation-delay: 0.6s; }
        .section-card:nth-child(7), .archive-card:nth-child(7) { animation-delay: 0.7s; }
        .section-card:nth-child(8), .archive-card:nth-child(8) { animation-delay: 0.8s; }

        @keyframes fadeInUpCard {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-card:hover, .archive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .section-header, .archive-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-icon, .archive-icon {
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

        .section-info, .archive-info {
            flex: 1;
        }

        .section-title, .archive-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 0.25rem 0;
        }

        .section-subtitle, .archive-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        .section-classes, .archive-classes {
            margin-bottom: 1.5rem;
        }

        .section-class-item, .archive-class-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .section-class-item:hover, .archive-class-item:hover {
            background-color: rgba(0, 123, 255, 0.05);
            border-radius: 6px;
        }

        .section-class-item:last-child, .archive-class-item:last-child {
            border-bottom: none;
        }

        .section-class-icon, .archive-class-icon {
            font-size: 1.1rem;
            color: var(--primary);
            width: 20px;
        }

        .section-class-text, .archive-class-text {
            font-size: 0.9rem;
            color: var(--dark);
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 3rem 1rem;
            font-style: italic;
        }

        .success-message, .error-message {
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

        .archive-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-archive, .btn-unarchive {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s ease;
        }

        .btn-archive {
            background: var(--warning);
            color: white;
        }

        .btn-archive:hover {
            background: #e0a800;
        }

        .btn-unarchive {
            background: var(--success);
            color: white;
        }

        .btn-unarchive:hover {
            background: #218838;
        }

        @media (max-width: 768px) {
            .stat-card-enhanced {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar_professor.php'; ?>

    <!-- Sidebar -->
    <?php include '../includes/sidebar_professor.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-calendar-alt" style="margin-right: 10px;"></i>Manage My Classes</h2>
            <div class="table-actions-enhanced">
                <input type="search" id="searchInput" class="search-input-enhanced" placeholder="Search subjects or sections..." aria-label="Search subjects or sections" onkeyup="filterSubjects()">
                <button class="stat-primary-btn view-btn active" id="allSubjectsBtn" onclick="switchView('allSubjects')">
                    <i class="fas fa-book"></i> All Subjects
                </button>
                <button class="stat-primary-btn view-btn" id="sectionsBtn" onclick="switchView('sections')">
                    <i class="fas fa-users"></i> Sections
                </button>
                <button class="stat-primary-btn view-btn" id="archiveBtn" onclick="switchView('archive')">
                    <i class="fas fa-archive"></i> Archive
                </button>
            </div>
        </div>

        <div class="dashboard-container">
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($subjects)): ?>
                <div class="no-data">
                    <p>No classes found. Create your first class to get started.</p>
                    <button class="stat-primary-btn" onclick="openModal('addSubjectModal')">
                        <i class="fas fa-plus"></i> Add New Class
                    </button>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <?php foreach ($subjects as $subject): ?>
                    <div class="stat-card-enhanced" onclick="viewClassDetails('<?php echo $subject['class_id']; ?>', '<?php echo $subject['subject_name']; ?>')">
                        <div class="stat-header-enhanced">
                            <div class="stat-icon-enhanced">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-info-enhanced">
                                <h3 class="stat-title-enhanced"><?php echo $subject['subject_name']; ?></h3>
                                <span class="stat-subtitle-enhanced"><?php echo $subject['class_code']; ?> • <?php echo $subject['subject_code']; ?> • <?php echo $subject['school_year'] . ' ' . $subject['semester']; ?></span>
                            </div>
                        </div>

                        <div class="stat-details-enhanced">
                            <div class="stat-detail-enhanced">
                                <i class="fas fa-calendar stat-detail-icon"></i>
                                <span class="stat-detail-text"><?php echo $subject['schedule']; ?></span>
                            </div>
                            <div class="stat-detail-enhanced">
                                <i class="fas fa-map-marker-alt stat-detail-icon"></i>
                                <span class="stat-detail-text"><?php echo $subject['room']; ?></span>
                            </div>
                            <div class="stat-detail-enhanced">
                                <i class="fas fa-users stat-detail-icon"></i>
                                <span class="stat-detail-text"><?php echo $enrollment_counts[$subject['subject_id']] ?? 0; ?> students enrolled</span>
                            </div>
                            <div class="stat-detail-enhanced">
                                <i class="fas fa-graduation-cap stat-detail-icon"></i>
                                <span class="stat-detail-text"><?php echo $subject['term_status']; ?></span>
                            </div>
                        </div>

                        <div class="stat-actions-enhanced">
                            <button class="stat-action-btn" onclick="event.stopPropagation(); openAttendanceModal('<?php echo $subject['class_id']; ?>', '<?php echo $subject['subject_name']; ?>')">
                                <i class="fas fa-clipboard-check"></i> Take Attendance
                            </button>
                            <button class="stat-action-btn secondary" onclick="event.stopPropagation(); regenerateCode('<?php echo $subject['subject_id']; ?>')">
                                <i class="fas fa-sync-alt"></i> Regenerate Code
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sections View -->
                <div class="sections-view" id="sectionsView">
                    <div class="sections-grid">
                        <?php
                        // Group subjects by section
                        $subjectsBySection = [];
                        foreach ($subjects as $subject) {
                            $section = $subject['section'] ?? 'No Section';
                            if (!isset($subjectsBySection[$section])) {
                                $subjectsBySection[$section] = [];
                            }
                            $subjectsBySection[$section][] = $subject;
                        }

                        foreach ($subjectsBySection as $section => $sectionSubjects):
                        ?>
                        <div class="section-card">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="section-info">
                                    <h3 class="section-title"><?php echo htmlspecialchars($section); ?></h3>
                                    <span class="section-subtitle"><?php echo count($sectionSubjects); ?> classes</span>
                                </div>
                            </div>

                            <div class="section-classes">
                                <?php foreach ($sectionSubjects as $subject): ?>
                                <div class="section-class-item" onclick="openAttendanceModal('<?php echo $subject['class_id']; ?>', '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                    <i class="fas fa-book section-class-icon"></i>
                                    <span class="section-class-text"><?php echo htmlspecialchars($subject['subject_name']); ?> (<?php echo htmlspecialchars($subject['subject_code']); ?>)</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Archive View -->
                <div class="archive-view" id="archiveView">
                    <div class="archive-grid">
                        <?php
                        // Group subjects by archive status
                        $activeSubjects = array_filter($subjects, function($s) { return $s['term_status'] !== 'Archived'; });
                        $archivedSubjects = array_filter($subjects, function($s) { return $s['term_status'] === 'Archived'; });

                        // Show Active Classes
                        if (!empty($activeSubjects)):
                        ?>
                        <div class="archive-card">
                            <div class="archive-header">
                                <div class="archive-icon">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="archive-info">
                                    <h3 class="archive-title">Active Classes</h3>
                                    <span class="archive-subtitle"><?php echo count($activeSubjects); ?> active classes</span>
                                </div>
                            </div>

                            <div class="archive-classes">
                                <?php foreach ($activeSubjects as $subject): ?>
                                <div class="archive-class-item">
                                    <i class="fas fa-book archive-class-icon"></i>
                                    <span class="archive-class-text"><?php echo htmlspecialchars($subject['subject_name']); ?> (<?php echo htmlspecialchars($subject['subject_code']); ?>) - <?php echo htmlspecialchars($subject['school_year'] . ' ' . $subject['semester']); ?></span>
                                    <div class="archive-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="class_id" value="<?php echo $subject['class_id']; ?>">
                                            <input type="hidden" name="action" value="archive">
                                            <button type="submit" class="btn-archive" onclick="return confirm('Are you sure you want to archive this class?')">
                                                <i class="fas fa-archive"></i> Archive
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Show Archived Classes -->
                        <?php if (!empty($archivedSubjects)): ?>
                        <div class="archive-card">
                            <div class="archive-header">
                                <div class="archive-icon">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <div class="archive-info">
                                    <h3 class="archive-title">Archived Classes</h3>
                                    <span class="archive-subtitle"><?php echo count($archivedSubjects); ?> archived classes</span>
                                </div>
                            </div>

                            <div class="archive-classes">
                                <?php foreach ($archivedSubjects as $subject): ?>
                                <div class="archive-class-item">
                                    <i class="fas fa-book archive-class-icon"></i>
                                    <span class="archive-class-text"><?php echo htmlspecialchars($subject['subject_name']); ?> (<?php echo htmlspecialchars($subject['subject_code']); ?>) - <?php echo htmlspecialchars($subject['school_year'] . ' ' . $subject['semester']); ?></span>
                                    <div class="archive-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="class_id" value="<?php echo $subject['class_id']; ?>">
                                            <input type="hidden" name="action" value="unarchive">
                                            <button type="submit" class="btn-unarchive" onclick="return confirm('Are you sure you want to unarchive this class?')">
                                                <i class="fas fa-undo"></i> Unarchive
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function switchView(view) {
            const allSubjectsBtn = document.getElementById('allSubjectsBtn');
            const sectionsBtn = document.getElementById('sectionsBtn');
            const archiveBtn = document.getElementById('archiveBtn');
            const statsGrid = document.querySelector('.stats-grid');
            const sectionsView = document.getElementById('sectionsView');
            const archiveView = document.getElementById('archiveView');

            if (view === 'allSubjects') {
                allSubjectsBtn.classList.add('active');
                sectionsBtn.classList.remove('active');
                archiveBtn.classList.remove('active');
                statsGrid.style.display = 'grid';
                sectionsView.style.display = 'none';
                archiveView.style.display = 'none';
            } else if (view === 'sections') {
                sectionsBtn.classList.add('active');
                allSubjectsBtn.classList.remove('active');
                archiveBtn.classList.remove('active');
                statsGrid.style.display = 'none';
                sectionsView.style.display = 'block';
                archiveView.style.display = 'none';

                // Reset animation for section cards
                const sectionCards = document.querySelectorAll('.section-card');
                sectionCards.forEach(card => {
                    card.style.animation = 'none';
                    card.offsetHeight; // Trigger reflow
                    card.style.animation = '';
                });
            } else if (view === 'archive') {
                archiveBtn.classList.add('active');
                allSubjectsBtn.classList.remove('active');
                sectionsBtn.classList.remove('active');
                statsGrid.style.display = 'none';
                sectionsView.style.display = 'none';
                archiveView.style.display = 'block';

                // Reset animation for archive cards
                const archiveCards = document.querySelectorAll('.archive-card');
                archiveCards.forEach(card => {
                    card.style.animation = 'none';
                    card.offsetHeight; // Trigger reflow
                    card.style.animation = '';
                });
            }
        }

        function filterSubjects() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const allSubjectsBtn = document.getElementById('allSubjectsBtn');
            const sectionsBtn = document.getElementById('sectionsBtn');
            const archiveBtn = document.getElementById('archiveBtn');
            const statsGrid = document.querySelector('.stats-grid');
            const sectionsView = document.getElementById('sectionsView');
            const archiveView = document.getElementById('archiveView');

            if (allSubjectsBtn.classList.contains('active')) {
                // Filter subjects in allSubjects view
                const subjectCards = statsGrid.querySelectorAll('.stat-card-enhanced');
                subjectCards.forEach(card => {
                    const title = card.querySelector('.stat-title-enhanced').textContent.toLowerCase();
                    const subtitle = card.querySelector('.stat-subtitle-enhanced').textContent.toLowerCase();
                    if (title.includes(query) || subtitle.includes(query)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            } else if (sectionsBtn.classList.contains('active')) {
                // Filter sections in sections view
                const sectionCards = sectionsView.querySelectorAll('.section-card');
                sectionCards.forEach(card => {
                    const sectionTitle = card.querySelector('.section-title').textContent.toLowerCase();
                    if (sectionTitle.includes(query)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            } else if (archiveBtn.classList.contains('active')) {
                // Filter archive cards
                const archiveCards = archiveView.querySelectorAll('.archive-card');
                archiveCards.forEach(card => {
                    const archiveTitle = card.querySelector('.archive-title').textContent.toLowerCase();
                    if (archiveTitle.includes(query)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        }

        // Hamburger menu toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
            if (window.innerWidth <= 900) {
                document.body.classList.toggle('sidebar-open');
            }
        });

        // Close sidebar when clicking outside on mobile
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
