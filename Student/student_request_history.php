<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

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
$stmt->execute([$student_id]);
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
$stmt->execute([$student_id]);
$unenrollment_requests = $stmt->fetchAll();

// Combine and sort all requests
$all_requests = array_merge($enrollment_requests, $unenrollment_requests);
usort($all_requests, function($a, $b) {
    return strtotime($b['requested_at']) - strtotime($a['requested_at']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Request History - Global Reciprocal College</title>
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
                    <i class="fas fa-history"></i>
                    Request History
                </h1>
                <p class="page-subtitle">View the status of your enrollment and unenrollment requests</p>
            </div>
        </div>

        <!-- Request History Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    All Requests
                </h2>
                <div class="section-stats">
                    <span class="stat-badge">
                        <i class="fas fa-file-alt"></i>
                        <?php echo count($all_requests); ?> Total Requests
                    </span>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Request Type</th>
                            <th><i class="fas fa-book-open"></i> Subject</th>
                            <th><i class="fas fa-graduation-cap"></i> Class</th>
                            <th><i class="fas fa-user-tie"></i> Professor</th>
                            <th><i class="fas fa-calendar-plus"></i> Requested</th>
                            <th><i class="fas fa-calendar-check"></i> Processed</th>
                            <th class="text-center"><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_requests)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="fas fa-inbox"></i>
                                        <h3>No Requests Found</h3>
                                        <p>You haven't submitted any enrollment or unenrollment requests yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_requests as $request): ?>
                                <?php
                                $professor_name = (!empty($request['prof_first_name']) && !empty($request['prof_last_name']))
                                    ? 'Prof. ' . $request['prof_first_name'] . ' ' . $request['prof_last_name']
                                    : 'Not assigned';

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
                                    <td class="professor-cell">
                                        <div class="professor-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($professor_name); ?></span>
                                        </div>
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
                                    <td class="date-cell">
                                        <?php if ($request['processed_at']): ?>
                                            <div class="date-info">
                                                <i class="fas fa-calendar-check"></i>
                                                <span><?php echo date('M j, Y', strtotime($request['processed_at'])); ?></span>
                                            </div>
                                            <div class="time-info">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('g:i A', strtotime($request['processed_at'])); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="pending-text">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas <?php echo $status_icon; ?>"></i>
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

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
        .request-type-cell {
            min-width: 120px;
        }

        .request-type-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .request-enrollment {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .request-unenrollment {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        .class-cell {
            min-width: 150px;
        }

        .class-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
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

        .date-cell {
            min-width: 140px;
        }

        .date-info,
        .time-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.875rem;
        }

        .date-info i,
        .time-info i {
            color: var(--primary);
            width: 14px;
        }

        .pending-text {
            color: var(--gray);
            font-style: italic;
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

        .status-approved {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
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

            .request-type-badge,
            .subject-tag,
            .status-badge {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
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

            .date-info,
            .time-info {
                flex-direction: column;
                gap: 0.25rem;
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>
