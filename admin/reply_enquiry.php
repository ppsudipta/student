<?php
session_start();
// Debugging - log POST data
error_log('POST data: ' . print_r($_POST, true));
// Show errors in development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log errors to a specific file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.log');

date_default_timezone_set("Asia/Kolkata");

if (!isset($_SESSION['username'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include('config.php');

// Check if all required parameters are set
if (!isset($_POST['reply_submit']) || !isset($_POST['enquiry_id']) || !isset($_POST['reply_message'])) {
    $missing = [];
    if (!isset($_POST['enquiry_id'])) $missing[] = 'enquiry_id';
    if (!isset($_POST['reply_message'])) $missing[] = 'reply_message';
    if (!isset($_POST['reply_submit'])) $missing[] = 'reply_submit';
    
    error_log("Missing parameters: " . implode(', ', $missing));
    echo json_encode(['success' => false, 'message' => 'Missing parameters: ' . implode(', ', $missing)]);
    exit();
}

// Get and validate parameters
$enquiry_id = $_POST['enquiry_id'];
$reply_message = trim($_POST['reply_message']);

// Debug log raw values
error_log("Raw enquiry_id: " . $enquiry_id);
error_log("Raw reply_message: " . $reply_message);

// Convert to integer
$enquiry_id = intval($enquiry_id);
error_log("Integer enquiry_id: " . $enquiry_id);

if ($enquiry_id <= 0) {
    error_log("Invalid enquiry ID: " . $enquiry_id);
    echo json_encode(['success' => false, 'message' => 'Invalid enquiry ID: ' . $enquiry_id]);
    exit();
}

if (empty($reply_message)) {
    echo json_encode(['success' => false, 'message' => 'Reply message cannot be empty']);
    exit();
}

// Get current admin username
$replied_by = $_SESSION['username'] ?? 'Admin';

// Debug
error_log("Updating enquiry ID: " . $enquiry_id);
error_log("Reply by: " . $replied_by);
error_log("Reply message: " . $reply_message);

// Update the enquiry
$stmt = $con->prepare("UPDATE enquiries SET reply_message = ?, replied_at = NOW(), replied_by = ? WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("ssi", $reply_message, $replied_by, $enquiry_id);
    
    if ($stmt->execute()) {
        error_log("Update successful - Rows affected: " . $stmt->affected_rows);
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
        } else {
            // Check if the enquiry exists
            $check_stmt = $con->prepare("SELECT id FROM enquiries WHERE id = ?");
            $check_stmt->bind_param("i", $enquiry_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Enquiry does not exist']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No rows updated. Data may be the same.']);
            }
            $check_stmt->close();
        }
    } else {
        $error = $stmt->error;
        error_log("Update failed: " . $error);
        echo json_encode(['success' => false, 'message' => 'Error sending reply: ' . $error]);
    }
    $stmt->close();
} else {
    $error = $con->error;
    error_log("Prepare failed: " . $error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $error]);
}

$con->close();
?>