    <!-- Navbar -->
<style>
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
            <div class="history-list" id="historyList">
                <?php if (empty($all_requests)): ?>
                    <div class="no-notifications">
                        <div class="no-notifications-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="no-notifications-text">No Request History</div>
                        <div class="no-notifications-subtext">You haven't submitted any enrollment or unenrollment requests yet.</div>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-tag"></i> Type</th>
                                    <th><i class="fas fa-book-open"></i> Subject</th>
                                    <th><i class="fas fa-graduation-cap"></i> Class</th>
                                    <th><i class="fas fa-calendar-plus"></i> Requested</th>
                                    <th class="text-center"><i class="fas fa-info-circle"></i> Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_requests as $request): ?>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch ($request['status']) {
                                        case 'approved':
                                            $status_class = 'status-approved';
                                            $status_icon = 'fa-check-circle';
                                            break;
                                        case 'rejected':
                                            $status_class = 'status-rejected';
                                            $status_icon = 'fa-times-circle';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_icon = 'fa-clock';
                                            break;
                                    }

                                    $request_type_class = $request['request_type'] === 'enrollment' ? 'request-enrollment' : 'request-unenrollment';
                                    $request_type_icon = $request['request_type'] === 'enrollment' ? 'fa-plus-circle' : 'fa-minus-circle';
                                    $request_type_text = ucfirst($request['request_type']);
                                    ?>
                                    <tr>
                                        <td class="request-type-cell">
                                            <span class="request-type-badge <?php echo $request_type_class; ?>">
                                                <i class="fas <?php echo $request_type_icon; ?>"></i>
                                                <?php echo $request_type_text; ?>
                                            </span>
                                        </td>
                                        <td class="subject-cell">
                                            <span class="subject-tag"><?php echo htmlspecialchars($request['subject_name']); ?></span>
                                        </td>
                                        <td class="class-cell">
                                            <span class="class-name"><?php echo htmlspecialchars($request['class_name']); ?></span>
                                        </td>
                                        <td class="date-cell">
                                            <div class="date-info">
                                                <i class="fas fa-calendar-day"></i>
                                                <span><?php echo date('M j, Y', strtotime($request['requested_at'])); ?></span>
                                            </div>
                                            <div class="time-info">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('g:i A', strtotime($request['requested_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <i class="fas <?php echo $status_icon; ?>"></i>
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    font-weight: bold;
    min-width: 1.2rem;
    text-align: center;
    border: 2px solid white;
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

.modal-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.tab-button {
    flex: 1;
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--gray);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    position: relative;
}

.tab-button.active {
    color: var(--primary);
    background: white;
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--primary);
}

.tab-button:hover:not(.active) {
    color: var(--primary);
    background: rgba(247, 82, 112, 0.05);
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

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(247, 82, 112, 0.2);
}

.btn-primary:hover {
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
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.notification-item.unread {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-left: 4px solid var(--primary);
}

.notification-item.read {
    background: #f8f9fa;
}

.notification-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
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
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.notification-message {
    font-size: 0.9rem;
    color: var(--gray);
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.8rem;
    color: var(--gray);
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
    color: var(--primary);
}

.status-read {
    background: rgba(108, 117, 125, 0.1);
    color: var(--gray);
}

.no-notifications {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--gray);
}

.no-notifications-icon {
    font-size: 4rem;
    color: var(--light-gray);
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

/* History List Styles */
.history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.data-table thead {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.data-table th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table th i {
    margin-right: 0.5rem;
    opacity: 0.8;
}

.data-table tbody tr {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: background-color 0.2s ease;
}

.data-table tbody tr:hover {
    background: rgba(247, 82, 112, 0.02);
}

.data-table td {
    padding: 1.25rem 1.5rem;
    vertical-align: middle;
}

.request-type-cell {
    width: 100px;
}

.request-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.request-enrollment {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.request-unenrollment {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.subject-cell {
    width: 120px;
}

.subject-tag {
    background: rgba(247, 82, 112, 0.1);
    color: var(--primary);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.class-cell {
    width: 120px;
}

.class-name {
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.date-cell {
    width: 140px;
}

.date-info, .time-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray);
}

.date-info i, .time-info i {
    width: 14px;
    color: var(--primary);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-approved {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-rejected {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.status-pending {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.text-center {
    text-align: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
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
    window.openNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        modal.classList.add('show');
    };

    window.closeNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('show');
    };

    window.markAsRead = function(notificationId) {
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
                // Update the notification item
                const notificationItem = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                    const statusElement = notificationItem.querySelector('.notification-status');
                    if (statusElement) {
                        statusElement.classList.remove('status-unread');
                        statusElement.classList.add('status-read');
                        statusElement.textContent = 'READ';
                    }
                    const actionButton = notificationItem.querySelector('.notification-actions');
                    if (actionButton) {
                        actionButton.remove();
                    }
                }
            } else {
                alert('Error marking notification as read: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking the notification as read.');
        });
    };

    window.markAllAsRead = function() {
        if (!confirm('Are you sure you want to mark all notifications as read?')) {
            return;
        }

        fetch('notifications.php?mark_read=all', {
            method: 'GET'
        })
        .then(response => {
            if (response.ok) {
                // Reload the page to reflect changes
                location.reload();
            } else {
                alert('Error marking all notifications as read.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking all notifications as read.');
        });
    };

    window.switchTab = function(tabName) {
        const tabButtons = document.querySelectorAll('.tab-button');
        const notificationList = document.getElementById('notificationList');
        const historyList = document.getElementById('historyList');

        // Remove active class from all tabs
        tabButtons.forEach(button => button.classList.remove('active'));

        // Add active class to clicked tab
        const activeTab = document.querySelector(`.tab-button[onclick="switchTab('${tabName}')"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Show/hide content based on tab
        if (tabName === 'notifications') {
            notificationList.style.display = 'flex';
            historyList.style.display = 'none';
        } else if (tabName === 'history') {
            notificationList.style.display = 'none';
            historyList.style.display = 'flex';
        }
    };

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
