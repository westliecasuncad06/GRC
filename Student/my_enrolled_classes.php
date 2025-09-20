<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Get enrolled classes
$stmt = $pdo->prepare("SELECT c.*, s.subject_name, p.first_name, p.last_name
                     FROM student_classes sc
                     JOIN classes c ON sc.class_id = c.class_id
                     JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN professors p ON c.professor_id = p.professor_id
                     LEFT JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                     WHERE sc.student_id = ? AND c.status != 'archived'");
$stmt->execute([$student_id]);
$enrolled_classes = $stmt->fetchAll();

// Get pending unenrollment requests
$stmt = $pdo->prepare("SELECT ur.request_id, ur.requested_at, c.class_name, s.subject_name, p.first_name, p.last_name
                     FROM unenrollment_requests ur
                     JOIN classes c ON ur.class_id = c.class_id
                     JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN professors p ON c.professor_id = p.professor_id
                     WHERE ur.student_id = ? AND ur.status = 'pending'
                     ORDER BY ur.requested_at DESC");
$stmt->execute([$student_id]);
$pending_unenrollment_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Enrolled Classes - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles_fixed.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --primary: #DC143C;
            --primary-dark: #B01030;
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

        /* Enhanced Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .page-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0.5rem 0 0 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Classes Grid */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .class-card-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .class-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .class-card-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }

        .class-header-enhanced {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .class-icon-enhanced {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .class-info-enhanced {
            flex: 1;
            margin-left: 1rem;
        }

        .class-name-enhanced {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 0.25rem 0;
        }

        .class-subject-enhanced {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
            margin: 0 0 1rem 0;
        }

        .class-details-enhanced {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .class-detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .class-detail-icon {
            font-size: 1rem;
            width: 20px;
            text-align: center;
            color: var(--primary);
        }

        .class-detail-label {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }

        .class-detail-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
        }

        .class-actions-enhanced {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .class-action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .class-action-btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .class-action-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .class-action-btn-warning {
            background: var(--warning);
            color: var(--dark);
        }

        .class-action-btn-warning:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .class-action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Enhanced Empty State */
        .empty-state-enhanced {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
            grid-column: 1 / -1;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.6;
            color: var(--primary);
        }

        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .empty-state-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .empty-state-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Table Header */
        .table-header-enhanced {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title-enhanced {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Table Container */
        .table-container-enhanced {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-enhanced {
            width: 100%;
            border-collapse: collapse;
        }

        .table-enhanced th {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-enhanced td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }

        .table-enhanced tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table-enhanced tr:hover {
            background-color: #e9ecef;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 100px;
        }

        .status-badge.pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 16px;
            padding: 0;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: #212529;
            border-radius: 16px 16px 0 0;
        }

        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .modal-close {
            background: rgba(0, 0, 0, 0.1);
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #212529;
            padding: 0.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.2);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 0 0 16px 16px;
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }

        .toast {
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            border-left-color: #28a745;
        }

        .toast.error {
            border-left-color: #dc3545;
        }

        .toast-icon {
            font-size: 1.25rem;
        }

        .toast-message {
            flex: 1;
            font-weight: 500;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-close:hover {
            color: #343a40;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .classes-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .class-card-enhanced {
                padding: 1.5rem;
            }

            .class-details-enhanced {
                grid-template-columns: 1fr;
            }

            .class-actions-enhanced {
                flex-direction: column;
            }

            .class-action-btn {
                width: 100%;
                justify-content: center;
            }

            .table-header-enhanced {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .page-title {
                font-size: 2rem;
            }

            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
            }

            .toast {
                min-width: auto;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-book" style="margin-right: 10px;"></i>My Enrolled Classes</h1>
            <p class="page-subtitle">Manage your current class enrollments and view pending requests</p>
        </div>

        <!-- Enrolled Classes Section -->
        <div class="classes-grid">
            <?php
            foreach ($enrolled_classes as $class) {
                $professor_name = (!empty($class['first_name']) && !empty($class['last_name'])) ? 'Prof. ' . $class['first_name'] . ' ' . $class['last_name'] : 'N/A';

                // Check if there's a pending unenrollment request for this class
                $has_pending_request = false;
                foreach ($pending_unenrollment_requests as $request) {
                    if ($request['class_id'] == $class['class_id']) {
                        $has_pending_request = true;
                        break;
                    }
                }

                $button_text = $has_pending_request ? 'Pending Approval' : 'Unenroll';
                $button_class = $has_pending_request ? 'class-action-btn-warning' : 'class-action-btn-primary';
                $button_icon = $has_pending_request ? 'fas fa-clock' : 'fas fa-times';
                $button_disabled = $has_pending_request ? 'disabled' : '';

                echo '<div class="class-card-enhanced">
                    <div class="class-header-enhanced">
                        <div class="class-icon-enhanced">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="class-info-enhanced">
                            <h3 class="class-name-enhanced">' . htmlspecialchars($class['class_name']) . '</h3>
                            <p class="class-subject-enhanced">' . htmlspecialchars($class['subject_name']) . '</p>
                        </div>
                    </div>

                    <div class="class-details-enhanced">
                        <div class="class-detail-item">
                            <i class="fas fa-user-tie class-detail-icon"></i>
                            <div>
                                <div class="class-detail-label">Professor</div>
                                <div class="class-detail-value">' . htmlspecialchars($professor_name) . '</div>
                            </div>
                        </div>
                        <div class="class-detail-item">
                            <i class="fas fa-clock class-detail-icon"></i>
                            <div>
                                <div class="class-detail-label">Schedule</div>
                                <div class="class-detail-value">' . htmlspecialchars($class['schedule']) . '</div>
                            </div>
                        </div>
                        <div class="class-detail-item">
                            <i class="fas fa-map-marker-alt class-detail-icon"></i>
                            <div>
                                <div class="class-detail-label">Room</div>
                                <div class="class-detail-value">' . htmlspecialchars($class['room']) . '</div>
                            </div>
                        </div>
                        <div class="class-detail-item">
                            <i class="fas fa-calendar class-detail-icon"></i>
                            <div>
                                <div class="class-detail-label">Status</div>
                                <div class="class-detail-value" style="color: var(--success);">
                                    <i class="fas fa-check-circle"></i> Enrolled
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="class-actions-enhanced">
                        <button class="class-action-btn ' . $button_class . '" ' . $button_disabled . ' onclick="unenrollFromClass(\'' . $class['class_id'] . '\')">
                            <i class="' . $button_icon . '"></i> ' . $button_text . '
                        </button>
                    </div>
                </div>';
            }

            if (empty($enrolled_classes)) {
                echo '<div class="empty-state-enhanced">
                    <i class="fas fa-book-open empty-state-icon"></i>
                    <div class="empty-state-text">No classes enrolled yet</div>
                    <p style="margin-bottom: 2rem; color: var(--gray);">You haven\'t enrolled in any classes for this semester.</p>
                    <button class="empty-state-btn">
                        <i class="fas fa-plus"></i> Browse Available Classes
                    </button>
                </div>';
            }
            ?>
        </div>

        <!-- Pending Unenrollment Requests Section -->
        <?php if (!empty($pending_unenrollment_requests)): ?>
            <div class="table-header-enhanced">
                <h2 class="table-title-enhanced"><i class="fas fa-clock" style="margin-right: 10px;"></i>Pending Unenrollment Requests</h2>
            </div>
            <div class="table-container-enhanced">
                <table class="table-enhanced">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Subject</th>
                            <th>Professor</th>
                            <th>Request Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_unenrollment_requests as $request): ?>
                            <?php
                            $professor_name = (!empty($request['first_name']) && !empty($request['last_name'])) ? 'Prof. ' . $request['first_name'] . ' ' . $request['last_name'] : 'N/A';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($professor_name); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?></td>
                                <td><span class="status-badge pending">Pending Approval</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Unenrollment Request</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit an unenrollment request for this class? This will send a request to your professor for approval.</p>
                <div id="requestInfo" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--warning);">
                    <p><strong>Note:</strong> Your professor will need to approve this request before you are unenrolled from the class.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="class-action-btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="class-action-btn class-action-btn-warning" id="confirmUnenrollBtn"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        let currentClassId = null;

        function unenrollFromClass(classId) {
            console.log('unenrollFromClass called with classId:', classId);
            currentClassId = classId;
            showModal();
            // Enable the confirm button when a class is selected
            document.getElementById('confirmUnenrollBtn').disabled = false;
        }

        function showModal() {
            document.getElementById('confirmationModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('confirmationModal').classList.remove('show');
            currentClassId = null;
            // Disable the confirm button when modal is closed
            document.getElementById('confirmUnenrollBtn').disabled = true;
        }

        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} toast-icon"></i>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        function confirmUnenroll() {
            if (!currentClassId) {
                showToast('Error: No class selected for unenrollment.', 'error');
                return;
            }

            // Save currentClassId to a local variable before closing modal
            const classIdToUnenroll = currentClassId;

            closeModal();

            // Show loading state
            showToast('Submitting unenrollment request...', 'success');

            fetch('../php/unenroll_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    class_id: classIdToUnenroll
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Unenrollment request submitted successfully! Waiting for professor approval.', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while submitting the request.', 'error');
            });
        }

        // Event listeners
        window.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('confirmUnenrollBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', confirmUnenroll);
            }

            const modal = document.getElementById('confirmationModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });
            }
        });
    </script>
</body>
</html>
