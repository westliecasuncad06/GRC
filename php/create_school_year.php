<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_label = trim($_POST['year_label']);
    $status = $_POST['status'] ?? 'Active';

    if (empty($year_label)) {
        $_SESSION['error'] = 'School year label is required.';
        header('Location: ../Admin/admin_manage_school_years.php');
        exit();
    }

    try {
        $admin_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO school_years (year_label, status, created_by, updated_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$year_label, $status, $admin_id, $admin_id]);

        $_SESSION['success'] = 'School year created successfully.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error creating school year: ' . $e->getMessage();
    }

    header('Location: ../Admin/admin_manage_school_years.php');
    exit();
}
?>
