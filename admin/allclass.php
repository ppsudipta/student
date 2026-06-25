<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
include('config.php');

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $con->query("DELETE FROM class_session WHERE id = $delete_id");
    echo "<script>alert('Class deleted successfully'); window.location.href='allclass.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Classes</title>
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
      <h1>All Classes</h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Class List</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Class & Session Table</h3>
        </div>
        <div class="box-body">
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Class</th>
                <th>Session</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Student Count</th>
                <th>Created At</th>
                <th style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
           <?php
$result = $con->query("SELECT * FROM class_session ORDER BY id DESC");
$total_students = 0; // ✅ grand total

if ($result->num_rows > 0):
  while ($row = $result->fetch_assoc()):
    $class_name = $con->real_escape_string($row['class']);

    // ✅ Count students only by class (ignore session)
    $count_sql = "
      SELECT COUNT(*) as total 
      FROM students 
      WHERE (
        class = '$class_name'
        OR FIND_IN_SET('$class_name', REPLACE(class, ', ', ','))
        OR FIND_IN_SET('$class_name', REPLACE(class, ',', ','))
      )
    ";
    $count_res = $con->query($count_sql);
    $student_count = $count_res->fetch_assoc()['total'] ?? 0;

    $total_students += $student_count;
?>
<tr>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['class']) ?></td>
  <td><?= htmlspecialchars($row['session']) ?></td>
  <td><?= htmlspecialchars($row['subject']) ?></td>
  <td>
    <span class="label label-<?= $row['status'] == 'active' ? 'success' : 'default' ?>">
      <?= ucfirst($row['status']) ?>
    </span>
  </td>
  <td><?= $student_count ?></td>
  <td><?= $row['created_at'] ?></td>
  <td>
    <a href="editclass.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
      <i class="fa fa-pencil"></i>Edit
    </a>
    <a href="allclass.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to delete this class?');">
      <i class="fa fa-trash"></i>Delete
    </a>
  </td>
</tr>
<?php endwhile; ?>
<!-- ✅ Grand total row -->
<tr style="font-weight:bold; background:#f2f2f2;">
  <td colspan="5" class="text-right">Total Students:</td>
  <td><?= $total_students ?></td>
  <td colspan="2"></td>
</tr>
<?php else: ?>
  <tr><td colspan="8" class="text-center">No records found</td></tr>
<?php endif; ?>



            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; 2025 Sunrise Academy</strong> All rights reserved.
  </footer>

  <div class="control-sidebar-bg"></div>
</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>
