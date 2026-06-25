<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
include('config.php');

$msg = '';

// Handle form submission
if (isset($_POST['submit'])) {
    $class = mysqli_real_escape_string($con, $_POST['class']);
    $session = mysqli_real_escape_string($con, $_POST['session']);
    $subject = mysqli_real_escape_string($con, $_POST['subject']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    $query = "INSERT INTO class_session (class, session,subject, status) VALUES ('$class', '$session','$subject','$status')";
    if ($con->query($query)) {
        $msg = '<div class="alert alert-success">Class and Session added successfully.</div>';
    } else {
        $msg = '<div class="alert alert-danger">Error: ' . $con->error . '</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Class & Session</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
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
      <h1>Add Class & Session</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Add Class</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Class/Session Form</h3>
        </div>
        <div class="box-body">
          <?= $msg ?>
          <form method="POST">
            <div class="form-group">
              <label>Class</label>
              <input type="text" name="class" class="form-control" required placeholder="Enter class (e.g., Class 8)">
            </div>
            <div class="form-group">
              <label>Session</label>
              <input type="text" name="session" class="form-control" required placeholder="Enter session (e.g., Session 1)">
            </div>
            <div class="form-group">
              <label>subject</label>
              <input type="text" name="subject" class="form-control" required placeholder="Enter subject (e.g., English)">
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="box-footer text-center">
              <button type="submit" name="submit" class="btn btn-primary">Add Class</button>
              <a href="viewclass.php" class="btn btn-default">View All</a>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; 2025 Supnits Classes</strong> All rights reserved.
  </footer>

  <div class="control-sidebar-bg"></div>
</div>

<!-- JS Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>
