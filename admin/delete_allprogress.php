<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}

include('config.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: allprogress.php');
    exit();
}

// Prefer returning to the filtered list the user came from
$redirect = 'allprogress.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = (string) $_SERVER['HTTP_REFERER'];
    $path = parse_url($ref, PHP_URL_PATH);
    $query = parse_url($ref, PHP_URL_QUERY);
    if (is_string($path) && substr($path, -strlen('allprogress.php')) === 'allprogress.php') {
        $redirect = 'allprogress.php' . ($query ? ('?' . $query) : '');
    }
}

$delete = $con->prepare('DELETE FROM attendance WHERE id = ?');
if (!$delete) {
    echo "<script>alert('Delete failed: database error'); window.location.href=" . json_encode($redirect) . ";</script>";
    exit();
}

$delete->bind_param('i', $id);
$ok = $delete->execute();
$affected = $delete->affected_rows;
$delete->close();

if ($ok && $affected > 0) {
    echo "<script>alert('Attendance deleted successfully'); window.location.href=" . json_encode($redirect) . ";</script>";
    exit();
}

if ($ok && $affected === 0) {
    echo "<script>alert('Attendance record not found (already deleted?)'); window.location.href=" . json_encode($redirect) . ";</script>";
    exit();
}

echo "<script>alert('Failed to delete attendance record'); window.location.href=" . json_encode($redirect) . ";</script>";
exit();
