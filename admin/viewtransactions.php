<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

// --- DATE FILTER LOGIC ---
$where = "";
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from_date = mysqli_real_escape_string($con, $_GET['from_date']);
    $to_date = mysqli_real_escape_string($con, $_GET['to_date']);
    $where = "WHERE donation_date BETWEEN '$from_date' AND '$to_date'";
}

// --- CLASS FILTER LOGIC ---
$class_filter = "";
if (!empty($_GET['class_filter'])) {
    $class_filter_value = mysqli_real_escape_string($con, $_GET['class_filter']);
    if ($where === "") {
        $class_filter = "WHERE s.class LIKE '%$class_filter_value%'";
    } else {
        $class_filter = "AND s.class LIKE '%$class_filter_value%'";
    }
}

// --- REGISTRATION CODE FILTER LOGIC ---
$reg_filter = "";
if (!empty($_GET['reg_filter'])) {
    $reg_filter_value = mysqli_real_escape_string($con, $_GET['reg_filter']);
    if ($where === "" && $class_filter === "") {
        $reg_filter = "WHERE d.student_registration_code = '$reg_filter_value'";
    } else {
        $reg_filter = "AND d.student_registration_code = '$reg_filter_value'";
    }
}

// Build the query with joins to get student class information
$sql = "SELECT d.*, s.class 
        FROM donations d 
        LEFT JOIN students s ON d.student_registration_code = s.registration_code 
        $where $class_filter $reg_filter 
        ORDER BY d.id DESC";
$result = mysqli_query($con, $sql);

// --- FETCH STUDENTS FOR DROPDOWN ---
$students = mysqli_query($con, "SELECT id, name, registration_code, total_fees FROM students ORDER BY name ASC");

// --- FETCH UNIQUE CLASSES FOR CLASS-WISE SELECTION ---
$classes_result = mysqli_query($con, "SELECT class FROM students WHERE class IS NOT NULL AND class != ''");
$classes = [];

while ($row = mysqli_fetch_assoc($classes_result)) {
    // explode comma-separated classes
    $classArray = array_map('trim', explode(',', $row['class']));
    // merge into main list
    $classes = array_merge($classes, $classArray);
}

// remove duplicates and sort
$classes = array_unique($classes);
sort($classes);

// --- FETCH REGISTRATION CODES FOR DROPDOWN ---
$reg_codes_result = mysqli_query($con, "SELECT registration_code, name FROM students WHERE registration_code IS NOT NULL AND registration_code != '' ORDER BY registration_code");
$reg_codes = [];
while ($row = mysqli_fetch_assoc($reg_codes_result)) {
    $reg_codes[$row['registration_code']] = $row['registration_code'] . ' - ' . $row['name'];
}

// --- GET STUDENTS BY CLASS ---
$class_students = [];
if (isset($_GET['selected_class']) && !empty($_GET['selected_class'])) {
    $selected_class = mysqli_real_escape_string($con, $_GET['selected_class']);
    $class_students_query = mysqli_query($con, "SELECT registration_code, name, email, total_fees FROM students WHERE class LIKE '%$selected_class%' ORDER BY name");
    while($student = mysqli_fetch_assoc($class_students_query)) {
        $class_students[] = $student;
    }
}

// --- SEARCH STUDENT BY NAME OR REGISTRATION CODE ---
$search_results = [];
if (isset($_GET['search_student']) && !empty($_GET['search_term'])) {
    $search_term = mysqli_real_escape_string($con, $_GET['search_term']);
    $search_query = mysqli_query($con, "SELECT registration_code, name, email, total_fees FROM students WHERE name LIKE '%$search_term%' OR registration_code LIKE '%$search_term%' ORDER BY name");
    while($student = mysqli_fetch_assoc($search_query)) {
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
    .select2-container--default .select2-selection--single {
      height: 34px;
      padding: 3px;
    }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

<?php include 'header.php'; ?>
<aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

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
              <label>From: </label>
              <input type="date" name="from_date" class="form-control" value="<?= @$_GET['from_date'] ?>">
            </div>
            
            <div class="form-group">
              <label>To: </label>
              <input type="date" name="to_date" class="form-control" value="<?= @$_GET['to_date'] ?>">
            </div>
            
            <div class="form-group">
              <label>Class: </label>
              <select name="class_filter" class="form-control">
                <option value="">All Classes</option>
                <?php foreach($classes as $class): ?>
                  <option value="<?= htmlspecialchars($class) ?>" <?= (isset($_GET['class_filter']) && $_GET['class_filter'] == $class) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($class) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>Registration Code: </label>
              <select name="reg_filter" class="form-control select2-registration" style="width: 250px;">
                <option value="">All Students</option>
                <?php foreach($reg_codes as $code => $display): ?>
                  <option value="<?= htmlspecialchars($code) ?>" <?= (isset($_GET['reg_filter']) && $_GET['reg_filter'] == $code) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($display) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <button type="submit" class="btn btn-primary">Apply Filters</button>
              <a href="viewtransactions.php?tab=transactions" class="btn btn-default">Reset</a>
            </div>
          </form>
          
          <table id="example2" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>#</th><th>Student</th><th>Class</th><th>Phone</th><th>Email</th>
                <th>Amount</th><th>For Which MOnth</th><th>Status</th><th>Date</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php $sn=1; while($row=mysqli_fetch_assoc($result)){ ?>
              <tr>
                <td><?= $sn++; ?></td>
                <td><?= htmlspecialchars($row['donor_name']); ?> (<?= $row['student_registration_code']; ?>)</td>
                <td><?= !empty($row['class']) ? htmlspecialchars($row['class']) : 'N/A'; ?></td>
                <td><?= $row['donor_phone']; ?></td>
                <td><?= $row['donor_email']; ?></td>
                <td>₹<?= number_format($row['amount'],2); ?></td>
                <td><?= $row['payment_reason']; ?></td>
                <td><span class="label label-<?= $row['status']=='success'?'success':'danger'; ?>"><?= ucfirst($row['status']); ?></span></td>
                <td><?= date('d M Y',strtotime($row['donation_date'])); ?></td>
                <td>
                  <a href="edit_transaction.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-xs">Edit</a>
                  <a href="delete_transaction.php?id=<?= $row['id']; ?>" onclick="return confirm('Delete?')" class="btn btn-danger btn-xs">Delete</a>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
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
    </div>

    <!-- Payment Date -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Payment Date</label>
      <div class="col-sm-6">
        <input type="date" name="donation_date" class="form-control" required>
      </div>
    </div>

    <!-- Payment For Month -->
    <div class="form-group">
      <label class="col-sm-2 control-label">Payment For Month</label>
      <div class="col-sm-6">
        <select name="payment_reason" class="form-control" required>
          <?php foreach ([
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
          ] as $month): ?>
            <option value="<?= $month ?>"><?= $month ?></option>
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
$(function(){
  $('#example2').DataTable();
  
  // Initialize Select2 for registration code dropdown
  $('.select2-registration').select2({
    placeholder: "Search registration code...",
    allowClear: true
  });
  
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

  // When student changes, show info + fees
  $('#studentSelect').on('change', function(){
    var name = $('option:selected', this).data('name');
    var email = $('option:selected', this).data('email');
    var fee = $('option:selected', this).data('fee');

    if(name){
      $('#stuName').text(name);
      $('#stuEmail').text(email);
      $('#stuFee').text(parseFloat(fee).toFixed(2));
      $('#feeAmount').val(fee);
      $('#studentInfo').show();
    } else {
      $('#studentInfo').hide();
    }
  });
});
</script>

</body>
</html>