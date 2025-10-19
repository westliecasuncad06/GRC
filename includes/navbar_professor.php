<?php
require_once '../php/db.php';

$professor_id = $_SESSION['user_id'] ?? null;
$enrollment_notifications = [];
$pending_unenrollment_requests = [];
$pending_requests_count = 0;

if ($professor_id) {
    // Fetch recent enrollment notifications for professor's classes (last 30 days)
    $enrollment_notifications_query = "
        SELECT n.notification_id, n.title, n.message, n.created_at, n.is_read,
               c.class_code, s.subject_name
        FROM notifications n
        JOIN classes c ON n.related_class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        WHERE n.user_type = 'professor' AND c.professor_id = ? AND n.type = 'student_enrolled'
        AND n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY n.created_at DESC
        LIMIT 20

    ";
    $enrollment_stmt = $pdo->prepare($enrollment_notifications_query);
    $enrollment_stmt->execute([$professor_id]);
    $enrollment_notifications = $enrollment_stmt->fetchAll();

    echo '<script>console.log(' . json_encode($enrollment_notifications) . ')</script>';

    // Fetch unenrollment requests for professor's classes

    $stmt = $pdo->prepare("
        SELECT ur.request_id, ur.requested_at, ur.status, s.subject_name, c.class_code, st.first_name, st.last_name
        FROM unenrollment_requests ur
        JOIN classes c ON ur.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN students st ON ur.student_id = st.student_id
        WHERE c.professor_id = ? AND ur.status = 'pending'
        ORDER BY ur.requested_at DESC
    ");
    $stmt->execute([$professor_id]);
    $pending_unenrollment_requests = $stmt->fetchAll();

    $pending_requests_count = count($pending_unenrollment_requests);
}
?>


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

    position: fixed;

    top: 0;

    left: 0;

    right: 0;

    z-index: 1000;

    box-shadow: 0 2px 4px rgba(0,0,0,0.1);

}

.navbar-title {

    display: block;

}

.navbar-title-mobile {
    display: none;
}

.notification-btn {
    position: relative;
    background: rgba(247, 82, 112, 0.1);
    border: 2px solid #F75270;
    color: #F75270;
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
    background: #F75270;
    color: white;
    transform: scale(1.05);
}

.notification-btn.has-notifications {
    background: #F75270;
    color: white;
    border-color: #F75270;
}

.notification-badge {
    display: inline-block;
}

