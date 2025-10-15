<style>
        /* Sidebar Styles */
        .student-sidebar {
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
            .student-sidebar {
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
                z-index: 1010;
                background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
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
                position: relative;
                z-index: 1020;
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
            .nav-item[href*="my_enrolled_classes.php"] span {
                display: none;
            }
            .nav-item[href*="my_enrolled_classes.php"]::after {
                content: "Classes";
                display: block;
                font-size: 0.75rem;
                text-align: center;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-bottom: 70px; /* Increased to ensure no overlap */
            }
            .settings-mobile-hide {
                display: none;
            }
        }


</style>

<!-- Sidebar -->
<div class="student-sidebar">
    <div class="sidebar-header">
        <i class="fas fa-user-graduate"></i>
        <span>Student Panel</span>
    </div>
    <nav class="sidebar-nav">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <a href="../Student/student_dashboard.php" class="nav-item <?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="../Student/student_manage_schedule.php" class="nav-item <?php echo ($current_page == 'student_manage_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>My Schedule</span>
        </a>
        <a href="../Student/my_enrolled_classes.php" class="nav-item <?php echo ($current_page == 'my_enrolled_classes.php') ? 'active' : ''; ?>">
            <i class="fas fa-book"></i>
            <span>My Enrolled Classes</span>
        </a>
        <a href="#" onclick="openNotificationModal(); return false;" class="nav-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php
            require_once '../php/db.php';

            // Get enrollment request history
            $stmt = $pdo->prepare("
                SELECT
                    er.request_id,
                    er.status,
                    er.requested_at,
                    er.processed_at,
                    c.class_name,
                    s.subject_name,
                    p.first_name as prof_first_name,
                    p.last_name as prof_last_name,
                    'enrollment' as request_type
                FROM enrollment_requests er
                JOIN classes c ON er.class_id = c.class_id
                JOIN subjects s ON c.subject_id = s.subject_id
                LEFT JOIN professors p ON er.processed_by = p.professor_id
                WHERE er.student_id = ?
                ORDER BY er.requested_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $enrollment_requests = $stmt->fetchAll();

            // Get unenrollment request history
            $stmt = $pdo->prepare("
                SELECT
                    ur.request_id,
                    ur.status,
                    ur.requested_at,
                    ur.processed_at,
                    c.class_name,
                    s.subject_name,
                    p.first_name as prof_first_name,
                    p.last_name as prof_last_name,
                    'unenrollment' as request_type
                FROM unenrollment_requests ur
                JOIN classes c ON ur.class_id = c.class_id
                JOIN subjects s ON c.subject_id = s.subject_id
                LEFT JOIN professors p ON ur.processed_by = p.professor_id
                WHERE ur.student_id = ?
                ORDER BY ur.requested_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $unenrollment_requests = $stmt->fetchAll();

            // Combine and sort all requests
            $all_requests = array_merge($enrollment_requests, $unenrollment_requests);
            usort($all_requests, function($a, $b) {
                return strtotime($b['requested_at']) - strtotime($a['requested_at']);
            });

            $pending_count = count(array_filter($all_requests, fn($r) => $r['status'] === 'pending'));

            if ($pending_count > 0):
            ?>
            <span class="notification-badge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="../Student/student_archive.php" class="nav-item <?php echo ($current_page == 'student_archive.php') ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            <span>Archive</span>
        </a>
        <a href="../Admin/settings.php" class="nav-item settings-mobile-hide <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>
</div>
