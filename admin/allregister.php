<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

if (isset($_GET['id'])) {
    $id2 = $_GET['id'];
    echo "<script>alert('Deleted');</script>"; 
    $sql5 = "DELETE FROM students WHERE id='$id2'";
    $con->query($sql5);
    echo "<script>window.location.href='allregister.php'</script>";
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'ongoing';
$class_filter = isset($_GET['class_filter']) ? $_GET['class_filter'] : '';
$registration_filter = isset($_GET['registration_filter']) ? $_GET['registration_filter'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build SQL query with filters
$sql = "SELECT * FROM `students` WHERE 1=1";

if ($status_filter != 'all') {
    $sql .= " AND status = '$status_filter'";
}

// Class filter (works with comma separated values)
if (!empty($class_filter)) {
    $sql .= " AND FIND_IN_SET('$class_filter', class)";
}

// Registration code filter
if (!empty($registration_filter)) {
    $sql .= " AND registration_code LIKE '%$registration_filter%'";
}

// General search filter
if (!empty($search_term)) {
    $sql .= " AND (name LIKE '%$search_term%' OR 
                  father_name LIKE '%$search_term%' OR 
                  mobile_number LIKE '%$search_term%' OR 
                  email LIKE '%$search_term%' OR 
                  address LIKE '%$search_term%')";
}

$sql .= " ORDER BY registration_code";
$result = mysqli_query($con, $sql);

// Get unique classes for filter dropdown
$class_query = "SELECT class FROM students WHERE class IS NOT NULL AND class != ''";
$class_result = mysqli_query($con, $class_query);
$classes = [];
while ($row = mysqli_fetch_assoc($class_result)) {
    $split = array_map('trim', explode(',', $row['class']));
    foreach ($split as $c) {
        if (!in_array($c, $classes) && !empty($c)) {
            $classes[] = $c;
        }
    }
}
sort($classes);

// Get unique registration codes
$registration_query = "SELECT DISTINCT registration_code FROM students WHERE registration_code IS NOT NULL AND registration_code != '' ORDER BY registration_code";
$registration_result = mysqli_query($con, $registration_query);
$registration_codes = [];
while ($row = mysqli_fetch_assoc($registration_result)) {
    if (!empty($row['registration_code'])) {
        $registration_codes[] = $row['registration_code'];
    }
}
sort($registration_codes);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Student Registrations</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">

  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
  <style>
    @media print {
      body * { visibility: hidden; }
      .print-section, .print-section * { visibility: visible; }
      .print-section { position: absolute; left: 0; top: 0; width: 100%; }
      .no-print { display: none !important; }
      .box-header { display: block !important; }
      table { width: 100% !important; font-size: 10px; table-layout: fixed; }
      th, td { padding: 3px !important; border: 1px solid #ddd !important; word-wrap: break-word; }
      .print-header { display: block !important; text-align: center; margin-bottom: 15px; }
      .print-header h2 { margin: 5px 0; font-size: 18px; }
      .print-header p { margin: 3px 0; font-size: 12px; }
    }
    #search { width: 300px; border: 1px solid green; padding: 6px 8px; border-radius: 5px; margin-bottom: 10px; }
    .nav-tabs-custom { margin-bottom: 20px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,0.1); border-radius: 3px; }
    .filter-section { padding: 10px; background: #f9f9f9; border-bottom: 1px solid #eee; }
    .action-buttons { margin-bottom: 15px; }
    .filter-row { margin-bottom: 10px; }
    .print-header { display: none; }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>All Student Registrations</h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">All Registration</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Student Records</h3>
          <div class="box-tools pull-right no-print">
            <div class="btn-group">
              <a style="background:#9cd89c;" type="button" class="btn btn-default" href="print.php"><i class="fa fa-print"></i>Go To Print , Export Page</a>
              <a style="background:#bebeff;" type="button" class="btn btn-default" href="roll.php"><i class="fa fa-user"></i> See Available Roll Numbers</a>
              
            </div>
          </div>
        </div>
        <div class="box-body">

          <div class="nav-tabs-custom no-print">
            <ul class="nav nav-tabs">
              <li class="<?php echo $status_filter == 'ongoing' ? 'active' : ''; ?>">
                <a href="?status=ongoing&class_filter=<?php echo $class_filter; ?>&registration_filter=<?php echo $registration_filter; ?>&search=<?php echo $search_term; ?>">Ongoing</a>
              </li>
              <li class="<?php echo $status_filter == 'suspended' ? 'active' : ''; ?>">
                <a href="?status=suspended&class_filter=<?php echo $class_filter; ?>&registration_filter=<?php echo $registration_filter; ?>&search=<?php echo $search_term; ?>">Suspended</a>
              </li>
            </ul>

            <div class="filter-section no-print">
              <form method="get" class="form">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <div class="row filter-row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="class_filter">Filter by Class:</label>
                      <select name="class_filter" id="class_filter" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                          <option value="<?php echo $class; ?>" <?php echo ($class_filter == $class) ? 'selected' : ''; ?>><?php echo $class; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="registration_filter">Filter by Roll Number:</label>
                      <select name="registration_filter" id="registration_filter" class="form-control">
                        <option value="">All Codes</option>
                        <?php foreach ($registration_codes as $code): ?>
                          <option value="<?php echo $code; ?>" <?php echo ($registration_filter == $code) ? 'selected' : ''; ?>><?php echo $code; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="search">Search:</label>
                      <div class="input-group">
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, father, mobile, email..." value="<?php echo $search_term; ?>">
                        <span class="input-group-btn"><button class="btn btn-info" type="submit"><i class="fa fa-search"></i></button></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row"><div class="col-md-12">
                  <button type="submit" class="btn btn-primary">Apply Filters</button>
                  <a href="allregister.php?status=<?php echo $status_filter; ?>" class="btn btn-default">Reset Filters</a>
                </div></div>
              </form>
            </div>

            <div class="tab-content">
              <div class="tab-pane active">
                <div class="print-section">
                  <div class="print-header">
                    <h2>Sunrise Academy - Student Registrations</h2>
                    <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p>Status: <?php echo ucfirst($status_filter); ?></p>
                    <?php if (!empty($class_filter)): ?><p>Class: <?php echo $class_filter; ?></p><?php endif; ?>
                    <?php if (!empty($registration_filter)): ?><p>Roll Number: <?php echo $registration_filter; ?></p><?php endif; ?>
                    <?php if (!empty($search_term)): ?><p>Search Term: <?php echo $search_term; ?></p><?php endif; ?>
                    <hr>
                  </div>

                  <div class="table-responsive">
                    <table id="studentTable" class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th class="no-print">Image</th>
                           <th width="8%">Roll Number</th>
                          <th width="10%">Name</th>
                          <th width="10%">Father's Name</th>
                          <th width="10%">School Name</th>
                          <th width="8%">Class</th>
                          <th width="12%">Address</th>
                          <th width="8%">Mobile</th>
                          <th width="10%">Email</th>
                          <th width="6%">Total Fees</th>
                         
                          <th width="6%">Session</th>
                          <th width="6%">Status</th>
                          <th width="8%">Reg. Date</th>
                         
                          <th class="no-print">ID Card</th>
                          <th class="no-print">Edit</th>
                          <th class="no-print">Delete</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0) {
                          while ($row = mysqli_fetch_array($result)) { ?>
                          <tr>
                            <td class="no-print">
                             <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
    <img style="height:70px; width:100px" src="<?php echo $row['image']; ?>">
<?php else: ?>
    <img style="height:70px; width:74px" src="images/user.jpg">
<?php endif; ?>

                            </td>
                               <td><?php echo $row['registration_code']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['father_name']; ?></td>
                            <td><?php echo $row['school_name']; ?></td>
                            <td><?php echo $row['class']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td><?php echo $row['mobile_number']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['total_fees']; ?></td>
                         
                            <td><?php echo $row['session']; ?></td>
                            <td><span class="label label-<?php echo ($row['status'] == 'ongoing') ? 'success' : 'warning'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo $row['date']; ?></td>
                           <td class="no-print"><a href="view_idcard.php?id=<?= $row['id']; ?>" target="_blank"><button class="btn btn-info"><i class="fa fa-id-card"></i></button></a></td>
                            <td class="no-print"><a href="editregister.php?id=<?php echo $row['id']; ?>"><button class="btn btn-warning"><i class="fa fa-pencil"></i></button></a></td>
                            <td class="no-print"><a onclick="return confirm('Are you sure to Delete?');" href="deletestudent.php?id=<?php echo $row['id']; ?>"><button class="btn btn-danger"><i class="fa fa-trash"></i></button></a></td>
                          </tr>
                        <?php } } else {
                          echo '<tr><td colspan="16" class="text-center">No records found</td></tr>';
                        } ?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- /.print-section -->
              </div>
            </div>

          </div><!-- /.nav-tabs-custom -->

        </div>
      </div>
    </section>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
  function printPage() {
    document.querySelector('.print-header').style.display = 'block';
    window.print();
    setTimeout(function() {
      document.querySelector('.print-header').style.display = 'none';
    }, 500);
  }
  function downloadPDF() { alert("PDF export would be implemented here"); }
  function exportToExcel() { alert("Excel export would be implemented here"); }
  window.onafterprint = function() {
    document.querySelector('.print-header').style.display = 'none';
  };
</script>
</body>
</html>