.pending-pill {
    display: inline-block;
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

@media (max-width: 768px) {

    .navbar-user {

        display: flex;

        align-items: center;

        gap: 0.5rem;

    }

    .welcome-text {

        display: block;

    }

    .notification-btn,

    .user-dropdown .dropdown-toggle {

        width: 40px;

        height: 40px;

        border-radius: 50%;

        background: rgba(255, 255, 255, 0.1);

        border: none;

        color: white;

        font-size: 1.2rem;

        cursor: pointer;

        display: flex;

        align-items: center;

        justify-content: center;

        transition: background 0.3s ease;

    }

    .notification-btn:hover,

    .user-dropdown .dropdown-toggle:hover {

        background: rgba(255, 255, 255, 0.2);

    }

}

@media (max-width: 414px) {

    .navbar {

        padding: 0.75rem 1rem;

        height: 56px;

    }

    .navbar-brand {

        font-size: 1.2rem;

    }

    .navbar-user {

        gap: 0.25rem;

    }

    .notification-btn,

    .user-dropdown .dropdown-toggle,

    .hamburger-btn {

        width: 44px;

        height: 44px;

        font-size: 1.1rem;

    }

}

.full-name {

    display: inline;

}

.mobile-name {

    display: none;

}

@media (max-width: 768px) {

    .full-name {

        display: none;

    }

    .mobile-name {

        display: inline;

    }

}


</style>

<nav class="navbar">
    <div class="navbar-brand">
        <span class="navbar-title">Global Reciprocal Colleges</span>
        <span class="navbar-title-mobile">GRC</span>
    </div>
    <div class="navbar-user">
    <?php
    // Try to use professor name from database if available
    $prof_name = '';
    if (isset($professor) && is_array($professor) && isset($professor['first_name'])) {
        $prof_name = $professor['first_name'] . ' ' . $professor['last_name'];
    } else {
        $prof_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
    }
    ?>
    <span class="welcome-text">Welcome, <span class="full-name"><?php echo htmlspecialchars($prof_name); ?></span><span class="mobile-name"><?php echo htmlspecialchars($professor['first_name'] ?? $_SESSION['first_name'] ?? ''); ?></span></span>
        <button type="button" class="notification-btn" onclick="openNotificationModal()" title="Notifications" style="position: relative;">
            <i class="fas fa-bell" aria-hidden="true"></i>
            <!-- numeric badge for enrollment notifications (red) -->
            <span class="notification-badge" id="profEnrollBadge" style="display:none;position:absolute;top:-8px;right:-8px;background:#DC3545;color:white;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;box-shadow:0 2px 8px rgba(220,53,69,0.35);border:2px solid white;line-height:1;">0</span>
            <!-- pending unenrollment pill (orange indicator) -->
            <span class="pending-pill" id="profPendingPill" style="display:none;position:absolute;bottom:-8px;right:-8px;background:#FF9800;color:white;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;box-shadow:0 2px 10px rgba(255,152,0,0.4);border:2px solid white;line-height:1;" title="Pending unenrollment requests">!</span>
        </button>
        <div class="user-dropdown">
            <button type="button" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu">
                <a href="settings.php" class="dropdown-item">Settings</a>
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
            <h4 style="margin: 0 0 1rem 0; font-weight: 600; color: #343a40;">Pending Unenrollment Requests</h4>
            <div class="notification-list" id="pendingUnenrollmentList"></div>
            <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid rgba(0,0,0,0.08);">

            <h4 style="margin: 0 0 1rem 0; font-weight: 600; color: #343a40;">All Notifications</h4>
            <div class="notification-list" id="notificationList"></div>
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

<!-- Confirmation Modal -->
<div id="confirmationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-content">
                <h3 class="modal-title">
                    <div class="modal-title-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    Confirm Action
                </h3>
            </div>
            <button class="modal-close" onclick="hideConfirmationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="confirmation-content">
                <div class="confirmation-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="confirmation-text">
                    <h4 id="confirmation-title">Are you sure you want to perform this action?</h4>
                    <p>This action cannot be undone.</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-enhanced btn-secondary-enhanced" onclick="hideConfirmationModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn-enhanced btn-primary" onclick="confirmRequest()">
                <i class="fas fa-check"></i> OK
            </button>
        </div>
    </div>
</div>

<script>
    // Global function for notification modal close button
    let notificationsIntervalId = null;
    let lastBadgeCount = null;
    let lastPendingCount = null;

    function closeNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('show');
        // Reset the notification button to original state
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.classList.remove('has-notifications');
        }
        // stop auto-refresh
        if (notificationsIntervalId) {
            clearInterval(notificationsIntervalId);
            notificationsIntervalId = null;
        }
    }

    // Function to open notification modal
    function openNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.add('show');
        refreshPendingUnenrollments();
        loadNotifications(true);
        // auto-refresh while open
        if (notificationsIntervalId) clearInterval(notificationsIntervalId);
        notificationsIntervalId = setInterval(() => {
            refreshPendingUnenrollments();
            loadNotifications(true);
        }, 2000);
    }

    // Close notification modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notificationModal');
        if (event.target === modal) {
            closeNotificationModal();
        }
    });

    // Handle enrollment request accept/reject
    function handleEnrollmentRequest(requestId, action) {
        if (!['accept', 'reject'].includes(action)) {
            alert('Invalid action');
            return;
        }
        fetch('../php/handle_enrollment_request_with_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                request_id: requestId,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the handled request from the notification list immediately
                const requestElem = document.getElementById('request-' + requestId);
                if (requestElem) {
                    requestElem.remove();
                }
                // Update notification badge count
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    let count = parseInt(badge.textContent);
                    count = Math.max(0, count - 1);
                    if (count === 0) {
                        badge.remove();
                    } else {
                        badge.textContent = count;
                    }
                }
            } else {
                alert('Failed to handle request: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while handling the request.');
        });
    }

    // Global variables for confirmation modal
    let currentRequestId = null;
    let currentAction = null;
    let currentType = null;

    // Function to show confirmation modal
    function showConfirmationModal(requestId, action, type) {
        currentRequestId = requestId;
        currentAction = action;
        currentType = type;
        const titleElement = document.getElementById('confirmation-title');
        if (type === 'enrollment') {
            if (action === 'accept') {
                titleElement.textContent = 'Are you sure you want to accept this enrollment request?';
            } else if (action === 'reject') {
                titleElement.textContent = 'Are you sure you want to reject this enrollment request?';
            }
        } else if (type === 'unenrollment') {
            if (action === 'accept') {
                titleElement.textContent = 'Are you sure you want to accept this unenrollment request?';
            } else if (action === 'reject') {
                titleElement.textContent = 'Are you sure you want to reject this unenrollment request?';
            }
        }
        const modal = document.getElementById('confirmationModal');
        modal.classList.add('show');
    }

    // Function to hide confirmation modal
    function hideConfirmationModal() {
        const modal = document.getElementById('confirmationModal');
        modal.classList.remove('show');
        currentRequestId = null;
        currentAction = null;
        currentType = null;
    }

    // Function to confirm and handle request
    function confirmRequest() {
        if (!currentRequestId || !currentAction || !currentType) return;
        if (currentType === 'enrollment') {
            handleEnrollmentRequest(currentRequestId, currentAction);
        } else if (currentType === 'unenrollment') {
            handleUnenrollmentRequest(currentRequestId, currentAction);
        }
        hideConfirmationModal();
    }

    // Handle unenrollment request accept/reject
    function handleUnenrollmentRequest(requestId, action) {
        if (!['accept', 'reject'].includes(action)) {
            alert('Invalid action');
            return;
        }
        fetch('../php/handle_unenrollment_request_with_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                request_id: requestId,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the handled request from the notification list immediately
                const requestElem = document.getElementById('unenrollment-request-' + requestId);
                if (requestElem) {
                    requestElem.remove();
                }
                // Update notification badge count
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    let count = parseInt(badge.textContent);
                    count = Math.max(0, count - 1);
                    if (count === 0) {
                        badge.remove();
                    } else {
                        badge.textContent = count;
                    }
                }
            } else {
                alert('Failed to handle request: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while handling the request.');
        });
    }

    // Load notifications from API
    // Paging state
    let notifOffset = 0;
    const notifLimit = 4;

    function loadNotifications(reset = false) {
        if (reset) notifOffset = 0;
        fetch(`../php/get_notifications.php?limit=${notifLimit}&offset=${notifOffset}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayNotifications(data.notifications, reset);
                    notifOffset += data.notifications.length;
                    renderLoadMore(data.has_more);
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
    function displayNotifications(notifications, reset = false) {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;

        if (reset) notificationList.innerHTML = '';

        if (!notifications || notifications.length === 0) {
            if (reset || notificationList.children.length === 0) {
                showNoNotifications();
            }
            return;
        }

        notifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = 'notification-item';

            const isRead = notification.is_read == 1;
            const statusClass = isRead ? 'status-read' : 'status-unread';
            const statusText = isRead ? 'READ' : 'UNREAD';

            const iconClass = getNotificationIcon(notification);

            notificationItem.innerHTML = `
                <div class="notification-icon">
                    <i class="fas ${iconClass}"></i>
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

            notificationItem.addEventListener('click', () => markNotificationRead(notification.notification_id));

            notificationList.appendChild(notificationItem);
        });
    }

    function renderLoadMore(hasMore) {
        const existing = document.getElementById('loadMoreContainer');
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;
        if (existing) existing.remove();
        if (!hasMore) return;
        const container = document.createElement('div');
        container.id = 'loadMoreContainer';
        container.style.textAlign = 'center';
        container.style.marginTop = '1rem';
        const btn = document.createElement('button');
        btn.className = 'btn-enhanced btn-secondary-enhanced';
        btn.innerHTML = '<i class="fas fa-chevron-down"></i> Load More';
        btn.onclick = () => loadNotifications(false);
        container.appendChild(btn);
        notificationList.parentNode.appendChild(container);
    }

    function getNotificationIcon(n) {
        const t = (n.type || '').toLowerCase();
        const title = (n.title || '').toLowerCase();
        if (t === 'enrollment_approved' || title.includes('enrollment approved')) return 'fa-check-circle';
        if (t === 'enrollment_rejected' || title.includes('enrollment rejected')) return 'fa-times-circle';
        if (t === 'unenrollment_approved' || title.includes('unenrollment approved')) return 'fa-check-circle';
        if (t === 'unenrollment_rejected' || title.includes('unenrollment rejected')) return 'fa-times-circle';
        if (title.includes('new student enrollment')) return 'fa-user-plus';
        if (title.includes('unenrollment request')) return 'fa-user-minus';
        return 'fa-bell';
    }

    function showNoNotifications() {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;
        notificationList.innerHTML = `
            <div class="no-notifications">
                <div class="no-notifications-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <div class="no-notifications-text">No notifications</div>
                <div class="no-notifications-subtext">You're all caught up.</div>
            </div>
        `;
    }

    function markNotificationRead(notificationId) {
        fetch('../php/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error marking notification read:', error));
    }

    function markAllNotificationsRead() {
        fetch('../php/mark_all_notifications_read.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }

    function updateNotificationBadge() {
        // Fetch only enrollment-type unread notifications for the numeric badge
        fetch('../php/get_unread_notification_count.php?type=student_enrolled')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationBtn = document.querySelector('.notification-btn');
                    if (!notificationBtn) return;
                    const badge = document.getElementById('profEnrollBadge');
                    const pendingPill = document.getElementById('profPendingPill');

                    // Update enrollment numeric badge
                    const enrollCount = (data.count || 0);
                    if (enrollCount > 0) {
                        badge.style.display = 'inline-block';
                        badge.textContent = enrollCount;
                        notificationBtn.classList.add('has-notifications');
                    } else {
                        badge.style.display = 'none';
                        notificationBtn.classList.remove('has-notifications');
                    }

                    // Update pending unenrollment pill (server still returns pending field)
                    const pendingCount = (data.pending || 0);
                    if (pendingCount > 0) {
                        pendingPill.style.display = 'flex';
                        pendingPill.textContent = pendingCount > 9 ? '9+' : pendingCount;
                    } else {
                        pendingPill.style.display = 'none';
                    }

                    // If modal is open and counts changed, refresh lists immediately
                    const modal = document.getElementById('notificationModal');
                    const modalOpen = modal && modal.classList.contains('show');
                    if (modalOpen && (lastBadgeCount !== enrollCount || lastPendingCount !== pendingCount)) {
                        refreshPendingUnenrollments();
                        loadNotifications(true);
                    }
                    lastBadgeCount = enrollCount;
                    lastPendingCount = pendingCount;
                }
            })
            .catch(error => console.error('Error updating notification badge:', error));
    }

    // Initial poll and periodic 2-second refresh (global)
    updateNotificationBadge();
    if (!window.__profBadgeInterval) {
        window.__profBadgeInterval = setInterval(updateNotificationBadge, 2000);
    }

    // Full notifications refresh every 2 minutes to capture real-time changes
    if (!window.__profFullRefreshInterval) {
        window.__profFullRefreshInterval = setInterval(() => {
            try {
                refreshPendingUnenrollments();
                loadNotifications(true);
                updateNotificationBadge();
                console.debug('Performed full notification refresh (2min)');
            } catch (e) {
                console.error('Error during full notification refresh:', e);
            }
        }, 120000); // 120000 ms = 2 minutes
    }

    // Render dynamic pending unenrollment requests
    function refreshPendingUnenrollments() {
        fetch('../php/get_pending_unenrollment_requests.php')
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('pendingUnenrollmentList');
                if (!container) return;
                container.innerHTML = '';
                if (!data.success || !Array.isArray(data.requests) || data.requests.length === 0) return;
                data.requests.forEach(req => {
                    const div = document.createElement('div');
                    div.className = 'notification-item';
                    div.id = `unenrollment-request-${req.request_id}`;
                    const time = new Date(req.requested_at);
                    const timeStr = time.toLocaleDateString() + ' ' + time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    div.innerHTML = `
                        <div class="notification-icon">
                            <i class="fas fa-user-minus"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Unenrollment Request</div>
                            <div class="notification-message">
                                ${escapeHtml(req.first_name + ' ' + req.last_name)} has requested to unenroll from
                                <strong>${escapeHtml(req.subject_name)}</strong>
                                (Class Code: ${escapeHtml(req.class_code)}).
                            </div>
                            <div class="notification-meta">
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    ${timeStr}
                                </div>
                                <div class="notification-status status-unread">PENDING</div>
                            </div>
                            <div class="notification-actions" style="margin-top: 10px;">
                                <button class="btn-enhanced btn-primary" onclick="showConfirmationModal('${req.request_id}', 'accept', 'unenrollment')">Accept</button>
                                <button class="btn-enhanced btn-secondary" onclick="showConfirmationModal('${req.request_id}', 'reject', 'unenrollment')">Reject</button>
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                });
            })
            .catch(() => {});
    }

    // Initialize badge on page load and set polling for real-time updates
    updateNotificationBadge();
    setInterval(updateNotificationBadge, 5000); // every 5s

    // Utils
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
</script>
<style>
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

/* Pending pill removed: we now rely solely on the unread badge */
/* Pending pill styling */
.pending-pill {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ff9800;
    color: #fff;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    line-height: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}
.notification-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}
.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
    border-radius: 5px;
    cursor: pointer;
}
.btn-success {
    background-color: #28a745;
    color: white;
    border: none;
}
.btn-success:hover {
    background-color: #218838;
}
.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
}
.btn-danger:hover {
    background-color: #c82333;
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
    animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform: scale(0.9);
    opacity: 0;
    position: relative;
}

.modal.show .modal-content {
    transform: scale(1);
    opacity: 1;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes modalSlideIn {
    from {
        transform: scale(0.9) translateY(-20px);
        opacity: 0;
    }
    to {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, #F75270 0%, #DC143C 100%);
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
    background: rgba(255, 255, 255, 0.2);
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

.btn-enhanced.btn-primary {
    background: linear-gradient(135deg, #F75270 0%, #DC143C 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(247, 82, 112, 0.2);
}

.btn-enhanced.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(247, 82, 112, 0.3);
}

.btn-enhanced.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(108, 117, 125, 0.2);
}

.btn-enhanced.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(108, 117, 125, 0.3);
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

    .notification-actions {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
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
// User dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.querySelector('.user-dropdown .dropdown-toggle');
    const dropdownMenu = document.querySelector('.user-dropdown .dropdown-menu');
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!dropdownMenu.contains(e.target) && !dropdownToggle.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});
</script>
