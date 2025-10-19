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
            <?php if ($pending_requests_count > 0): ?>
                <span class="notification-badge"><?php echo $pending_requests_count; ?></span>
            <?php endif; ?>
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
            <div class="notification-list">
                <?php if (!empty($enrollment_notifications) || !empty($pending_unenrollment_requests)): ?>
                    <?php foreach ($enrollment_notifications as $notification): ?>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-message"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></div>
                                <div class="notification-meta">
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M j, Y, g:i a', strtotime($notification['created_at'])); ?>
                                    </div>
                                    <div class="notification-status <?php echo $notification['is_read'] ? 'status-read' : 'status-unread'; ?>">
                                        <?php echo $notification['is_read'] ? 'READ' : 'UNREAD'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($pending_unenrollment_requests as $request): ?>
                        <div class="notification-item" id="unenrollment-request-<?php echo $request['request_id']; ?>">
                            <div class="notification-icon">
                                <i class="fas fa-user-minus"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Unenrollment Request</div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> has requested to unenroll from
                                    <strong><?php echo htmlspecialchars($request['subject_name']); ?></strong>
                                    (Class Code: <?php echo htmlspecialchars($request['class_code']); ?>).
                                </div>
                                <div class="notification-meta">
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M j, Y, g:i a', strtotime($request['requested_at'])); ?>
                                    </div>
                                    <div class="notification-status status-unread">PENDING</div>
                                </div>
                                <div class="notification-actions" style="margin-top: 10px;">
                                    <button class="btn-enhanced btn-primary" onclick="showConfirmationModal('<?php echo $request['request_id']; ?>', 'accept', 'unenrollment')">Accept</button>
                                    <button class="btn-enhanced btn-secondary" onclick="showConfirmationModal('<?php echo $request['request_id']; ?>', 'reject', 'unenrollment')">Reject</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-notifications">
                        <div class="no-notifications-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <div class="no-notifications-text">No new notifications</div>
                        <div class="no-notifications-subtext">You have no pending requests or recent enrollments.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-enhanced btn-secondary-enhanced" onclick="closeNotificationModal()">
                <i class="fas fa-times"></i> Close
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
    function closeNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('show');
        // Reset the notification button to original state
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.classList.remove('has-notifications');
        }
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
