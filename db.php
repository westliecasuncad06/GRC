<?php
// Database connection settings
$host = 'localhost';
$db = 'GRC_STUDENT_PORTAL_FOR_ATTENDANCE_MONITORING';
$user = 'root'; // Change as necessary
$pass = ''; // Change as necessary

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database connection failed: " . $e->getMessage());

    // Don't output the error directly in production
    // Instead, set $pdo to null so other scripts can handle the error gracefully
    $pdo = null;
}
?>
