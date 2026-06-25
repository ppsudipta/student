<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// Check if report id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: progress_reports.php');
    exit;
}

$report_id = $_GET['id'];

// Get student_id for redirect
$stmt = $pdo->prepare("SELECT student_id FROM progress_reports WHERE id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if ($report) {
    // Delete the report
    $delete_stmt = $pdo->prepare("DELETE FROM progress_reports WHERE id = ?");
    $delete_stmt->execute([$report_id]);
    
    header("Location: progress_reports.php?student_id=" . $report['student_id']);
} else {
    header('Location: progress_reports.php');
}
exit;
?>