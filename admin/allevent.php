<?php 
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

// Delete material logic
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $check = mysqli_fetch_assoc(mysqli_query($con, "SELECT file_path FROM student_materials WHERE id = $id"));
    if ($check) {
        if (file_exists($check['file_path'])) {
            unlink($check['file_path']);
        }
        $delete = "DELETE FROM student_materials WHERE id = $id";
        if ($con->query($delete)) {
            echo "<script>alert('Material deleted successfully'); window.location='allevent.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error deleting material');</script>";
        }
    } else {
        echo "<script>alert('Material not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Study Materials</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">

  <!-- CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/skins/_all-skins.min.css">
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>

  <!-- Sidebar -->
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <!-- Content -->
  <div class="content-wrapper">
    <section class="content-header">
      <h1>Manage Study Materials</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li class="active">Study Materials</li>
      </ol>
    </section>

    <section class="content">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">All Uploaded Materials</h3>
        </div>
        <div class="box-body table-responsive">
          <table id="materialsTable" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>Title</th>
               
                <th>Class</th>
                <th>Session Year</th>
                <th>Type</th>
                <th>Assign To</th>
                <th>Permission</th>
                <th>Upload Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM student_materials ORDER BY id DESC";
              $result = $con->query($sql);
              while ($row = $result->fetch_assoc()) {
              ?>
              <tr>
                <td><?php echo htmlspecialchars($row['material_title']); ?></td>
               
                <td><?php echo htmlspecialchars($row['class']); ?></td>
                <td><?php echo htmlspecialchars($row['session']); ?></td>
                <td><?php echo strtoupper($row['material_type']); ?></td>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td>
                  <span class="label label-<?php echo $row['permission'] == 'yes' ? 'success' : 'default'; ?>">
                    <?php echo ucfirst($row['permission']); ?>
                  </span>
                </td>
                <td><?php echo date('d M Y', strtotime($row['upload_date'])); ?></td>
                <td>
                  <a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-eye"></i> View</a>
                  <a href="edit_material.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> Edit</a>
                  <a onclick="return confirm('Are you sure you want to delete this material?');" href="?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>


</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>

<script>
  $(function () {
    $('#materialsTable').DataTable({
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
