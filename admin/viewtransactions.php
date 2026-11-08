<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');
include_once __DIR__ . '/students_helper.php';
include_once __DIR__ . '/fees_helper.php';

$fee_months = fees_month_names();
$year_options = fees_year_options();

$from_date = trim((string) ($_GET['from_date'] ?? ''));
$to_date = trim((string) ($_GET['to_date'] ?? ''));
$class_filter_value = trim((string) ($_GET['class_filter'] ?? ''));
$reg_filter_value = trim((string) ($_GET['reg_filter'] ?? ''));
$month_filter = trim((string) ($_GET['month_filter'] ?? ''));
$year_filter = trim((string) ($_GET['year_filter'] ?? ''));
$show_mode = trim((string) ($_GET['show_mode'] ?? 'paid'));
if (!in_array($show_mode, ['paid', 'unpaid'], true)) {
    $show_mode = 'paid';
}
if ($month_filter !== '' && !in_array($month_filter, $fee_months, true)) {
    $month_filter = '';
}
if ($year_filter !== '' && !preg_match('/^\d{4}$/', $year_filter)) {
    $year_filter = '';
}

$unpaid_error = '';
$unpaid_rows = [];
$result = false;
$last_paid_display = '';

if ($show_mode === 'unpaid') {
    if ($month_filter === '') {
        $unpaid_error = 'Please select a Fee Month to list not-paid active students.';
    } elseif ($year_filter === '') {
        $unpaid_error = 'Please select a Year with Fee Month for not-paid active students.';
    } else {
        $conds = ["s.status = 'ongoing'", "s.registration_code IS NOT NULL", "s.registration_code != ''"];

        if ($class_filter_value !== '') {
            $classEsc = mysqli_real_escape_string($con, $class_filter_value);
            $conds[] = students_class_match_literal('s.class', $classEsc);
        }
        if ($reg_filter_value !== '') {
            $regEsc = mysqli_real_escape_string($con, $reg_filter_value);
            $conds[] = "s.registration_code = '$regEsc'";
        }

        $periodMatch = fees_period_match_sql($con, $month_filter, $year_filter, 'd');
        $sql = "SELECT s.registration_code, s.name, s.class, s.mobile_number, s.email, s.total_fees, s.status
                FROM students s
                WHERE " . implode(' AND ', $conds) . "
                  AND NOT EXISTS (
                    SELECT 1 FROM donations d
                    WHERE d.student_registration_code = s.registration_code
                      AND d.status = 'success'
                      AND $periodMatch
                  )
                ORDER BY s.name ASC";
        $result = mysqli_query($con, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $unpaid_rows[] = $row;
            }
        }
    }
} else {
    $conds = [];
    if ($from_date !== '' && $to_date !== '') {
        $fromEsc = mysqli_real_escape_string($con, $from_date);
        $toEsc = mysqli_real_escape_string($con, $to_date);
        $conds[] = "d.donation_date BETWEEN '$fromEsc' AND '$toEsc'";
    }
    if ($class_filter_value !== '') {
        $classEsc = mysqli_real_escape_string($con, $class_filter_value);
        $conds[] = students_class_match_literal('s.class', $classEsc);
    }
    if ($reg_filter_value !== '') {
        $regEsc = mysqli_real_escape_string($con, $reg_filter_value);
        $conds[] = "d.student_registration_code = '$regEsc'";
    }
    if ($month_filter !== '' && $year_filter !== '') {
        $conds[] = fees_period_match_sql($con, $month_filter, $year_filter, 'd');
    } elseif ($month_filter !== '') {
        $conds[] = fees_period_match_sql($con, $month_filter, '', 'd');
    } elseif ($year_filter !== '') {
        $conds[] = fees_year_match_sql($con, $year_filter, 'd');
    }

    $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
    $sql = "SELECT d.*, s.class
            FROM donations d
            LEFT JOIN students s ON d.student_registration_code = s.registration_code
            $whereSql
            ORDER BY d.id DESC";
    $result = mysqli_query($con, $sql);
}

