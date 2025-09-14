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
        <button type="button" class="notification-btn" onclick="openNotificationModal()" title="Notifications">
            <i class="fas fa-bell" aria-hidden="true"></i>
        </button>
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

<!-- Notification Modal -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-content">
                <h3 class="modal-title">
                    <div class="modal-title-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    Notifications
                </h3>
            </div>
            <button class="modal-close" onclick="closeNotificationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="notification-list">
                <!-- Sample notifications -->
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">New Student Enrolled</div>
                        <div class="notification-message">John Doe has been enrolled in your Mathematics class.</div>
                        <div class="notification-meta">
                            <div class="notification-time">
                                <i class="fas fa-clock"></i>
                                2 hours ago
                            </div>
                            <div class="notification-status status-unread">Unread</div>
                        </div>
                    </div>
                </div>

                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Attendance Reminder</div>
                        <div class="notification-message">Don't forget to take attendance for your Physics class scheduled for tomorrow.</div>
                        <div class="notification-meta">
                            <div class="notification-time">
                                <i class="fas fa-clock"></i>
                                1 day ago
                            </div>
                            <div class="notification-status status-read">Read</div>
                        </div>
                    </div>
                </div>

                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Monthly Report Available</div>
                        <div class="notification-message">Your monthly attendance report for October is now available for download.</div>
                        <div class="notification-meta">
                            <div class="notification-time">
                                <i class="fas fa-clock"></i>
                                3 days ago
                            </div>
                            <div class="notification-status status-read">Read</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-enhanced btn-secondary-enhanced" onclick="closeNotificationModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<script>
    // Global function for notification modal close button
    function closeNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('show');
    }

    // Function to open notification modal
    function openNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.add('show');
    }

    // Close notification modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notificationModal');
        if (event.target === modal) {
            closeNotificationModal();
        }
    });
</script>
