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

// Get professor's classes with school year semester information
$query = "SELECT s.*, c.class_id, c.class_code, c.schedule, c.room, c.section,
                 sys.school_year, sys.semester, sys.status as term_status
          FROM subjects s
          JOIN classes c ON s.subject_id = c.subject_id
          JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
          WHERE c.professor_id = ?
          ORDER BY sys.status ASC, sys.school_year DESC, sys.semester DESC, s.subject_name";
$stmt = $pdo->prepare($query);
$stmt->execute([$professor_id]);
$classes = $stmt->fetchAll();

// Handle archive/unarchive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'archive') {
            // Archive: Update the school_year_semester status to 'Archived'
            $stmt = $pdo->prepare("UPDATE school_year_semester sys
                                   JOIN classes c ON c.school_year_semester_id = sys.id
                                   SET sys.status = 'Archived'
                                   WHERE c.class_id = ? AND c.professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);
        } elseif ($action === 'unarchive') {
            // Unarchive: Update the school_year_semester status to 'Active'
            $stmt = $pdo->prepare("UPDATE school_year_semester sys
                                   JOIN classes c ON c.school_year_semester_id = sys.id
                                   SET sys.status = 'Active'
                                   WHERE c.class_id = ? AND c.professor_id = ?");
            $stmt->execute([$class_id, $professor_id]);
        }

        // Update the classes.status for backward compatibility
        $stmt = $pdo->prepare("UPDATE classes c
                               JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                               SET c.status = CASE WHEN sys.status = 'Archived' THEN 'archived' ELSE 'active' END
                               WHERE c.class_id = ?");
        $stmt->execute([$class_id]);

        // Redirect to refresh the page
        header('Location: archive_updated.php');
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
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

        .archive-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .archive-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            color: white;
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
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
        }

        .btn {
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
        }

        .no-classes-icon {
            font-size: 4rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
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
            .class-header {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons {
                justify-content: center;
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
            padding: 2rem;
            border-radius: 12px;
            max-width: 1200px;
            width: 100%;
            max-height: 90%;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .modal-close:hover {
            color: var(--dark);
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
            <button class="tab-btn active" onclick="showTab('active')">Active Classes</button>
            <button class="tab-btn" onclick="showTab('archived')">Archived Classes</button>
        </div>

            <div id="active-tab" class="tab-content active">
                <?php
                $active_classes = array_filter($classes, function($class) {
                    return $class['term_status'] !== 'Archived';
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
                                    <div class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($class['school_year'] . ' ' . $class['semester']); ?>
                                    </div>
                                </div>

                            </div>
                            <div class="action-buttons">
                                <span class="status-badge status-active"><?php echo htmlspecialchars($class['term_status']); ?></span>
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
                $archived_classes = array_filter($classes, function($class) {
                    return $class['term_status'] === 'Archived';
                });

                if (!empty($archived_classes)):
                    foreach ($archived_classes as $class):
                ?>
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
                                        <div class="detail-item">
                                            <i class="fas fa-graduation-cap"></i>
                                            <?php echo htmlspecialchars($class['school_year'] . ' ' . $class['semester']); ?>
                                        </div>
                                    </div>
                                    <div class="class-students">
                                        <h4>Enrolled Students</h4>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT st.first_name, st.last_name, st.email FROM class_enrollments ce JOIN students st ON ce.student_id = st.student_id WHERE ce.class_id = ?");
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
                                </div>
                                <div class="action-buttons">
                                    <span class="status-badge status-archived"><?php echo htmlspecialchars($class['term_status']); ?></span>
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
                <?php
                    endforeach;
                else:
                ?>
                    <div class="no-classes">
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

    <!-- Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div id="modalBody"></div>
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
                    document.getElementById('modalBody').innerHTML = data;
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
    </script>
</body>
</html>
