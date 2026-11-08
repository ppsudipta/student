<?php 
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');
include_once __DIR__ . '/students_helper.php';

$titleMonths = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];
$boardTypes = ['WB Board', 'Competitive', 'ICSE/CBSE'];
$currentYear = (int) date('Y');
$titleYears = range($currentYear - 5, $currentYear + 2);

$filterClass = trim((string) ($_GET['class_name'] ?? ''));
$filterStudent = trim((string) ($_GET['student_id'] ?? ''));
$filterReg = trim((string) ($_GET['reg_code'] ?? ''));
$filterYear = trim((string) ($_GET['title_year'] ?? ''));
$filterMonth = trim((string) ($_GET['title_month'] ?? ''));
$filterDay = trim((string) ($_GET['title_day'] ?? ''));
$filterBoard = trim((string) ($_GET['title_board'] ?? ''));
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
    .filter-row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 16px;
      align-items: flex-end;
    }
    .filter-row .form-group {
      margin: 0;
      min-width: 140px;
    }
    .filter-row label {
      display: block;
      font-weight: 600;
      margin-bottom: 4px;
    }
    .filter-actions {
      display: flex;
      gap: 8px;
      align-items: flex-end;
      padding-bottom: 1px;
    }
    /* Hide Select2 clear (×) inside filter fields */
    .filter-row .select2-selection__clear {
      display: none !important;
    }
  </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php';?>
  <?php include 'sidebar.php'; ?>

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
              <form method="get">
                <div class="filter-row">
                  <div class="form-group">
                    <label for="class_filter">Class</label>
                    <select class="form-control select2" id="class_filter" name="class_name" style="width: 160px;">
                      <option value="">All Classes</option>
                      <?php
                      $all_classes = students_unique_classes($con);
                      foreach ($all_classes as $cls):
                      ?>
                        <option value="<?= htmlspecialchars($cls) ?>" <?= $filterClass === $cls ? 'selected' : '' ?>>
                          <?= htmlspecialchars($cls) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="student_filter">Student</label>
                    <select class="form-control select2" id="student_filter" name="student_id" style="width: 200px;">
                      <option value="">All Students</option>
                      <?php
                      $students = $con->query("SELECT s.id, s.name, s.registration_code
                                                FROM students s
                                                JOIN attendance a ON s.id = a.student_id
                                                GROUP BY s.id
                                                ORDER BY s.name");
                      while ($student = $students->fetch_assoc()):
                      ?>
                        <option value="<?= (int) $student['id'] ?>" <?= $filterStudent === (string) $student['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['registration_code']) ?>)
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="reg_code_filter">Roll Number</label>
                    <select class="form-control select2" id="reg_code_filter" name="reg_code" style="width: 150px;">
                      <option value="">All Roll Numbers</option>
                      <?php
                      $reg_codes = $con->query("SELECT DISTINCT registration_code FROM students WHERE registration_code != '' ORDER BY registration_code");
                      while ($rc = $reg_codes->fetch_assoc()):
                      ?>
                        <option value="<?= htmlspecialchars($rc['registration_code']) ?>" <?= $filterReg === $rc['registration_code'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($rc['registration_code']) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_year">Title Year</label>
                    <select class="form-control select2" id="title_year" name="title_year" style="width: 120px;">
                      <option value="">All Years</option>
                      <?php foreach ($titleYears as $year): ?>
                        <option value="<?= $year ?>" <?= $filterYear === (string) $year ? 'selected' : '' ?>><?= $year ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_month">Title Month</label>
                    <select class="form-control select2" id="title_month" name="title_month" style="width: 140px;">
                      <option value="">All Months</option>
                      <?php foreach ($titleMonths as $month): ?>
                        <option value="<?= $month ?>" <?= $filterMonth === $month ? 'selected' : '' ?>><?= $month ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_day">Title Day</label>
                    <select class="form-control select2" id="title_day" name="title_day" style="width: 110px;">
                      <option value="">All Days</option>
                      <?php for ($d = 1; $d <= 60; $d++): ?>
                        <option value="<?= $d ?>" <?= $filterDay === (string) $d ? 'selected' : '' ?>><?= $d ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_board">Board Type</label>
                    <select class="form-control select2" id="title_board" name="title_board" style="width: 150px;">
                      <option value="">All Types</option>
                      <?php foreach ($boardTypes as $board): ?>
                        <option value="<?= htmlspecialchars($board) ?>" <?= $filterBoard === $board ? 'selected' : '' ?>>
                          <?= htmlspecialchars($board) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="allprogress.php" class="btn btn-default">Reset</a>
                  </div>
                </div>
                <p class="help-block" style="margin:10px 0 0;">Title format matches insert: Year - Month - Day - Board Type (e.g. 2025 - December - 1 - Competitive)</p>
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

                  if ($filterClass !== '') {
                    $query .= " AND a.class_name LIKE ?";
                    $params[] = '%' . $filterClass . '%';
                    $types .= 's';
                  }

                  if ($filterStudent !== '' && ctype_digit($filterStudent)) {
                    $query .= " AND a.student_id = ?";
                    $params[] = (int) $filterStudent;
                    $types .= 'i';
                  }

                  if ($filterReg !== '') {
                    $query .= " AND s.registration_code = ?";
                    $params[] = $filterReg;
                    $types .= 's';
                  }

                  // Title parts match insert format: "2025 - December - 1 - Competitive"
                  if ($filterYear !== '') {
                    $query .= " AND a.attendance_title LIKE ?";
                    $params[] = $filterYear . ' - %';
                    $types .= 's';
                  }
                  if ($filterMonth !== '') {
                    $query .= " AND a.attendance_title LIKE ?";
                    $params[] = '% - ' . $filterMonth . ' - %';
                    $types .= 's';
                  }
                  if ($filterDay !== '') {
                    $query .= " AND a.attendance_title LIKE ?";
                    $params[] = '% - ' . $filterDay . ' - %';
                    $types .= 's';
                  }
                  if ($filterBoard !== '') {
                    $query .= " AND a.attendance_title LIKE ?";
                    $params[] = '% - ' . $filterBoard;
                    $types .= 's';
                  }

                  $query .= " ORDER BY a.attendance_date DESC, a.class_name, s.name";
                  $stmt = $con->prepare($query);

                  if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                  }

                  $stmt->execute();
                  $result = $stmt->get_result();

                  while ($attendance = $result->fetch_assoc()):
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

          <?php
          // Second table: class students who were NOT Present under the same filters
          $showNotPresent = ($filterClass !== '');
          $notPresentRows = [];
          $notPresentHint = '';

          if ($showNotPresent) {
            $hasTitleFilter = ($filterYear !== '' || $filterMonth !== '' || $filterDay !== '' || $filterBoard !== '');
            if (!$hasTitleFilter) {
              $notPresentHint = 'Tip: also select Title Year / Month / Day (and Board) to narrow “not present” to a particular class session.';
            }

            // Present student IDs matching the same attendance filters
            $presentQuery = "SELECT DISTINCT a.student_id
                             FROM attendance a
                             JOIN students s ON a.student_id = s.id
                             WHERE a.status = 'Present'
                               AND a.class_name LIKE ?";
            $presentParams = ['%' . $filterClass . '%'];
            $presentTypes = 's';

            if ($filterStudent !== '' && ctype_digit($filterStudent)) {
              $presentQuery .= " AND a.student_id = ?";
              $presentParams[] = (int) $filterStudent;
              $presentTypes .= 'i';
            }
            if ($filterReg !== '') {
              $presentQuery .= " AND s.registration_code = ?";
              $presentParams[] = $filterReg;
              $presentTypes .= 's';
            }
            if ($filterYear !== '') {
              $presentQuery .= " AND a.attendance_title LIKE ?";
              $presentParams[] = $filterYear . ' - %';
              $presentTypes .= 's';
            }
            if ($filterMonth !== '') {
              $presentQuery .= " AND a.attendance_title LIKE ?";
              $presentParams[] = '% - ' . $filterMonth . ' - %';
              $presentTypes .= 's';
            }
            if ($filterDay !== '') {
              $presentQuery .= " AND a.attendance_title LIKE ?";
              $presentParams[] = '% - ' . $filterDay . ' - %';
              $presentTypes .= 's';
            }
            if ($filterBoard !== '') {
              $presentQuery .= " AND a.attendance_title LIKE ?";
              $presentParams[] = '% - ' . $filterBoard;
              $presentTypes .= 's';
            }

            $presentIds = [];
            $pStmt = $con->prepare($presentQuery);
            $pStmt->bind_param($presentTypes, ...$presentParams);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            while ($pRow = $pRes->fetch_assoc()) {
              $presentIds[] = (int) $pRow['student_id'];
            }

            // Ongoing students in this class (optionally narrowed by student/reg filters)
            $stuQuery = "SELECT id, name, registration_code, class, mobile_number, email, status
                         FROM students
                         WHERE status = 'ongoing'
                           AND FIND_IN_SET(?, class)";
            $stuParams = [$filterClass];
            $stuTypes = 's';

            if ($filterStudent !== '' && ctype_digit($filterStudent)) {
              $stuQuery .= " AND id = ?";
              $stuParams[] = (int) $filterStudent;
              $stuTypes .= 'i';
            }
            if ($filterReg !== '') {
              $stuQuery .= " AND registration_code = ?";
              $stuParams[] = $filterReg;
              $stuTypes .= 's';
            }
            $stuQuery .= " ORDER BY name ASC";

            $sStmt = $con->prepare($stuQuery);
            $sStmt->bind_param($stuTypes, ...$stuParams);
            $sStmt->execute();
            $sRes = $sStmt->get_result();
            while ($stu = $sRes->fetch_assoc()) {
              if (!in_array((int) $stu['id'], $presentIds, true)) {
                $notPresentRows[] = $stu;
              }
            }
          }
          ?>

          <?php if ($showNotPresent): ?>
          <div class="box box-danger">
            <div class="box-header with-border">
              <h3 class="box-title">
                Students Not Present
                <small>
                  — Class: <?= htmlspecialchars($filterClass) ?>
                  <?php
                  $titleBits = array_filter([$filterYear, $filterMonth, $filterDay, $filterBoard]);
                  if ($titleBits) {
                    echo ' | Title filter: ' . htmlspecialchars(implode(' - ', $titleBits));
                  }
                  ?>
                </small>
              </h3>
            </div>
            <div class="box-body table-responsive">
              <?php if ($notPresentHint !== ''): ?>
                <p class="text-muted"><?= htmlspecialchars($notPresentHint) ?></p>
              <?php endif; ?>
              <p class="help-block">
                Ongoing students in this class with no <strong>Present</strong> attendance matching the filters above
                (includes unchecked / missing and Absent).
              </p>
              <table id="notPresentTable" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Class</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($notPresentRows)): ?>
                    <tr>
                      <td colspan="7" class="text-center text-success">All filtered ongoing students were Present.</td>
                    </tr>
                  <?php else: ?>
                    <?php $np = 1; foreach ($notPresentRows as $stu): ?>
                    <tr class="attendance-absent">
                      <td><?= $np++ ?></td>
                      <td><?= htmlspecialchars($stu['name']) ?></td>
                      <td><?= htmlspecialchars($stu['registration_code']) ?></td>
                      <td><?= htmlspecialchars($stu['class']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['mobile_number']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['email']) ?></td>
                      <td><span class="label label-danger">Not Present</span></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

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
    $('.filter-row select.select2').select2({
      allowClear: false,
      width: '100%',
      minimumResultsForSearch: 8
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

    if ($('#notPresentTable').length) {
      $('#notPresentTable').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        pageLength: 25
      });
    }
  });
</script>
</body>
</html>
