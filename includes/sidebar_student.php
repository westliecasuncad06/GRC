<!-- Sidebar -->
<aside class="sidebar">
    <ul class="sidebar-menu">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li class="sidebar-item">
            <a href="../Student/student_dashboard.php" class="sidebar-link <?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="sidebar-item">
            <a href="../Student/student_manage_schedule.php" class="sidebar-link <?php echo ($current_page == 'student_manage_schedule.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> My Schedule</a>
        </li>
        <li class="sidebar-item">
            <a href="../Student/my_enrolled_classes.php" class="sidebar-link <?php echo ($current_page == 'my_enrolled_classes.php') ? 'active' : ''; ?>"><i class="fas fa-book"></i> My Enrolled Classes</a>
        </li>
        <li class="sidebar-item">
            <a href="../Student/student_archive.php" class="sidebar-link <?php echo ($current_page == 'student_archive.php') ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archive</a>
        </li>
        <li class="sidebar-item">
            <a href="../Admin/settings.php" class="sidebar-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        </li>
    </ul>
</aside>


