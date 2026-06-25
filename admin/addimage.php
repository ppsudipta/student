<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_homework'])) {
    $id = $_POST['homework_id'];
    
    $stmt = $con->prepare("DELETE FROM homework_assignments WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Homework assignment deleted successfully!";
        header("Location: addimage.php");
        exit();
    } else {
        $error_message = "Error deleting homework assignment: " . $con->error;
    }
}

// Fetch all homework assignments
$homeworks = $con->query("
    SELECT ha.*, 
           IFNULL(ha.class, 'All Classes') as class_name,
           IFNULL(ha.session, 'All Sessions') as session_name
    FROM homework_assignments ha
    ORDER BY ha.deadline DESC
");

// Check if viewing a specific homework
$view_homework = null;
$submissions = [];
$submissions_count = 0;

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $homework_id = $_GET['view'];
    
    // Get homework details
    $stmt = $con->prepare("SELECT * FROM homework_assignments WHERE id = ?");
    $stmt->bind_param("i", $homework_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $view_homework = $result->fetch_assoc();
    
    if ($view_homework) {
        // Get submissions for this homework
        $submissions_result = $con->query("
            SELECT hs.*, s.name as student_name 
            FROM homework_submissions hs
            JOIN students s ON hs.student_id = s.id
            WHERE hs.homework_id = '$homework_id'
            ORDER BY hs.submission_date DESC
        ");
        $submissions = $submissions_result->fetch_all(MYSQLI_ASSOC);
        $submissions_count = $submissions_result->num_rows;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin | Homework Management</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Homework Management</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Homework</li>
      </ol>
    </section>

    <section class="content">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>
      
      <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <div class="row">
        <div class="col-md-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Homework Assignments</h3>
              <div class="box-tools pull-right">
                <a href="add_homework.php" class="btn btn-sm btn-primary">
                  <i class="fa fa-plus"></i> Add New
                </a>
              </div>
            </div>
            <div class="box-body">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Session</th>
                    <th>Deadline</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($homework = $homeworks->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($homework['title']); ?></td>
                    <td><?php echo htmlspecialchars($homework['subject']); ?></td>
                    <td><?php echo htmlspecialchars($homework['class']); ?></td>
                    <td><?php echo htmlspecialchars($homework['session']); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($homework['deadline'])); ?></td>
                    <td>
                      <a href="?view=<?php echo $homework['id']; ?>" class="btn btn-xs btn-info">
                        <i class="fa fa-eye"></i> View
                      </a>
                      <a href="edit_homework.php?id=<?php echo $homework['id']; ?>" class="btn btn-xs btn-warning">
                        <i class="fa fa-edit"></i> Edit
                      </a>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="homework_id" value="<?php echo $homework['id']; ?>">
                        <button type="submit" name="delete_homework" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure you want to delete this homework?')">
                          <i class="fa fa-trash"></i> Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <?php if ($view_homework): ?>
        <div class="col-md-12">
          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Assignment Details</h3>
            </div>
            <div class="box-body">
              <h3><?php echo htmlspecialchars($view_homework['title']); ?></h3>
              <p class="text-muted">
                <strong>Subject:</strong> <?php echo htmlspecialchars($view_homework['subject']); ?><br>
                <strong>Class:</strong> <?php echo htmlspecialchars($view_homework['class'] ? 'Class '.$view_homework['class'] : 'All Classes'); ?><br>
                <strong>Session:</strong> <?php echo htmlspecialchars($view_homework['session'] ? 'Session '.$view_homework['session'] : 'All Sessions'); ?><br>
                <strong>Deadline:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($view_homework['deadline'])); ?>
              </p>
              
              <hr>
              
              <h4>Description:</h4>
              <div class="well">
                <?php echo $view_homework['description']; ?>
              </div>
              
              <div class="box-footer">
                <a href="addimage.php" class="btn btn-default">Back to List</a>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Student Submissions (<?php echo $submissions_count; ?>)</h3>
            </div>
            <div class="box-body">
              <?php if ($submissions_count > 0): ?>
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
                  <?php foreach ($submissions as $submission): ?>
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
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php else: ?>
              <div class="alert alert-info">
                No submissions have been received for this assignment yet.
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> 2025 Sunrise Academy</strong> All rights reserved.
  </footer>

</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>