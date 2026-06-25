<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

// Fetch image data
$id = intval($_GET['id']);
$res = $con->query("SELECT * FROM gallery WHERE id = $id");
$row = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Gallery</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
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
      <h1>Edit Gallery</h1>
      <ol class="breadcrumb">
        <li><a href="allgallery.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Edit Gallery</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Edit Image</h3>
        </div>
        <form method="post" enctype="multipart/form-data">
          <div class="box-body">
            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

            <div class="form-group">
              <label>Gallery Name</label>
              <input type="text" name="gallery_name" class="form-control" required value="<?php echo htmlspecialchars($row['name']); ?>">
            </div>

            <div class="form-group">
              <label>Image Type</label>
              <select name="type" class="form-control" required>
                <option value="Promotional" <?php if ($row['type'] === 'Promotional') echo 'selected'; ?>>Promotional</option>
                <option value="Gallery" <?php if ($row['type'] === 'Gallery') echo 'selected'; ?>>Gallery</option>
              </select>
            </div>

            <div class="form-group">
              <label>Change Image (optional)</label>
              <input type="file" name="image1" class="form-control" accept="image/*">
              <p class="help-block">Leave blank to keep current image.</p>
              <img src="<?php echo htmlspecialchars($row['image']); ?>" height="70" style="margin-top:10px;">
            </div>
          </div>
          <div class="box-footer text-center">
            <button type="submit" name="submit" class="btn btn-primary">Update</button>
            <a href="allgallery.php" class="btn btn-default">Cancel</a>
          </div>
        </form>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> Supnits Classes</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>

<?php
if (isset($_POST['submit'])) {
  $gallery_name = mysqli_real_escape_string($con, $_POST['gallery_name']);
  $type = mysqli_real_escape_string($con, $_POST['type']);
  $date = date('d-m-Y');

  if (!empty($_FILES['image1']['name'])) {
    $filename = basename($_FILES['image1']['name']);
    $path1 = 'gallery/' . uniqid() . '_' . $filename;
    move_uploaded_file($_FILES['image1']['tmp_name'], $path1);
  } else {
    $path1 = $row['image']; // keep old image
  }

  $update = "UPDATE gallery SET name='$gallery_name', image='$path1', type='$type', date='$date' WHERE id=$id";
  if ($con->query($update)) {
    echo "<script>alert('Gallery updated successfully'); window.location.href='allgallery.php';</script>";
  } else {
    echo "<script>alert('Update failed: " . $con->error . "');</script>";
  }
}
?>
