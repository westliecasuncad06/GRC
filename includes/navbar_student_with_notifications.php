<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <button type="button" class="hamburger-menu" id="sidebarToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <span class="navbar-title">Global Reciprocal Colleges</span>
        <span class="navbar-title-mobile">GRC</span>
    </div>
    <div class="navbar-user">
        <span>Welcome, <?php echo $_SESSION['first_name']; ?></span>

        <!-- Notifications -->
        <div class="notifications-dropdown">
            <button type="button" class="notifications-toggle" id="notificationsToggle" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell" aria-hidden="true"></i>
                <?php
                require_once '../php/db.php';
                require_once '../php/notifications.php';
                $notificationManager = new NotificationManager($pdo);
                $unread_count = $notificationManager->getUnreadCount($_SESSION['user_id'], 'student');
                if ($unread_count > 0):
                ?>
                    <span class="notification-badge"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="notifications-menu" id="notificationsMenu">
                <div class="notifications-header">
                    <h4>Notifications</h4>
                    <?php if ($unread_count > 0): ?>
                        <a href="../Student/notifications.php?mark_read=all" class="mark-all-read">Mark all as read</a>
                    <?php endif; ?>
                </div>
                <div class="notifications-list" id="notificationsList">
                    <?php
                    $notifications = $notificationManager->getNotifications($_SESSION['user_id'], 'student', 5, 0);
                    if (empty($notifications)):
                    ?>
                        <div class="no-notifications">No notifications</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                <div class="notification-icon">
                                    <i class="fas <?php
                                        echo match($notification['type']) {
                                            'enrollment_approved' => 'fa-check-circle',
                                            'enrollment_rejected' => 'fa-times-circle',
                                            'unenrollment_approved' => 'fa-check-circle',
                                            'unenrollment_rejected' => 'fa-times-circle',
                                            'success' => 'fa-check-circle',
                                            'warning' => 'fa-exclamation-triangle',
                                            'info' => 'fa-info-circle',
                                            default => 'fa-bell'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="notification-message"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . (strlen($notification['message']) > 50 ? '...' : ''); ?></div>
                                    <div class="notification-time"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="view-all">
                            <a href="../Student/notifications.php">View all notifications</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="user-dropdown">
            <button type="button" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu">
                <a href="../Admin/settings.php" class="dropdown-item">Settings</a>
                <a href="../php/logout.php" class="dropdown-item">Logout</a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[navbar_student] DOMContentLoaded');

    // Notifications dropdown functionality
    const notificationsToggle = document.getElementById('notificationsToggle');
    const notificationsMenu = document.getElementById('notificationsMenu');

    if (notificationsToggle && notificationsMenu) {
        notificationsToggle.addEventListener('click', function(event) {
            console.log('[navbar_student] notifications toggle clicked');
            event.stopPropagation();
            // Close other dropdowns first
            document.querySelectorAll('.dropdown-menu.show, .notifications-menu.show').forEach(function(otherMenu) {
                if (otherMenu !== notificationsMenu) {
                    otherMenu.classList.remove('show');
                    const otherToggle = otherMenu.closest('.user-dropdown, .notifications-dropdown')?.querySelector('.dropdown-toggle, .notifications-toggle');
                    if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
                }
            });
            const isOpen = notificationsMenu.classList.toggle('show');
            notificationsToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // Dropdown functionality for multiple dropdowns
    document.querySelectorAll('.user-dropdown').forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (toggle && menu) {
            toggle.addEventListener('click', function(event) {
                console.log('[navbar_student] dropdown toggle clicked');
                event.stopPropagation();
                // Close other dropdowns first
                document.querySelectorAll('.dropdown-menu.show, .notifications-menu.show').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.remove('show');
                        const otherToggle = otherMenu.closest('.user-dropdown, .notifications-dropdown')?.querySelector('.dropdown-toggle, .notifications-toggle');
                        if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
                    }
                });
                const isOpen = menu.classList.toggle('show');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        document.querySelectorAll('.dropdown-menu.show, .notifications-menu.show').forEach(function(menu) {
            if (!event.target.closest('.user-dropdown') && !event.target.closest('.notifications-dropdown')) {
                menu.classList.remove('show');
            }
        });
    });

    // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Close sidebar when clicking on a sidebar link (for mobile)
    document.querySelectorAll('.sidebar-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
            }
        });
    });

    // Close sidebar when clicking outside (for mobile)
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && sidebar && !sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    });
});
</script>

<style>
.notifications-dropdown {
    position: relative;
    margin-right: 1rem;
}

.notifications-toggle {
    background: none;
    border: none;
    color: #007bff;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    position: relative;
    transition: all 0.2s ease;
}

.notifications-toggle:hover {
    background: rgba(0, 123, 255, 0.1);
    color: #0056b3;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    border: 2px solid white;
    box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.3);
}

.notifications-menu {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    max-height: 400px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    border: 1px solid #e9ecef;
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.notifications-menu.show {
    display: block;
}

.notifications-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.notifications-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.mark-all-read {
    color: white;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: opacity 0.2s ease;
}

.mark-all-read:hover {
    opacity: 0.8;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-icon {
    flex-shrink: 0;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #343a40;
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.notification-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

.view-all {
    padding: 1rem;
    text-align: center;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.view-all a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: color 0.2s ease;
}

.view-all a:hover {
    color: #0056b3;
}

.no-notifications {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
    font-size: 0.9rem;
}
