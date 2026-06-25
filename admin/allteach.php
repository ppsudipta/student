<?php 
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

// Handle deletion
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM teachers WHERE id = $id";
    if ($con->query($sql)) {
        echo "<script>alert('Teacher Deleted'); window.location.href='allteach.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error deleting teacher');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Teachers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Styles -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/skins/_all-skins.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <!-- Header and Sidebar -->
  <?php include 'header.php'; ?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <!-- Main Content -->
  <div class="content-wrapper">
    <section class="content-header">
      <h1>All Teachers</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li class="active">Teachers</li>
      </ol>
    </section>

    <section class="content">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Teacher Records</h3>
        </div>
        <div class="box-body table-responsive">
          <table id="teachersTable" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Subject</th>
                <th>Joining Date</th>
                <th>Edit</th>
                <th>Delete</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM teachers ORDER BY id DESC";
              $res = $con->query($sql);
              while ($row = $res->fetch_assoc()) {
              ?>
              <tr>
                <td>
                  <img src="<?php echo htmlspecialchars($row['photo']); ?>" style="height:60px; width:60px; border-radius:50%;">
                </td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                <td><?php echo htmlspecialchars($row['added_on']); ?></td>
                <td>
                  <a href="editteacher.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                    <i class="fa fa-pencil"></i>
                  </a>
                </td>
                <td>
                  <a onclick="return confirm('Are you sure you want to delete this teacher?');" href="allteach.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">
                    <i class="fa fa-trash"></i>
                  </a>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> Sunrise Academy</strong> All rights reserved.
  </footer>
</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
  $(function () {
    $('#teachersTable').DataTable({
      paging: true,
      lengthChange: true,
      searching: true,
      ordering: true,
      info: true,
      autoWidth: false
    });
  });
</script>
</body>
</html>
