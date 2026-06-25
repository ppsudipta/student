<?php
session_start();
if($_SESSION['username'] == true) {
} else {
    header('location:index.php');
}
include('config.php');

// Get notice details based on random_id
if(isset($_GET['random_id'])) {
    $random_id = $_GET['random_id'];
    
    // Get the first notice with this random_id to pre-fill the form
    $sql = "SELECT * FROM notices WHERE random_id = '$random_id' LIMIT 1";
    $result = $con->query($sql);
    
    if($result && $result->num_rows > 0) {
        $notice = $result->fetch_assoc();
    } else {
        header('location: allnotice.php?status=error&message=Notice not found');
        exit();
    }
} else {
    header('location: allnotice.php?status=error&message=Invalid request');
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_content = $_POST['notice_content'];
    $notice_type = $_POST['notice_type'];
    
    // Update all notices with this random_id
    $update_sql = "UPDATE notices SET notice_content = '$new_content', notice_type = '$notice_type' WHERE random_id = '$random_id'";
    
    if($con->query($update_sql)) {
        header('location: allnotice.php?status=success&message=Notice updated successfully');
    } else {
        $error = "Error updating notice: " . $con->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Admin | Edit Notice</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
  
  <!-- Font Awesome -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <!-- Morris chart -->
  <link rel="stylesheet" href="css/morris.css">
  <!-- jvectormap -->
  <link rel="stylesheet" href="css/jquery-jvectormap.css">
  <!-- Date Picker -->
  <link rel="stylesheet" href="css/bootstrap-datepicker.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="css/daterangepicker.css">

  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="css/bootstrap3-wysihtml5.min.css">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <!-- Left side column. contains the logo and sidebar -->
  <?php include 'header.php';?>
  
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
          <img src="upload/924Koala.jpg" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p>Admin</p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      
      <?php include 'sidebar.php'; ?>
  </section>
  </aside>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Edit Notice
        <small>Control panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allnotice.php">All Notices</a></li>
        <li class="active">Edit Notice</li>
      </ol>

    </section>
    <!-- Main content -->
    <section class="content">

            <div class="container-fluid">
                <div class="row m-1">
                    <div class="col-12">
                        <h5>Edit Notice</h5>
                       
                        <div class="row m-1 mb-3">
                            <div class="col-12">
                                <a href="allnotice.php" class="btn btn-default">Back to All Notices</a>
                            </div>
                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Edit Notice Form</h5>
                                <p>Update the notice details below.</p>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Notice Type:</label>
                                        <select name="notice_type" class="form-control" required>
                                            <option value="text" <?php echo $notice['notice_type'] == 'text' ? 'selected' : ''; ?>>Text</option>
                                            <option value="image" <?php echo $notice['notice_type'] == 'image' ? 'selected' : ''; ?>>Image</option>
                                            <option value="video" <?php echo $notice['notice_type'] == 'video' ? 'selected' : ''; ?>>Video</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Notice Content:</label>
                                        <?php if($notice['notice_type'] == 'text'): ?>
                                            <textarea name="notice_content" class="form-control" rows="5" required><?php echo htmlspecialchars($notice['notice_content']); ?></textarea>
                                        <?php else: ?>
                                            <input type="text" name="notice_content" class="form-control" value="<?php echo htmlspecialchars($notice['notice_content']); ?>" required>
                                            <small class="form-text text-muted">
                                                <?php echo $notice['notice_type'] == 'image' ? 'Enter image URL' : 'Enter video URL'; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Notice</button>
                                    <a href="allnotice.php" class="btn btn-default">Cancel</a>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Current Notice Preview</h5>
                            </div>
                            <div class="card-body">
                                <?php if($notice['notice_type'] == 'text'): ?>
                                    <div class="well">
                                        <p><?php echo nl2br(htmlspecialchars($notice['notice_content'])); ?></p>
                                    </div>
                                <?php elseif($notice['notice_type'] == 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($notice['notice_content']); ?>" alt="Notice Image" class="img-responsive" style="max-width: 100%;">
                                <?php elseif($notice['notice_type'] == 'video'): ?>
                                    <video controls class="img-responsive" style="max-width: 100%;">
                                        <source src="<?php echo htmlspecialchars($notice['notice_content']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <p><strong>Type:</strong> <?php echo ucfirst($notice['notice_type']); ?></p>
                                    <p><strong>Created:</strong> <?php echo $notice['created_at']; ?></p>
                                    <p><strong>Notice ID:</strong> <?php echo $notice['random_id']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      
</section>
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version</b> 2.4.0
    </div>
    <strong>Copyright &copy; 2025 Sunrise Academy.</strong> All rights
    reserved.
  </footer>

    <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->
<!-- jQuery 3 -->
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
<script src="js/dataTables.bootstrap.min.js"></script>
<!-- Sparkline -->
<script src="js/jquery.sparkline.min.js"></script>
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
<script src="js/jquery.dataTables.min.js"></script>

<script>
// Update preview when type changes
$('select[name="notice_type"]').change(function() {
    var type = $(this).val();
    var contentField = $('input[name="notice_content"], textarea[name="notice_content"]');
    
    if (type === 'text') {
        if (contentField.is('input')) {
            var currentVal = contentField.val();
            contentField.replaceWith('<textarea name="notice_content" class="form-control" rows="5" required>' + currentVal + '</textarea>');
        }
    } else {
        if (contentField.is('textarea')) {
            var currentVal = contentField.val();
            contentField.replaceWith('<input type="text" name="notice_content" class="form-control" value="' + currentVal + '" required>');
        }
        
        // Update help text
        var helpText = type === 'image' ? 'Enter image URL' : 'Enter video URL';
        $('.form-text').text(helpText);
    }
    
    // Update preview section
    updatePreview();
});

// Update preview when content changes
$(document).on('keyup', 'input[name="notice_content"], textarea[name="notice_content"]', function() {
    updatePreview();
});

function updatePreview() {
    var type = $('select[name="notice_type"]').val();
    var content = $('input[name="notice_content"], textarea[name="notice_content"]').val();
    
    var previewHtml = '';
    if (type === 'text') {
        previewHtml = '<div class="well"><p>' + content.replace(/\n/g, '<br>') + '</p></div>';
    } else if (type === 'image') {
        previewHtml = '<img src="' + content + '" alt="Notice Image" class="img-responsive" style="max-width: 100%;">';
    } else if (type === 'video') {
        previewHtml = '<video controls class="img-responsive" style="max-width: 100%;">' +
                      '<source src="' + content + '" type="video/mp4">' +
                      'Your browser does not support the video tag.' +
                      '</video>';
    }
    
    $('.card-body').eq(1).find('.well, img, video').remove();
    $('.card-body').eq(1).prepend(previewHtml);
}
</script>

</body>
</html>