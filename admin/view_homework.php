<?php 
session_start();
if($_SESSION['username'] == true){
}else{
  header('location:index.php');
}
?>
<?php include('config.php'); 

// Check if homework ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('location:addimage.php');
    exit();
}

$homework_id = $_GET['id'];

// Fetch homework details
$sql = "SELECT * FROM homework_assignments WHERE id = '$homework_id'";
$result = $con->query($sql);

if($result->num_rows == 0) {
    header('location:addimage.php');
    exit();
}

$homework = $result->fetch_assoc();

// Fetch submissions for this homework
$submissions_sql = "SELECT hs.*, s.name as student_name 
                    FROM homework_submissions hs
                    JOIN students s ON hs.student_id = s.id
                    WHERE hs.homework_id = '$homework_id'
                    ORDER BY hs.submission_date DESC";
$submissions_result = $con->query($submissions_sql);
$submissions_count = $submissions_result->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Admin | View Homework</title>
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
        View Homework Assignment
      </h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="addimage.php">Homework Assignments</a></li>
        <li class="active">View Homework</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-md-8">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Assignment Details</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <h3><?php echo htmlspecialchars($homework['title']); ?></h3>
              <p class="text-muted">
                <strong>Subject:</strong> <?php echo htmlspecialchars($homework['subject']); ?><br>
                <strong>Deadline:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($homework['deadline'])); ?>
              </p>
              
              <hr>
              
              <h4>Description:</h4>
              <div class="well">
                <?php echo $homework['description']; ?>
              </div>
              
              <?php if(!empty($homework['attachment_path'])): ?>
              <h4>Attachment:</h4>
              <div class="form-group">
                <a href="<?php echo $homework['attachment_path']; ?>" class="btn btn-default" target="_blank">
                  <i class="fa fa-download"></i> Download Attachment
                </a>
              </div>
              <?php endif; ?>
              
              <div class="box-footer">
                <a href="addimage.php" class="btn btn-default">Back to List</a>
                <a href="edit_homework.php?id=<?php echo $homework['id']; ?>" class="btn btn-primary">Edit Assignment</a>
              </div>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
        
        <div class="col-md-4">
          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Assignment Statistics</h3>
            </div>
            <div class="box-body">
              <div class="small-box bg-aqua">
                <div class="inner">
                  <h3><?php echo $submissions_count; ?></h3>
                  <p>Submissions Received</p>
                </div>
                <div class="icon">
                  <i class="ion ion-ios-paper"></i>
                </div>
                <a href="#submissions" class="small-box-footer">View Submissions <i class="fa fa-arrow-circle-down"></i></a>
              </div>
              
              <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Time Remaining</span>
                  <span class="info-box-number">
                    <?php 
                    $now = new DateTime();
                    $deadline = new DateTime($homework['deadline']);
                    
                    if($now > $deadline) {
                        echo "Deadline passed";
                    } else {
                        $interval = $now->diff($deadline);
                        echo $interval->format('%d days %h hours %i minutes');
                    }
                    ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Submissions Section -->
      <div class="row" id="submissions">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Student Submissions</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <?php if($submissions_count > 0): ?>
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Student Name</th>
                    <th>Submission Date</th>
                    <th>File</th>
                    <th>Comments</th>
                   
                  </tr>
                </thead>
                <tbody>
                  <?php while($submission = $submissions_result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></td>
                    <td>
                      <a href="../pages/uploads/<?php echo $submission['file_path']; ?>" class="btn btn-xs btn-default" target="_blank">
                        <i class="fa fa-download"></i> View
                      </a>
                    </td>
                    <td><?php echo !empty($submission['comments']) ? htmlspecialchars($submission['comments']) : 'No comments'; ?></td>
                    
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
              <?php else: ?>
              <div class="alert alert-info">
                No submissions have been received for this assignment yet.
              </div>
              <?php endif; ?>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
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
</body>
</html>