<?php
include("config.php");

header("Content-Type: application/json");

$result = $con->query("SELECT id, name, registration_code, course, status FROM students");
$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
