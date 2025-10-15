<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

// Handle form submission for creating subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $description = trim($_POST['description']);
    $credits = (int)$_POST['credits'];
    $semester_id = $_POST['semester_id'];

    if (empty($subject_name) || empty($subject_code) || empty($semester_id)) {
        $error = 'Subject name, code, and semester are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description, credits, semester_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$subject_name, $subject_code, $description, $credits, $semester_id]);
            $success = 'Subject created successfully.';
        } catch (PDOException $e) {
            $error = 'Error creating subject: ' . $e->getMessage();
        }
    }
}

// Fetch all subjects with semester and school year info
try {
    $stmt = $pdo->prepare("
        SELECT s.*, sem.semester_name, y.year_label
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

// Fetch semester options for dropdown
try {
    $stmt = $pdo->prepare("
        SELECT s.id, CONCAT(y.year_label, ' - ', s.semester_name) AS label
        FROM semesters s
        JOIN school_years y ON s.school_year_id = y.id
        WHERE s.status = 'Active' AND y.status = 'Active'
        ORDER BY y.year_label DESC, s.semester_name
    ");
    $stmt->execute();
    $semester_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $semester_options = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Global Reciprocal Colleges</title>
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

        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            min-height: 100px;
            resize: vertical;
            transition: border-color 0.3s ease;
        }

        .form-textarea:focus {
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
            <h1 class="page-title"><i class="fas fa-book"></i> Manage Subjects</h1>
            <button class="btn-create" onclick="toggleCreateForm()">
                <i class="fas fa-plus"></i> Create Subject
            </button>
        </div>

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

        <div class="content-card" id="createForm" style="display: none;">
            <h3><i class="fas fa-plus-circle"></i> Create New Subject</h3>
            <form method="POST">
                <input type="hidden" name="create_subject" value="1">
                <div class="form-group">
                    <label class="form-label" for="subject_name">Subject Name *</label>
                    <input type="text" id="subject_name" name="subject_name" class="form-input" placeholder="e.g., Introduction to Programming" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="subject_code">Subject Code *</label>
                    <input type="text" id="subject_code" name="subject_code" class="form-input" placeholder="e.g., CS101" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-textarea" placeholder="Brief description of the subject"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="credits">Credits</label>
                    <input type="number" id="credits" name="credits" class="form-input" value="3" min="1" max="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="semester_id">Semester *</label>
                    <select id="semester_id" name="semester_id" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semester_options as $option): ?>
                            <option value="<?php echo $option['id']; ?>"><?php echo htmlspecialchars($option['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Create Subject
                </button>
            </form>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-list"></i> Existing Subjects</h3>
            <?php if (empty($subjects)): ?>
                <div class="no-data">
                    <p>No subjects found. Create your first subject above.</p>
                </div>
            <?php else: ?>
                <div class="subject-grid">
                    <?php foreach ($subjects as $subject): ?>
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
    </main>

    <script>
        function toggleCreateForm() {
            const form = document.getElementById('createForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
