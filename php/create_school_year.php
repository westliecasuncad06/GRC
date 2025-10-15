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
        $stmt = $pdo->prepare("INSERT INTO school_years (year_label, status) VALUES (?, ?)");
        $stmt->execute([$year_label, $status]);

        $_SESSION['success'] = 'School year created successfully.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error creating school year: ' . $e->getMessage();
    }

    header('Location: ../Admin/admin_manage_school_years.php');
    exit();
}
?>
