<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include('config.php');

if (!isset($_POST['reply_submit']) || !isset($_POST['enquiry_id']) || !isset($_POST['reply_message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$enquiry_id = intval($_POST['enquiry_id']);
$reply_message = trim($_POST['reply_message']);
$replied_by = $_SESSION['username'] ?? 'Admin';

if ($enquiry_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid enquiry ID']);
    exit();
}

if ($reply_message === '') {
    echo json_encode(['success' => false, 'message' => 'Reply message cannot be empty']);
    exit();
}

$tableCheck = $con->query("SHOW TABLES LIKE 'enquiry_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmt = $con->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_type, sender_name, message, created_at) VALUES (?, 'admin', ?, ?, NOW())");
    $stmt->bind_param("iss", $enquiry_id, $replied_by, $reply_message);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to save message: ' . $stmt->error]);
        exit();
    }
    $stmt->close();
}

$stmt = $con->prepare("UPDATE enquiries SET reply_message = ?, replied_at = NOW(), replied_by = ? WHERE id = ?");
$stmt->bind_param("ssi", $reply_message, $replied_by, $enquiry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error sending reply: ' . $stmt->error]);
}

$stmt->close();
$con->close();
