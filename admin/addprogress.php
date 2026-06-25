<?php  
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

// Fetch all students
$students = [];
$result = $con->query("SELECT id, name, registration_code, class FROM students ORDER BY registration_code ASC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Get unique classes from comma separated field
$classList = [];
$res2 = $con->query("SELECT class FROM students");
while ($row = $res2->fetch_assoc()) {
    $parts = array_map('trim', explode(',', $row['class']));
    foreach ($parts as $c) {
        if ($c != '') $classList[] = $c;
    }
}
$classList = array_unique($classList);
sort($classList);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_type = $_POST['attendance_type'];
    $attendance_date = $_POST['attendance_date'];
    $day_name = date('l', strtotime($attendance_date));
    $attendance_title = $_POST['attendance_title'];

    $student_ids = [];
    $class_name = "";

    if ($attendance_type == "single") {
        $student_ids[] = $_POST['student_id'];
        $class_name = $con->real_escape_string($_POST['class_name_single']);
    } elseif ($attendance_type == "classwise") {
        $class_name = $_POST['class_name'];
        if (!empty($_POST['selected_students'])) {
            $student_ids = $_POST['selected_students']; // only checked students
        }
    } elseif ($attendance_type == "all") {
        $class_name = "All Classes";
        foreach ($students as $st) {
            $student_ids[] = $st['id'];
        }
    }

    foreach ($student_ids as $sid) {
        $stmt = $con->prepare("INSERT INTO attendance (student_id, class_name, attendance_date, day_name, attendance_title, status) VALUES (?, ?, ?, ?, ?, 'Present')");
        $stmt->bind_param("issss", $sid, $class_name, $attendance_date, $day_name, $attendance_title);
        $stmt->execute();
    }

    echo "<script>alert('Attendance added successfully'); window.location.href='allprogress.php';</script>";
}

// Function to get classes for a specific student
function getStudentClasses($con, $student_id) {
    $classes = [];
    $stmt = $con->prepare("SELECT class FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $classes = array_map('trim', explode(',', $row['class']));
    }
    
    return $classes;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Progress Report</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="css/summernote.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

  <style>
    .select2-container--default .select2-selection--single {
      height: 34px !important;
      padding: 6px 12px;
      font-size: 14px;
    }
    #class_select_single, #class_students {
      display: none;
    }
    #students_list div {
      margin-bottom: 5px;
    }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php';?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>  
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Add Progress Report</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allprogress.php">Progress Reports</a></li>
        <li class="active">Add Report</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Progress Report Details</h3>
            </div>
          <div class="container mt-4">
  <h2>Add Attendance</h2>
  <form method="post">
    <div class="form-group">
      <label>Attendance Type</label>
      <select name="attendance_type" id="attendance_type" class="form-control" required>
        <option value="">-- Select --</option>
        <option value="classwise">Class Wise</option>
        <option value="all">All Students</option>
        <option value="single">Single Student</option>
      </select>
    </div>

    <div class="form-group" id="class_select">
      <label>Select Class</label>
      <select name="class_name" id="class_name" class="form-control">
        <option value="">-- Select Class --</option>
        <?php foreach($classList as $cl): ?>
          <option value="<?php echo $cl; ?>"><?php echo $cl; ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group" id="class_students">
      <label>Available Students</label>
      <div id="students_list"></div>
    </div>

    <div class="form-group" id="student_select" style="display:none;">
      <label>Select Student</label>
      <select name="student_id" id="student_id" class="form-control">
        <option value="">-- Select Student --</option>
        <?php foreach ($students as $st): ?>
          <option value="<?php echo $st['id']; ?>">
            <?php echo $st['name']." (".$st['registration_code'].")"; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group" id="class_select_single">
      <label>Select Class for this Student</label>
      <select name="class_name_single" id="class_name_single" class="form-control">
        <option value="">-- Select Class --</option>
      </select>
    </div>

    <div class="form-group">
      <label>Attendance Date</label>
      <input type="date" name="attendance_date" class="form-control" required>
    </div>

    <div class="form-group">
      <label>Attendance Title</label>
      <input type="text" name="attendance_title" class="form-control" placeholder="e.g. Day 1" required>
    </div>

    <button type="submit" class="btn btn-primary">Save Attendance</button>
  </form>
</div>
          </div>
        </div>
      </div>
    </section>
  </div>

</div>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  $(document).ready(function() {
    $('#student_id').select2({
      placeholder: "-- Select Student --",
      allowClear: true,
      width: '100%'
    });

    $('#attendance_type').change(function(){
      var type = $(this).val();
      $('#class_select').toggle(type === 'classwise');
      $('#class_students').hide();
      $('#student_select').toggle(type === 'single');
      $('#class_select_single').toggle(type === 'single');
      if (type !== 'single') {
        $('#class_name_single').html('<option value="">-- Select Class --</option>');
      }
    });

    $('#class_name').change(function(){
      var className = $(this).val();
      if (className) {
        $.ajax({
          url: 'get_class_students.php',
          type: 'POST',
          data: {class_name: className},
          dataType: 'json',
          success: function(response) {
            var html = '';
            if (response.success && response.students.length > 0) {
              $.each(response.students, function(i, st) {
                html += '<div><label><input type="checkbox" name="selected_students[]" value="'+st.id+'"> '
                      + st.name + ' ('+st.registration_code+')</label></div>';
              });
              $('#students_list').html(html);
              $('#class_students').show();
            } else {
              $('#students_list').html('<p>No students found.</p>');
              $('#class_students').show();
            }
          }
        });
      }
    });

    $('#student_id').change(function(){
      var studentId = $(this).val();
      if (studentId) {
        $.ajax({
          url: 'get_student_classes.php',
          type: 'POST',
          data: {student_id: studentId},
          dataType: 'json',
          success: function(response) {
            var options = '<option value="">-- Select Class --</option>';
            if (response.success && response.classes.length > 0) {
              $.each(response.classes, function(index, className) {
                options += '<option value="'+className+'">'+className+'</option>';
              });
            }
            $('#class_name_single').html(options);
          }
        });
      } else {
        $('#class_name_single').html('<option value="">-- Select Class --</option>');
      }
    });
  });
</script>
</body>
</html>
