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
// Keep form display values even when cohort validation fails
$formYear = trim((string) ($_GET['title_year'] ?? ''));
$formMonth = trim((string) ($_GET['title_month'] ?? ''));
$formDay = trim((string) ($_GET['title_day'] ?? ''));
$formBoard = trim((string) ($_GET['title_board'] ?? ''));

// Cohort fields are all-or-nothing: if any one is filled, Year+Month+Day+Board are required
$cohortFilledCount = ($formYear !== '' ? 1 : 0)
  + ($formMonth !== '' ? 1 : 0)
  + ($formDay !== '' ? 1 : 0)
  + ($formBoard !== '' ? 1 : 0);
$filterError = '';
$hasFullCohort = ($cohortFilledCount === 4);

// Applied filters (empty when partial cohort — blocks incomplete filtering)
$filterYear = $hasFullCohort ? $formYear : '';
$filterMonth = $hasFullCohort ? $formMonth : '';
$filterDay = $hasFullCohort ? $formDay : '';
$filterBoard = $hasFullCohort ? $formBoard : '';

if ($cohortFilledCount > 0 && $cohortFilledCount < 4) {
  $filterError = 'Please fill all cohort fields together: Title Year, Month, Day, and Board Type (or leave all empty).';
}

/**
 * Append exact title-part filters for format: "Year - Month - Day - Board Type"
 * Uses SUBSTRING_INDEX so Day=2 does not match Day=12 / Day=1 incorrectly.
 *
 * @param list<mixed> $params
 */