// Prefill last paid month when a registration code is already selected
if ($reg_filter_value !== '') {
    $lastInfo = fees_last_paid_for_student($con, $reg_filter_value);
    $last_paid_display = $lastInfo['last_paid'];
}

// --- FETCH STUDENTS FOR DROPDOWN ---
$students = mysqli_query($con, "SELECT id, name, registration_code, total_fees FROM students ORDER BY name ASC");

// --- FETCH UNIQUE CLASSES FOR CLASS-WISE SELECTION ---
$classes = students_unique_classes($con);

// --- FETCH REGISTRATION CODES FOR DROPDOWN ---
$reg_codes_result = mysqli_query($con, "SELECT registration_code, name FROM students WHERE registration_code IS NOT NULL AND registration_code != '' ORDER BY " . students_roll_order_sql('registration_code') . ", name ASC");
$reg_codes = [];
while ($row = mysqli_fetch_assoc($reg_codes_result)) {
    $reg_codes[$row['registration_code']] = $row['registration_code'] . ' - ' . $row['name'];
}

// --- GET STUDENTS BY CLASS ---
$class_students = [];
if (isset($_GET['selected_class']) && !empty($_GET['selected_class'])) {
    $selected_class = mysqli_real_escape_string($con, $_GET['selected_class']);
    $classMatch = students_class_match_literal('class', $selected_class);
    $class_students_query = mysqli_query($con, "SELECT registration_code, name, email, total_fees FROM students WHERE $classMatch ORDER BY name");
    while ($student = mysqli_fetch_assoc($class_students_query)) {
        $class_students[] = $student;
    }
}

// --- SEARCH STUDENT BY NAME OR REGISTRATION CODE ---
$search_results = [];
if (isset($_GET['search_student']) && !empty($_GET['search_term'])) {
    $search_term = mysqli_real_escape_string($con, $_GET['search_term']);
    $search_query = mysqli_query($con, "SELECT registration_code, name, email, total_fees FROM students WHERE name LIKE '%$search_term%' OR registration_code LIKE '%$search_term%' ORDER BY name");
    while ($student = mysqli_fetch_assoc($search_query)) {
        $search_results[] = $student;
    }
}

// Get active tab from URL or session
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_SESSION['active_fee_tab']) ? $_SESSION['active_fee_tab'] : 'transactions');
$_SESSION['active_fee_tab'] = $active_tab;
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .dataTables_wrapper{
            overflow-y:scroll !important;
        }
    </style>
  <meta charset="utf-8">
  <title>Fee Management</title>
 <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <!-- Morris chart -->
  <link rel="stylesheet" href="css/morris.css">
  <!-- jvectormap -->
  <link rel="stylesheet" href="css/jquery-jvectormap.css">
  <!-- Date Picker -->
  <link rel="stylesheet" href="css/bootstrap-datepicker.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="css/daterangepicker.css">
  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="css/bootstrap3-wysihtml5.min.css">


 <link rel="stylesheet" href="test/css/bootstrap.min.css">
	<link rel="stylesheet" href="test/css/font-awesome.min.css">
	<link rel="stylesheet" href="test/css/ionicons.min.css">
	<link rel="stylesheet" href="test/css/datepicker3.css">
	<link rel="stylesheet" href="test/css/all.css">
	<link rel="stylesheet" href="test/css/select2.min.css">
	<link rel="stylesheet" href="test/css/dataTables.bootstrap.css">
	<link rel="stylesheet" href="test/css/jquery.fancybox.css">
	<link rel="stylesheet" href="test/css/AdminLTE.min.css">
	<link rel="stylesheet" href="test/css/_all-skins.min.css">
	<link rel="stylesheet" href="test/css/on-off-switch.css"/>
	<link rel="stylesheet" href="test/css/summernote.css">
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <style>
    .student-checkbox {
      margin: 5px 0;
    }
    .student-list {
      max-height: 300px;
      overflow-y: auto;
      border: 1px solid #ddd;
      padding: 10px;
      margin-bottom: 15px;
    }
    .fee-amount {
      font-weight: bold;
      color: #007bff;
    }
    .filter-row {
      margin-bottom: 15px;
    }
    .filter-row .form-group {
      margin-right: 10px;
      margin-bottom: 10px;
      vertical-align: top;
    }
    .select2-container--default .select2-selection--single {
      height: 34px;
      padding: 3px;
    }
    #lastPaidWrap {
      display: none;
    }
    #lastPaidWrap.visible {
      display: inline-block;
    }
    #lastPaidMonth {
      background: #f5f5f5;
      font-weight: 600;
      min-width: 160px;
    }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

