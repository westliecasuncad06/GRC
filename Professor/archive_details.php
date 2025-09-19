<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$professor_id = $_SESSION['user_id'];

if (!isset($_GET['class_id'])) {
    echo "Class ID is required.";
    exit();
}

$class_id = $_GET['class_id'];

// Fetch class and subject details
$stmt = $pdo->prepare("SELECT c.*, s.subject_name FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ? AND c.professor_id = ?");
$stmt->execute([$class_id, $professor_id]);
$class = $stmt->fetch();

if (!$class) {
    echo "Class not found or you do not have permission to view it.";
    exit();
}

// Fetch attendance records for the class
$stmt = $pdo->prepare("SELECT a.date, st.first_name, st.last_name, st.email, a.status, a.remarks
                       FROM attendance a
                       JOIN students st ON a.student_id = st.student_id
                       WHERE a.class_id = ?
                       ORDER BY a.date DESC");
$stmt->execute([$class_id]);
$attendance_records = $stmt->fetchAll();

?>
<?php
if (isset($_GET['modal'])) {
    // Output only the content for modal
    ?>
    <div class="modal-header">
        <h3 class="modal-title" id="attendanceModalTitle">Attendance for <?php echo htmlspecialchars($class['subject_name']); ?></h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body">
        <div id="attendanceContent">
            <div class="attendance-record">
                <div class="record-header">
                    <div>Date</div>
                    <div>Student Name</div>
                    <div>Status</div>
                    <div>Remarks</div>
                </div>
                <?php if ($attendance_records): ?>
                    <?php foreach ($attendance_records as $record): ?>
                    <div class="record-item">
                        <div><?php echo htmlspecialchars($record['date']); ?></div>
                        <div><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></div>
                        <div><span class="attendance-status <?php echo htmlspecialchars($record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></div>
                        <div><?php echo htmlspecialchars($record['remarks'] ?: 'No remarks'); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No attendance records found for this class.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
} else {
    // Full page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Archive Class Details - <?php echo htmlspecialchars($class['subject_name']); ?></title>
        <link rel="stylesheet" href="../css/styles_fixed.css" />
    </head>
    <body>
        <?php include '../includes/navbar_professor.php'; ?>
        <?php include '../includes/sidebar_professor.php'; ?>

        <main class="main-content">
            <h1>Archive Class Details: <?php echo htmlspecialchars($class['subject_name']); ?></h1>
            <p>Class Code: <?php echo htmlspecialchars($class['class_code']); ?></p>
            <p>School Year: <?php echo htmlspecialchars($class['school_year']); ?></p>
            <p>Section: <?php echo htmlspecialchars($class['section']); ?></p>

            <h2>Attendance Records</h2>
            <?php if ($attendance_records): ?>
                <table border="1" cellpadding="5" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['email']); ?></td>
                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No attendance records found for this class.</p>
            <?php endif; ?>
        </main>
    </body>
    </html>
    <?php
}
?>
