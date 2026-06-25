<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access');
}

include('config.php');

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid homework ID');
}

$homework_id = (int)$_GET['id'];

// Prepare and execute query using prepared statement
$stmt = $con->prepare("SELECT id, title, subject, description, deadline FROM homework_assignments WHERE id = ?");
$stmt->bind_param("i", $homework_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Homework not found');
}

$homework = $result->fetch_assoc();

// Format the deadline for the datetime picker
$homework['deadline'] = date('Y-m-d H:i', strtotime($homework['deadline']));

// Return JSON response
header('Content-Type: application/json');
echo json_encode($homework);

$stmt->close();
$con->close();
?>