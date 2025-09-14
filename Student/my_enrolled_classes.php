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
                                         WHERE sc.student_id = ?");
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

    <script>
        function unenrollFromClass(classId) {
            if (confirm('Are you sure you want to unenroll from this class?')) {
                fetch('../php/unenroll_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: classId
                    })
                })
                .then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Successfully unenrolled from the class.');
        alert('You have been unenrolled from the subject.');
        location.reload();
    } else {
        alert('Error: ' + data.message);
    }
})
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while unenrolling.');
                });
            }
        }
    </script>
</body>
</html>
