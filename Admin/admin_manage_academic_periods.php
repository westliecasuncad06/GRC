<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

// Handle form submission for creating academic period
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_academic_period'])) {
    $school_year = trim($_POST['school_year']);
    $semester = $_POST['semester'];
    $status = $_POST['status'] ?? 'Active';

    if (empty($school_year) || empty($semester)) {
        $error = 'School year and semester are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO school_year_semester (school_year, semester, status) VALUES (?, ?, ?)");
            $stmt->execute([$school_year, $semester, $status]);
            $success = 'Academic period created successfully.';
        } catch (PDOException $e) {
            $error = 'Error creating academic period: ' . $e->getMessage();
        }
    }
}

// Handle archiving academic period
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_period'])) {
    $period_id = $_POST['period_id'];
    try {
        // Archive the academic period
        $stmt = $pdo->prepare("UPDATE school_year_semester SET status = 'Archived' WHERE id = ?");
        $stmt->execute([$period_id]);

        // Archive all classes associated with this academic period
        $stmt = $pdo->prepare("UPDATE classes SET status = 'archived' WHERE school_year_semester_id = ?");
        $stmt->execute([$period_id]);

        $success = 'Academic period and all associated subjects archived successfully.';
    } catch (PDOException $e) {
        $error = 'Error archiving academic period: ' . $e->getMessage();
    }
}

// Handle unarchiving academic period
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unarchive_period'])) {
    $period_id = $_POST['period_id'];
    try {
        // Unarchive the academic period
        $stmt = $pdo->prepare("UPDATE school_year_semester SET status = 'Active' WHERE id = ?");
        $stmt->execute([$period_id]);

        // Unarchive all classes associated with this academic period
        $stmt = $pdo->prepare("UPDATE classes SET status = 'active' WHERE school_year_semester_id = ?");
        $stmt->execute([$period_id]);

        $success = 'Academic period and all associated subjects unarchived successfully.';
    } catch (PDOException $e) {
        $error = 'Error unarchiving academic period: ' . $e->getMessage();
    }
}

// Fetch all academic periods
try {
    $stmt = $pdo->query("SELECT * FROM school_year_semester ORDER BY school_year DESC, semester");
    $academic_periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching academic periods: ' . $e->getMessage();
    $academic_periods = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Periods - Global Reciprocal Colleges</title>
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
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .tab-button {
            background: #e9ecef;
            border: none;
            padding: 0.75rem 1.5rem;
            margin: 0 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-create {
            background: white;
            color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-create:hover {
            background: var(--light);
            transform: translateY(-2px);
        }

        .btn-archive {
            background: var(--warning);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-archive:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .btn-unarchive {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-unarchive:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-archived {
            background: #e2e3e5;
            color: #383d41;
        }

        .card-meta {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 3rem;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .tabs {
                flex-direction: column;
                align-items: center;
            }

            .tab-button {
                margin: 0.5rem 0;
                width: 200px;
            }

            .grid {
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
            <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Manage Academic Periods</h1>
        </div>

        <!-- Academic Periods Content -->
        <div class="content-card">
            <h3><i class="fas fa-plus-circle"></i> Create New Academic Period</h3>
            <form method="POST">
                <input type="hidden" name="create_academic_period" value="1">
                <div class="form-group">
                    <label class="form-label" for="school_year">School Year *</label>
                    <input type="text" id="school_year" name="school_year" class="form-input" placeholder="e.g., 2024-2025" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="semester">Semester *</label>
                    <select id="semester" name="semester" class="form-select" required>
                        <option value="">Select Semester</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Archived">Archived</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Create Academic Period
                </button>
            </form>
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-list"></i> Existing Academic Periods</h3>
            <?php if (empty($academic_periods)): ?>
                <div class="no-data">
                    <p>No academic periods found. Create your first academic period above.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($academic_periods as $period): ?>
                        <div class="card">
                            <div class="card-title"><?php echo htmlspecialchars($period['school_year'] . ' ' . $period['semester']); ?></div>
                            <span class="status status-<?php echo strtolower($period['status']); ?>">
                                <?php echo $period['status']; ?>
                            </span>
                            <div class="card-meta">
                                Created: <?php echo date('M d, Y', strtotime($period['created_at'])); ?>
                            </div>
                            <?php if ($period['status'] === 'Archived'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="unarchive_period" value="1">
                                    <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                    <button type="submit" class="btn-unarchive" onclick="return confirm('Are you sure you want to unarchive this academic period?')">
                                        <i class="fas fa-undo"></i> Unarchive
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="archive_period" value="1">
                                    <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                    <button type="submit" class="btn-archive" onclick="return confirm('Are you sure you want to archive this academic period?')">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function showTab(tabId) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));

            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
