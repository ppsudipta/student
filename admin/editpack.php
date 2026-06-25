<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('location:allpack.php');
  exit();
}

$id = intval($_GET['id']);

// Fetch course data
$sql = "SELECT * FROM event WHERE id = $id";
$res = $con->query($sql);
if (!$res || $res->num_rows == 0) {
  echo "<script>alert('Invalid Course ID'); window.location.href='allpack.php';</script>";
  exit();
}
$row = $res->fetch_assoc();
$img = $row['image'];
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Course</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">

  <!-- Styles -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/skins/_all-skins.min.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker.min.css">
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
      <h1>Edit Course</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li class="active">Edit Course</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Edit Course Details</h3>
        </div>
        <form method="post" enctype="multipart/form-data">
          <div class="box-body">
            <div class="form-group">
              <label>Course Name</label>
              <input type="text" name="c_name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required>
            </div>

            <div class="form-group">
              <label>Course Details</label>
              <textarea name="details" class="form-control" rows="6"><?= htmlspecialchars($row['description']) ?></textarea>
            </div>

            <div class="form-group">
              <label>Package Cost</label>
              <input type="number" name="price" value="<?= htmlspecialchars($row['price']) ?>" class="form-control">
            </div>

            <div class="form-group">
              <label>Course Image</label>
              <input type="file" name="image1" class="form-control" accept="image/*">
              <?php if (!empty($img)): ?>
                <p>Current Image:</p>
                <img src="<?= htmlspecialchars($img) ?>" width="120" style="border:1px solid #ccc;">
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label>Date</label>
              <input type="date" name="date" value="<?= htmlspecialchars($row['date']) ?>" class="form-control" required>
            </div>
          </div>

          <div class="box-footer text-center">
            <button type="submit" name="submit" class="btn btn-primary">Update Course</button>
            <a href="allpack.php" class="btn btn-default">Cancel</a>
          </div>
        </form>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?= date("Y") ?> Sunrise Academy</strong> All rights reserved.
  </footer>

  <div class="control-sidebar-bg"></div>
</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-datepicker.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
</body>
</html>

<?php
if (isset($_POST['submit'])) {
    $c_name = mysqli_real_escape_string($con, $_POST['c_name']);
    $event_details = mysqli_real_escape_string($con, $_POST['details']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $date = mysqli_real_escape_string($con, $_POST['date']);

    $path1 = $img;
    if (!empty($_FILES['image1']['name'])) {
        $targetDir = "event/";
        $filename = time() . '_' . basename($_FILES['image1']['name']);
        $path1 = $targetDir . $filename;
        move_uploaded_file($_FILES['image1']['tmp_name'], $path1);
    }

    $update = "UPDATE event SET 
                name = '$c_name', 
                description = '$event_details', 
                price = '$price', 
                date = '$date',
                image = '$path1' 
               WHERE id = $id";

    if ($con->query($update)) {
        echo "<script>alert('Course updated successfully'); window.location.href='allpack.php';</script>";
    } else {
        echo "<script>alert('Error updating course');</script>";
    }
}
?>
