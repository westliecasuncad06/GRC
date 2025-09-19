<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];

// Get archived classes for the student
$query = "SELECT c.*, s.subject_name, s.subject_code, p.first_name, p.last_name, sys.school_year, sys.semester
          FROM student_classes sc
          JOIN classes c ON sc.class_id = c.class_id
          JOIN subjects s ON c.subject_id = s.subject_id
          JOIN professors p ON c.professor_id = p.professor_id
          JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
          WHERE sc.student_id = ? AND c.status = 'archived'
          ORDER BY sys.school_year DESC, sys.semester DESC, s.subject_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute([$student_id]);
$archived_classes = $stmt->fetchAll();

// Group by school year and semester
$grouped_classes = [];
foreach ($archived_classes as $class) {
    $key = $class['school_year'] . ' - ' . $class['semester'];
    if (!isset($grouped_classes[$key])) {
        $grouped_classes[$key] = [];
    }
    $grouped_classes[$key][] = $class;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles_fixed.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .table-header-enhanced {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .table-title-enhanced {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .archive-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .archive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .archive-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .archive-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .archive-info {
            flex: 1;
        }

        .archive-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 0.25rem 0;
        }

        .archive-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        .archive-classes {
            margin-bottom: 1.5rem;
        }

        .archive-class-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .archive-class-item:hover {
            background-color: rgba(0, 123, 255, 0.05);
            border-radius: 6px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            max-height: 90%;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .modal-close:hover {
            color: var(--dark);
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .attendance-table th {
            background: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .attendance-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .attendance-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-present {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-absent {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .status-late {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-excused {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .archive-class-item:last-child {
            border-bottom: none;
        }

        .archive-class-icon {
            font-size: 1.1rem;
            color: var(--primary);
            width: 20px;
        }

        .archive-class-text {
            font-size: 0.9rem;
            color: var(--dark);
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 3rem 1rem;
            font-style: italic;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .archive-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-archive" style="margin-right: 10px;"></i>My Archived Classes</h2>
        </div>

        <div class="dashboard-container">
            <?php if (empty($grouped_classes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="empty-state-text">No archived classes found.</div>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_classes as $term => $classes): ?>
                    <div class="archive-card">
                        <div class="archive-header">
                            <div class="archive-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="archive-info">
                                <h3 class="archive-title"><?php echo htmlspecialchars($term); ?></h3>
                                <span class="archive-subtitle"><?php echo count($classes); ?> archived classes</span>
                            </div>
                        </div>

                        <div class="archive-classes">
                            <?php foreach ($classes as $class): ?>
                                <div class="archive-class-item" onclick="openAttendanceModal('<?php echo $class['class_id']; ?>', '<?php echo htmlspecialchars($class['subject_name']); ?>')">
                                    <i class="fas fa-book archive-class-icon"></i>
                                    <span class="archive-class-text">
                                        <?php echo htmlspecialchars($class['subject_name']); ?> (<?php echo htmlspecialchars($class['subject_code']); ?>) -
                                        Prof. <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?> -
                                        <?php echo htmlspecialchars($class['schedule']); ?> -
                                        <?php echo htmlspecialchars($class['room']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeAttendanceModal()">&times;</span>
            <h2 id="modalTitle">Attendance Records</h2>
            <div id="modalBody">
                <p>Loading attendance data...</p>
            </div>
        </div>
    </div>

    <script>
        function openAttendanceModal(classId, subjectName) {
            document.getElementById('modalTitle').innerText = `Attendance Records - ${subjectName}`;
            document.getElementById('attendanceModal').style.display = 'flex';
            loadAttendanceData(classId);
        }

        function closeAttendanceModal() {
            document.getElementById('attendanceModal').style.display = 'none';
        }

        async function loadAttendanceData(classId) {
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = '<p>Loading attendance data...</p>';

            try {
                const response = await fetch(`../php/get_student_attendance.php?class_id=${classId}&student_id=<?php echo $student_id; ?>`);
                if (!response.ok) throw new Error('Failed to fetch attendance data');
                const attendanceRecords = await response.json();

                if (attendanceRecords.length === 0) {
                    modalBody.innerHTML = '<p>No attendance records found for this class.</p>';
                    return;
                }

                let html = '<table class="attendance-table">';
                html += '<thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead><tbody>';

                attendanceRecords.forEach(record => {
                    const statusClass = record.status === 'Present' ? 'status-present' :
                                        record.status === 'Absent' ? 'status-absent' :
                                        record.status === 'Late' ? 'status-late' : 'status-excused';
                    html += `<tr>
                        <td>${record.date}</td>
                        <td><span class="attendance-status ${statusClass}">${record.status || 'No status'}</span></td>
                        <td>${record.remarks || ''}</td>
                    </tr>`;
                });

                html += '</tbody></table>';
                modalBody.innerHTML = html;
            } catch (error) {
                modalBody.innerHTML = '<p>Error loading attendance data.</p>';
                console.error(error);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('attendanceModal');
            if (event.target === modal) {
                closeAttendanceModal();
            }
        }
    </script>
</body>
</html>
