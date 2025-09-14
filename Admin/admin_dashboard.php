<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Global Reciprocal Colleges</title>
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

        .dashboard-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .dashboard-title-enhanced {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .dashboard-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
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
        .recent-activities-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }
        .section-icon {
            font-size: 1.5rem;
            color: var(--primary);
            margin-right: 10px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s ease;
            border-left: 4px solid transparent;
        }
        .activity-item:hover {
            background-color: #f8f9fa;
            border-left-color: var(--primary);
        }
        .activity-icon {
            font-size: 1.2rem;
            color: var(--primary);
            margin-right: 15px;
            min-width: 20px;
        }
        .activity-content {
            flex: 1;
        }
        .activity-text {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        .activity-meta {
            font-size: 0.85rem;
            color: var(--gray);
        }
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        .fade-in-delayed {
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .dashboard-title-enhanced {
                font-size: 2rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .stats-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>

    <?php include '../includes/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-header-enhanced fade-in">
            <h1 class="dashboard-title-enhanced"><i class="fas fa-tachometer-alt" style="margin-right: 15px;"></i>Admin Dashboard</h1>
            <p class="dashboard-subtitle">Welcome back! Here's an overview of your college management system</p>
        </div>

        <!-- Dashboard Stats -->
        <div class="stats-grid">
            <?php
            // Get statistics
            $stats = [];

            // Total Students
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
            $stats['students'] = $stmt->fetch()['count'];

            // Total Professors
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM professors");
            $stats['professors'] = $stmt->fetch()['count'];

            // Total Classes
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
            $stats['classes'] = $stmt->fetch()['count'];
            ?>

            <div class="stats-card fade-in">
                <i class="fas fa-user-graduate stats-icon"></i>
                <div class="stats-number"><?php echo $stats['students']; ?></div>
                <div class="stats-label">Total Students</div>
            </div>

            <div class="stats-card fade-in-delayed">
                <i class="fas fa-chalkboard-teacher stats-icon"></i>
                <div class="stats-number"><?php echo $stats['professors']; ?></div>
                <div class="stats-label">Total Professors</div>
            </div>

            <div class="stats-card fade-in">
                <i class="fas fa-school stats-icon"></i>
                <div class="stats-number"><?php echo $stats['classes']; ?></div>
                <div class="stats-label">Total Classes</div>
            </div>

        </div>

        <!-- User Management Section -->
        <div class="recent-activities-section fade-in">
            <div class="section-header">
                <i class="fas fa-users-cog section-icon"></i>
                <h2 class="section-title">User Management</h2>
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <i class="fas fa-user-shield activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text"><a href="admin_manage_roles.php" style="color: inherit; text-decoration: none;">Manage User Roles</a></div>
                        <div class="activity-meta">View and change user roles across the system</div>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-user-plus activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text"><a href="admin_manage_students.php" style="color: inherit; text-decoration: none;">Manage Students</a></div>
                        <div class="activity-meta">Add, edit, or remove student accounts</div>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-chalkboard-teacher activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text"><a href="admin_manage_professors.php" style="color: inherit; text-decoration: none;">Manage Professors</a></div>
                        <div class="activity-meta">Add, edit, or remove professor accounts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="recent-activities-section fade-in">
            <div class="section-header">
                <i class="fas fa-history section-icon"></i>
                <h2 class="section-title">Recent Activities</h2>
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <i class="fas fa-user-plus activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text">New student registered</div>
                        <div class="activity-meta">2024-01-15 14:30 • John Doe</div>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-plus-circle activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text">Class created</div>
                        <div class="activity-meta">2024-01-15 13:45 • Prof. Smith</div>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-check-circle activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text">Attendance marked</div>
                        <div class="activity-meta">2024-01-15 12:00 • Prof. Johnson</div>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-graduation-cap activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text">Subject completed</div>
                        <div class="activity-meta">2024-01-14 16:20 • Sarah Wilson</div>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-calendar-alt activity-icon"></i>
                    <div class="activity-content">
                        <div class="activity-text">Schedule updated</div>
                        <div class="activity-meta">2024-01-14 11:15 • Admin</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footbar.php'; ?>

</body>
</html>