function attendance_append_title_filters(
    string &$sql,
    array &$params,
    string &$types,
    string $year,
    string $month,
    string $day,
    string $board,
    bool $includeDay = true,
    string $alias = 'a'
): void {
    $col = $alias . '.attendance_title';
    // Normalize separators for parsing: Year | Month | Day | Board...
    $partsExpr = "REPLACE($col, ' - ', '|')";

    if ($year !== '') {
        $sql .= " AND SUBSTRING_INDEX($partsExpr, '|', 1) = ?";
        $params[] = $year;
        $types .= 's';
    }
    if ($month !== '') {
        $sql .= " AND SUBSTRING_INDEX(SUBSTRING_INDEX($partsExpr, '|', 2), '|', -1) = ?";
        $params[] = $month;
        $types .= 's';
    }
    if ($includeDay && $day !== '') {
        $sql .= " AND SUBSTRING_INDEX(SUBSTRING_INDEX($partsExpr, '|', 3), '|', -1) = ?";
        $params[] = $day;
        $types .= 's';
    }
    if ($board !== '') {
        $sql .= " AND TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT($partsExpr, '|'), '|', 4), '|', -1)) = ?";
        $params[] = $board;
        $types .= 's';
    }
}
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
              <?php if ($filterError !== ''): ?>
                <div class="alert alert-warning" style="margin-bottom:12px;"><?= htmlspecialchars($filterError) ?></div>
              <?php endif; ?>
              <form method="get" id="attendanceFilterForm">
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
                    <select class="form-control select2 cohort-field" id="title_year" name="title_year" style="width: 120px;">
                      <option value="">All Years</option>
                      <?php foreach ($titleYears as $year): ?>
                        <option value="<?= $year ?>" <?= $formYear === (string) $year ? 'selected' : '' ?>><?= $year ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_month">Title Month</label>
                    <select class="form-control select2 cohort-field" id="title_month" name="title_month" style="width: 140px;">
                      <option value="">All Months</option>
                      <?php foreach ($titleMonths as $month): ?>
                        <option value="<?= $month ?>" <?= $formMonth === $month ? 'selected' : '' ?>><?= $month ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_day">Title Day</label>
                    <select class="form-control select2 cohort-field" id="title_day" name="title_day" style="width: 110px;">
                      <option value="">All Days</option>
                      <?php for ($d = 1; $d <= 60; $d++): ?>
                        <option value="<?= $d ?>" <?= $formDay === (string) $d ? 'selected' : '' ?>><?= $d ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="title_board">Board Type</label>
                    <select class="form-control select2 cohort-field" id="title_board" name="title_board" style="width: 150px;">
                      <option value="">All Types</option>
                      <?php foreach ($boardTypes as $board): ?>
                        <option value="<?= htmlspecialchars($board) ?>" <?= $formBoard === $board ? 'selected' : '' ?>>
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
                <p class="help-block" style="margin:10px 0 0;">
                  Cohort = <strong>Year – Month – Day – Board</strong>
                  (e.g. 2025 - December - 1 - Competitive).
                  If you fill any one of these four, you must fill all four.
                </p>
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
                  attendance_append_title_filters(
                    $query, $params, $types,
                    $filterYear, $filterMonth, $filterDay, $filterBoard,
                    true, 'a'
                  );

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
                      <a href="edit_allprogress.php?id=<?= (int) $attendance['id'] ?>" class="btn btn-warning btn-xs"><i class="fa fa-edit"></i> Edit</a>
                      <a href="delete_allprogress.php?id=<?= (int) $attendance['id'] ?>"
                         class="btn btn-danger btn-xs js-delete-attendance"
                         data-id="<?= (int) $attendance['id'] ?>"><i class="fa fa-trash"></i> Delete</a>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <?php
          // Cohort = Year - Month - Day - Board (exact attendance_title).
          // Not Present = ongoing students in that cohort's class who were not Present.
          $cohortTitle = '';
          if ($hasFullCohort) {
            $cohortTitle = $filterYear . ' - ' . $filterMonth . ' - ' . $filterDay . ' - ' . $filterBoard;
          }

          $notPresentRows = [];
          $notPresentClasses = [];
          $notPresentHint = '';
          $showNotPresent = false;

          if ($hasFullCohort) {
            $showNotPresent = true;
            $notPresentHint = '';

            $addClassFromRow = static function (array &$classSet, string $attClass, string $studentClass): void {
              $attClass = trim($attClass);
              if ($attClass !== '' && strcasecmp($attClass, 'All Classes') !== 0) {
                $classSet[$attClass] = true;
                return;
              }
              foreach (array_map('trim', explode(',', $studentClass)) as $part) {
                if ($part !== '') {
                  $classSet[$part] = true;
                }
              }
            };

            $classSet = [];
            if ($filterClass !== '') {
              $classSet[$filterClass] = true;
            }

            // Present for this exact cohort title
            $presentQuery = "SELECT a.student_id, a.class_name, s.class AS student_class
                             FROM attendance a
                             JOIN students s ON a.student_id = s.id
                             WHERE a.status = 'Present'
                               AND a.attendance_title = ?";
            $presentParams = [$cohortTitle];
            $presentTypes = 's';
            if ($filterClass !== '') {
              $presentQuery .= " AND (a.class_name LIKE ? OR FIND_IN_SET(?, s.class))";
              $presentParams[] = '%' . $filterClass . '%';
              $presentParams[] = $filterClass;
              $presentTypes .= 'ss';
            }
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

            $presentIds = [];
            $pStmt = $con->prepare($presentQuery);
            $pStmt->bind_param($presentTypes, ...$presentParams);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            while ($pRow = $pRes->fetch_assoc()) {
              $presentIds[(int) $pRow['student_id']] = true;
              $addClassFromRow($classSet, (string) $pRow['class_name'], (string) $pRow['student_class']);
            }

            // Also read class from any attendance rows for this exact cohort (even if empty present set edge cases)
            $clsStmt = $con->prepare(
              "SELECT a.class_name, s.class AS student_class
               FROM attendance a
               JOIN students s ON a.student_id = s.id
               WHERE a.attendance_title = ?"
            );
            $clsStmt->bind_param('s', $cohortTitle);
            $clsStmt->execute();
            $clsRes = $clsStmt->get_result();
            while ($cRow = $clsRes->fetch_assoc()) {
              $addClassFromRow($classSet, (string) $cRow['class_name'], (string) $cRow['student_class']);
            }

            $notPresentClasses = array_keys($classSet);
            $expectedIds = [];

            if (!empty($notPresentClasses)) {
              $classConds = [];
              $stuParams = [];
              $stuTypes = '';
              foreach ($notPresentClasses as $cls) {
                // Match profile class OR any prior attendance.class_name for that student
                $classConds[] = '(FIND_IN_SET(?, s.class) OR EXISTS (
                  SELECT 1 FROM attendance ax
                  WHERE ax.student_id = s.id AND ax.class_name = ?
                ))';
                $stuParams[] = $cls;
                $stuParams[] = $cls;
                $stuTypes .= 'ss';
              }

              $stuQuery = "SELECT s.id, s.name, s.registration_code, s.class, s.mobile_number, s.email, s.status
                           FROM students s
                           WHERE (s.status = 'ongoing' OR s.status = '' OR s.status IS NULL)
                             AND (" . implode(' OR ', $classConds) . ")";
              if ($filterStudent !== '' && ctype_digit($filterStudent)) {
                $stuQuery .= " AND s.id = ?";
                $stuParams[] = (int) $filterStudent;
                $stuTypes .= 'i';
              }
              if ($filterReg !== '') {
                $stuQuery .= " AND s.registration_code = ?";
                $stuParams[] = $filterReg;
                $stuTypes .= 's';
              }
              $stuQuery .= " ORDER BY s.name ASC";

              $cStmt = $con->prepare($stuQuery);
              $cStmt->bind_param($stuTypes, ...$stuParams);
              $cStmt->execute();
              $cRes = $cStmt->get_result();
              while ($stu = $cRes->fetch_assoc()) {
                $expectedIds[(int) $stu['id']] = $stu;
              }
            }

            foreach ($expectedIds as $sid => $stu) {
              if (isset($presentIds[$sid])) {
                continue;
              }
              if (strtolower(trim((string) ($stu['status'] ?? ''))) === 'suspended') {
                continue;
              }
              $notPresentRows[] = $stu;
            }

            if (empty($notPresentClasses)) {
              $notPresentHint = 'No class found for cohort "' . $cohortTitle . '". Ensure attendance has a class, or select Class filter.';
            }
          }
          ?>

          <?php if ($showNotPresent): ?>
          <div class="box box-danger">
            <div class="box-header with-border">
              <h3 class="box-title">
                Students Not Present (Same Class)
                <small>
                  <?php if ($cohortTitle !== ''): ?>
                    — Cohort: <?= htmlspecialchars($cohortTitle) ?>
                  <?php endif; ?>
                  <?php if (!empty($notPresentClasses)): ?>
                    | Class: <?= htmlspecialchars(implode(', ', $notPresentClasses)) ?>
                  <?php endif; ?>
                </small>
              </h3>
            </div>
            <div class="box-body table-responsive">
              <?php if ($notPresentHint !== ''): ?>
                <p class="text-muted"><?= htmlspecialchars($notPresentHint) ?></p>
              <?php endif; ?>
              <?php if ($hasFullCohort): ?>
              <p class="help-block">
                Cohort is <strong>Year – Month – Day – Board</strong>.
                Ongoing classmates (same class) with no <strong>Present</strong> mark for this cohort.
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
                  <?php if (empty($notPresentClasses) && empty($notPresentRows)): ?>
                    <tr>
                      <td colspan="7" class="text-center text-warning">No class found for this cohort.</td>
                    </tr>
                  <?php elseif (empty($notPresentRows)): ?>
                    <tr>
                      <td colspan="7" class="text-center text-success">All classmates were Present for this cohort.</td>
                    </tr>
                  <?php else: ?>
                    <?php $np = 1; foreach ($notPresentRows as $stu): ?>
                    <tr class="attendance-absent">
                      <td><?= $np++ ?></td>
                      <td><?= htmlspecialchars($stu['name']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['registration_code']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['class']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['mobile_number']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['email']) ?></td>
                      <td><span class="label label-danger">Not Present</span></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
              <?php endif; ?>
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

    // If any cohort field is filled, all four (Year/Month/Day/Board) are required
    $('#attendanceFilterForm').on('submit', function (e) {
      var year = $.trim($('#title_year').val() || '');
      var month = $.trim($('#title_month').val() || '');
      var day = $.trim($('#title_day').val() || '');
      var board = $.trim($('#title_board').val() || '');
      var filled = (year ? 1 : 0) + (month ? 1 : 0) + (day ? 1 : 0) + (board ? 1 : 0);
      if (filled > 0 && filled < 4) {
        e.preventDefault();
        alert('Please fill all cohort fields together: Title Year, Month, Day, and Board Type (or leave all empty).');
        return false;
      }
    });

    var attendanceTable = $('#attendanceTable').DataTable({
      paging: true,
      lengthChange: true,
      searching: true,
      ordering: true,
      info: true,
      autoWidth: false,
      responsive: true,
      pageLength: 25
    });

    // DataTables can break inline onclick — use delegated click for delete
    $('#attendanceTable').on('click', 'a.js-delete-attendance', function (e) {
      e.preventDefault();
      var href = $(this).attr('href');
      if (confirm('Are you sure you want to delete this attendance record?')) {
        window.location.href = href;
      }
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
