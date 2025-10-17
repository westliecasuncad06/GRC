<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $description = trim($_POST['description']);
    $credits = (int)$_POST['credits'];
    $semester_id = $_POST['semester_id'];
    $duration_id = isset($_POST['duration_id']) && $_POST['duration_id'] !== '' ? (int)$_POST['duration_id'] : null;

    if (empty($subject_name) || empty($subject_code) || empty($semester_id)) {
        $_SESSION['error'] = 'Subject name, code, and semester are required.';
        header('Location: ../Admin/admin_manage_subjects.php');
        exit();
    }

    try {
    $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description, credits, duration_id, semester_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$subject_name, $subject_code, $description, $credits, $duration_id, $semester_id]);

        $_SESSION['success'] = 'Subject created successfully.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error creating subject: ' . $e->getMessage();
    }

    header('Location: ../Admin/admin_manage_subjects.php');
    exit();
}
?>