<?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Fee Management <small>Transactions & Add Fees</small></h1>
  </section>

  <section class="content">
    <div class="nav-tabs-custom">
      <ul class="nav nav-tabs">
        <li class="<?= $active_tab == 'transactions' ? 'active' : '' ?>"><a href="#tab_transactions" data-toggle="tab">View Transactions</a></li>
        <li class="<?= $active_tab == 'addfees' ? 'active' : '' ?>"><a href="#tab_addfees" data-toggle="tab">Add Fees</a></li>
      </ul>
      <div class="tab-content">

        <!-- Transactions Tab -->
        <div class="tab-pane <?= $active_tab == 'transactions' ? 'active' : '' ?>" id="tab_transactions">
          <form method="GET" class="form-inline filter-row">
            <input type="hidden" name="tab" value="transactions">

            <div class="form-group">
              <label>Show: </label>
              <select name="show_mode" id="showMode" class="form-control">
                <option value="paid" <?= $show_mode === 'paid' ? 'selected' : '' ?>>Paid transactions</option>
                <option value="unpaid" <?= $show_mode === 'unpaid' ? 'selected' : '' ?>>Not paid (active students)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Fee Month: </label>
              <select name="month_filter" class="form-control">
                <option value="">All Months</option>
                <?php foreach ($fee_months as $month): ?>
                  <option value="<?= htmlspecialchars($month) ?>" <?= $month_filter === $month ? 'selected' : '' ?>>
                    <?= htmlspecialchars($month) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Year: </label>
              <select name="year_filter" class="form-control">
                <option value="">All Years</option>
                <?php foreach ($year_options as $y): ?>
                  <option value="<?= $y ?>" <?= $year_filter === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group paid-only-filter">
              <label>From: </label>
              <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
            </div>

            <div class="form-group paid-only-filter">
              <label>To: </label>
              <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
            </div>

            <div class="form-group">
              <label>Class: </label>
              <select name="class_filter" class="form-control">
                <option value="">All Classes</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= htmlspecialchars($class) ?>" <?= $class_filter_value === $class ? 'selected' : '' ?>>
                    <?= htmlspecialchars($class) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Registration Code: </label>
              <select name="reg_filter" id="regFilter" class="form-control select2-registration" style="width: 250px;">
                <option value="">All Students</option>
                <?php foreach ($reg_codes as $code => $display): ?>
                  <option value="<?= htmlspecialchars($code) ?>" <?= $reg_filter_value === $code ? 'selected' : '' ?>>
                    <?= htmlspecialchars($display) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group <?= $reg_filter_value !== '' ? 'visible' : '' ?>" id="lastPaidWrap">
              <label>Last Paid Month: </label>
              <input type="text" id="lastPaidMonth" class="form-control" readonly
                     value="<?= htmlspecialchars($last_paid_display) ?>"
                     placeholder="Select registration code">
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary">Apply Filters</button>
              <a href="viewtransactions.php?tab=transactions" class="btn btn-default">Reset</a>
            </div>
          </form>

          <?php if ($unpaid_error !== ''): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($unpaid_error) ?></div>
          <?php endif; ?>

          <?php if ($show_mode === 'unpaid'): ?>
            <p class="text-muted">
              Showing <strong>ongoing</strong> students with no successful fee for
              <strong><?= htmlspecialchars($month_filter !== '' && $year_filter !== '' ? fees_format_period($month_filter, $year_filter) : '—') ?></strong>.
            </p>
            <table id="example2" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Class</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Total Fees</th>
                  <th>Status</th>
                  <th>Fee Month</th>
                </tr>
              </thead>
              <tbody>
                <?php $sn = 1; foreach ($unpaid_rows as $row): ?>
                <tr>
                  <td><?= $sn++; ?></td>
                  <td><?= htmlspecialchars($row['name']); ?> (<?= htmlspecialchars($row['registration_code']); ?>)</td>
                  <td><?= !empty($row['class']) ? htmlspecialchars($row['class']) : 'N/A'; ?></td>
                  <td><?= htmlspecialchars((string) $row['mobile_number']); ?></td>
                  <td><?= htmlspecialchars((string) $row['email']); ?></td>
                  <td>₹<?= number_format((float) $row['total_fees'], 2); ?></td>
                  <td><span class="label label-success">Ongoing</span></td>
                  <td>
                    <span class="label label-danger">
                      Not paid — <?= htmlspecialchars($month_filter) ?><?= $year_filter !== '' ? ' ' . htmlspecialchars($year_filter) : '' ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <table id="example2" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>#</th><th>Student</th><th>Class</th><th>Phone</th><th>Email</th>
                  <th>Amount</th><th>For Which MOnth</th><th>Status</th><th>Date</th><th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $sn = 1; if ($result) { while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                  <td><?= $sn++; ?></td>
                  <td><?= htmlspecialchars($row['donor_name']); ?> (<?= $row['student_registration_code']; ?>)</td>
                  <td><?= !empty($row['class']) ? htmlspecialchars($row['class']) : 'N/A'; ?></td>
                  <td><?= $row['donor_phone']; ?></td>
                  <td><?= $row['donor_email']; ?></td>
                  <td>₹<?= number_format($row['amount'], 2); ?></td>
                  <td><?= htmlspecialchars((string) $row['payment_reason']); ?></td>
                  <td><span class="label label-<?= $row['status'] == 'success' ? 'success' : 'danger'; ?>"><?= ucfirst($row['status']); ?></span></td>
                  <td><?= date('d M Y', strtotime($row['donation_date'])); ?></td>
                  <td>
                    <a href="edit_transaction.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-xs">Edit</a>
                    <a href="delete_transaction.php?id=<?= $row['id']; ?>" onclick="return confirm('Delete?')" class="btn btn-danger btn-xs">Delete</a>
                  </td>
                </tr>
                <?php } } ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

        <!-- Add Fees Tab -->
       <!-- Add Fees Tab -->
