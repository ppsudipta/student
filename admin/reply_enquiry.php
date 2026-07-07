<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include('config.php');

function save_enquiry_attachment_file($file, &$errorMessage) {
    $maxBytes = 5 * 1024 * 1024;
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    if (!isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'File upload failed.';
        return false;
    }
    if ($file['size'] > $maxBytes) {
        $errorMessage = 'File is too large. Maximum size is 5 MB.';
        return false;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        $errorMessage = 'File type not allowed. Use JPG, PNG, PDF, DOC, DOCX, XLS, or XLSX.';
        return false;
    }

    $uploadDir = realpath(__DIR__ . '/../pages/uploads');
    if ($uploadDir === false) {
        $uploadDir = __DIR__ . '/../pages/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    }

    $storedName = uniqid('api_', true) . '.' . $extension;
    $targetPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errorMessage = 'Unable to save uploaded file.';
        return false;
    }

    return $storedName;
}

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

$attachment = null;
$uploadError = '';
if (!empty($_FILES['attachment']['name'])) {
    $saved = save_enquiry_attachment_file($_FILES['attachment'], $uploadError);
    if ($saved === false) {
        echo json_encode(['success' => false, 'message' => $uploadError]);
        exit();
    }
    $attachment = $saved;
}

$tableCheck = $con->query("SHOW TABLES LIKE 'enquiry_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmt = $con->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_type, sender_name, message, attachment, created_at) VALUES (?, 'admin', ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $enquiry_id, $replied_by, $reply_message, $attachment);
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
