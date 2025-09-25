<!-- Sidebar -->
<aside class="sidebar">
    <ul class="sidebar-menu">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li class="sidebar-item">
            <a href="admin_dashboard.php" class="sidebar-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="sidebar-item">
            <a href="admin_manage_professors.php" class="sidebar-link <?php echo ($current_page == 'admin_manage_professors.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Manage Professors</a>
        </li>
        <li class="sidebar-item">
            <a href="admin_manage_students.php" class="sidebar-link <?php echo ($current_page == 'admin_manage_students.php') ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Manage Students</a>
        </li>
        <li class="sidebar-item">
            <a href="admin_manage_schedule.php" class="sidebar-link <?php echo ($current_page == 'admin_manage_schedule.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Manage Schedule</a>
        </li>
    </ul>
</aside>


