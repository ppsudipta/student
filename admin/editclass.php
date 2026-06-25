<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
include('config.php');

// Get class record by ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = $con->query("SELECT * FROM class_session WHERE id = $id");

if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Class not found.'); window.location.href='allclass.php';</script>";
    exit();
}

$row = $result->fetch_assoc();
$subject = $row['subject'];
$class = $row['class'];
$session = $row['session'];
$msg = "";

// Handle update
if (isset($_POST['submit'])) {
    $class = mysqli_real_escape_string($con, $_POST['class']);
    $session = mysqli_real_escape_string($con, $_POST['session']);
    $subject = mysqli_real_escape_string($con, $_POST['subject']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    $update = "UPDATE class_session SET class='$class', session='$session',subject='$subject', status='$status' WHERE id=$id";
    if ($con->query($update)) {
        echo "<script>alert('Class updated successfully.'); window.location.href='allclass.php';</script>";
        exit();
    } else {
        $msg = '<div class="alert alert-danger">Error: ' . $con->error . '</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Class</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit Class</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allclass.php">All Classes</a></li>
        <li class="active">Edit</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Update Class & Session</h3></div>
        <div class="box-body">
          <?= $msg ?>
          <form method="POST">
            <div class="form-group">
              <label>Class</label>
              <input type="text" name="class" class="form-control" value="<?= $class ?>" required>
            </div>
            <div class="form-group">
              <label>Session</label>
              <input type="text" name="session" class="form-control" value="<?= $session ?>" required>
            </div>
            <div class="form-group">
              <label>subject</label>
              <input type="text" name="subject" class="form-control" value="<?= $subject ?>" required>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="active" <?= $row['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $row['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
            <div class="text-center">
              <button type="submit" name="submit" class="btn btn-primary">Update Class</button>
              <a href="allclass.php" class="btn btn-default">Back</a>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; 2025 Sunrise Academy</strong> All rights reserved.
  </footer>
  <div class="control-sidebar-bg"></div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>
