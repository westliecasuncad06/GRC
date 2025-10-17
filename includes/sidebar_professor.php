<?php
require_once '../php/db.php';

$professor_id = $_SESSION['user_id'] ?? null;
$pending_requests_count = 0;

if ($professor_id) {
    // Fetch enrollment requests for professor's classes including handled ones
    $stmt = $pdo->prepare("
        SELECT er.request_id, er.requested_at, er.status, s.subject_name, c.class_code, st.first_name, st.last_name
        FROM enrollment_requests er
        JOIN classes c ON er.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN students st ON er.student_id = st.student_id
        WHERE c.professor_id = ?
        ORDER BY er.requested_at DESC
    ");
    $stmt->execute([$professor_id]);
    $all_requests = $stmt->fetchAll();

    // Separate pending requests
    $pending_requests = array_filter($all_requests, function($req) {
        return $req['status'] === 'pending';
    });

    // Fetch unenrollment requests for professor's classes
    $stmt = $pdo->prepare("
        SELECT ur.request_id, ur.requested_at, ur.status, s.subject_name, c.class_code, st.first_name, st.last_name
        FROM unenrollment_requests ur
        JOIN classes c ON ur.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN students st ON ur.student_id = st.student_id
        WHERE c.professor_id = ?
        ORDER BY ur.requested_at DESC
    ");
    $stmt->execute([$professor_id]);
    $all_unenrollment_requests = $stmt->fetchAll();

    // Separate pending unenrollment requests
    $pending_unenrollment_requests = array_filter($all_unenrollment_requests, function($req) {
        return $req['status'] === 'pending';
    });

    $pending_requests_count = count($pending_requests) + count($pending_unenrollment_requests);
}
?>

<style>
        /* Sidebar Styles */
        .professor-sidebar {
            position: fixed;
            left: 0;
            top: 60px; /* Assuming navbar height */
            width: 250px;
            height: calc(100vh - 60px);
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
        }
        .sidebar-header i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .sidebar-header span {
            display: block;
            font-weight: 600;
        }
        .sidebar-nav {
            padding: 0 1rem;
        }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .nav-item i {
            margin-right: 10px;
            width: 20px;
        }
        .nav-item span {
            font-weight: 500;
        }
        .notification-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            font-weight: bold;
            min-width: 1.2rem;
            text-align: center;
        }

        /* Adjust main content for sidebar */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }


        @media (max-width: 768px) {

            .professor-sidebar {

                position: fixed;

                bottom: 0;

                left: 0;

                width: 100%;

                height: 60px;

                top: auto;

                padding: 0;

                display: flex;

                justify-content: space-around;

                align-items: center;

                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);

            }

            .sidebar-header {

                display: none;

            }

            .sidebar-nav {

                display: flex;

                width: 100%;

                padding: 0;

                justify-content: space-around;

            }

            .nav-item {

                flex: 1;

                text-align: center;

                padding: 0.5rem 0;

                margin: 0;

                flex-direction: column;

                border-radius: 0;

            }

            .nav-item i {

                margin-right: 0;

                margin-bottom: 0.25rem;

                font-size: 1.2rem;

            }

            .nav-item span {

                font-size: 0.75rem;

                display: block;

            }

            .main-content {

                margin-left: 0;

                width: 100%;

                padding-bottom: 60px;

            }

            .settings-mobile-hide {

                display: none;

            }

        }

        @media (max-width: 480px) {

            .professor-sidebar {

                height: 50px;

            }

            .nav-item {

                padding: 0.25rem 0;

            }

            .nav-item i {

                font-size: 1rem;

                margin-bottom: 0.1rem;

            }

            .nav-item span {

                font-size: 0.65rem;

            }

            .main-content {

                padding-bottom: 50px;

            }

        }



</style>

<!-- Sidebar -->
<div class="professor-sidebar">
    <div class="sidebar-header">
        <i class="fas fa-chalkboard-teacher"></i>
        <span>Professor Panel</span>
    </div>
    <nav class="sidebar-nav">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <a href="professor_dashboard.php" class="nav-item <?php echo ($current_page == 'professor_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="manage_subjects.php" class="nav-item <?php echo ($current_page == 'manage_subjects.php') ? 'active' : ''; ?>">
            <i class="fas fa-book"></i>
            <span>Subjects</span>
        </a>
        <a href="manage_students.php" class="nav-item <?php echo ($current_page == 'manage_students.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Students</span>
        </a>
        <a href="professor_manage_schedule.php" class="nav-item <?php echo ($current_page == 'professor_manage_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Class</span>
        </a>
        <a href="archive.php" class="nav-item <?php echo ($current_page == 'archive.php') ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            <span>Archive</span>
        </a>
    </nav>
</div>
