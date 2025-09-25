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
$stmt = $pdo->prepare("SELECT ur.request_id, ur.requested_at, ur.class_id, c.class_name, s.subject_name, p.first_name, p.last_name
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
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-book"></i>
                    My Enrolled Classes
                </h1>
                <p class="page-subtitle">Manage your current class enrollments and view pending requests</p>
            </div>
        </div>

        <!-- Enrolled Classes Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-graduation-cap"></i>
                    Current Enrollments
                </h2>
                <div class="section-stats">
                    <span class="stat-badge">
                        <i class="fas fa-users"></i>
                        <?php echo count($enrolled_classes); ?> Classes
                    </span>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Class Name</th>
                            <th><i class="fas fa-book-open"></i> Subject</th>
                            <th><i class="fas fa-user-tie"></i> Professor</th>
                            <th><i class="fas fa-calendar-alt"></i> Schedule</th>
                            <th><i class="fas fa-map-marker-alt"></i> Room</th>
                            <th class="text-center"><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrolled_classes)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="fas fa-inbox"></i>
                                        <h3>No Classes Enrolled</h3>
                                        <p>You haven't enrolled in any classes yet. Contact your administrator to get started.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enrolled_classes as $class): ?>
                                <?php
                                $professor_name = (!empty($class['first_name']) && !empty($class['last_name']))
                                    ? 'Prof. ' . $class['first_name'] . ' ' . $class['last_name']
                                    : 'N/A';

                                // Check if there's a pending unenrollment request for this class
                                $has_pending_request = false;
                                foreach ($pending_unenrollment_requests as $request) {
                                    if ($request['class_id'] == $class['class_id']) {
                                        $has_pending_request = true;
                                        break;
                                    }
                                }

                                $button_text = $has_pending_request ? 'Pending Approval' : 'Unenroll';
                                $button_class = $has_pending_request ? 'btn-warning' : 'btn-danger';
                                $button_icon = $has_pending_request ? 'fas fa-clock' : 'fas fa-times';
                                $button_disabled = $has_pending_request ? 'disabled' : '';
                                ?>
                                <tr>
                                    <td class="class-name-cell">
                                        <div class="class-info">
                                            <span class="class-name"><?php echo htmlspecialchars($class['class_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="subject-cell">
                                        <span class="subject-tag"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                    </td>
                                    <td class="professor-cell">
                                        <div class="professor-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($professor_name); ?></span>
                                        </div>
                                    </td>
                                    <td class="schedule-cell">
                                        <div class="schedule-info">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo htmlspecialchars($class['schedule']); ?></span>
                                        </div>
                                    </td>
                                    <td class="room-cell">
                                        <span class="room-badge"><?php echo htmlspecialchars($class['room']); ?></span>
                                    </td>
                                    <td class="action-cell">
                                        <button class="btn <?php echo $button_class; ?> <?php echo $button_disabled ? 'btn-disabled' : ''; ?>"
                                                <?php echo $button_disabled; ?>
                                                onclick="unenrollFromClass('<?php echo $class['class_id']; ?>')">
                                            <i class="<?php echo $button_icon; ?>"></i>
                                            <?php echo $button_text; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Unenrollment Requests Section -->
        <?php if (!empty($pending_unenrollment_requests)): ?>
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i>
                        Pending Unenrollment Requests
                    </h2>
                    <div class="section-stats">
                        <span class="stat-badge warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo count($pending_unenrollment_requests); ?> Pending
                        </span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-tag"></i> Class Name</th>
                                <th><i class="fas fa-book-open"></i> Subject</th>
                                <th><i class="fas fa-user-tie"></i> Professor</th>
                                <th><i class="fas fa-calendar"></i> Request Date</th>
                                <th class="text-center"><i class="fas fa-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_unenrollment_requests as $request): ?>
                                <?php
                                $professor_name = (!empty($request['first_name']) && !empty($request['last_name']))
                                    ? 'Prof. ' . $request['first_name'] . ' ' . $request['last_name']
                                    : 'N/A';
                                ?>
                                <tr>
                                    <td class="class-name-cell">
                                        <span class="class-name"><?php echo htmlspecialchars($request['class_name']); ?></span>
                                    </td>
                                    <td class="subject-cell">
                                        <span class="subject-tag"><?php echo htmlspecialchars($request['subject_name']); ?></span>
                                    </td>
                                    <td class="professor-cell">
                                        <div class="professor-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($professor_name); ?></span>
                                        </div>
                                    </td>
                                    <td class="schedule-cell">
                                        <div class="date-info">
                                            <i class="fas fa-calendar-day"></i>
                                            <span><?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge pending">
                                            <i class="fas fa-clock"></i>
                                            Pending Approval
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm Unenrollment Request
                </h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4>Submit Unenrollment Request</h4>
                <p>Are you sure you want to submit an unenrollment request for this class? This action will send a request to your professor for approval.</p>

                <div class="info-notice">
                    <div class="notice-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="notice-content">
                        <strong>Important:</strong> Your professor will need to approve this request before you are unenrolled from the class. You will remain enrolled until approval is granted.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="btn btn-warning" id="confirmUnenrollBtn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Request
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <style>
        /* Page Layout */
        .main-content {
            padding: 2rem;
            min-height: calc(100vh - 70px);
            background: var(--light);
            transition: margin-left 0.3s ease, padding 0.3s ease;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(247, 82, 112, 0.3);
        }

        .page-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 400;
        }

        /* Content Sections */
        .content-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            background: linear-gradient(135deg, var(--light) 0%, var(--light-gray) 100%);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(247, 82, 112, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-stats {
            display: flex;
            gap: 0.5rem;
        }

        .stat-badge {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-badge.warning {
            background: var(--warning);
            color: var(--dark);
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .data-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .data-table th i {
            margin-right: 0.5rem;
            opacity: 0.8;
        }

        .data-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(247, 82, 112, 0.1);
            vertical-align: middle;
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: rgba(247, 202, 201, 0.1);
            transform: translateX(5px);
        }

        .data-table .text-center {
            text-align: center;
        }

        /* Table Cell Styling */
        .class-name-cell {
            min-width: 200px;
        }

        .class-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }

        .subject-cell {
            min-width: 150px;
        }

        .subject-tag {
            background: var(--accent);
            color: var(--dark);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .professor-cell {
            min-width: 180px;
        }

        .professor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
        }

        .professor-info i {
            color: var(--primary);
        }

        .schedule-cell {
            min-width: 150px;
        }

        .schedule-info,
        .date-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
        }

        .schedule-info i,
        .date-info i {
            color: var(--primary);
        }

        .room-cell {
            min-width: 100px;
        }

        .room-badge {
            background: var(--light);
            color: var(--dark);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .action-cell {
            min-width: 140px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .empty-state-content i {
            font-size: 4rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
        }

        .empty-state-content h3 {
            color: var(--gray);
            font-size: 1.25rem;
            margin: 0;
        }

        .empty-state-content p {
            color: var(--gray);
            font-size: 0.95rem;
            max-width: 400px;
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            min-width: 120px;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(247, 82, 112, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 20, 60, 0.3);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }

        .btn-warning:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(253, 235, 208, 0.3);
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--dark);
            transform: translateY(-2px);
        }

        .btn:disabled,
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.pending {
            background: linear-gradient(135deg, var(--warning) 0%, var(--accent) 100%);
            color: var(--dark);
            border: 1px solid rgba(247, 82, 112, 0.2);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            animation: modalFadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal-container {
            position: relative;
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.4s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid rgba(247, 82, 112, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-close {
            background: var(--light);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
        }

        .modal-icon.warning {
            background: var(--warning);
            color: var(--dark);
        }

        .modal-body h4 {
            text-align: center;
            margin-bottom: 1rem;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .modal-body p {
            text-align: center;
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .info-notice {
            background: var(--light);
            border: 1px solid rgba(247, 82, 112, 0.1);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .notice-icon {
            color: var(--primary);
            font-size: 1.1rem;
            margin-top: 0.1rem;
        }

        .notice-content {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .notice-content strong {
            color: var(--dark);
        }

        .modal-footer {
            padding: 1.5rem 2rem 2rem;
            border-top: 1px solid rgba(247, 82, 112, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Toast Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        }

        .toast {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-left: 4px solid var(--success);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 350px;
            max-width: 400px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.4s ease;
            pointer-events: all;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            border-left-color: var(--success);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        .toast-icon {
            font-size: 1.25rem;
        }

        .toast.success .toast-icon {
            color: var(--success);
        }

        .toast.error .toast-icon {
            color: var(--danger);
        }

        .toast-message {
            flex: 1;
            font-weight: 500;
            color: var(--dark);
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--gray);
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toast-close:hover {
            background: var(--light);
            color: var(--dark);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .section-header {
                padding: 1rem 1.5rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .data-table th {
                font-size: 0.8rem;
            }

            .modal-container {
                margin: 1rem;
                width: calc(100% - 2rem);
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1.5rem;
            }

            .toast {
                min-width: 300px;
                max-width: calc(100vw - 40px);
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .page-header-content {
                flex-direction: column;
                text-align: center;
            }

            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }

            .modal-footer {
                flex-direction: column-reverse;
            }

            .modal-footer .btn {
                width: 100%;
                margin: 0;
            }
        }
    </style>

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
