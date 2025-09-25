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

<!-- Sidebar -->
<aside class="sidebar">
    <ul class="sidebar-menu">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li class="sidebar-item">
            <a href="../Professor/professor_dashboard.php" class="sidebar-link <?php echo ($current_page == 'professor_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="sidebar-item">
            <a href="../Professor/manage_subjects.php" class="sidebar-link <?php echo ($current_page == 'manage_subjects.php') ? 'active' : ''; ?>"><i class="fas fa-book"></i> Manage Subjects</a>
        </li>
        <li class="sidebar-item">
            <a href="../Professor/manage_students.php" class="sidebar-link <?php echo ($current_page == 'manage_students.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Students</a>
        </li>
        <li class="sidebar-item">
            <a href="../Professor/professor_manage_schedule.php" class="sidebar-link <?php echo ($current_page == 'professor_manage_schedule.php') ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i> Manage Class</a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link notification-link" onclick="openNotificationModal()" style="position: relative;" title="Notifications">
                <i class="fas fa-bell" aria-hidden="true"></i> Notification
                <?php if ($pending_requests_count > 0): ?>
                    <span class="notification-badge"><?php echo $pending_requests_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../Professor/archive.php" class="sidebar-link <?php echo ($current_page == 'archive.php') ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archive</a>
        </li>
        <li class="sidebar-item">
            <a href="../Admin/settings.php" class="sidebar-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        </li>
    </ul>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu toggle - works for both desktop and mobile
    document.getElementById('sidebarToggle').addEventListener('click', function(e) {
        e.stopPropagation();
        document.body.classList.toggle('sidebar-collapsed');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        const isMobile = window.innerWidth <= 900;

        if (isMobile && !isCollapsed && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
            document.body.classList.add('sidebar-collapsed');
        }
    });

    // Prevent sidebar click from closing it
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});


</script>
