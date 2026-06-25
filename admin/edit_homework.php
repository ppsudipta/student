<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location:addimage.php');
    exit();
}

$homework_id = $_GET['id'];

// Fetch homework details
$stmt = $con->prepare("SELECT * FROM homework_assignments WHERE id = ?");
$stmt->bind_param("i", $homework_id);
$stmt->execute();
$result = $stmt->get_result();
$homework = $result->fetch_assoc();

if (!$homework) {
    header('location:addimage.php');
    exit();
}

// Fetch available classes and sessions
$classes = $con->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != 0 ORDER BY class");
$sessions = $con->query("SELECT DISTINCT session FROM students WHERE session IS NOT NULL ORDER BY session");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $subject = $_POST['subject'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $class = !empty($_POST['class']) ? $_POST['class'] : NULL;
    $session = !empty($_POST['session']) ? $_POST['session'] : NULL;
    
    $stmt = $con->prepare("UPDATE homework_assignments SET title=?, subject=?, description=?, deadline=?, class=?, session=? WHERE id=?");
    $stmt->bind_param("ssssssi", $title, $subject, $description, $deadline, $class, $session, $homework_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Homework assignment updated successfully!";
        header("Location: addimage.php");
        exit();
    } else {
        $error_message = "Error updating homework assignment: " . $con->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin | Edit Homework</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css" rel="stylesheet">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit Homework</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="addimage.php">Homework</a></li>
        <li class="active">Edit Homework</li>
      </ol>
    </section>

    <section class="content">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Homework Details</h3>
            </div>
            <form method="POST">
              <div class="box-body">
                <div class="form-group">
                  <label>Title *</label>
                  <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($homework['title']); ?>" required>
                </div>
                <div class="form-group">
                  <label>Subject *</label>
                  <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($homework['subject']); ?>" required>
                </div>
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($homework['description']); ?></textarea>
                </div>
                <div class="form-group">
                  <label>Class</label>
                  <select name="class" class="form-control">
                    <option value="">All Classes</option>
                    <?php while($class = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $class['class']; ?>" <?php echo ($homework['class'] == $class['class']) ? 'selected' : ''; ?>>
                      Class <?php echo htmlspecialchars($class['class']); ?>
                    </option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Session</label>
                  <select name="session" class="form-control">
                    <option value="">All Sessions</option>
                    <?php 
                    $sessions->data_seek(0); // Reset pointer
                    while($session = $sessions->fetch_assoc()): ?>
                    <option value="<?php echo $session['session']; ?>" <?php echo ($homework['session'] == $session['session']) ? 'selected' : ''; ?>>
                      Session <?php echo htmlspecialchars($session['session']); ?>
                    </option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Deadline *</label>
                  <input type="datetime-local" name="deadline" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($homework['deadline'])); ?>" required>
                </div>
              </div>
              <div class="box-footer">
                <button type="submit" class="btn btn-primary">Update Homework</button>
                <a href="addimage.php" class="btn btn-default">Cancel</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> Your School</strong> All rights reserved.
  </footer>

</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
</body>
</html>