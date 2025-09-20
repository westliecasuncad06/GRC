<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Get archived classes for the student
$query = "SELECT c.*, s.subject_name, p.first_name, p.last_name, sys.school_year, sys.semester
          FROM student_classes sc
          JOIN classes c ON sc.class_id = c.class_id
          JOIN subjects s ON c.subject_id = s.subject_id
          JOIN professors p ON c.professor_id = p.professor_id
          JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
          WHERE sc.student_id = ? AND sys.status = 'Archived'
          ORDER BY sys.school_year DESC, sys.semester DESC, s.subject_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute([$student_id]);
$archived_classes = $stmt->fetchAll();

// Group by school year and semester
$grouped_classes = [];
foreach ($archived_classes as $class) {
    $key = $class['school_year'] . ' - ' . $class['semester'];
    if (!isset($grouped_classes[$key])) {
        $grouped_classes[$key] = [];
    }
    $grouped_classes[$key][] = $class;
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

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
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

        .archive-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .archive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .archive-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .archive-icon {
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

        .archive-info {
            flex: 1;
        }

        .archive-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 0.25rem 0;
        }

        .archive-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        .archive-classes {
            margin-bottom: 1.5rem;
        }

        .archive-class-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .archive-class-item:hover {
            background-color: rgba(0, 123, 255, 0.05);
            border-radius: 6px;
        }

        .archive-class-item:last-child {
            border-bottom: none;
        }

        .archive-class-icon {
            font-size: 1.1rem;
            color: var(--primary);
            width: 20px;
        }

        .archive-class-text {
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

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .archive-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-archive" style="margin-right: 10px;"></i>My Archived Classes</h2>
        </div>

        <div class="dashboard-container">
            <?php if (empty($grouped_classes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="empty-state-text">No archived classes found.</div>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_classes as $term => $classes): ?>
                    <div class="archive-card">
                        <div class="archive-header">
                            <div class="archive-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="archive-info">
                                <h3 class="archive-title"><?php echo htmlspecialchars($term); ?></h3>
                                <span class="archive-subtitle"><?php echo count($classes); ?> archived classes</span>
                            </div>
                        </div>

                        <div class="archive-classes">
                            <?php foreach ($classes as $class): ?>
                                <div class="archive-class-item">
                                    <i class="fas fa-book archive-class-icon"></i>
                                    <span class="archive-class-text">
                                        <?php echo htmlspecialchars($class['subject_name']); ?> (<?php echo htmlspecialchars($class['subject_code']); ?>) - 
                                        Prof. <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?> - 
                                        <?php echo htmlspecialchars($class['schedule']); ?> - 
                                        <?php echo htmlspecialchars($class['room']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
