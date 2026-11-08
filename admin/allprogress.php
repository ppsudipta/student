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
// Title parts are independent — any combination is allowed
$filterYear = trim((string) ($_GET['title_year'] ?? ''));
$filterMonth = trim((string) ($_GET['title_month'] ?? ''));
$filterDay = trim((string) ($_GET['title_day'] ?? ''));
$filterBoard = trim((string) ($_GET['title_board'] ?? ''));
$formYear = $filterYear;
$formMonth = $filterMonth;
$formDay = $filterDay;
$formBoard = $filterBoard;

$hasFullCohort = ($filterYear !== '' && $filterMonth !== '' && $filterDay !== '' && $filterBoard !== '');
$hasAnyTitleFilter = ($filterYear !== '' || $filterMonth !== '' || $filterDay !== '' || $filterBoard !== '');
$filterAttempted = isset($_GET['class_name']) || isset($_GET['title_year']) || isset($_GET['student_id'])
  || isset($_GET['reg_code']) || isset($_GET['title_month']) || isset($_GET['title_day']) || isset($_GET['title_board']);
$filterError = '';
// Class is mandatory to filter / view attendance lists
if ($filterAttempted && $filterClass === '') {
  $filterError = 'Please select a Class to filter attendance.';
}
$canFilter = ($filterClass !== '');

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
                    <label for="class_filter">Class <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="class_filter" name="class_name" style="width: 160px;" required>
                      <option value="">-- Select Class --</option>
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
                      $reg_codes = $con->query("SELECT DISTINCT registration_code FROM students WHERE registration_code != '' ORDER BY " . students_roll_order_sql('registration_code'));
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
                  <strong>Class is required.</strong>
                  Year / Month / Day / Board are optional and independent
                  (example title: 2025 - December - 1 - Competitive).
                  For <strong>Mark Present</strong>, also select full Year + Month + Day + Board.
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
                  <?php if (!$canFilter): ?>
                    <tr>
                      <td colspan="8" class="text-center text-muted">Select a <strong>Class</strong> and click Filter to view attendance.</td>
                    </tr>
                  <?php else:
                  $query = "SELECT a.*, s.name as student_name, s.registration_code 
                            FROM attendance a 
                            JOIN students s ON a.student_id = s.id 
                            WHERE (a.class_name = ? OR " . students_class_match_sql('s.class') . ")";

                  $params = [$filterClass, $filterClass];
                  $types = 'ss';

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
                  $stmt->bind_param($types, ...$params);
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
                  <?php endwhile; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <?php
          // Not Present requires Class. Title Year/Month/Day/Board are optional filters.
          $cohortTitle = '';
          if ($hasFullCohort) {
            $cohortTitle = $filterYear . ' - ' . $filterMonth . ' - ' . $filterDay . ' - ' . $filterBoard;
          }

          $notPresentRows = [];
          $notPresentClasses = [];
          $notPresentHint = '';
          $showNotPresent = $canFilter;
          $cohortDate = '';
          $returnQuery = $_SERVER['QUERY_STRING'] ?? '';
          $returnUrl = 'allprogress.php' . ($returnQuery !== '' ? ('?' . $returnQuery) : '');

          if ($canFilter) {
            $notPresentClasses = [$filterClass];

            // Present students for this class (+ optional title filters)
            $presentQuery = "SELECT a.student_id, a.attendance_date, a.attendance_title
                             FROM attendance a
                             JOIN students s ON a.student_id = s.id
                             WHERE a.status = 'Present'
                               AND (a.class_name = ? OR " . students_class_match_sql('s.class') . ")";
            $presentParams = [$filterClass, $filterClass];
            $presentTypes = 'ss';
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
            attendance_append_title_filters(
              $presentQuery, $presentParams, $presentTypes,
              $filterYear, $filterMonth, $filterDay, $filterBoard,
              true, 'a'
            );

            $presentIds = [];
            $pStmt = $con->prepare($presentQuery);
            $pStmt->bind_param($presentTypes, ...$presentParams);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            while ($pRow = $pRes->fetch_assoc()) {
              $presentIds[(int) $pRow['student_id']] = true;
              if ($cohortDate === '' && !empty($pRow['attendance_date'])) {
                $cohortDate = (string) $pRow['attendance_date'];
              }
            }

            // Expected = ongoing students in this class
            $stuQuery = "SELECT s.id, s.name, s.registration_code, s.class, s.mobile_number, s.email, s.status
                         FROM students s
                         WHERE (s.status = 'ongoing' OR s.status = '' OR s.status IS NULL)
                           AND (" . students_class_match_sql('s.class') . " OR EXISTS (
                             SELECT 1 FROM attendance ax
                             WHERE ax.student_id = s.id AND ax.class_name = ?
                           ))";
            $stuParams = [$filterClass, $filterClass];
            $stuTypes = 'ss';
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
            $stuQuery .= ' ORDER BY ' . students_roll_order_sql('s.registration_code') . ', s.name ASC';

            $cStmt = $con->prepare($stuQuery);
            $cStmt->bind_param($stuTypes, ...$stuParams);
            $cStmt->execute();
            $cRes = $cStmt->get_result();
            while ($stu = $cRes->fetch_assoc()) {
              $sid = (int) $stu['id'];
              if (isset($presentIds[$sid])) {
                continue;
              }
              if (strtolower(trim((string) ($stu['status'] ?? ''))) === 'suspended') {
                continue;
              }
              $notPresentRows[] = $stu;
            }

            if (!$hasFullCohort) {
              $notPresentHint = 'Tip: select Year + Month + Day + Board to enable Mark Present for a specific session title.';
            }
          }
          ?>

          <?php if ($showNotPresent): ?>
          <div class="box box-danger">
            <div class="box-header with-border">
              <h3 class="box-title">
                Students Not Present (Same Class)
                <small>
                  — Class: <?= htmlspecialchars($filterClass) ?>
                  <?php
                  $titleBits = array_filter([$filterYear, $filterMonth, $filterDay !== '' ? ('Day ' . $filterDay) : '', $filterBoard]);
                  if ($titleBits) {
                    echo ' | Filters: ' . htmlspecialchars(implode(' - ', $titleBits));
                  }
                  if ($cohortTitle !== '') {
                    echo ' | Cohort: ' . htmlspecialchars($cohortTitle);
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
                Ongoing classmates with no <strong>Present</strong> mark for the current filters.
                <?php if ($hasFullCohort): ?>Use <strong>Mark Present</strong> to create a Present record (Absent is not stored).<?php endif; ?>
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
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($notPresentRows)): ?>
                    <tr>
                      <td colspan="8" class="text-center text-success">All classmates were Present for these filters.</td>
                    </tr>
                  <?php else: ?>
                    <?php
                    $np = 1;
                    foreach ($notPresentRows as $stu):
                      $markUrl = $hasFullCohort
                        ? ('mark_attendance_present.php?' . http_build_query([
                            'student_id' => (int) $stu['id'],
                            'class_name' => $filterClass,
                            'title' => $cohortTitle,
                            'date' => $cohortDate,
                            'return' => $returnUrl,
                          ]))
                        : '';
                    ?>
                    <tr class="attendance-absent">
                      <td><?= $np++ ?></td>
                      <td><?= htmlspecialchars($stu['name']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['registration_code']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['class']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['mobile_number']) ?></td>
                      <td><?= htmlspecialchars((string) $stu['email']) ?></td>
                      <td><span class="label label-danger">Not Present</span></td>
                      <td>
                        <?php if ($hasFullCohort): ?>
                        <a href="<?= htmlspecialchars($markUrl) ?>"
                           class="btn btn-success btn-xs js-mark-present"
                           onclick="return confirm('Mark this student as Present for this cohort?');">
                          <i class="fa fa-check"></i> Mark Present
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
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

    // Class is mandatory
    $('#attendanceFilterForm').on('submit', function (e) {
      var cls = $.trim($('#class_filter').val() || '');
      if (!cls) {
        e.preventDefault();
        alert('Please select a Class to filter attendance.');
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
