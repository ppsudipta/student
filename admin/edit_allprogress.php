<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

// Fetch record by ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Request");
}
$id = intval($_GET['id']);

// Handle update form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date  = $_POST['attendance_date'];
    $attendance_title = $_POST['attendance_title'];
    $class_name       = $_POST['class_name'];
    $status           = $_POST['status'];

    $day_name = date('l', strtotime($attendance_date));

    $stmt = $con->prepare("UPDATE attendance 
                           SET class_name=?, attendance_date=?, day_name=?, attendance_title=?, status=? 
                           WHERE id=?");
    $stmt->bind_param("sssssi", $class_name, $attendance_date, $day_name, $attendance_title, $status, $id);
    $stmt->execute();

    echo "<script>alert('Attendance updated successfully'); window.location.href='allprogress.php';</script>";
    exit;
}

// Fetch record details for editing
$stmt = $con->prepare("SELECT a.*, s.name, s.registration_code 
                       FROM attendance a 
                       JOIN students s ON a.student_id = s.id 
                       WHERE a.id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();

if (!$attendance) {
    die("Record not found.");
}

// Fetch distinct class names for dropdown
$classes = $con->query("SELECT DISTINCT class_name FROM attendance");
$all_classes = [];
while ($row = $classes->fetch_assoc()) {
    $split = array_map('trim', explode(',', $row['class_name']));
    $all_classes = array_merge($all_classes, $split);
}
$all_classes = array_unique($all_classes);
sort($all_classes);
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
    .form-section {
      padding: 20px;
    }
  </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php';?>
  <aside class="main-sidebar">
    <section class="sidebar"><?php include 'sidebar.php'; ?></section>
  </aside>

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

            <form method="post" class="form-section">
              <div class="form-group">
                <label>Student Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($attendance['name']) ?> (<?= htmlspecialchars($attendance['registration_code']) ?>)" disabled>
              </div>

              <div class="form-group">
                <label>Class</label>
                <select name="class_name" class="form-control select2" style="width:100%;" required>
                  <?php foreach($all_classes as $cls): ?>
                    <option value="<?= $cls ?>" <?= ($attendance['class_name'] == $cls) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cls) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Date</label>
                <input type="date" name="attendance_date" class="form-control" value="<?= $attendance['attendance_date'] ?>" required>
              </div>

              <div class="form-group">
                <label>Title</label>
                <input type="text" name="attendance_title" class="form-control" value="<?= htmlspecialchars($attendance['attendance_title']) ?>" required>
              </div>

              <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control" required>
                  <option value="Present" <?= ($attendance['status'] == 'Present') ? 'selected' : '' ?>>Present</option>
                  <option value="Absent" <?= ($attendance['status'] == 'Absent') ? 'selected' : '' ?>>Absent</option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary">Update Attendance</button>
              <a href="allprogress.php" class="btn btn-default">Cancel</a>
            </form>

          </div>
        </div>
      </div>
    </section>
  </div>

</div>

<!-- JS Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/adminlte.min.js"></script>

<script>
  $(function () {
    $('.select2').select2();
  });
</script>
</body>
</html>
