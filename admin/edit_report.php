<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php'); 

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: allprogress.php');
    exit;
}

$report_id = $_GET['id'];

$stmt = $con->prepare("SELECT pr.*, s.name as student_name, s.registration_code, s.course 
                      FROM progress_reports pr 
                      JOIN students s ON pr.student_id = s.id 
                      WHERE pr.id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if(!$report) {
    header('Location: allprogress.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = $_POST['report_date'];
    $subject = $_POST['subject'];
    $marks_obtained = $_POST['marks_obtained'];
    $marks_out_of = $_POST['marks_out_of'];
    $academic_performance = $_POST['academic_performance'];
    $attendance = $_POST['attendance'];
    $behavior_notes = $_POST['behavior_notes'];
    $teacher_comments = $_POST['teacher_comments'];

    $stmt = $con->prepare("UPDATE progress_reports 
                          SET report_date = ?, subject = ?, marks_obtained = ?, marks_out_of = ?, academic_performance = ?, attendance = ?, behavior_notes = ?, teacher_comments = ? 
                          WHERE id = ?");
    $stmt->bind_param("ssddssssi", $report_date, $subject, $marks_obtained, $marks_out_of, $academic_performance, $attendance, $behavior_notes, $teacher_comments, $report_id);
    
    if($stmt->execute()) {
        echo "<script>alert('Progress report updated successfully')</script>";
        echo "<script>window.location.href='allprogress.php?student_id=".$report['student_id']."'</script>";
    } else {
        echo "<script>alert('Error updating progress report')</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Progress Report</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="css/summernote.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php';?>
  
  <aside class="main-sidebar">
    <section class="sidebar"><?php include 'sidebar.php'; ?></section>  
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit Progress Report <small>for <?php echo htmlspecialchars($report['student_name']); ?></small></h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allprogress.php">Progress Reports</a></li>
        <li class="active">Edit Report</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Student Information</h3>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="col-md-4"><p><strong>Name:</strong> <?php echo htmlspecialchars($report['student_name']); ?></p></div>
                <div class="col-md-4"><p><strong>Registration Code:</strong> <?php echo htmlspecialchars($report['registration_code']); ?></p></div>
                <div class="col-md-4"><p><strong>Course:</strong> <?php echo htmlspecialchars($report['course']); ?></p></div>
              </div>
            </div>
          </div>

          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Report Details</h3>
            </div>
            <div class="box-body">
              <form method="post">
                <div class="box-body">
                  <div class="form-group">
                    <label for="report_date">Report Date</label>
                    <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo htmlspecialchars($report['report_date']); ?>" required>
                  </div>

                  <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($report['subject']); ?>" required>
                  </div>

                  <div class="form-group">
                    <label for="marks_obtained">Marks Obtained</label>
                    <input type="number" step="0.01" class="form-control" id="marks_obtained" name="marks_obtained" value="<?php echo htmlspecialchars($report['marks_obtained']); ?>" required>
                  </div>

                  <div class="form-group">
                    <label for="marks_out_of">Marks Out Of</label>
                    <input type="number" step="0.01" class="form-control" id="marks_out_of" name="marks_out_of" value="<?php echo htmlspecialchars($report['marks_out_of']); ?>" required>
                  </div>
                  
                  <div class="form-group">
                    <label for="academic_performance">Academic Performance</label>
                    <select class="form-control" id="academic_performance" name="academic_performance" required>
                      <option value="">-- Select Performance --</option>
                      <?php 
                      $performances = ['Excellent', 'Good', 'Fair', 'Needs Improvement'];
                      foreach ($performances as $perf) {
                        $selected = ($report['academic_performance'] == $perf) ? 'selected' : '';
                        echo "<option value='$perf' $selected>$perf</option>";
                      }
                      ?>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="attendance">Attendance</label>
                    <select class="form-control" id="attendance" name="attendance" required>
                      <option value="Excellent (95-100%)" <?= $report['attendance'] == 'Excellent (95-100%)' ? 'selected' : '' ?>>Excellent (95-100%)</option>
                      <option value="Good (85-94%)" <?= $report['attendance'] == 'Good (85-94%)' ? 'selected' : '' ?>>Good (85-94%)</option>
                      <option value="Fair (75-84%)" <?= $report['attendance'] == 'Fair (75-84%)' ? 'selected' : '' ?>>Fair (75-84%)</option>
                      <option value="Needs Improvement (Below 75%)" <?= $report['attendance'] == 'Needs Improvement (Below 75%)' ? 'selected' : '' ?>>Needs Improvement (Below 75%)</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="behavior_notes">Behavior Notes</label>
                    <textarea class="form-control summernote" id="behavior_notes" name="behavior_notes" rows="3"><?php echo htmlspecialchars($report['behavior_notes']); ?></textarea>
                  </div>
                  
                  <div class="form-group">
                    <label for="teacher_comments">Teacher Comments</label>
                    <textarea class="form-control summernote" id="teacher_comments" name="teacher_comments" rows="3" required><?php echo htmlspecialchars($report['teacher_comments']); ?></textarea>
                  </div>
                </div>

                <div class="box-footer">
                  <button type="submit" class="btn btn-primary">Update Report</button>
                  <a href="allprogress.php?student_id=<?php echo $report['student_id']; ?>" class="btn btn-default">Cancel</a>
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
    <strong>&copy; <?php echo date('Y'); ?></strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/fastclick.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="js/summernote.js"></script>
<script>
  $(function () {
    $('.summernote').summernote({
      height: 150,
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link']],
        ['view', ['fullscreen', 'codeview', 'help']]
      ]
    });
  });
</script>
</body>
</html>
