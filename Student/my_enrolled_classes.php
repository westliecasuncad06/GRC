<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';

$student_id = $_SESSION['user_id'];
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
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-book"></i> My Enrolled Classes</h2>
        </div>
        <div class="table-container" style="margin-top: 1rem;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Subject</th>
                        <th>Professor</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT c.*, s.subject_name, p.first_name, p.last_name
                                         FROM student_classes sc
                                         JOIN classes c ON sc.class_id = c.class_id
                                         JOIN subjects s ON c.subject_id = s.subject_id
                                         JOIN professors p ON c.professor_id = p.professor_id
                                         JOIN school_year_semester sys ON c.school_year_semester_id = sys.id
                                         WHERE sc.student_id = ? AND sys.status != 'Archived'");
                    $stmt->execute([$student_id]);
                    $enrolled_classes = $stmt->fetchAll();

                    foreach ($enrolled_classes as $class) {
                        echo '<tr>
                            <td>' . $class['class_name'] . '</td>
                            <td>' . $class['subject_name'] . '</td>
                            <td>Prof. ' . $class['first_name'] . ' ' . $class['last_name'] . '</td>
                            <td>' . $class['schedule'] . '</td>
                            <td>' . $class['room'] . '</td>
                            <td><button class="btn" style="background: #dc3545; color: white;" onclick="unenrollFromClass(\'' . $class['class_id'] . '\')"><i class="fas fa-times"></i> Unenroll</button></td>
                        </tr>';
                    }

                    if (empty($enrolled_classes)) {
                        echo "<tr><td colspan='6' style='text-align: center;'>No classes enrolled yet</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Unenrollment</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to unenroll from this class? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmUnenrollBtn" disabled>Unenroll</button>
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

            // Bypass for testing: simulate success without server call
            if (window.bypassUnenrollTest) {
                showToast('Successfully unenrolled from the class! (Bypass)', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
                return;
            }

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
                showToast('Successfully unenrolled from the class!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while unenrolling.', 'error');
        });
        }

        // Add bypass toggle for testing
        window.bypassUnenrollTest = false;

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
