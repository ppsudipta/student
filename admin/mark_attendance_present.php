<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

$redirect = 'allprogress.php';
if (!empty($_GET['return'])) {
    $ret = (string) $_GET['return'];
    // Only allow relative return to allprogress.php
    if (strpos($ret, 'allprogress.php') === 0) {
        $redirect = $ret;
    }
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = (string) $_SERVER['HTTP_REFERER'];
    $path = parse_url($ref, PHP_URL_PATH);
    $query = parse_url($ref, PHP_URL_QUERY);
    if (is_string($path) && substr($path, -strlen('allprogress.php')) === 'allprogress.php') {
        $redirect = 'allprogress.php' . ($query ? ('?' . $query) : '');
    }
}

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$className = trim((string) ($_GET['class_name'] ?? ''));
$title = trim((string) ($_GET['title'] ?? ''));
$date = trim((string) ($_GET['date'] ?? ''));

if ($studentId <= 0 || $title === '') {
    echo '<script>alert("Invalid request"); window.location.href=' . json_encode($redirect) . ';</script>';
    exit();
}

// Prefer date from an existing Present row for this cohort; else today
if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $dateStmt = $con->prepare(
        "SELECT attendance_date, class_name FROM attendance
         WHERE attendance_title = ? AND status = 'Present'
         ORDER BY id DESC LIMIT 1"
    );
    $dateStmt->bind_param('s', $title);
    $dateStmt->execute();
    $dateRes = $dateStmt->get_result();
    if ($row = $dateRes->fetch_assoc()) {
        $date = (string) $row['attendance_date'];
        if ($className === '' && trim((string) $row['class_name']) !== '' && strcasecmp((string) $row['class_name'], 'All Classes') !== 0) {
            $className = (string) $row['class_name'];
        }
    } else {
        $date = date('Y-m-d');
    }
    $dateStmt->close();
}

if ($className === '') {
    $stu = $con->prepare('SELECT class FROM students WHERE id = ? LIMIT 1');
    $stu->bind_param('i', $studentId);
    $stu->execute();
    $stuRes = $stu->get_result();
    if ($s = $stuRes->fetch_assoc()) {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $s['class']))));
        $className = $parts[0] ?? '';
    }
    $stu->close();
}

if ($className === '') {
    echo '<script>alert("Could not determine class for this student"); window.location.href=' . json_encode($redirect) . ';</script>';
    exit();
}

// Already Present for this cohort?
$check = $con->prepare(
    "SELECT id FROM attendance
     WHERE student_id = ? AND attendance_title = ? AND status = 'Present'
     LIMIT 1"
);
$check->bind_param('is', $studentId, $title);
$check->execute();
$checkRes = $check->get_result();
if ($checkRes && $checkRes->num_rows > 0) {
    $check->close();
    echo '<script>alert("Student is already Present for this cohort"); window.location.href=' . json_encode($redirect) . ';</script>';
    exit();
}
$check->close();

$dayName = date('l', strtotime($date));
$ins = $con->prepare(
    "INSERT INTO attendance (student_id, class_name, attendance_date, day_name, attendance_title, status)
     VALUES (?, ?, ?, ?, ?, 'Present')"
);
$ins->bind_param('issss', $studentId, $className, $date, $dayName, $title);
if ($ins->execute()) {
    echo '<script>alert("Marked Present successfully"); window.location.href=' . json_encode($redirect) . ';</script>';
} else {
    echo '<script>alert("Failed to mark Present"); window.location.href=' . json_encode($redirect) . ';</script>';
}
$ins->close();
exit();
