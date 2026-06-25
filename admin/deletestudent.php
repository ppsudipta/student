<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}

include('config.php');

// Check if delete request is valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Verify student exists before deleting
    $check_stmt = $con->prepare("SELECT id FROM students WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Prepare delete statement to prevent SQL injection
        $delete_stmt = $con->prepare("DELETE FROM students WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_msg'] = "Student record deleted successfully!";
        } else {
            $_SESSION['error_msg'] = "Error deleting student record: " . $con->error;
        }
        
        $delete_stmt->close();
    } else {
        $_SESSION['error_msg'] = "Student record not found!";
    }
    
    $check_stmt->close();
    header('Location: allregister.php');
    exit();
}

// If no valid ID provided, redirect back
header('Location: allregister.php');
exit();
?>