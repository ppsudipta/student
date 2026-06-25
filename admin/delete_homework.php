<?php
session_start();
if(!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}

include('config.php');

// Check if homework ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No homework ID specified.";
    header('location:addimage.php');
    exit();
}

$homework_id = $con->real_escape_string($_GET['id']);

// Verify the homework exists before deleting
$check_sql = "SELECT id, title FROM homework_assignments WHERE id = '$homework_id'";
$check_result = $con->query($check_sql);

if($check_result->num_rows == 0) {
    $_SESSION['error'] = "Homework assignment not found.";
    header('location:addimage.php');
    exit();
}

$homework = $check_result->fetch_assoc();

// Check if form was submitted (confirmation)
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Begin transaction
    $con->begin_transaction();
    
    try {
        // First delete all submissions for this homework
        $delete_submissions_sql = "DELETE FROM homework_submissions WHERE homework_id = '$homework_id'";
        if(!$con->query($delete_submissions_sql)) {
            throw new Exception("Failed to delete submissions: " . $con->error);
        }
        
        // Then delete the homework assignment
        $delete_homework_sql = "DELETE FROM homework_assignments WHERE id = '$homework_id'";
        if(!$con->query($delete_homework_sql)) {
            throw new Exception("Failed to delete homework: " . $con->error);
        }
        
        // Commit transaction if both queries succeeded
        $con->commit();
        
        $_SESSION['success'] = "Homework assignment '".htmlspecialchars($homework['title'])."' and all its submissions were successfully deleted.";
        header('location:addimage.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $con->rollback();
        $_SESSION['error'] = "Error deleting homework: " . $e->getMessage();
        header("location:view_homework.php?id=$homework_id");
        exit();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Admin | Delete Homework</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins folder -->
  <link rel="stylesheet" href="css/_all-skins.min.css">
  
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php';?>
  
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>  
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Delete Homework Assignment
      </h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="addimage.php">Homework Assignments</a></li>
        <li class="active">Delete Homework</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <div class="box box-danger">
            <div class="box-header with-border">
              <h3 class="box-title">Confirm Deletion</h3>
            </div>
            <div class="box-body">
              <div class="alert alert-danger">
                <h4><i class="icon fa fa-warning"></i> Warning!</h4>
                You are about to permanently delete the following homework assignment and all its submissions. This action cannot be undone.
              </div>
              
              <div class="well">
                <h4><?php echo htmlspecialchars($homework['title']); ?></h4>
                <p>ID: <?php echo $homework['id']; ?></p>
              </div>
              
              <?php
              // Check how many submissions exist
              $submissions_sql = "SELECT COUNT(*) as count FROM homework_submissions WHERE homework_id = '$homework_id'";
              $submissions_result = $con->query($submissions_sql);
              $submissions_count = $submissions_result->fetch_assoc()['count'];
              
              if($submissions_count > 0) {
                  echo '<div class="alert alert-warning">';
                  echo '<i class="fa fa-exclamation-triangle"></i> This assignment has '.$submissions_count.' submission(s) that will also be deleted.';
                  echo '</div>';
              }
              ?>
              
              <form method="post">
                <div class="form-group">
                  <label for="confirm">Type "DELETE" to confirm:</label>
                  <input type="text" class="form-control" id="confirm" name="confirm" required 
                         placeholder="Enter DELETE in all caps" pattern="DELETE">
                </div>
                
                <div class="box-footer">
                  <a href="view_homework.php?id=<?php echo $homework_id; ?>" class="btn btn-default">Cancel</a>
                  <button type="submit" class="btn btn-danger pull-right">Permanently Delete</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
 
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version</b> 1.0
    </div>
    <strong>Copyright &copy; 2025</strong> All rights reserved.
  </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery 3 -->
<script src="js/jquery.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="js/bootstrap.min.js"></script>
<!-- AdminLTE App -->
<script src="js/adminlte.min.js"></script>

<script>
// Client-side validation for the confirmation input
$(document).ready(function() {
    $('form').submit(function(e) {
        var confirmText = $('#confirm').val();
        if(confirmText !== 'DELETE') {
            alert('Please type "DELETE" in all caps to confirm.');
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>