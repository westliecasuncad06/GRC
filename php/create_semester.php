<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_year_id = $_POST['school_year_id'];
    $semester_name = $_POST['semester_name'];
    $status = $_POST['status'] ?? 'Active';

    if (empty($school_year_id) || empty($semester_name)) {
        $_SESSION['error'] = 'School year and semester name are required.';
        header('Location: ../Admin/admin_manage_semesters.php');
        exit();
    }

    try {
        $admin_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO semesters (school_year_id, semester_name, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$school_year_id, $semester_name, $status, $admin_id, $admin_id]);

        $_SESSION['success'] = 'Semester created successfully.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error creating semester: ' . $e->getMessage();
    }

    header('Location: ../Admin/admin_manage_semesters.php');
    exit();
}
?>
