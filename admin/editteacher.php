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
  <title>Edit Teacher</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
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
      <h1>Edit Teacher</h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Edit Teacher</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header"><h3 class="box-title">Edit Teacher Info</h3></div>

<?php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $con->query("SELECT * FROM teachers WHERE id=$id");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $old_photo = $row['photo'];
?>
            <div class="box-body">
              <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                  <label>Name</label>
                  <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required>
                </div>

                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" class="form-control">
                </div>

                <div class="form-group">
                  <label>Phone</label>
                  <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" class="form-control">
                </div>

                <div class="form-group">
                  <label>Subject</label>
                  <input type="text" name="subject" value="<?= htmlspecialchars($row['subject']) ?>" class="form-control">
                </div>

                <div class="form-group">
                  <label>About / Bio</label>
                  <textarea name="about" class="form-control" id="editor1"><?= htmlspecialchars($row['about']) ?></textarea>
                </div>

                <div class="form-group">
                  <label>Photo</label><br>
                  <?php if (!empty($old_photo)): ?>
                    <img src="<?= htmlspecialchars($old_photo) ?>" style="height:70px;"><br><br>
                  <?php endif; ?>
                  <input type="file" name="photo" class="form-control" accept="image/*">
                </div>

                <button type="submit" name="update" class="btn btn-primary">Update Teacher</button>
              </form>
            </div>
<?php
    } else {
        echo "<div class='box-body'><div class='alert alert-danger'>Teacher not found.</div></div>";
    }
} else {
    echo "<div class='box-body'><div class='alert alert-warning'>No teacher ID specified.</div></div>";
}
?>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="pull-right hidden-xs"><b>Version</b> 1.0</div>
    <strong>&copy; <?= date('Y') ?> Your Organization.</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="js/summernote.js"></script>
<script>
  $(document).ready(function () {
    $('#editor1').summernote({ height: 200 });
  });
</script>
</body>
</html>

<?php
if (isset($_POST['update'])) {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $about   = trim($_POST['about']);

    $photo_path = $old_photo;
    if (!empty($_FILES['photo']['name'])) {
        $target_dir = 'images/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $photo_path = $target_dir . time() . '_' . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            // Optional: Delete old photo if needed
            // if (!empty($old_photo) && file_exists($old_photo)) { unlink($old_photo); }
        } else {
            $photo_path = $old_photo;
        }
    }

    $sql = "UPDATE teachers SET name=?, email=?, phone=?, subject=?, about=?, photo=? WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $email, $phone, $subject, $about, $photo_path, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Teacher updated successfully'); window.location.href='allteach.php';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
}
?>
