
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
                    <div class="notifications-container">
                        <?php foreach ($all_requests as $request): ?>
                            <?php
                            $status_class = '';
                            $status_icon = '';
                            $status_text = '';
                            switch ($request['status']) {
                                case 'accepted':
                                    $status_class = 'status-approved';
                                    $status_icon = 'fa-check-circle';
                                    $status_text = 'Approved';
                                    break;
                                case 'rejected':
                                    $status_class = 'status-rejected';
                                    $status_icon = 'fa-times-circle';
                                    $status_text = 'Rejected';
                                    break;
                                case 'pending':
                                    $status_class = 'status-pending';
                                    $status_icon = 'fa-clock';
                                    $status_text = 'Pending';
                                    break;
                            }

                            $request_type_class = $request['request_type'] === 'enrollment' ? 'request-enrollment' : 'request-unenrollment';
                            $request_type_icon = $request['request_type'] === 'enrollment' ? 'fa-plus-circle' : 'fa-minus-circle';
                            $request_type_text = ucfirst($request['request_type']);
                            ?>
                            <div class="notification-card" onclick="toggleDetails(this)">
                                <div class="notification-header">
                                    <div class="notification-subject">
                                        <i class="fas fa-book-open subject-icon"></i>
                                        <span class="subject-name"><?php echo htmlspecialchars($request['subject_name']); ?></span>
                                    </div>
                                    <div class="notification-meta">
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas <?php echo $status_icon; ?>"></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                        <i class="fas fa-chevron-down expand-icon"></i>
                                    </div>
                                </div>
                                <div class="notification-details" style="display: none;">
                                    <div class="detail-item">
                                        <strong>Type:</strong>
                                        <span class="request-type-badge <?php echo $request_type_class; ?>">
                                            <i class="fas <?php echo $request_type_icon; ?>"></i>
                                            <?php echo $request_type_text; ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Class:</strong>
                                        <span class="class-tag"><?php echo htmlspecialchars($request['class_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Requested:</strong>
                                        <div class="date-time-info">
                                            <div class="date-info">
                                                <i class="fas fa-calendar-day"></i>
                                                <span><?php echo date('M j, Y', strtotime($request['requested_at'])); ?></span>
                                            </div>
                                            <div class="time-info">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('g:i A', strtotime($request['requested_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

.notifications-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.notification-subject {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.subject-icon {
    color: var(--primary);
    font-size: 1.2rem;
}

.subject-name {
    font-weight: 600;
    color: var(--dark);
    font-size: 1rem;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.expand-icon {
    color: var(--gray);
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.notification-details {
    padding: 1.5rem;
    background: white;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item strong {
    min-width: 80px;
    color: var(--gray);
    font-weight: 600;
}

.class-tag {
    background: rgba(247, 82, 112, 0.1);
    color: var(--primary);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.date-time-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
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

    .notification-card {
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

    .notifications-container {
        gap: 0.75rem;
    }

    .notification-card {
        padding: 0.75rem;
        gap: 0.75rem;
    }

    .notification-header {
        padding: 1rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .notification-subject {
        gap: 0.5rem;
    }

    .subject-name {
        font-size: 0.9rem;
    }

    .notification-meta {
        width: 100%;
        justify-content: space-between;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
    }

    .notification-details {
        padding: 1rem;
    }

    .detail-item {
        font-size: 0.85rem;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .detail-item strong {
        min-width: 70px;
    }

    .request-type-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
    }

    .class-tag {
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
    }

    .date-time-info {
        gap: 0.5rem;
    }

    .date-info, .time-info {
        font-size: 0.75rem;
        gap: 0.25rem;
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
    window.openNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        modal.classList.add('show');
    };

    window.closeNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('show');
        // Hide the notification badges after viewing
        document.querySelectorAll('.notification-badge').forEach(badge => {
            badge.style.display = 'none';
        });
    };

    window.toggleDetails = function(card) {
        const detailsDiv = card.querySelector('.notification-details');
        const icon = card.querySelector('.expand-icon');
        if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
            detailsDiv.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            detailsDiv.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
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
