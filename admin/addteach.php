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
  <title>Add Teacher</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="css/summernote.css">
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
      <h1>Add Teacher</h1>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-body">
          <form method="post" enctype="multipart/form-data">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" class="form-control">
            </div>

            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control">
            </div>

            <div class="form-group">
              <label>Subject</label>
              <input type="text" name="subject" class="form-control" required>
            </div>

            <!-- ✅ About Field Added -->
            <div class="form-group">
              <label>About</label>
              <textarea name="about" class="form-control" rows="4" placeholder="Write something about the teacher..." required></textarea>
            </div>

            <div class="form-group">
              <label>Photo</label>
              <input type="file" name="photo" class="form-control" accept="image/*" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">Add Teacher</button>
          </form>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> Your School.</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>

</body>
</html>

<?php
if (isset($_POST['submit'])) {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $subject = $_POST['subject'];
    $about   = $_POST['about']; // ✅ Capturing about field from form

    $photo = $_FILES['photo']['name'];
    $target = 'images/' . basename($photo);

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
        $stmt = $con->prepare("INSERT INTO teachers (name, email, phone, subject, about, photo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $subject, $about, $target);

        if ($stmt->execute()) {
            echo "<script>alert('Teacher added successfully'); window.location.href='allteach.php';</script>";
        } else {
            echo "<script>alert('Error: {$stmt->error}');</script>";
        }
    } else {
        echo "<script>alert('Failed to upload photo');</script>";
    }
}
?>
