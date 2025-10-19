<!-- Navbar -->

<style>

.navbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 1rem 2rem;

    background-color: #F75270;

    color: white;

    font-family: 'Poppins', sans-serif;

}

.navbar-title {

    display: block;

}

.navbar-title-mobile {

    display: none;

}

@media (max-width: 768px) {

    .navbar-title {

        display: none;

    }

    .navbar-title-mobile {

        display: block;

    }

}

@media (max-width: 480px) {

    .navbar {

        flex-wrap: wrap;

        padding: 0.5rem 1rem;

    }

    .navbar-user {

        font-size: 0.8rem;

    }

    .user-dropdown button {

        padding: 0.5rem;

    }

}

</style>

<nav class="navbar">
    <div class="navbar-brand">
        <span class="navbar-title">Global Reciprocal Colleges</span>
        <span class="navbar-title-mobile">GRC</span>
    </div>
    <div class="navbar-user">
        <span>Welcome, <?php echo $_SESSION['first_name']; ?></span>
        <div class="navbar-actions">
            <button type="button" class="notification-btn" onclick="openNotificationModal()" title="Notifications">
                <i class="fas fa-bell"></i>
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
            <div class="notification-list" id="notificationList">
                <!-- Notifications will be loaded here -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-enhanced btn-secondary-enhanced" onclick="closeNotificationModal()">
                <i class="fas fa-times"></i> Close
            </button>
            <button type="button" class="btn-enhanced btn-primary" onclick="markAllNotificationsRead()">
                <i class="fas fa-check"></i> Mark All Read
            </button>
        </div>
    </div>
</div>

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

