<?php
session_start();
include('config.php');

if (!isset($_SESSION['username'])) {
    die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
}

if (isset($_POST['id']) && isset($_POST['is_favorite'])) {
    $materialId = intval($_POST['id']);
    $isFavorite = intval($_POST['is_favorite']);
    
    // Get student ID
    $username = $_SESSION['username'];
    $stmt = $con->prepare("SELECT id FROM students WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $student = $res->fetch_assoc();
    $studentId = $student['id'];
    
    // Update favorite status
    $stmt = $con->prepare("UPDATE student_materials SET is_favorite = ? WHERE id = ? AND student_id = ?");
    $stmt->bind_param("iii", $isFavorite, $materialId, $studentId);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}
?>