<?php
include('config.php');

if (isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    
    $stmt = $con->prepare("SELECT class FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $classes = array_map('trim', explode(',', $row['class']));
        echo json_encode(['success' => true, 'classes' => $classes]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No student ID provided']);
}
?>