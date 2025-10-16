<style>
        /* Sidebar Styles */
        .admin-sidebar {
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

        /* Adjust main content for sidebar */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }


        @media (max-width: 768px) {

            .admin-sidebar {

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

            .nav-item[href*="admin_manage_academic_periods.php"] span {

                display: none;

            }

            .nav-item[href*="admin_manage_academic_periods.php"]::after {

                content: "Academic";

                display: block;

                font-size: 0.75rem;

                text-align: center;

            }

            .nav-item[href*="admin_subjects_archive.php"] span {

                display: none;

            }

            .nav-item[href*="admin_subjects_archive.php"]::after {

                content: "Archive";

                display: block;

                font-size: 0.75rem;

                text-align: center;

            }

            .main-content {

                margin-left: 0;

                width: 100%;

                padding-bottom: 60px;

            }

        }

        @media (max-width: 480px) {

            .admin-sidebar {

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

            .nav-item[href*="admin_manage_academic_periods.php"]::after {

                font-size: 0.65rem;

            }

            .nav-item[href*="admin_subjects_archive.php"]::after {

                font-size: 0.65rem;

            }

            .main-content {

                padding-bottom: 50px;

            }

        }



</style>

<!-- Sidebar -->
<div class="admin-sidebar">
    <div class="sidebar-header">
        <i class="fas fa-shield-alt"></i>
        <span>Admin Panel</span>
    </div>
    <nav class="sidebar-nav">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <a href="admin_dashboard.php" class="nav-item <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin_manage_students.php" class="nav-item <?php echo ($current_page == 'admin_manage_students.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Students</span>
        </a>
        <a href="admin_manage_professors.php" class="nav-item <?php echo ($current_page == 'admin_manage_professors.php') ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Professors</span>
        </a>
        <a href="admin_manage_schedule.php" class="nav-item <?php echo ($current_page == 'admin_manage_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Schedule</span>
        </a>
        <a href="http://localhost/grc/Admin/admin_manage_academic_periods.php" class="nav-item <?php echo ($current_page == 'admin_manage_academic_periods.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-days"></i>
            <span>Academic Periods</span>
        </a>
        <a href="admin_subjects_archive.php" class="nav-item <?php echo ($current_page == 'admin_subjects_archive.php') ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            <span>Subjects Archive</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>
</div>
