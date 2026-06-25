<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('HTTP/1.0 403 Forbidden');
    exit();
}

include('config.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("SELECT * FROM enquiries WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $enquiry = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $enquiry]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Enquiry not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>