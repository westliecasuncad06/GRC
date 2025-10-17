<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

if (!isset($_GET['class_id'])) {
    echo '<div class="modal-header">
            <h3 class="modal-title">Error</h3>
            <button class="modal-close" onclick="closeAttendanceModal()">&times;</button>
          </div>
          <div class="modal-body">
            <p>Invalid request.</p>
          </div>';
    exit();
}

$class_id = $_GET['class_id'];
$professor_id = $_SESSION['user_id'];

// Verify the class belongs to the professor
$stmt = $pdo->prepare("SELECT c.class_id, s.subject_name, c.class_code
                      FROM classes c
                      JOIN subjects s ON c.subject_id = s.subject_id
                      WHERE c.class_id = ? AND c.professor_id = ?");
$stmt->execute([$class_id, $professor_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    echo '<div class="modal-header">
            <h3 class="modal-title">Error</h3>
            <button class="modal-close" onclick="closeAttendanceModal()">&times;</button>
          </div>
          <div class="modal-body">
            <p>Class not found or access denied.</p>
          </div>';
    exit();
}

// Fetch attendance dates for the class
$stmt = $pdo->prepare("SELECT DISTINCT date FROM attendance WHERE class_id = ? ORDER BY date DESC");
$stmt->execute([$class_id]);
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>

<div class="modal-header">
    <h3 class="modal-title">
        <i class="fas fa-calendar-check"></i>
        Attendance for <?php echo htmlspecialchars($class['subject_name']); ?> (<?php echo htmlspecialchars($class['class_code']); ?>)
    </h3>
    <button class="modal-close" onclick="closeAttendanceModal()">&times;</button>
</div>

<div class="modal-body">
    <?php if (!empty($dates)): ?>
        <div id="attendanceDatesAccordion">
            <?php foreach ($dates as $date): ?>
                <div class="date-card">
                    <div class="date-header" onclick="toggleDateCollapse(this)" aria-expanded="false">
                            <h4><?php echo date('m/d/Y', strtotime($date)); ?></h4>
                            <span class="collapse-icon">▼</span>
                        </div>
                        <div class="date-content" style="display: none;">
                        <div class="students-list" data-date="<?php echo $date; ?>">
                            <?php
                            // Fetch attendance for this date
                            $stmt = $pdo->prepare("SELECT a.status, a.remarks, st.first_name, st.last_name, st.student_id
                                                  FROM attendance a
                                                  JOIN students st ON a.student_id = st.student_id
                                                  WHERE a.class_id = ? AND a.date = ?
                                                  ORDER BY st.last_name, st.first_name");
                            $stmt->execute([$class_id, $date]);
                            $attendances = $stmt->fetchAll();
                            ?>
                            <?php foreach ($attendances as $attendance): ?>
                                <div class="attendance-mobile-card">
                                    <div class="attendance-mobile-header">
                                        <div class="attendance-mobile-student"><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></div>
                                        <div class="attendance-mobile-id"><?php echo htmlspecialchars($attendance['student_id']); ?></div>
                                    </div>
                                    <div class="attendance-mobile-row">
                                        <div class="attendance-mobile-label">Status:</div>
                                        <div class="attendance-mobile-value">
                                            <span class="status-display status-<?php echo strtolower($attendance['status']); ?>"><?php echo htmlspecialchars($attendance['status']); ?></span>
                                        </div>
                                    </div>
                                    <div class="attendance-mobile-row">
                                        <div class="attendance-mobile-label">Remarks:</div>
                                        <div class="attendance-mobile-value">
                                            <span><?php echo htmlspecialchars($attendance['remarks'] ?: 'No remarks'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No attendance records found for this class.</p>
    <?php endif; ?>
</div>

<style>
/* Reuse styles from the reference, but make read-only */
.date-card {
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.date-header {
    background: #f8f9fa;
    padding: 1rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.date-header h4 {
    margin: 0;
    font-size: 1.1rem;
}

.collapse-icon {
    font-size: 1.2rem;
    transition: transform 0.3s;
}

.date-content {
    padding: 1rem;
}

.students-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.attendance-mobile-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    background: white;
}

.attendance-mobile-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.attendance-mobile-student {
    font-weight: bold;
}

.attendance-mobile-id {
    color: #6c757d;
}

.attendance-mobile-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.attendance-mobile-label {
    font-weight: bold;
    flex: 0 0 80px;
}

.attendance-mobile-value {
    flex: 1;
}

.status-display {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: bold;
    text-transform: capitalize;
}

.status-present { background: #d4edda; color: #155724; }
.status-absent { background: #f8d7da; color: #721c24; }
.status-late { background: #fff3cd; color: #856404; }
.status-excused { background: #d1ecf1; color: #0c5460; }

@media (max-width: 768px) {
    .attendance-mobile-row {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
function closeAttendanceModal() {
    document.getElementById('attendanceModal').style.display = 'none';
}

function toggleDateCollapse(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.collapse-icon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.textContent = '▲';
    } else {
        content.style.display = 'none';
        icon.textContent = '▼';
    }
}
</script>
