<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');
include_once __DIR__ . '/students_helper.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Invalid Request');
}
$id = (int) $_GET['id'];

$redirect = 'allprogress.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = (string) $_SERVER['HTTP_REFERER'];
    $path = parse_url($ref, PHP_URL_PATH);
    $query = parse_url($ref, PHP_URL_QUERY);
    if (is_string($path) && substr($path, -strlen('allprogress.php')) === 'allprogress.php') {
        $redirect = 'allprogress.php' . ($query ? ('?' . $query) : '');
    }
}

// Fetch record details for editing
$stmt = $con->prepare("SELECT a.*, s.name, s.registration_code
                       FROM attendance a
                       JOIN students s ON a.student_id = s.id
                       WHERE a.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();

if (!$attendance) {
    die('Record not found.');
}

$originalClass = (string) $attendance['class_name'];

// Handle update form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date  = trim((string) ($_POST['attendance_date'] ?? ''));
    $attendance_title = trim((string) ($_POST['attendance_title'] ?? ''));
    $class_name       = trim((string) ($_POST['class_name'] ?? ''));
    $status           = trim((string) ($_POST['status'] ?? 'Present'));
    $orig_class       = trim((string) ($_POST['original_class_name'] ?? $originalClass));

    $classChanged = (strcasecmp($class_name, $orig_class) !== 0);
    $markAbsent = (strcasecmp($status, 'Absent') === 0);

    // Absent is not stored — delete the Present row.
    // Class change also removes this row (student no longer Present for this class/session).
    if ($markAbsent || $classChanged) {
        $del = $con->prepare('DELETE FROM attendance WHERE id = ?');
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();

        if ($markAbsent && $classChanged) {
            $msg = 'Class changed and marked Absent — record deleted (Absent is not stored).';
        } elseif ($markAbsent) {
            $msg = 'Marked Absent — record deleted (Absent is not stored).';
        } else {
            $msg = 'Class changed — record deleted. Mark the student Present again under the correct class if needed.';
        }
        echo '<script>alert(' . json_encode($msg) . '); window.location.href=' . json_encode($redirect) . ';</script>';
        exit;
    }

    $day_name = date('l', strtotime($attendance_date));
    $status = 'Present';

    $upd = $con->prepare('UPDATE attendance
                           SET class_name=?, attendance_date=?, day_name=?, attendance_title=?, status=?
                           WHERE id=?');
    $upd->bind_param('sssssi', $class_name, $attendance_date, $day_name, $attendance_title, $status, $id);
    $upd->execute();
    $upd->close();

    echo '<script>alert("Attendance updated successfully"); window.location.href=' . json_encode($redirect) . ';</script>';
    exit;
}

$all_classes = students_unique_classes($con);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Attendance</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <style>
    .form-section { padding: 20px; }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit Attendance Record</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allprogress.php">Attendance</a></li>
        <li class="active">Edit Record</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Update Attendance Details</h3>
            </div>

            <form method="post" class="form-section" id="editAttendanceForm">
              <input type="hidden" name="original_class_name" value="<?= htmlspecialchars($originalClass) ?>">

              <div class="alert alert-info">
                <strong>Note:</strong> Absent is not stored.
                Choosing <em>Absent</em> or changing <em>Class</em> will <strong>delete</strong> this Present record.
                To mark a not-present student as Present, use <strong>Mark Present</strong> on the Not Present list.
              </div>

              <div class="form-group">
                <label>Student Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($attendance['name']) ?> (<?= htmlspecialchars($attendance['registration_code']) ?>)" disabled>
              </div>

              <div class="form-group">
                <label>Class</label>
                <select name="class_name" id="class_name" class="form-control select2" style="width:100%;" required>
                  <?php foreach ($all_classes as $cls): ?>
                    <option value="<?= htmlspecialchars($cls) ?>" <?= ($attendance['class_name'] == $cls) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cls) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="help-block">Changing class deletes this record (Absent is not stored).</p>
              </div>

              <div class="form-group">
                <label>Date</label>
                <input type="date" name="attendance_date" class="form-control" value="<?= htmlspecialchars((string) $attendance['attendance_date']) ?>" required>
              </div>

              <div class="form-group">
                <label>Title</label>
                <input type="text" name="attendance_title" class="form-control" value="<?= htmlspecialchars($attendance['attendance_title']) ?>" required>
              </div>

              <div class="form-group">
                <label>Status</label>
                <select name="status" id="status" class="form-control" required>
                  <option value="Present" <?= ($attendance['status'] == 'Present') ? 'selected' : '' ?>>Present</option>
                  <option value="Absent" <?= ($attendance['status'] == 'Absent') ? 'selected' : '' ?>>Absent (delete record)</option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary">Save</button>
              <a href="<?= htmlspecialchars($redirect) ?>" class="btn btn-default">Cancel</a>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
  $(function () {
    $('.select2').select2();
    var originalClass = <?= json_encode($originalClass) ?>;

    $('#editAttendanceForm').on('submit', function () {
      var status = $('#status').val();
      var newClass = $('#class_name').val() || '';
      if (status === 'Absent') {
        return confirm('Mark Absent? This will DELETE the attendance record (Absent is not stored).');
      }
      if (newClass !== originalClass) {
        return confirm('Class changed. This will DELETE the attendance record. Continue?');
      }
      return true;
    });
  });
</script>
</body>
</html>
