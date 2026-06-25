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
  <title>Student Attendance Management</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <style>
    .attendance-present { background-color: #dff0d8; }
    .attendance-absent { background-color: #f2dede; }
    .filter-section {
      margin-bottom: 15px;
      padding: 15px;
      background-color: #f9f9f9;
      border-radius: 5px;
    }
  </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php';?>
  <aside class="main-sidebar">
    <section class="sidebar"><?php include 'sidebar.php'; ?></section>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Student Attendance <small>Management Panel</small></h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Attendance</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Filter Attendance Records</h3>
            </div>

            <div class="box-body">
              <form method="get" class="form-inline">

                <!-- Class Filter -->
                <div class="form-group">
                  <label for="class_filter">Class:</label>
                  <select class="form-control select2" id="class_filter" name="class_name" style="width: 180px;">
                    <option value="">All Classes</option>
                    <?php
                    $classes = $con->query("SELECT DISTINCT class_name FROM attendance");
                    $all_classes = [];
                    while($class = $classes->fetch_assoc()) {
                      $split = array_map('trim', explode(',', $class['class_name']));
                      $all_classes = array_merge($all_classes, $split);
                    }
                    $all_classes = array_unique($all_classes);
                    sort($all_classes);
                    foreach($all_classes as $cls):
                    ?>
                      <option value="<?= $cls ?>" <?= (isset($_GET['class_name']) && $_GET['class_name'] == $cls) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cls) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Student Filter -->
                <div class="form-group" style="margin-left: 15px;">
                  <label for="student_filter">Student:</label>
                  <select class="form-control select2" id="student_filter" name="student_id" style="width: 180px;">
                    <option value="">All Students</option>
                    <?php
                    $students = $con->query("SELECT s.id, s.name, s.registration_code 
                                              FROM students s 
                                              JOIN attendance a ON s.id = a.student_id 
                                              GROUP BY s.id 
                                              ORDER BY s.name");
                    while($student = $students->fetch_assoc()):
                    ?>
                      <option value="<?= $student['id'] ?>" <?= (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['registration_code']) ?>)
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <!-- Roll Number Filter -->
                <div class="form-group" style="margin-left: 15px;">
                  <label for="reg_code_filter">Roll Number:</label>
                  <select class="form-control select2" id="reg_code_filter" name="reg_code" style="width: 180px;">
                    <option value="">All Roll Numbers</option>
                    <?php
                    $reg_codes = $con->query("SELECT DISTINCT registration_code FROM students WHERE registration_code != '' ORDER BY registration_code");
                    while($rc = $reg_codes->fetch_assoc()):
                    ?>
                      <option value="<?= $rc['registration_code'] ?>" <?= (isset($_GET['reg_code']) && $_GET['reg_code'] == $rc['registration_code']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rc['registration_code']) ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <!-- Month Filter -->
                <div class="form-group" style="margin-left: 15px;">
                  <label for="month_filter">Month:</label>
                  <select class="form-control" id="month_filter" name="month" style="width: 150px;">
                    <option value="">All Months</option>
                    <?php
                    $months = [
                      '01'=>'January','02'=>'February','03'=>'March','04'=>'April',
                      '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
                      '09'=>'September','10'=>'October','11'=>'November','12'=>'December'
                    ];
                    foreach ($months as $num=>$name): 
                    ?>
                      <option value="<?= $num ?>" <?= (isset($_GET['month']) && $_GET['month'] == $num) ? 'selected' : '' ?>>
                        <?= $name ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Title Filter -->
                <div class="form-group" style="margin-left: 15px;">
                  <label for="title_filter">Title:</label>
                  <select class="form-control select2" id="title_filter" name="attendance_title" style="width: 200px;">
                    <option value="">All Titles</option>
                    <?php
                    $titles = $con->query("SELECT DISTINCT attendance_title FROM attendance WHERE attendance_title != '' ORDER BY attendance_title");
                    while($t = $titles->fetch_assoc()):
                    ?>
                      <option value="<?= $t['attendance_title'] ?>" <?= (isset($_GET['attendance_title']) && $_GET['attendance_title'] == $t['attendance_title']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['attendance_title']) ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-left: 15px;">Filter</button>
                <a href="allprogress.php" class="btn btn-default" style="margin-left: 10px;">Reset</a>
              </form>
            </div>
          </div>

          <!-- Attendance Table -->
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Attendance Records</h3>
              <div class="box-tools pull-right">
                <a href="addprogress.php" class="btn btn-success"><i class="fa fa-plus"></i> Add New Attendance</a>
              </div>
            </div>

            <div class="box-body table-responsive">
              <table id="attendanceTable" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Class</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $query = "SELECT a.*, s.name as student_name, s.registration_code 
                            FROM attendance a 
                            JOIN students s ON a.student_id = s.id 
                            WHERE 1=1";

                  $params = [];
                  $types = '';

                  if(isset($_GET['class_name']) && !empty($_GET['class_name'])) {
                    $query .= " AND a.class_name LIKE ?";
                    $params[] = '%' . $_GET['class_name'] . '%';
                    $types .= 's';
                  }

                  if(isset($_GET['student_id']) && !empty($_GET['student_id'])) {
                    $query .= " AND a.student_id = ?";
                    $params[] = $_GET['student_id'];
                    $types .= 'i';
                  }

                  if(isset($_GET['reg_code']) && !empty($_GET['reg_code'])) {
                    $query .= " AND s.registration_code = ?";
                    $params[] = $_GET['reg_code'];
                    $types .= 's';
                  }

                  if(isset($_GET['month']) && !empty($_GET['month'])) {
                    $query .= " AND MONTH(a.attendance_date) = ?";
                    $params[] = $_GET['month'];
                    $types .= 's';
                  }

                  if(isset($_GET['attendance_title']) && !empty($_GET['attendance_title'])) {
                    $query .= " AND a.attendance_title = ?";
                    $params[] = $_GET['attendance_title'];
                    $types .= 's';
                  }

                  $query .= " ORDER BY a.attendance_date DESC, a.class_name, s.name";
                  $stmt = $con->prepare($query);

                  if(!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                  }

                  $stmt->execute();
                  $result = $stmt->get_result();

                  while($attendance = $result->fetch_assoc()):
                    $status_class = ($attendance['status'] == 'Present') ? 'attendance-present' : 'attendance-absent';
                  ?>
                  <tr class="<?= $status_class ?>">
                    <td><?= htmlspecialchars($attendance['attendance_title']) ?></td>
                    <td><?= htmlspecialchars($attendance['student_name']) ?></td>
                    <td><?= htmlspecialchars($attendance['registration_code']) ?></td>
                    <td><?= htmlspecialchars($attendance['class_name']) ?></td>
                    <td><?= date('M j, Y', strtotime($attendance['attendance_date'])) ?></td>
                    <td><?= htmlspecialchars($attendance['day_name']) ?></td>
                    <td>
                      <span class="label label-<?= ($attendance['status'] == 'Present') ? 'success' : 'danger' ?>">
                        <?= htmlspecialchars($attendance['status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="edit_allprogress.php?id=<?= $attendance['id'] ?>" class="btn btn-warning btn-xs"><i class="fa fa-edit"></i> Edit</a>
                      <a href="delete_allprogress.php?id=<?= $attendance['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="fa fa-trash"></i> Delete</a>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </section>
  </div>
</div>

<!-- JS Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/adminlte.min.js"></script>

<script>
  $(function () {
    $('.select2').select2({
      placeholder: "-- Select Option --",
      allowClear: true,
      width: 'resolve'
    });

    $('#attendanceTable').DataTable({
      paging: true,
      lengthChange: true,
      searching: true,
      ordering: true,
      info: true,
      autoWidth: false,
      responsive: true,
      pageLength: 25
    });
  });
</script>
</body>
</html>
