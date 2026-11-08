<?php
include('config.php');
include_once __DIR__ . '/students_helper.php';

header('Content-Type: application/json');

if (!isset($_POST['class_name']) || empty($_POST['class_name'])) {
    echo json_encode(['success' => false, 'students' => []]);
    exit;
}

$class_name = trim((string) $_POST['class_name']);
$match = students_class_match_sql('class');

$rollOrder = students_roll_order_sql('registration_code');
$stmt = $con->prepare(
    "SELECT id, name, registration_code
     FROM students
     WHERE $match
     ORDER BY $rollOrder, name ASC"
);
$stmt->bind_param('s', $class_name);
$stmt->execute();
$res = $stmt->get_result();

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
