<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once '../php/db.php';

// Get all professors with their subjects and latest attendance
$query = "
    SELECT
        p.professor_id,
        p.first_name,
        p.last_name,
        p.department,
        s.subject_id,
        s.subject_name,
        s.subject_code,
        c.class_id,
        c.schedule,
        c.room,
        MAX(a.date) as latest_attendance_date,
        COUNT(DISTINCT a.date) as total_sessions
    FROM professors p
    LEFT JOIN classes c ON p.professor_id = c.professor_id AND c.status = 'active'
    LEFT JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN attendance a ON c.class_id = a.class_id
    GROUP BY p.professor_id, s.subject_id, c.class_id
    ORDER BY p.first_name, p.last_name, s.subject_name
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$professor_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data by professor
$professors = [];
foreach ($professor_data as $row) {
    $prof_id = $row['professor_id'];
    if (!isset($professors[$prof_id])) {
        $professors[$prof_id] = [
            'professor_id' => $row['professor_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'department' => $row['department'],
            'full_name' => $row['first_name'] . ' ' . $row['last_name'],
            'subjects' => []
        ];
    }
    if ($row['subject_id']) {
        $professors[$prof_id]['subjects'][] = [
            'subject_id' => $row['subject_id'],
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code'],
            'class_id' => $row['class_id'],
            'schedule' => $row['schedule'],
            'room' => $row['room'],
            'latest_attendance_date' => $row['latest_attendance_date'],
            'total_sessions' => $row['total_sessions']
        ];
    }
}

// Function to get attendance details for a subject
function getAttendanceDetails($pdo, $class_id) {
    $query = "
        SELECT
            a.date,
            TIME(a.created_at) as time,
            a.status,
            COUNT(*) as student_count
        FROM attendance a
        WHERE a.class_id = ?
        GROUP BY a.date, TIME(a.created_at), a.status
        ORDER BY a.date DESC, TIME(a.created_at) DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX request for attendance details
if (isset($_GET['action']) && $_GET['action'] == 'get_attendance_details') {
    $class_id = $_GET['class_id'];
    $details = getAttendanceDetails($pdo, $class_id);
    header('Content-Type: application/json');
    echo json_encode($details);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Global Reciprocal Colleges</title>
    <link rel="stylesheet" href="../css/styles_fixed.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap 5 CSS -->

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


        .professor-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .professor-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .professor-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .professor-info h5 {
            margin: 0;
            font-weight: 600;
        }
        .professor-info p {
            margin: 0;
            opacity: 0.9;
        }
        .professor-stats {
            display: flex;
            gap: 1rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        .expand-icon {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }
        .professor-card.expanded .expand-icon {
            transform: rotate(180deg);
        }

        .subjects-container {
            display: none;
            padding: 1.5rem;
            background: #f8f9fa;
        }
        .professor-card.expanded .subjects-container {
            display: block;
        }

        .subject-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: box-shadow 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        .subject-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .subject-info h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark);
        }
        .subject-info p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--gray);
        }
        .subject-stats {
            text-align: right;
        }
        .latest-attendance {
            font-size: 0.8rem;
            color: var(--success);
            font-weight: 500;
        }



        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-table th,
        .attendance-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .attendance-table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }
        .attendance-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        .status-late {
            background: #fff3cd;
            color: #856404;
        }
        .status-excused {
            background: #d1ecf1;
            color: #0c5460;
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 2rem;
            font-style: italic;
        }

        .enhanced-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
            max-width: 600px;
        }
        .search-container {
            padding-top: 1rem;
            position: relative;
            flex-grow: 1;
            min-width: 200px;
            max-width: 400px;
            display: flex;
            align-items: center;
        }
        .search-input {
            width: 100%;
            height: 40px;
            padding: 10px 16px 10px 40px;
            border: 2px solid var(--primary);
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
            background-color: var(--light);
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(247, 82, 112, 0.2);
            background-color: white;
        }
        .search-icon {
            position: absolute;
            left: 11px;
            top: 65%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.1rem;
        }
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content {
            display: flex;
            flex-direction: column;
            width: 100%;
            padding: 0 2rem;
            box-sizing: border-box;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }
            .dashboard-container {
                padding: 0;
            }
            .dashboard-title-enhanced {
                font-size: 2rem;
                padding: 1.5rem;
            }
            .dashboard-subtitle {
                font-size: 1rem;
            }
            .desktop-stats {
                display: none;
            }
            .mobile-stats {
                display: none;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .stats-card {
                padding: 1rem;
                border-radius: 12px;
            }
            .stats-icon {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }
            .stats-number {
                font-size: 2rem;
                margin-bottom: 0.25rem;
            }
            .stats-label {
                font-size: 0.9rem;
            }
            .recent-activities-section {
                padding: 1.5rem;
            }
            .section-header {
                margin-bottom: 1rem;
            }
            .section-title {
                font-size: 1.3rem;
            }
            .activity-item {
                padding: 1.5rem 1rem;
                margin-bottom: 1rem;
                border-radius: 12px;
            }
            .activity-icon {
                font-size: 1.5rem;
                margin-right: 20px;
                min-width: 25px;
            }
            .activity-text {
                font-size: 1.1rem;
            }
            .activity-meta {
                font-size: 0.9rem;
            }
        }
        @media (max-width: 480px) {
            .main-content {
                padding: 0 0.5rem;
            }
            .dashboard-title-enhanced {
                font-size: 1.8rem;
                padding: 1rem;
            }
            .dashboard-subtitle {
                font-size: 0.95rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .stats-card {
                padding: 0.75rem 0.5rem;
            }
            .stats-icon {
                font-size: 1.5rem;
                margin-bottom: 0.25rem;
            }
            .stats-number {
                font-size: 1.5rem;
                margin-bottom: 0.25rem;
            }
            .stats-label {
                font-size: 0.8rem;
            }
            .recent-activities-section {
                padding: 1rem;
            }
            .activity-item {
                padding: 1.25rem 0.75rem;
            }
            .activity-icon {
                font-size: 1.3rem;
                margin-right: 15px;
                min-width: 20px;
            }
            .activity-text {
                font-size: 1rem;
            }
            .activity-meta {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 414px) {
            .main-content {
                padding: 0 0.25rem;
            }
            .dashboard-container {
                padding: 0;
            }
            .enhanced-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
                gap: 1rem;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .header-actions {
                width: 100%;
                max-width: none;
                justify-content: flex-start;
            }
            .search-container {
                padding-top: 0;
                min-width: 100%;
                max-width: none;
            }
            .search-input {
                height: 36px;
                font-size: 0.9rem;
                padding: 8px 12px 8px 36px;
            }
            .search-icon {
                left: 10px;
                font-size: 1rem;
            }
            .professor-card {
                margin-bottom: 0.75rem;
            }
            .professor-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .professor-info {
                width: 100%;
            }
            .professor-stats {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                align-items: flex-start;
            }
            .stat-item {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
                text-align: left;
            }
            .stat-number {
                font-size: 1rem;
            }
            .stat-label {
                font-size: 0.75rem;
            }
            .expand-icon {
                position: absolute;
                top: 1rem;
                right: 1rem;
            }
            .subjects-container {
                padding: 1rem;
            }
            .subject-item {
                padding: 0.75rem;
                margin-bottom: 0.25rem;
            }
            .subject-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .subject-info h6 {
                font-size: 1rem;
            }
            .subject-info p {
                font-size: 0.85rem;
            }
            .subject-stats {
                text-align: left;
                width: 100%;
            }
            .latest-attendance {
                font-size: 0.75rem;
            }
            .attendance-container {
                overflow-x: auto;
                padding: 0.75rem;
            }
            .attendance-table {
                min-width: 300px;
                font-size: 0.85rem;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 0.5rem;
            }
            .no-data {
                padding: 1rem;
            }
        }

        @media (min-width: 417px) and (max-width: 467px) {
            .main-content {
                padding: 0 0.3rem;
            }
            .dashboard-container {
                padding: 0;
            }
            .enhanced-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
                gap: 1rem;
            }
            .header-title {
                font-size: 1.4rem;
            }
            .header-actions {
                width: 100%;
                max-width: none;
                justify-content: flex-start;
            }
            .search-container {
                padding-top: 0;
                min-width: 100%;
                max-width: none;
            }
            .search-input {
                height: 35px;
                font-size: 0.9rem;
                padding: 8px 12px 8px 35px;
            }
            .search-icon {
                left: 10px;
                font-size: 1rem;
            }
            .professor-card {
                margin-bottom: 0.75rem;
            }
            .professor-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .professor-info {
                width: 100%;
            }
            .professor-stats {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                align-items: flex-start;
            }
            .stat-item {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
                text-align: left;
            }
            .stat-number {
                font-size: 0.95rem;
            }
            .stat-label {
                font-size: 0.7rem;
            }
            .expand-icon {
                position: absolute;
                top: 1rem;
                right: 1rem;
            }
            .subjects-container {
                padding: 1rem;
            }
            .subject-item {
                padding: 0.75rem;
                margin-bottom: 0.25rem;
            }
            .subject-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .subject-info h6 {
                font-size: 0.95rem;
            }
            .subject-info p {
                font-size: 0.8rem;
            }
            .subject-stats {
                text-align: left;
                width: 100%;
            }
            .latest-attendance {
                font-size: 0.7rem;
            }
            .attendance-container {
                overflow-x: auto;
                padding: 0.3rem;
            }
            .attendance-table {
                min-width: 180px;
                font-size: 0.65rem;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 0.15rem;
            }
            .no-data {
                padding: 1rem;
            }
        }

        /* Modal responsiveness */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem auto;
                max-width: none;
                width: calc(100% - 1rem);
            }
            .modal-content {
                border-radius: 8px;
            }
            .modal-header {
                padding: 1rem;
            }
            .modal-title {
                font-size: 1.1rem;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            .modal-body {
                padding: 1rem;
                max-height: 60vh;
                overflow-y: auto;
            }
            .attendance-table {
                font-size: 0.85rem;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 0.5rem 0.25rem;
                white-space: nowrap;
            }
        }
        @media (max-width: 480px) {
            .modal-title {
                font-size: 1rem;
            }
            .modal-body {
                padding: 0.75rem;
            }
            .attendance-table {
                font-size: 0.8rem;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 0.4rem 0.2rem;
            }
        }



    </style>
