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
        $stmt = $pdo->prepare("SELECT school_year, semester FROM school_year_semester WHERE status = 'active'");
        $stmt->execute();
        $active_term = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($active_term) {
            $school_year = $active_term['school_year'];
            $semester = $active_term['semester'];

            // Archive all classes for the active school year and semester for this professor
            $archiveQuery = "UPDATE classes c
                             JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                             SET c.status = 'archived'
                             WHERE c.professor_id = ? AND sys.school_year = ? AND sys.semester = ?";
            $stmt = $pdo->prepare($archiveQuery);
            $stmt->execute([$professor_id, $school_year, $semester]);

            header('Location: archive.php');
            exit();
        } else {
            // Handle the case where no active school year and semester are found
            echo "No active school year and semester found.";
            exit();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'unarchive_all_1st') {
        // Unarchive all classes for 1st semester 2025-2026 for this professor
        $unarchiveQuery = "UPDATE classes c
                         JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                         SET c.status = 'active'
                         WHERE c.professor_id = ? AND sys.school_year = '2025-2026' AND sys.semester = '1st Semester'";
        $stmt = $pdo->prepare($unarchiveQuery);
        $stmt->execute([$professor_id]);

        header('Location: archive.php');
        exit();
    } elseif (isset($_POST['action']) && isset($_POST['class_id'])) {
        $class_id = $_POST['class_id'];
        $action = $_POST['action'];

        if ($action === 'archive') {
            $stmt = $pdo->prepare("UPDATE classes SET status = 'archived' WHERE class_id = ? AND professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);
        } elseif ($action === 'unarchive') {
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
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
        }

        .archive-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .archive-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            color: white;
            text-align: center;
            width: 100%;
        }

        .archive-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .archive-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0.5rem 0 0 0;
        }

        .class-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .class-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--dark);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .class-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .detail-item i {
            width: 16px;
            color: var(--primary);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-archived {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-dark);
        }

        .no-classes {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray);
            width: 100%;
        }

        .no-classes-icon {
            font-size: 4rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .tabs {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(247, 82, 112, 0.3);
        }

        .tab-content {
            display: none;
            width: 100%;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .class-header {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons {
                justify-content: center;
            }

            .tabs {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
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
            color: white;
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
            grid-template-columns: 1fr 2fr 1fr 2fr;
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
            grid-template-columns: 1fr 2fr 1fr 2fr;
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
    </style>
    <style>
        .collapse-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: background-color 0.3s ease;
        }

        .collapse-btn:hover {
            background-color: var(--primary-dark);
        }

        .collapse-btn i {
            transition: transform 0.3s ease;
        }

        .collapse-btn.collapsed i {
            transform: rotate(-180deg);
        }

        .collapse-content {
            margin-top: 1rem;
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
                <button class="tab-btn active" onclick="showTab('active')">Active Classes</button>
                <button class="tab-btn" onclick="showTab('archived')">Archived Classes</button>
            </div>
        </div>

            <div id="active-tab" class="tab-content active">
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="archive_all_2025_1st">
                    <button type="button" class="btn btn-archive" style="padding: 0.75rem 1.5rem; font-size: 0.9rem; border-radius: 8px;" onclick="showArchiveConfirmModal()">
                        <i class="fas fa-archive"></i> Archive All 2025-2026 1st Semester
                    </button>
                </form>
                <?php
                $active_classes = array_filter($classes, function($class) {
                    return $class['status'] !== 'archived';
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
                                <a href="professor_dashboard.php" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
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
                            <?php if ($year == '2025-2026' && $semester == '1st Semester'): ?>
                            <form method="POST" style="margin-bottom: 1rem;">
                                <input type="hidden" name="action" value="unarchive_all_1st">
                            </form>
                            <button type="button" class="btn btn-unarchive" style="padding: 0.75rem 1.5rem; font-size: 0.9rem; border-radius: 8px;" onclick="showUnarchiveConfirmModal()">
                                <i class="fas fa-undo"></i> Unarchive All 1st Semester
                            </button>
                            <?php endif; ?>
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
                                            <span class="status-badge status-archived">Archived</span>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="action" value="unarchive">
                                                <button type="submit" class="btn btn-unarchive" onclick="return confirm('Are you sure you want to unarchive this class?')">
                                                    <i class="fas fa-undo"></i> Unarchive
                                                </button>
                                            </form>
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

    <script>
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
            event.target.classList.add('active');
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

        // Modal functions
        function openModal(classId) {
            fetch('archive_details.php?class_id=' + classId + '&modal=1')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('archiveModal').querySelector('.modal-content').innerHTML = data;
                    document.getElementById('archiveModal').style.display = 'flex';
                })
                .catch(error => console.error('Error loading modal content:', error));
        }

        function closeModal() {
            document.getElementById('archiveModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('archiveModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Collapse toggle function
