<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Get enrolled classes
$stmt = $pdo->prepare("SELECT c.class_id, c.class_name, c.class_code, c.subject_id, c.professor_id, c.schedule, c.room, c.created_at, c.updated_at, c.section, c.semester_id, c.status, c.school_year_semester_id, s.subject_name, p.first_name, p.last_name
                     FROM student_classes sc
                     JOIN classes c ON sc.class_id = c.class_id
                     LEFT JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN professors p ON c.professor_id = p.professor_id
                     LEFT JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                     WHERE sc.student_id = ? AND c.status != 'archived'");
$stmt->execute([$student_id]);
$enrolled_classes = $stmt->fetchAll();

// Get all unenrollment requests (not just pending) to check if any exist for each class
$stmt = $pdo->prepare("SELECT ur.request_id, ur.requested_at, ur.class_id, ur.status, c.class_name, s.subject_name, p.first_name, p.last_name
                     FROM unenrollment_requests ur
                     JOIN classes c ON ur.class_id = c.class_id
                     LEFT JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN professors p ON c.professor_id = p.professor_id
                     WHERE ur.student_id = ?
                     ORDER BY ur.requested_at DESC");
$stmt->execute([$student_id]);
$unenrollment_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Enrolled Classes - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/notifications-mobile.css" />
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
                <table class="data-table desktop-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Class Name</th>
                            <th><i class="fas fa-book-open"></i> Subject</th>
                            <th class="mobile-hidden"><i class="fas fa-user-tie"></i> Professor</th>
                            <th class="mobile-hidden"><i class="fas fa-calendar-alt"></i> Schedule</th>
                            <th class="mobile-hidden"><i class="fas fa-map-marker-alt"></i> Room</th>
                            <th class="text-center mobile-hidden"><i class="fas fa-cogs"></i> Actions</th>
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
                                foreach ($unenrollment_requests as $request) {
                                    if (isset($request['class_id']) && $request['class_id'] == $class['class_id'] && $request['status'] == 'pending') {
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
                                    <td class="subject-cell mobile-clickable" onclick="openMobileDetailsModal('<?php echo htmlspecialchars($class['subject_name'] ?? 'N/A'); ?>', '<?php echo htmlspecialchars($class['schedule']); ?>', '<?php echo htmlspecialchars($class['class_name']); ?>', '<?php echo htmlspecialchars($professor_name); ?>', '<?php echo htmlspecialchars($class['room']); ?>', '<?php echo $class['class_id']; ?>', <?php echo $has_pending_request ? 'true' : 'false'; ?>)">
                                        <span class="subject-tag"><?php echo htmlspecialchars($class['subject_name'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="professor-cell mobile-hidden">
                                        <div class="professor-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($professor_name); ?></span>
                                        </div>
                                    </td>
                                    <td class="schedule-cell mobile-hidden">
                                        <div class="schedule-info">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo htmlspecialchars($class['schedule']); ?></span>
                                        </div>
                                    </td>
                                    <td class="room-cell mobile-hidden">
                                        <span class="room-badge"><?php echo htmlspecialchars($class['room']); ?></span>
                                    </td>
                                    <td class="action-cell mobile-hidden">
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
                <div class="mobile-table">
                    <?php if (empty($enrolled_classes)): ?>
                        <div class="empty-state">
                            <div class="empty-state-content">
                                <i class="fas fa-inbox"></i>
                                <h3>No Classes Enrolled</h3>
                                <p>You haven't enrolled in any classes yet. Contact your administrator to get started.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($enrolled_classes as $class): ?>
                            <?php
                            $professor_name = (!empty($class['first_name']) && !empty($class['last_name']))
                                ? 'Prof. ' . $class['first_name'] . ' ' . $class['last_name']
                                : 'N/A';

                            // Check if there's any unenrollment request for this class (any status)
                            $has_pending_request = false;
                            foreach ($unenrollment_requests as $request) {
                                if (isset($request['class_id']) && $request['class_id'] == $class['class_id']) {
                                    $has_pending_request = true;
                                    break;
                                }
                            }

                            $button_text = $has_pending_request ? 'Pending Approval' : 'Unenroll';
                            $button_class = $has_pending_request ? 'btn-warning' : 'btn-danger';
                            $button_icon = $has_pending_request ? 'fas fa-clock' : 'fas fa-times';
                            $button_disabled = $has_pending_request ? 'disabled' : '';
                            ?>
<div class="mobile-table-row" onclick="openMobileDetailsModal('<?php echo htmlspecialchars($class['subject_name'] ?? 'N/A'); ?>', '<?php echo htmlspecialchars($class['schedule']); ?>', '<?php echo htmlspecialchars($class['class_name']); ?>', '<?php echo htmlspecialchars($professor_name); ?>', '<?php echo htmlspecialchars($class['room']); ?>', '<?php echo $class['class_id']; ?>', <?php echo $has_pending_request ? 'true' : 'false'; ?>)" style="cursor: pointer;">
    <div class="mobile-table-cell class-name-cell">
        <strong>Class Name:</strong>
        <span><?php echo htmlspecialchars($class['class_name']); ?></span>
    </div>
    <div class="mobile-table-cell subject-cell">
        <strong>Subject:</strong>
        <span class="subject-tag"><?php echo htmlspecialchars($class['subject_name'] ?? 'N/A'); ?></span>
    </div>
</div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

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

    <!-- Mobile Details Modal -->
    <div id="mobileDetailsModal" class="modal">
        <div class="modal-backdrop" onclick="closeMobileModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-info-circle"></i>
                    Class Details
                </h3>
                <button class="modal-close" onclick="closeMobileModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="detail-item">
                    <strong>Class Name:</strong> <span id="modalClassName"></span>
                </div>
                <div class="detail-item">
                    <strong>Professor:</strong> <span id="modalProfessor"></span>
                </div>
                <div class="detail-item">
                    <strong>Room:</strong> <span id="modalRoom"></span>
                </div>
                <div class="detail-item">
                    <strong>Action:</strong> <button id="modalActionBtn" class="btn btn-danger">Unenroll</button>
                </div>
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
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-subtitle {
            font-size: clamp(0.9rem, 3vw, 1rem);
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
            font-size: clamp(1.2rem, 4vw, 1.5rem);
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
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
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
            -webkit-overflow-scrolling: touch;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            min-width: 600px; /* Prevent table from shrinking too much */
        }

        .data-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
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
            font-size: clamp(0.9rem, 3vw, 1rem);
        }

        .subject-cell {
            min-width: 150px;
            text-align: left;
        }

        .subject-tag {
            background: var(--accent);
            color: var(--dark);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
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
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
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
            font-size: clamp(3rem, 10vw, 4rem);
            color: var(--light-gray);
            margin-bottom: 1rem;
        }

        .empty-state-content h3 {
            color: var(--gray);
            font-size: clamp(1.1rem, 4vw, 1.25rem);
            margin: 0;
        }

        .empty-state-content p {
            color: var(--gray);
            font-size: clamp(0.9rem, 3vw, 0.95rem);
            max-width: 400px;
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            min-width: 120px;
            justify-content: center;
            min-height: 44px; /* Touch-friendly */
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
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
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

        /* Mobile Table Styles */
        .mobile-table {
            display: none;
            flex-direction: column;
            gap: 1rem;
        }

        .mobile-table-row {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            border: 1px solid rgba(247, 82, 112, 0.1);
        }

        .mobile-table-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(247, 82, 112, 0.05);
        }

        .mobile-table-cell:last-child {
            border-bottom: none;
        }

        .mobile-table-cell strong {
            color: var(--dark);
            font-weight: 600;
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
        }

        .mobile-table-cell span {
            color: var(--gray);
            font-weight: 500;
            font-size: clamp(0.8rem, 2.5vw, 0.85rem);
        }

.subject-cell {
    transition: background 0.3s ease;
}

.mobile-table-row {
    cursor: pointer;
    transition: background 0.3s ease;
}

.mobile-table-row:hover {
    background: rgba(247, 202, 201, 0.1);
}

.subject-cell:hover {
    background: rgba(247, 202, 201, 0.1);
}

        .subject-tag {
            background: var(--accent);
            color: var(--dark);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: clamp(0.8rem, 2.5vw, 0.875rem);
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1440px) {
            .main-content {
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.75rem;
            }

            .page-title {
                font-size: 1.75rem;
            }

            .page-subtitle {
                font-size: 0.9rem;
            }

            .section-header {
                padding: 1.25rem 1.75rem;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .data-table th,
            .data-table td {
                padding: 1rem 1.25rem;
                font-size: 0.9rem;
            }

            .data-table th {
                font-size: 0.85rem;
            }

            .mobile-table-row {
                padding: 0.9rem;
            }

            .btn {
                padding: 0.7rem 1.25rem;
                font-size: 0.9rem;
            }

            .empty-state {
                padding: 3.5rem 1.75rem;
            }

            .empty-state-content h3 {
                font-size: 1.15rem;
            }

            .empty-state-content p {
                font-size: 0.9rem;
            }

            .stat-badge {
                padding: 0.4rem 0.9rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                padding: 1.25rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .page-subtitle {
                font-size: 0.85rem;
            }

            .section-header {
                padding: 1rem 1.5rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.9rem 1rem;
                font-size: 0.85rem;
            }

            .data-table th {
                font-size: 0.8rem;
            }

            .mobile-table-row {
                padding: 0.8rem;
            }

            .btn {
                padding: 0.65rem 1rem;
                font-size: 0.85rem;
            }

            .empty-state {
                padding: 3rem 1.5rem;
            }

            .empty-state-content h3 {
                font-size: 1.1rem;
            }

            .empty-state-content p {
                font-size: 0.85rem;
            }

            .stat-badge {
                padding: 0.35rem 0.8rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .mobile-hidden {
                display: none;
            }

            .mobile-clickable {
                cursor: pointer;
            }

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

            .desktop-table {
                display: none;
            }

            .mobile-table {
                display: flex;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
                min-width: 100px;
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
                font-size: 0.8rem;
            }

            .main-content {
                padding: 0.75rem;
            }

            .page-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .section-header {
                padding: 0.75rem 1rem;
            }

            .empty-state {
                padding: 2rem 1rem;
            }

            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
                min-width: 80px;
            }

            .stat-badge {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 414px) {
            .page-title {
                font-size: 1.3rem;
            }

            .page-subtitle {
                font-size: 0.85rem;
            }

            .section-title {
                font-size: 1.1rem;
            }

            .mobile-table-row {
                padding: 0.75rem;
            }

            .btn {
                min-height: 40px;
                padding: 0.5rem 0.5rem;
            }
        }

        @media (max-width: 375px) {
            .main-content {
                padding: 0.5rem;
            }

            .page-header {
                padding: 0.75rem;
                border-radius: 12px;
            }

            .page-title {
                font-size: 1.2rem;
                gap: 0.5rem;
            }

            .section-header {
                padding: 0.5rem 0.75rem;
            }

            .mobile-table-row {
                padding: 0.5rem;
            }

            .btn {
                min-height: 36px;
                padding: 0.4rem 0.4rem;
                font-size: 0.7rem;
            }

            .empty-state-content i {
                font-size: 2.5rem;
            }

            .empty-state-content h3 {
                font-size: 1rem;
            }

            .empty-state-content p {
                font-size: 0.8rem;
            }
        }

        /* --- Add: modal base layout and stacking rules --- */
        /* Ensure modals use fixed positioning and can be stacked via z-index */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }

        .modal.show {
            display: flex;
        }

        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.45);
        }

        .modal-container {
            position: relative;
            z-index: 2; /* relative stacking inside modal */
            background: white;
            border-radius: 12px;
            max-width: 520px;
            width: calc(100% - 40px);
            margin: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        /* Explicit stacking so confirmation modal overlays the mobile details modal */
        #mobileDetailsModal.show {
            z-index: 10000;
        }
        #mobileDetailsModal .modal-backdrop {
            z-index: 9990;
        }
        #mobileDetailsModal .modal-container {
            z-index: 10000;
            position: relative;
        }

        #confirmationModal.show {
            z-index: 10020;
        }
        #confirmationModal .modal-backdrop {
            z-index: 10010;
        }
        #confirmationModal .modal-container {
            z-index: 10020;
            position: relative;
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
            const confirmation = document.getElementById('confirmationModal');
            const mobile = document.getElementById('mobileDetailsModal');
            // Add show class and ensure confirmation modal stacks above mobile modal
            confirmation.classList.add('show');
            // explicit z-index guard in case inline styles are preferred
            confirmation.style.zIndex = '10020';
            if (mobile) {
                mobile.style.zIndex = '10000';
            }
        }

        function closeModal() {
            const confirmation = document.getElementById('confirmationModal');
            confirmation.classList.remove('show');
            // reset z-index
            confirmation.style.zIndex = '';
            currentClassId = null;
            // Disable the confirm button when modal is closed
            const btn = document.getElementById('confirmUnenrollBtn');
            if (btn) btn.disabled = true;
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

        function openMobileDetailsModal(subject, schedule, className, professor, room, classId, hasPending) {
            document.getElementById('modalClassName').textContent = className;
            document.getElementById('modalProfessor').textContent = professor;
            document.getElementById('modalRoom').textContent = room;
            const btn = document.getElementById('modalActionBtn');
            if (hasPending) {
                btn.textContent = 'Pending Approval';
                btn.className = 'btn btn-warning';
                btn.disabled = true;
                btn.onclick = null;
            } else {
                btn.textContent = 'Unenroll';
                btn.className = 'btn btn-danger';
                btn.disabled = false;
                btn.onclick = () => unenrollFromClass(classId);
            }
            const mobile = document.getElementById('mobileDetailsModal');
            if (mobile) {
                mobile.classList.add('show');
                mobile.style.zIndex = '10000';
            }
        }

        function closeMobileModal() {
            const mobile = document.getElementById('mobileDetailsModal');
            if (mobile) {
                mobile.classList.remove('show');
                mobile.style.zIndex = '';
            }
        }

        // --- Auto-refresh Pending Approval buttons every 10 seconds ---
        function refreshPendingButtons() {
            fetch('../php/get_student_pending_unenrollments.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const pendingSet = new Set((data.pending_class_ids || []).map(String));
                    // Desktop table buttons
                    document.querySelectorAll('.action-cell .btn').forEach(btn => {
                        const onclick = btn.getAttribute('onclick') || '';
                        const match = onclick.match(/unenrollFromClass\('([^']+)'\)/);
                        if (!match) return;
                        const classId = match[1];
                        if (pendingSet.has(String(classId))) {
                            btn.textContent = 'Pending Approval';
                            btn.classList.remove('btn-danger');
                            btn.classList.add('btn-warning', 'btn-disabled');
                            btn.disabled = true;
                            btn.onclick = null;
                        } else {
                            btn.innerHTML = '<i class="fas fa-times"></i> Unenroll';
                            btn.classList.remove('btn-warning', 'btn-disabled');
                            btn.classList.add('btn-danger');
                            btn.disabled = false;
                            btn.setAttribute('onclick', onclick);
                        }
                    });

                    // Update mobile modal action button state if open
                    const mobile = document.getElementById('mobileDetailsModal');
                    if (mobile && mobile.classList.contains('show')) {
                        const btn = document.getElementById('modalActionBtn');
                        // We don't know classId in scope; rely on disabled state if already set by openMobileDetailsModal
                        // Optionally could store current modal classId in a variable to update accurately
                    }
                })
                .catch(() => {});
        }

    // Start polling every 3 seconds
    setInterval(refreshPendingButtons, 3000);
        // Initial refresh shortly after page load
        setTimeout(refreshPendingButtons, 2000);

        window.toggleDetails = function(row) {
            const detailsRow = row.nextElementSibling;
            const isExpanded = detailsRow.style.display !== 'none';
            if (isExpanded) {
                detailsRow.style.display = 'none';
                row.classList.remove('expanded');
            } else {
                detailsRow.style.display = 'table-row';
                row.classList.add('expanded');
            }
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
