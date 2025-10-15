<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

// Fetch all subjects with semester and school year status
try {
    $stmt = $pdo->prepare("
        SELECT s.*, sem.semester_name, sem.status as semester_status, y.year_label, y.status as year_status
        FROM subjects s
        LEFT JOIN semesters sem ON s.semester_id = sem.id
        LEFT JOIN school_years y ON sem.school_year_id = y.id
        ORDER BY y.year_label DESC, sem.semester_name, s.subject_name
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching subjects: ' . $e->getMessage();
    $subjects = [];
}

// Group subjects by status
$active_subjects = [];
$archived_subjects = [];

foreach ($subjects as $subject) {
    $is_active = ($subject['semester_status'] === 'Active' && $subject['year_status'] === 'Active');
    if ($is_active) {
        $active_subjects[] = $subject;
    } else {
        $archived_subjects[] = $subject;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Archive - Global Reciprocal Colleges</title>
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

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .tabs {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0;
            margin-bottom: 2rem;
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .tab-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--gray);
        }

        .tab-btn:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
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

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .subject-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .subject-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .subject-code {
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .subject-description {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .subject-semester {
            font-size: 0.85rem;
            color: var(--info);
            margin-bottom: 0.5rem;
        }

        .subject-credits {
            font-size: 0.9rem;
            color: var(--success);
            font-weight: 600;
        }

        .subject-meta {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.5rem;
            border-top: 1px solid #e9ecef;
            padding-top: 0.5rem;
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 3rem;
            font-style: italic;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .subject-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-book"></i> Subjects Archive</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('active')">Active Subjects</button>
            <button class="tab-btn" onclick="showTab('archived')">Archived Subjects</button>
        </div>

        <div id="active-tab" class="tab-content active">
            <div class="content-card">
                <h3><i class="fas fa-check-circle"></i> Active Subjects</h3>
                <?php if (empty($active_subjects)): ?>
                    <div class="no-data">
                        <p>No active subjects found.</p>
                    </div>
                <?php else: ?>
                    <div class="subject-grid">
                        <?php foreach ($active_subjects as $subject): ?>
                            <div class="subject-card">
                                <div class="subject-title"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                <div class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                                <div class="subject-description">
                                    <?php echo htmlspecialchars($subject['description'] ?: 'No description'); ?>
                                </div>
                                <div class="subject-semester">
                                    <?php echo htmlspecialchars(($subject['year_label'] ?? 'N/A') . ' - ' . ($subject['semester_name'] ?? 'N/A')); ?>
                                </div>
                                <div class="subject-credits"><?php echo $subject['credits']; ?> credits</div>
                                <div class="subject-meta">
                                    Created: <?php echo date('M d, Y', strtotime($subject['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="archived-tab" class="tab-content">
            <div class="content-card">
                <h3><i class="fas fa-archive"></i> Archived Subjects</h3>
                <?php if (empty($archived_subjects)): ?>
                    <div class="no-data">
                        <p>No archived subjects found.</p>
                    </div>
                <?php else: ?>
                    <div class="subject-grid">
                        <?php foreach ($archived_subjects as $subject): ?>
                            <div class="subject-card">
                                <div class="subject-title"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                <div class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                                <div class="subject-description">
                                    <?php echo htmlspecialchars($subject['description'] ?: 'No description'); ?>
                                </div>
                                <div class="subject-semester">
                                    <?php echo htmlspecialchars(($subject['year_label'] ?? 'N/A') . ' - ' . ($subject['semester_name'] ?? 'N/A')); ?>
                                </div>
                                <div class="subject-credits"><?php echo $subject['credits']; ?> credits</div>
                                <div class="subject-meta">
                                    Created: <?php echo date('M d, Y', strtotime($subject['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

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
    </script>
</body>
</html>
