<?php
include('config.php');

header('Content-Type: application/json');

if (!isset($_POST['class_name']) || empty($_POST['class_name'])) {
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

$class_name = $con->real_escape_string($_POST['class_name']);

// Fetch students belonging to this class (supports comma-separated classes)
$sql = "SELECT id, name, registration_code 
        FROM students 
        WHERE FIND_IN_SET('$class_name', REPLACE(class, ', ', ',')) 
        ORDER BY registration_code ASC";

$res = $con->query($sql);

$students = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $students[] = $r;
    }
}

echo json_encode([
    'success' => true,
    'students' => $students
]);
