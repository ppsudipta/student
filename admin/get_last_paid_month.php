<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['ok' => false, 'last_paid' => '', 'message' => 'Unauthorized']);
    exit();
}

include __DIR__ . '/config.php';
include_once __DIR__ . '/fees_helper.php';

$reg = trim((string) ($_GET['registration_code'] ?? ''));
if ($reg === '') {
    echo json_encode(['ok' => true, 'last_paid' => '', 'message' => '']);
    exit();
}

$info = fees_last_paid_for_student($con, $reg);
echo json_encode([
    'ok' => true,
    'last_paid' => $info['last_paid'],
    'month' => $info['month'],
    'year' => $info['year'],
    'message' => $info['month'] === '' ? 'No successful fee payment found' : '',
]);
