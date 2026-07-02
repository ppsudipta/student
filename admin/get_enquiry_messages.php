<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include('config.php');

$enquiry_id = isset($_GET['enquiry_id']) ? intval($_GET['enquiry_id']) : 0;
if ($enquiry_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid enquiry ID']);
    exit();
}

$messages = [];
$tableCheck = $con->query("SHOW TABLES LIKE 'enquiry_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmt = $con->prepare("SELECT * FROM enquiry_messages WHERE enquiry_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $enquiry_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

if (empty($messages)) {
    $stmt = $con->prepare("SELECT * FROM enquiries WHERE id = ?");
    $stmt->bind_param("i", $enquiry_id);
    $stmt->execute();
    $enquiry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($enquiry) {
        if (!empty($enquiry['message'])) {
            $messages[] = [
                'id' => 0,
                'enquiry_id' => $enquiry_id,
                'sender_type' => 'student',
                'sender_name' => $enquiry['name'],
                'message' => $enquiry['message'],
                'attachment' => $enquiry['attachment'],
                'created_at' => $enquiry['created_at'],
            ];
        }
        if (!empty($enquiry['reply_message'])) {
            $messages[] = [
                'id' => 0,
                'enquiry_id' => $enquiry_id,
                'sender_type' => 'admin',
                'sender_name' => $enquiry['replied_by'] ?? 'Admin',
                'message' => $enquiry['reply_message'],
                'attachment' => null,
                'created_at' => $enquiry['replied_at'] ?? $enquiry['created_at'],
            ];
        }
    }
}

echo json_encode(['success' => true, 'messages' => $messages]);
$con->close();
