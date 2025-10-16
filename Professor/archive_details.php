<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

if (!isset($_GET['class_id']) || !isset($_GET['modal'])) {
    echo '<div class="modal-header">
            <h3 class="modal-title">Error</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
          </div>
          <div class="modal-body">
            <p>Invalid request.</p>
          </div>';
    exit();
}

$class_id = $_GET['class_id'];
$professor_id = $_SESSION['user_id'];

// Fetch class details with subject and term info
$stmt = $pdo->prepare("SELECT s.*, c.class_id, c.class_code, c.schedule, c.room, c.section, c.status,
                             sys.school_year, sys.semester
                      FROM subjects s
                      JOIN classes c ON s.subject_id = c.subject_id
                      LEFT JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                      WHERE c.class_id = ? AND c.professor_id = ?");
$stmt->execute([$class_id, $professor_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    echo '<div class="modal-header">
            <h3 class="modal-title">Error</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
          </div>
          <div class="modal-body">
            <p>Class not found or access denied.</p>
          </div>';
    exit();
}

// Fetch enrolled students
$stmt = $pdo->prepare("SELECT st.student_id, st.first_name, st.last_name, st.email
                      FROM student_classes sc
                      JOIN students st ON sc.student_id = st.student_id
                      WHERE sc.class_id = ?");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll();

// Fetch attendance summary
$stmt = $pdo->prepare("SELECT COUNT(*) as total_sessions,
                             SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                             SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                             SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                      FROM attendance WHERE class_id = ?");
$stmt->execute([$class_id]);
$attendance_summary = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="modal-header">
    <h3 class="modal-title">
        <i class="fas fa-archive"></i>
        <?php echo htmlspecialchars($class['subject_name']); ?> - Details
    </h3>
    <button class="modal-close" onclick="closeModal()">&times;</button>
</div>

<div class="modal-body">
    <div class="class-details-section">
        <h4>Class Information</h4>
        <div class="details-grid">
            <div class="detail-item">
                <i class="fas fa-code"></i>
                <strong>Class Code:</strong> <?php echo htmlspecialchars($class['class_code']); ?>
            </div>
            <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <strong>Schedule:</strong> <?php echo htmlspecialchars($class['schedule']); ?>
            </div>
            <div class="detail-item">
                <i class="fas fa-map-marker-alt"></i>
                <strong>Room:</strong> <?php echo htmlspecialchars($class['room']); ?>
            </div>
            <div class="detail-item">
                <i class="fas fa-users"></i>
                <strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?>
            </div>
            <div class="detail-item">
                <i class="fas fa-calendar-alt"></i>
                <strong>School Year:</strong> <?php echo htmlspecialchars($class['school_year'] ?? 'N/A'); ?>
            </div>
            <div class="detail-item">
                <i class="fas fa-graduation-cap"></i>
                <strong>Semester:</strong> <?php echo htmlspecialchars($class['semester'] ?? 'N/A'); ?>
            </div>
        </div>
    </div>

    <div class="students-section">
        <h4>Enrolled Students (<?php echo count($students); ?>)</h4>
        <?php if (!empty($students)): ?>
            <div class="students-list">
                <?php foreach ($students as $student): ?>
                    <div class="student-item">
                        <div class="student-info">
                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="student-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewStudentAttendance(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                <i class="fas fa-eye"></i> Attendance
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No students enrolled in this class.</p>
        <?php endif; ?>
    </div>

    <div class="attendance-summary-section">
        <h4>Attendance Summary</h4>
        <div class="attendance-stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $attendance_summary['total_sessions'] ?? 0; ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-item present">
                <div class="stat-number"><?php echo $attendance_summary['present_count'] ?? 0; ?></div>
                <div class="stat-label">Present</div>
            </div>
            <div class="stat-item absent">
                <div class="stat-number"><?php echo $attendance_summary['absent_count'] ?? 0; ?></div>
                <div class="stat-label">Absent</div>
            </div>
            <div class="stat-item late">
                <div class="stat-number"><?php echo $attendance_summary['late_count'] ?? 0; ?></div>
                <div class="stat-label">Late</div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-header {
    background: linear-gradient(135deg, #F75270 0%, #DC143C 100%);
    color: white;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.modal-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
}

.modal-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: white;
    padding: 0.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modal-body {
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
}

.class-details-section h4,
.students-section h4,
.attendance-summary-section h4 {
    color: #343a40;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    border-bottom: 2px solid #F75270;
    padding-bottom: 0.5rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #FDEBD0;
    border-radius: 12px;
    border-left: 4px solid #F75270;
}

.detail-item i {
    color: #F75270;
    width: 20px;
}

.students-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    background: #f8f9fa;
}

.student-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.student-info strong {
    display: block;
    color: #343a40;
}

.student-info span {
    color: #6c757d;
    font-size: 0.9rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.btn-primary {
    background: #F75270;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #DC143C;
}

.attendance-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #343a40;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.stat-item.present .stat-number { color: #28a745; }
.stat-item.absent .stat-number { color: #dc3545; }
.stat-item.late .stat-number { color: #ffc107; }

@media (max-width: 768px) {
    .modal-body {
        padding: 1rem;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .student-item {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .attendance-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function closeModal() {
    document.getElementById('archiveModal').style.display = 'none';
}

function viewStudentAttendance(studentId, studentName) {
    // This could open another modal or redirect to attendance details
    alert('Attendance details for ' + studentName + ' would be shown here.');
}
</script>