/* Navbar Actions */
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notification-btn {
    position: relative;
    background: rgba(247, 82, 112, 0.1);
    border: 2px solid var(--primary);
    color: var(--primary);
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-btn:hover {
    background: var(--primary);
    color: white;
    transform: scale(1.05);
}

.notification-btn.has-notifications {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.notification-badge {
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 0.8rem;
    height: 0.8rem;
    font-size: 0.5rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    border: 1px solid white;
}

/* Enhanced Notification Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.6) 100%);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    animation: modalFadeIn 0.3s ease-out;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 0;
    width: 90%;
    max-width: 650px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 2rem;
    border-radius: 20px 20px 0 0;
    position: relative;
    overflow: hidden;
}

.modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="50" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="50" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.modal-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
}

.modal-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-title-icon {
    width: 40px;
    height: 40px;
    background: rgba(248, 243, 243, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.modal-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-size: 1.2rem;
    cursor: pointer;
    color: white;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    z-index: 2;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}



.modal-body {
    padding: 2.5rem;
    background: white;
}

.modal-footer {
    padding: 2rem 2.5rem;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.btn-enhanced {
    padding: 0.875rem 2rem;
    border-radius: 12px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.btn-enhanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn-enhanced:hover::before {
    left: 100%;
}

.btn-secondary-enhanced {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(108, 117, 125, 0.2);
}

.btn-secondary-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(108, 117, 125, 0.3);
}

.btn-enhanced.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(247, 82, 112, 0.2);
}

.btn-enhanced.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(247, 82, 112, 0.3);
}

/* Notification List Styles */
.notification-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.notification-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #F75270 0%, #DC143C 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #343a40;
    margin-bottom: 0.5rem;
}

.notification-message {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.8rem;
    color: #6c757d;
}

.notification-time {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.notification-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-unread {
    background: rgba(247, 82, 112, 0.1);
    color: #F75270;
}

.status-read {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.no-notifications {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.no-notifications-icon {
    font-size: 4rem;
    color: #F7CAC9;
    margin-bottom: 1rem;
    opacity: 0.6;
}

.no-notifications-text {
    font-size: 1.2rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.no-notifications-subtext {
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 1rem;
    }

    .modal-header {
        padding: 1.5rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1.5rem;
    }

    .notification-item {
        padding: 1rem;
    }
}

@media (max-width: 450px) {
    .modal-content {
        width: 98%;
        max-width: none;
        margin: 0.5rem;
    }

    .modal-header {
        padding: 1rem;
    }

    .modal-title {
        font-size: 1.2rem;
        gap: 0.5rem;
    }

    .modal-title-icon {
        width: 32px;
        height: 32px;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }

    .modal-body {
        padding: 1rem;
    }

    .modal-footer {
        padding: 1rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .notification-list {
        gap: 0.75rem;
    }

    .notification-item {
        padding: 0.75rem;
        gap: 0.75rem;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .notification-content {
        text-align: left;
    }

    .notification-title {
        font-size: 1rem;
    }

    .notification-message {
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .notification-meta {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .btn-enhanced {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        width: 100%;
        justify-content: center;
    }

    .no-notifications {
        padding: 2rem 0.5rem;
    }

    .no-notifications-icon {
        font-size: 3rem;
    }

    .no-notifications-text {
        font-size: 1rem;
    }

    .no-notifications-subtext {
        font-size: 0.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[navbar_student] DOMContentLoaded');

    // Dropdown functionality for multiple dropdowns
    document.querySelectorAll('.user-dropdown').forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (toggle && menu) {
            toggle.addEventListener('click', function(event) {
                console.log('[navbar_student] dropdown toggle clicked');
                event.stopPropagation();
                // Close other dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.remove('show');
                        const otherToggle = otherMenu.closest('.user-dropdown')?.querySelector('.dropdown-toggle');
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
        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            if (!event.target.closest('.user-dropdown')) {
                menu.classList.remove('show');
            }
        });
    });

    // Modal functions
    let notificationsIntervalId = null;

    window.openNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        modal.classList.add('show');
        loadNotifications();
        // auto-refresh while open
        if (notificationsIntervalId) clearInterval(notificationsIntervalId);
        notificationsIntervalId = setInterval(loadNotifications, 10000);
    };

    window.closeNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('show');
        // stop auto-refresh
        if (notificationsIntervalId) {
            clearInterval(notificationsIntervalId);
            notificationsIntervalId = null;
        }
        // Hide the notification badges after viewing
        document.querySelectorAll('.notification-badge').forEach(badge => {
            badge.style.display = 'none';
        });
        // Reset the notification button to original state
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.classList.remove('has-notifications');
        }
    };

    // Load notifications from API
    function loadNotifications() {
        fetch('../php/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayNotifications(data.notifications);
                } else {
                    console.error('Failed to load notifications:', data.message);
                    showNoNotifications();
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                showNoNotifications();
            });
    }

    // Display notifications in the modal
    function displayNotifications(notifications) {
        const notificationList = document.getElementById('notificationList');

        if (!notifications || notifications.length === 0) {
            showNoNotifications();
            return;
        }

        notificationList.innerHTML = '';

        notifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = 'notification-item';
            notificationItem.onclick = () => markNotificationRead(notification.notification_id);

            const isRead = notification.is_read == 1;
            const statusClass = isRead ? 'status-read' : 'status-unread';
            const statusText = isRead ? 'READ' : 'UNREAD';

            notificationItem.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notification.title)}</div>
                    <div class="notification-message">${escapeHtml(notification.message)}</div>
                    <div class="notification-meta">
                        <div class="notification-time">
                            <i class="fas fa-clock"></i>
                            ${formatDate(notification.created_at)}
                        </div>
                        <div class="notification-status ${statusClass}">${statusText}</div>
                    </div>
                </div>
            `;

            notificationList.appendChild(notificationItem);
        });
    }

    // Show no notifications message
    function showNoNotifications() {
        const notificationList = document.getElementById('notificationList');
        notificationList.innerHTML = `
            <div class="no-notifications">
                <div class="no-notifications-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <div class="no-notifications-text">No new notifications</div>
                <div class="no-notifications-subtext">You have no notifications at this time.</div>
            </div>
        `;
    }

    // Mark notification as read
    function markNotificationRead(notificationId) {
        fetch('../php/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload notifications to update the UI
                loadNotifications();
                updateNotificationBadge();
            } else {
                console.error('Failed to mark notification as read:', data.message);
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    // Mark all notifications as read
    window.markAllNotificationsRead = function() {
        fetch('../php/mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                updateNotificationBadge();
            } else {
                console.error('Failed to mark all notifications as read:', data.message);
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    };

    // Update notification badge
    function updateNotificationBadge() {
        fetch('../php/get_unread_notification_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationBtn = document.querySelector('.notification-btn');
                    let badge = notificationBtn.querySelector('.notification-badge');

                    if (data.count > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'notification-badge';
                            notificationBtn.appendChild(badge);
                        }
                        badge.textContent = data.count;
                        notificationBtn.classList.add('has-notifications');
                    } else {
                        if (badge) {
                            badge.remove();
                        }
                        notificationBtn.classList.remove('has-notifications');
                    }
                }
            })
            .catch(error => {
                console.error('Error updating notification badge:', error);
            });
    }

    // Initialize notification badge on page load and poll for real-time updates
    updateNotificationBadge();
    setInterval(updateNotificationBadge, 10000); // every 10s

    // Utility functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notificationModal');
        if (event.target === modal) {
            closeNotificationModal();
        }
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeNotificationModal();
        }
    });
});
</script>