<div class="tab-pane <?= $active_tab == 'addfees' ? 'active' : '' ?>" id="tab_addfees">
  <form action="insert_fees.php" method="POST" class="form-horizontal" style="margin-top:20px;">
    <input type="hidden" name="tab" value="addfees">

    <!-- Select Class -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Select Class</label>
      <div class="col-sm-6">
        <select name="selected_class" id="classSelect" class="form-control" required>
          <option value="">-- Select Class --</option>
          <?php foreach($classes as $class): ?>
            <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Select Registration Code (will load via AJAX) -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Registration Code</label>
      <div class="col-sm-6">
        <select name="student_registration_code" id="studentSelect" class="form-control" required>
          <option value="">-- Select Student --</option>
        </select>
      </div>
    </div>

    <!-- Show Student Info -->
    <div id="studentInfo" style="display:none; margin-bottom:15px;">
      <p><strong>Name:</strong> <span id="stuName"></span></p>
      <p><strong>Email:</strong> <span id="stuEmail"></span></p>
      <p><strong>Total Fees:</strong> ₹<span id="stuFee"></span></p>
      <p><strong>Last Paid Month:</strong> <span id="stuLastPaid">—</span></p>
    </div>

    <!-- Payment Date -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Payment Date</label>
      <div class="col-sm-6">
        <input type="date" name="donation_date" class="form-control" required>
      </div>
    </div>

    <!-- Payment For Month + Year -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Payment For Month</label>
      <div class="col-sm-3">
        <select name="fee_month" class="form-control" required>
          <?php foreach ($fee_months as $month): ?>
            <option value="<?= $month ?>"><?= $month ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-3">
        <select name="fee_year" class="form-control" required>
          <?php foreach ($year_options as $y): ?>
            <option value="<?= $y ?>" <?= $y === (int) date('Y') ? 'selected' : '' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Amount -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Amount</label>
      <div class="col-sm-6">
        <input type="number" step="0.01" name="amount" id="feeAmount" class="form-control" readonly required>
      </div>
    </div>

    <!-- Status -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Status</label>
      <div class="col-sm-6">
        <select name="status" class="form-control">
          <option value="success">Success</option>
          <option value="pending">Pending</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-6">
        <button type="submit" class="btn btn-success">Save Fee</button>
      </div>
    </div>
  </form>
