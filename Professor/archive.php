<?php
include '../php/db.php';
session_start();

if(!isset($_SESSION['professor_id'])){
    header("location: index.php");
    exit;
}

$professor_id = $_SESSION['professor_id'];

// Fetch archived subjects taught by the professor
$sql = "SELECT * FROM subjects WHERE professor_id = '$professor_id' AND status = 'archived'";
$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $conn->error;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Archived Subjects</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th>Subject Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>".$row["subject_name"]."</td>";
                        echo "<td>".$row["subject_code"]."</td>";
                        echo "<td><a href='archive_details.php?subject_id=".$row["subject_id"]."'>View Details</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No archived subjects found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
