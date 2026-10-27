<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin</title>
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
    <h1>Add Image</h1>
    <ol class="breadcrumb">
      <li><a href="allsliderimg.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Add Image</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Add Promotional or Gallery Image</h3>
          </div>
          <div class="box-body">
            <form role="form" enctype="multipart/form-data" method="post">
              <div class="form-group">
                <label for="Slider_name">Image Name</label>
                <input class="form-control" name="Slider_name" required placeholder="Enter Image Name" type="text">
              </div>

              <div class="form-group">
                <label for="image_type">Image Type</label>
                <select class="form-control" name="image_type" id="image_type" required>
                  <option value="promotional" selected>Promotional (App Home carousel)</option>
                  <option value="gallery">Gallery</option>
                </select>
                <small class="text-muted">Choose <strong>Promotional</strong> for images that must appear in the Android app home slider.</small>
              </div>

              <div class="form-group">
                <label for="image1">Upload Image</label>
                <input class="form-control" name="image1" type="file" required accept="image/*">
              </div>

              <div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Add Image</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>



<div class="control-sidebar-bg"></div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>

<?php
if (isset($_POST['submit'])) {
  $Slider_name = mysqli_real_escape_string($con, $_POST['Slider_name']);
  // Must match MySQL ENUM('gallery','promotional') — capital "Promotional" breaks on strict VPS.
  $rawType = strtolower(trim((string) ($_POST['image_type'] ?? 'promotional')));
  $image_type = $rawType === 'gallery' ? 'gallery' : 'promotional';
  $date = date('d-m-Y');

  $image = $_FILES['image1']['name'] ?? '';
  $temp = $_FILES['image1']['tmp_name'] ?? '';

  $folder = $image_type === 'promotional' ? 'promotional' : 'gallery';
  $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename((string) $image));
  if ($safeName === '' || $safeName === '_' || $safeName === '.') {
    $safeName = 'image.jpg';
  }
  $filename = uniqid() . '_' . $safeName;
  $path = $folder . '/' . $filename;

  if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
  }

  if ($temp !== '' && move_uploaded_file($temp, $path)) {
    $sql = "INSERT INTO gallery (name, image, date, type) VALUES ('$Slider_name', '$path', '$date', '$image_type')";
    if ($con->query($sql)) {
      echo "<script>alert('Image added successfully!')</script>";
      echo "<script>window.location.href='allgallery.php'</script>";
    } else {
      echo "<script>alert('DB error: " . addslashes($con->error) . "')</script>";
    }
  } else {
    echo "<script>alert('Failed to upload image.')</script>";
  }
}
?>
