<?php
session_start();
if ($_SESSION['username'] != true) {
    header('location:index.php');
    exit;
}
include('config.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('location:allabout.php');
    exit;
}

$uploadError = '';

if (isset($_POST['submit'])) {
    $stmt = $con->prepare('SELECT image, details, name, category FROM blog WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    if (!$current) {
        header('location:allabout.php');
        exit;
    }

    $details = isset($_POST['details']) ? trim((string) $_POST['details']) : '';
    if ($details === '') {
        $details = (string) $current['details'];
    }

    $name = (string) ($current['name'] ?? '');
    $category = (string) ($current['category'] ?? '');
    $imagePath = (string) ($current['image'] ?? '');

    if (isset($_FILES['image1']) && is_array($_FILES['image1']) && (int) ($_FILES['image1']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $fileError = (int) $_FILES['image1']['error'];
        if ($fileError !== UPLOAD_ERR_OK) {
            $uploadError = 'Image upload failed. Please try again.';
        } else {
            $original = (string) ($_FILES['image1']['name'] ?? '');
            $tmp = (string) ($_FILES['image1']['tmp_name'] ?? '');
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed, true)) {
                $uploadError = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
            } elseif ($tmp === '' || !is_uploaded_file($tmp)) {
                $uploadError = 'Invalid uploaded file.';
            } else {
                $dir = __DIR__ . DIRECTORY_SEPARATOR . 'category';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $safeName = 'about_' . $id . '_' . time() . '.' . $ext;
                $destFs = $dir . DIRECTORY_SEPARATOR . $safeName;
                if (!move_uploaded_file($tmp, $destFs)) {
                    $uploadError = 'Could not save the uploaded image. Check folder permissions.';
                } else {
                    $imagePath = 'category/' . $safeName;
                }
            }
        }
    }

    if ($uploadError === '') {
        $upd = $con->prepare('UPDATE blog SET name = ?, image = ?, details = ?, category = ? WHERE id = ?');
        $upd->bind_param('ssssi', $name, $imagePath, $details, $category, $id);
        if ($upd->execute()) {
            echo "<script>alert('Edit successful'); window.location.href='allabout.php';</script>";
            exit;
        }
        $uploadError = 'Database update failed.';
    }
}

$rowStmt = $con->prepare('SELECT * FROM blog WHERE id = ? LIMIT 1');
$rowStmt->bind_param('i', $id);
$rowStmt->execute();
$row = $rowStmt->get_result()->fetch_array();
if (!$row) {
    header('location:allabout.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Admin</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="css/morris.css">
  <link rel="stylesheet" href="css/jquery-jvectormap.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker.min.css">
  <link rel="stylesheet" href="css/daterangepicker.css">
  <link rel="stylesheet" href="css/bootstrap3-wysihtml5.min.css">
  <link rel="stylesheet" href="test/css/bootstrap.min.css">
  <link rel="stylesheet" href="test/css/font-awesome.min.css">
  <link rel="stylesheet" href="test/css/ionicons.min.css">
  <link rel="stylesheet" href="test/css/datepicker3.css">
  <link rel="stylesheet" href="test/css/all.css">
  <link rel="stylesheet" href="test/css/select2.min.css">
  <link rel="stylesheet" href="test/css/dataTables.bootstrap.css">
  <link rel="stylesheet" href="test/css/jquery.fancybox.css">
  <link rel="stylesheet" href="test/css/AdminLTE.min.css">
  <link rel="stylesheet" href="test/css/_all-skins.min.css">
  <link rel="stylesheet" href="test/css/on-off-switch.css"/>
  <link rel="stylesheet" href="test/css/summernote.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit About</h1>
      <ol class="breadcrumb">
        <li><a href="alltour.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Edit About</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Edit About</h3>
            </div>
            <div class="box-body">
              <?php if ($uploadError !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($uploadError); ?></div>
              <?php endif; ?>

              <form role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
                <div class="form-group">
                  <label for="editor1">About Details</label>
                  <textarea name="details" class="form-control" cols="50" rows="10" id="editor1"><?php echo $row['details'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="image1">Image</label>
                  <?php if (!empty($row['image'])): ?>
                    <div style="margin-bottom:10px;">
                      <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Current about image" style="max-width:220px;max-height:160px;border:1px solid #ddd;padding:4px;">
                      <div class="help-block">Current: <?php echo htmlspecialchars($row['image']); ?></div>
                    </div>
                  <?php endif; ?>
                  <input class="form-control" id="image1" name="image1" type="file" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,.png,.jpg,.jpeg,.gif,.webp">
                  <p class="help-block">Leave empty to keep the current image. JPG, PNG, GIF, WEBP allowed.</p>
                </div>

                <div class="box-footer">
                  <button type="submit" name="submit" value="1" class="btn btn-primary">Update About</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="pull-right hidden-xs"><b>Version</b> 1.0</div>
    <strong>Copyright &copy; 2018</strong> All rights reserved.
  </footer>
  <div class="control-sidebar-bg"></div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button);</script>
<script src="js/bootstrap.min.js"></script>
<script src="js/raphael.min.js"></script>
<script src="js/morris.min.js"></script>
<script src="js/jquery.sparkline.min.js"></script>
<script src="js/jquery-jvectormap-1.2.2.min.js"></script>
<script src="js/jquery-jvectormap-world-mill-en.js"></script>
<script src="js/jquery.knob.min.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/daterangepicker.js"></script>
<script src="js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap3-wysihtml5.all.min.js"></script>
<script src="bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<script src="bower_components/fastclick/lib/fastclick.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="js/dashboard.js"></script>
<script src="js/demo.js"></script>
<script src="test/js/jquery.dataTables.min.js"></script>
<script src="test/js/dataTables.bootstrap.min.js"></script>
<script src="test/js/select2.full.min.js"></script>
<script src="test/js/jquery.inputmask.js"></script>
<script src="test/js/jquery.inputmask.date.extensions.js"></script>
<script src="test/js/jquery.inputmask.extensions.js"></script>
<script src="test/js/moment.min.js"></script>
<script src="test/js/bootstrap-datepicker.js"></script>
<script src="test/js/icheck.min.js"></script>
<script src="test/js/fastclick.js"></script>
<script src="test/js/jquery.sparkline.min.js"></script>
<script src="test/js/jquery.slimscroll.min.js"></script>
<script src="test/js/jquery.fancybox.pack.js"></script>
<script src="test/js/app.min.js"></script>
<script src="test/js/jscolor.js"></script>
<script src="test/js/on-off-switch.js"></script>
<script src="test/js/on-off-switch-onload.js"></script>
<script src="test/js/clipboard.min.js"></script>
<script src="test/js/summernote.js"></script>
<script>
  $(document).ready(function () {
    $('#editor1').summernote({ height: 200 });
  });
</script>
</body>
</html>
