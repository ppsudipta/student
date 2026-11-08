<?php
include('config.php');
include_once __DIR__ . '/students_helper.php';

header('Content-Type: application/json');

if (!empty($_GET['class'])) {
    $class = trim((string) $_GET['class']);
    $match = students_class_match_sql('class');
    $stmt = $con->prepare(
        "SELECT registration_code, name, email, total_fees
         FROM students
         WHERE $match
         ORDER BY name ASC, registration_code ASC"
    );
    $stmt->bind_param('s', $class);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode($students);
}
?>