</head>
<body>
    <?php include '../includes/navbar_admin.php'; ?>

    <?php include '../includes/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-container">
            <div class="enhanced-header fade-in">
                <h1 class="header-title"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <div class="header-actions">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search professors..." onkeyup="filterProfessors()">
                    </div>
                </div>
            </div>



            <?php if (empty($professors)): ?>
                <div class="no-data">
                    <p>No professors found in the system.</p>
                </div>
            <?php else: ?>
                <?php foreach ($professors as $professor): ?>
                    <div class="professor-card" data-professor-id="<?php echo $professor['professor_id']; ?>">
                        <div class="professor-header" onclick="toggleProfessor(this)">
                            <div class="professor-info">
                                <h5><?php echo htmlspecialchars($professor['full_name']); ?></h5>
                                <p><?php echo htmlspecialchars($professor['department']); ?> Department</p>
                            </div>
                            <div class="professor-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo count($professor['subjects']); ?></div>
                                    <div class="stat-label">Subjects</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo array_sum(array_column($professor['subjects'], 'total_sessions')); ?></div>
                                    <div class="stat-label">Sessions</div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down expand-icon"></i>
                        </div>
                        <div class="subjects-container">
                            <?php if (empty($professor['subjects'])): ?>
                                <div class="no-data">
                                    <p>No subjects assigned to this professor.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($professor['subjects'] as $subject): ?>
                                    <div class="subject-item" data-class-id="<?php echo $subject['class_id']; ?>" data-subject-name="<?php echo htmlspecialchars($subject['subject_name']); ?>" onclick="openAttendanceModal(this)">
                                        <div class="subject-header">
                                            <div class="subject-info">
                                                <h6><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                                <p><?php echo htmlspecialchars($subject['subject_code']); ?> • <?php echo htmlspecialchars($subject['schedule']); ?> • <?php echo htmlspecialchars($subject['room']); ?></p>
                                            </div>
                                            <div class="subject-stats">
                                                <div class="latest-attendance">
                                                    <?php echo $subject['latest_attendance_date'] ? 'Latest: ' . date('M d, Y', strtotime($subject['latest_attendance_date'])) : 'No attendance yet'; ?>
                                                </div>
                                                <div class="stat-label"><?php echo $subject['total_sessions']; ?> sessions</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceModalLabel">Attendance Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalContent">
                        <div class="loading-spinner text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading attendance records...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <?php include '../includes/footbar.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleProfessor(header) {
            const card = header.closest('.professor-card');
            card.classList.toggle('expanded');
        }



        function openAttendanceModal(subjectItem) {
            const classId = subjectItem.getAttribute('data-class-id');
            const subjectName = subjectItem.getAttribute('data-subject-name');

            // Set modal title
            document.getElementById('attendanceModalLabel').textContent = `Attendance Records - ${subjectName}`;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
            modal.show();

            // Load attendance data
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = `
                <div class="loading-spinner text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading attendance records...</p>
                </div>
            `;

            // Fetch attendance data
            fetch(`admin_dashboard.php?action=get_attendance_details&class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        modalContent.innerHTML = `
                            <div class="no-data">
                                <p>No attendance records found for this subject.</p>
                            </div>
                        `;
                    } else {
                        let tableHtml = `
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Students</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                        data.forEach(record => {
                            const statusClass = `status-${record.status.toLowerCase()}`;
                            const statusText = record.status.charAt(0).toUpperCase() + record.status.slice(1);

                            tableHtml += `
                                <tr>
                                    <td>${new Date(record.date).toLocaleDateString()}</td>
                                    <td>${record.time}</td>
                                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                    <td>${record.student_count}</td>
                                </tr>
                            `;
                        });

                        tableHtml += `
                                </tbody>
                            </table>
                        `;

                        modalContent.innerHTML = tableHtml;
                    }
                })
                .catch(error => {
                    console.error('Error fetching attendance data:', error);
                    modalContent.innerHTML = `
                        <div class="no-data">
                            <p>Error loading attendance records. Please try again.</p>
                        </div>
                    `;
                });
        }

        function filterProfessors() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const professorCards = document.querySelectorAll('.professor-card');

            professorCards.forEach(card => {
                const name = card.querySelector('.professor-info h5').textContent.toLowerCase();
                const department = card.querySelector('.professor-info p').textContent.toLowerCase();
                const match = name.includes(query) || department.includes(query);
                card.style.display = match ? '' : 'none';
            });
        }

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Dashboard initialized');
        });
    </script>
</body>
</html>
