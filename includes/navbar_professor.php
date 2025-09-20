<?php
require_once '../php/db.php';

$professor_id = $_SESSION['user_id'] ?? null;
$pending_requests = [];
$pending_unenrollment_requests = [];
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

    // Separate pending and handled requests
    $pending_requests = array_filter($all_requests, function($req) {
        return $req['status'] === 'pending';
    });
    $handled_requests = array_filter($all_requests, function($req) {
        return $req['status'] !== 'pending';
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

    // Separate pending and handled unenrollment requests
    $pending_unenrollment_requests = array_filter($all_unenrollment_requests, function($req) {
        return $req['status'] === 'pending';
    });

    $pending_requests_count = count($pending_requests) + count($pending_unenrollment_requests);
}
?>

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
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?></span>
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
                <?php if ($pending_requests_count > 0): ?>
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="notification-item" id="request-<?php echo $request['request_id']; ?>">
                            <div class="notification-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Enrollment Request</div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> has requested to enroll in
                                    <strong><?php echo htmlspecialchars($request['subject_name']); ?></strong> (Class Code: <?php echo htmlspecialchars($request['class_code']); ?>).
                                </div>
                                <div class="notification-meta">
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php
                                            $datetime = new DateTime($request['requested_at']);
                                            echo $datetime->format('M j, Y, g:i a');
                                        ?>
                                    </div>
                                    <div class="notification-status status-unread">Pending</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-success btn-sm" onclick="handleEnrollmentRequest('<?php echo $request['request_id']; ?>', 'accept')">Accept</button>
                                    <button class="btn btn-danger btn-sm" onclick="handleEnrollmentRequest('<?php echo $request['request_id']; ?>', 'reject')">Reject</button>
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
                                    <strong><?php echo htmlspecialchars($request['subject_name']); ?></strong> (Class Code: <?php echo htmlspecialchars($request['class_code']); ?>).
                                </div>
                                <div class="notification-meta">
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php
                                            $datetime = new DateTime($request['requested_at']);
                                            echo $datetime->format('M j, Y, g:i a');
                                        ?>
                                    </div>
                                    <div class="notification-status status-unread">Pending</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-success btn-sm" onclick="handleUnenrollmentRequest('<?php echo $request['request_id']; ?>', 'accept')">Accept</button>
                                    <button class="btn btn-danger btn-sm" onclick="handleUnenrollmentRequest('<?php echo $request['request_id']; ?>', 'reject')">Reject</button>
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
                        <div class="no-notifications-subtext">You're all caught up!</div>
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

    // Handle enrollment request accept/reject
    function handleEnrollmentRequest(requestId, action) {
        if (!['accept', 'reject'].includes(action)) {
            alert('Invalid action');
            return;
        }
        fetch('../php/handle_enrollment_request.php', {
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
                alert(data.message);
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

    // Handle unenrollment request accept/reject
    function handleUnenrollmentRequest(requestId, action) {
        if (!['accept', 'reject'].includes(action)) {
            alert('Invalid action');
            return;
        }
        fetch('../php/handle_unenrollment_request.php', {
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
                alert(data.message);
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
    top: 4px;
    right: 4px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 7px;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1;
    pointer-events: none;
    user-select: none;
    box-shadow: 0 0 2px rgba(0,0,0,0.3);
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