</div>


      </div>
    </div>
  </section>
</div>



</div>
<script src="js/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="js/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button);
</script>
<!-- Bootstrap 3.3.7 -->
<script src="js/bootstrap.min.js"></script>
<!-- Morris.js charts -->
<script src="js/raphael.min.js"></script>
<script src="js/morris.min.js"></script>
<!-- Sparkline -->
<script src="js/jquery.sparkline.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<!-- jvectormap -->
<script src="js/jquery-jvectormap-1.2.2.min.js"></script>
<script src="js/jquery-jvectormap-world-mill-en.js"></script>
<!-- jQuery Knob Chart -->
<script src="js/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="js/moment.min.js"></script>
<script src="js/daterangepicker.js"></script>
<!-- datepicker -->
<script src="js/bootstrap-datepicker.min.js"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="js/bootstrap3-wysihtml5.all.min.js"></script>
<!-- Slimscroll -->
<script src="js/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="js/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="js/adminlte.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="js/pages/dashboard.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="js/demo.js"></script>
<script type="text/javascript">
        
        randomNum = '';
        
        randomNum += Math.round (Math.random() * 9999);
        
        window.onload = function () {
            document.getElementById("demo").value = randomNum;
        }
    </script>
	
  
<script>
  $(function () {
    $("#example1").DataTable();
  });
</script>
<script src="test/js/jquery.dataTables.min.js"></script>
	<script src="test/js/dataTables.bootstrap.min.js"></script>
	<script src="test/js/select2.full.min.js"></script>
	<script src="test/js/jquery.inputmask.js"></script>
	<script src="test/js/jquery.inputmask.date.extensions.js"></script>
	<script src="test/js/jquery.inputmask.extensions.js"></script>
	<script src="test/js/moment.min.js"></script>
	<script src="test/js/bootstrap-datepicker.js"></script>
	<script src="test/js/icheck.min.js"></script>
	<script src="test/js/fastclick.js"></script>
	<script src="test/js/jquery.sparkline.min.js"></script>
	<script src="test/js/jquery.slimscroll.min.js"></script>
	<script src="test/js/jquery.fancybox.pack.js"></script>
	<script src="test/js/app.min.js"></script>
	<script src="test/js/jscolor.js"></script>
	<script src="test/js/on-off-switch.js"></script>
    <script src="test/js/on-off-switch-onload.js"></script>
    <script src="test/js/clipboard.min.js"></script>
	
	<script src="test/js/summernote.js"></script>

	<script>
		$(document).ready(function() {
	        $('#editor1').summernote({
	        	height: 200
	        });
	        $('#editor2').summernote({
	        	height: 200
	        });
	        $('#editor3').summernote({
	        	height: 200
	        });
	        $('#editor4').summernote({
	        	height: 200
	        });
	        $('#editor5').summernote({
	        	height: 200
	        });
	    });
		
	</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
function updateLastPaidMonth(regCode) {
  var $wrap = $('#lastPaidWrap');
  var $input = $('#lastPaidMonth');
  if (!regCode) {
    $wrap.removeClass('visible');
    $input.val('');
    return;
  }
  $wrap.addClass('visible');
  $input.val('Loading...');
  $.getJSON('get_last_paid_month.php', { registration_code: regCode })
    .done(function (data) {
      $input.val((data && data.last_paid) ? data.last_paid : 'No payment found');
    })
    .fail(function () {
      $input.val('Unable to load');
    });
}

