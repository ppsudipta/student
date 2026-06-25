<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

if (isset($_GET['del_id'])) {
  $del = intval($_GET['del_id']);
  $sql2 = "DELETE FROM gallery WHERE id = '$del'";
  $con->query($sql2);
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Gallery</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
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
      <h1>All Gallery & Promotional Images</h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Gallery Images</li>
      </ol>
    </section>

    <section class="content">
      <div class="box">
        <div class="box-header">
          <h3 class="box-title">Uploaded Images</h3>
        </div>

        <div class="box-body table-responsive">
          <table id="example2" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>Image Type</th>
                <th>Image Name</th>
                <th>Image Preview</th>
                <th>Created Date</th>
                <th>Edit</th>
                <th>Delete</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $res = $con->query("SELECT * FROM gallery ORDER BY id DESC");
              while ($row = $res->fetch_assoc()) {
              ?>
                <tr>
                  <td>
                    <span class="label label-<?php echo $row['type'] === 'Promotional' ? 'info' : 'success'; ?>">
                      <?php echo htmlspecialchars($row['type']); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($row['name']); ?></td>
                  <td><img src="<?php echo htmlspecialchars($row['image']); ?>" style="height:70px;width:100px;"></td>
                  <td><?php echo htmlspecialchars($row['date']); ?></td>
                  <td>
                    <a href="editgallary.php?id=<?php echo $row['id']; ?>" class="btn btn-warning">
                      <i class="fa fa-pencil"></i>
                    </a>
                  </td>
                  <td>
                    <a href="?del_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure to delete this image?');" class="btn btn-danger">
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

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> Sunrise Academy</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="js/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button);
</script>
<!-- Bootstrap 3.3.7 -->
<script src="js/bootstrap.min.js"></script>
<!-- Morris.js charts -->
<script src="js/raphael.min.js"></script>
<script src="js/morris.min.js"></script>
<!-- Sparkline -->
<script src="js/jquery.sparkline.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<!-- jvectormap -->
<script src="js/jquery-jvectormap-1.2.2.min.js"></script>
<script src="js/jquery-jvectormap-world-mill-en.js"></script>
<!-- jQuery Knob Chart -->
<script src="js/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="js/moment.min.js"></script>
<script src="js/daterangepicker.js"></script>
<!-- datepicker -->
<script src="js/bootstrap-datepicker.min.js"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="js/bootstrap3-wysihtml5.all.min.js"></script>
<!-- Slimscroll -->
<script src="js/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="js/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="js/adminlte.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="js/pages/dashboard.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="js/demo.js"></script>
<script>
  $(function () {
    $('#example2').DataTable({
      paging: true,
      lengthChange: false,
      searching: true,
      ordering: true,
      info: true,
      autoWidth: false
    });
  });
</script>
</body>
</html>
