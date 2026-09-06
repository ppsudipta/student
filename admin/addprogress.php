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

$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];
$boardTypes = ['WB Board', 'Competitive', 'ICSE/CBSE'];
$currentYear = (int) date('Y');
$years = range($currentYear - 1, $currentYear + 2);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_type = $_POST['attendance_type'];
    $attendance_date = $_POST['attendance_date'];
    $day_name = date('l', strtotime($attendance_date));

    $title_year = trim((string) ($_POST['title_year'] ?? ''));
    $title_month = trim((string) ($_POST['title_month'] ?? ''));
    $title_day = trim((string) ($_POST['title_day'] ?? ''));
    $title_board = trim((string) ($_POST['title_board'] ?? ''));

    if ($title_year === '' || $title_month === '' || $title_day === '' || $title_board === '') {
        echo "<script>alert('Please complete Attendance Title (Year, Month, Day, Board Type)'); history.back();</script>";
        exit();
    }

    // Stored format matches existing records: "2025 - December - 1 - Competitive"
    $attendance_title = $title_year . ' - ' . $title_month . ' - ' . $title_day . ' - ' . $title_board;

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

    $student_ids = array_values(array_unique(array_filter(array_map('intval', $student_ids))));

    foreach ($student_ids as $sid) {
        $stmt = $con->prepare("INSERT INTO attendance (student_id, class_name, attendance_date, day_name, attendance_title, status) VALUES (?, ?, ?, ?, ?, 'Present')");
        $stmt->bind_param("issss", $sid, $class_name, $attendance_date, $day_name, $attendance_title);
        $stmt->execute();
    }

    echo "<script>alert('Attendance added successfully'); window.location.href='allprogress.php';</script>";
    exit();
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
  <title>Add Attendance Report</title>
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
    .attendance-title-row .form-group {
      margin-bottom: 0;
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
      <h1>Add Attendance Report</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allprogress.php">Attendance Reports</a></li>
        <li class="active">Add Attendance</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Attendance Report Details</h3>
            </div>
            <div class="box-body">
  <h4>Add Attendance</h4>
  <form method="post" id="attendanceForm">
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
          <option value="<?php echo htmlspecialchars($cl); ?>"><?php echo htmlspecialchars($cl); ?></option>
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
            <?php echo htmlspecialchars($st['name']." (".$st['registration_code'].")"); ?>
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
      <div class="row attendance-title-row">
        <div class="col-sm-3 col-xs-6">
          <select name="title_year" id="title_year" class="form-control" required>
            <option value="">Year</option>
            <?php foreach ($years as $year): ?>
              <option value="<?= $year ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-3 col-xs-6">
          <select name="title_month" id="title_month" class="form-control" required>
            <option value="">Month</option>
            <?php foreach ($months as $month): ?>
              <option value="<?= $month ?>" <?= $month === date('F') ? 'selected' : '' ?>><?= $month ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-top:8px;">
          <select name="title_day" id="title_day" class="form-control" required>
            <option value="">Day</option>
            <?php for ($d = 1; $d <= 60; $d++): ?>
              <option value="<?= $d ?>"><?= $d ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-top:8px;">
          <select name="title_board" id="title_board" class="form-control" required>
            <option value="">Board Type</option>
            <?php foreach ($boardTypes as $board): ?>
              <option value="<?= htmlspecialchars($board) ?>"><?= htmlspecialchars($board) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <p class="help-block" id="titlePreview" style="margin-top:8px;">Title preview: —</p>
      <input type="hidden" name="attendance_title" id="attendance_title" value="">
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
  function updateAttendanceTitlePreview() {
    var year = $('#title_year').val();
    var month = $('#title_month').val();
    var day = $('#title_day').val();
    var board = $('#title_board').val();
    if (year && month && day && board) {
      var title = year + ' - ' + month + ' - ' + day + ' - ' + board;
      $('#attendance_title').val(title);
      $('#titlePreview').text('Title preview: ' + title);
    } else {
      $('#attendance_title').val('');
      $('#titlePreview').text('Title preview: —');
    }
  }

  $(document).ready(function() {
    $('#student_id').select2({
      placeholder: "-- Select Student --",
      allowClear: true,
      width: '100%'
    });

    $('#title_year, #title_month, #title_day, #title_board').on('change', updateAttendanceTitlePreview);
    updateAttendanceTitlePreview();

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
