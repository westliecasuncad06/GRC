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
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content content-spacing">
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-book"></i> My Enrolled Classes</h2>
        </div>

        <div class="table-container-aligned table-section">
            <table class="table-aligned">
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Subject</th>
                        <th>Professor</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th class="action-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                        $button_class = $has_pending_request ? 'btn-warning' : 'btn-danger';
                        $button_icon = $has_pending_request ? 'fas fa-clock' : 'fas fa-times';
                        $button_disabled = $has_pending_request ? 'disabled' : '';

                        echo '<tr>
                            <td>' . htmlspecialchars($class['class_name']) . '</td>
                            <td>' . htmlspecialchars($class['subject_name']) . '</td>
                            <td>' . htmlspecialchars($professor_name) . '</td>
                            <td>' . htmlspecialchars($class['schedule']) . '</td>
                            <td>' . htmlspecialchars($class['room']) . '</td>
                            <td class="action-cell">
                                <button class="btn ' . $button_class . ' btn-sm" ' . $button_disabled . ' onclick="unenrollFromClass(\'' . $class['class_id'] . '\')">
                                    <i class="' . $button_icon . '"></i> ' . $button_text . '
                                </button>
                            </td>
                        </tr>';
                    }

                    if (empty($enrolled_classes)) {
                        echo "<tr><td colspan='6' class='text-center'>No classes enrolled yet</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pending Unenrollment Requests Section -->
        <?php if (!empty($pending_unenrollment_requests)): ?>
            <div class="table-header-enhanced section-spacing" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                <h2 class="table-title-enhanced"><i class="fas fa-clock" style="margin-right: 10px;"></i>Pending Unenrollment Requests</h2>
            </div>
            <div class="table-container-aligned table-section">
                <table class="table-aligned">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Subject</th>
                            <th>Professor</th>
                            <th>Request Date</th>
                            <th class="text-center">Status</th>
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
                                <td class="text-center"><span class="status-badge-aligned">Pending Approval</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal-aligned">
        <div class="modal-content-aligned">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Unenrollment Request</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body content-spacing">
                <p>Are you sure you want to submit an unenrollment request for this class? This will send a request to your professor for approval.</p>
                <div id="requestInfo" class="content-spacing" style="background: #f8f9fa; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <p><strong>Note:</strong> Your professor will need to approve this request before you are unenrolled from the class.</p>
                </div>
            </div>
            <div class="modal-footer btn-group-aligned">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-warning" id="confirmUnenrollBtn"><i class="fas fa-paper-plane"></i> Submit Request</button>
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