function togglePaidOnlyFilters() {
  var isUnpaid = $('#showMode').val() === 'unpaid';
  $('.paid-only-filter').toggle(!isUnpaid);
}

$(function(){
  $('#example2').DataTable();

  // Initialize Select2 for registration code dropdown
  $('.select2-registration').select2({
    placeholder: "Search registration code...",
    allowClear: true
  });

  togglePaidOnlyFilters();
  $('#showMode').on('change', togglePaidOnlyFilters);

  // Last paid month (fee month + year) after registration code selection
  $('#regFilter').on('change', function () {
    updateLastPaidMonth($(this).val());
  });
  if ($('#regFilter').val()) {
    $('#lastPaidWrap').addClass('visible');
  }

  // Activate the correct main tab based on URL parameter
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'addfees') {
    $('.nav-tabs a[href="#tab_addfees"]').tab('show');
  } else {
    $('.nav-tabs a[href="#tab_transactions"]').tab('show');
  }
});

function toggleSelectAll() {
  const checkboxes = document.querySelectorAll('.student-checkbox input[type="checkbox"]');
  const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
  
  checkboxes.forEach(checkbox => {
    checkbox.checked = !allChecked;
  });
}

function updateAmount(select) {
  const selectedOption = select.options[select.selectedIndex];
  const totalFee = selectedOption.getAttribute('data-fee') || 0;
  document.getElementById('totalFee').textContent = parseFloat(totalFee).toFixed(2);
  document.getElementById('amountInput').value = totalFee;
  document.getElementById('amountInput').readOnly = true; // Make it read-only
}

function updateAmount2(select) {
  const selectedOption = select.options[select.selectedIndex];
  const totalFee = selectedOption.getAttribute('data-fee') || 0;
  document.getElementById('totalFee2').textContent = parseFloat(totalFee).toFixed(2);
  document.getElementById('amountInput2').value = totalFee;
  document.getElementById('amountInput2').readOnly = true; // Make it read-only
}

// Initialize amounts on page load
document.addEventListener('DOMContentLoaded', function() {
  const studentSelect = document.getElementById('studentSelect');
  const studentSelect2 = document.getElementById('studentSelect2');
  
  if (studentSelect && studentSelect.value) {
    updateAmount(studentSelect);
  }
  if (studentSelect2 && studentSelect2.value) {
    updateAmount2(studentSelect2);
  }
});
</script>
<script>
$(document).ready(function(){
  // When class changes
  $('#classSelect').on('change', function(){
    var selectedClass = $(this).val();
    $('#studentSelect').html('<option value="">Loading...</option>');

    if(selectedClass){
      $.getJSON('get_students.php', {class: selectedClass}, function(data){
        var options = '<option value="">-- Select Student --</option>';
        $.each(data, function(i, student){
          options += '<option value="'+student.registration_code+'" data-email="'+student.email+'" data-fee="'+student.total_fees+'" data-name="'+student.name+'">'+student.registration_code+' - '+student.name+'</option>';
        });
        $('#studentSelect').html(options);
      });
    } else {
      $('#studentSelect').html('<option value="">-- Select Student --</option>');
      $('#studentInfo').hide();
    }
  });

  // When student changes, show info + fees + last paid month
  $('#studentSelect').on('change', function(){
    var name = $('option:selected', this).data('name');
    var email = $('option:selected', this).data('email');
    var fee = $('option:selected', this).data('fee');
    var regCode = $(this).val();

    if(name){
      $('#stuName').text(name);
      $('#stuEmail').text(email);
      $('#stuFee').text(parseFloat(fee).toFixed(2));
      $('#feeAmount').val(fee);
      $('#stuLastPaid').text('Loading...');
      $('#studentInfo').show();
      $.getJSON('get_last_paid_month.php', { registration_code: regCode })
        .done(function (data) {
          $('#stuLastPaid').text((data && data.last_paid) ? data.last_paid : 'No payment found');
        })
        .fail(function () {
          $('#stuLastPaid').text('Unable to load');
        });
    } else {
      $('#studentInfo').hide();
      $('#stuLastPaid').text('—');
    }
  });
});
</script>

</body>
</html>