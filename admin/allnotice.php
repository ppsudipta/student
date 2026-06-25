<?php 
session_start();
if($_SESSION['username'] == true){
	
}else{
  header('location:index.php');
}
?>
<?php include('config.php'); ?>
<?php
if(isset($_GET['del_id']))
{
	$del=$_GET['del_id'];
	$sql2="delete from notices where random_id='$del'";
	$con->query($sql2);
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Admin</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
  
  <!-- Font Awesome -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
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

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <script type="text/javascript">
function delete_id(id)
{
     if(confirm('Sure To Remove This Record ?'))
     {
        window.location.href='customerdelete.php?delete_id='+id;
     }
}
</script>

</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <!-- Left side column. contains the logo and sidebar -->
  <?php include 'header.php';?>
  
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
          <img src="upload/924Koala.jpg" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p>Admin</p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      
      <?php include 'sidebar.php'; ?>
  </section>
  </aside>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        All Gallery
        <small>Control panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">All Gallery</li>
      </ol>

    </section>
    <!-- Main content -->
    <section class="content">

            <div class="container-fluid">
                <div class="row m-1">
                    <div class="col-12">
                        <h5>All Notices</h5>
                       
                        <div class="row m-1 mb-3">
                            <div class="col-12">
                                <a href="addnotice.php" class="btn btn-primary">Add New Notice</a>
                            </div>
                            <?php
                            if (isset($_GET['status'])) {
                                echo $_GET['status'] == 'success' 
                                    ? '<div class="alert alert-success mt-2">Notice added successfully!</div>'
                                    : '<div class="alert alert-danger mt-2">Error. Please try again.</div>';
                            }
                            ?>
                        </div>
                        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search..." onkeyup="searchTable()" style="max-width: 300px;">
                    </div>
                </div>

                <div class="row table-section">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Notice Table</h5>
                                <p>All registered notices are listed below.</p>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Content</th>
                                                <th>Assign Student</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="noticeTable">
                                         <?php
// Step 1: Count total notices per type
$type_count_sql = "SELECT notice_type, COUNT(*) as type_total FROM notices GROUP BY notice_type";
$type_count_res = $con->query($type_count_sql);
$notice_type_count = [];

if ($type_count_res && $type_count_res->num_rows > 0) {
    while ($row = $type_count_res->fetch_assoc()) {
        $notice_type_count[$row['notice_type']] = $row['type_total'];
    }
}

// Step 2: Group by notice content to show one row per unique content
$content_sql = "
    SELECT 
        notice_content, 
        notice_type, 
        MIN(created_at) as created_at, 
        COUNT(DISTINCT student_id) as student_count,
        MIN(random_id) as random_id
    FROM notices 
    GROUP BY notice_content, notice_type 
    ORDER BY created_at DESC
";
$content_res = $con->query($content_sql);

if ($content_res && $content_res->num_rows > 0) {
    while ($row = $content_res->fetch_assoc()) {
        $type = $row['notice_type'];
        $content = trim($row['notice_content']);
        $student_count = $row['student_count'];
        $date = $row['created_at'];
        $random_id = $row['random_id'];

        echo "<tr class='notice-row'>";
        echo "<td><strong>" . htmlspecialchars($type) . "</strong>";
        if (isset($notice_type_count[$type])) {
            echo " ({$notice_type_count[$type]} total)";
        }
        echo "</td>";

        // Content column
        echo "<td>";
        if ($type === 'image') {
            echo "<img src='{$content}' alt='Notice Image' style='max-width:100px;'>";
        } elseif ($type === 'video') {
            echo "<video src='{$content}' controls style='max-width:150px;'></video>";
        } else {
            // Truncate long text content
            $truncated_content = strlen($content) > 100 ? substr($content, 0, 100) . "..." : $content;
            echo htmlspecialchars($truncated_content);
        }
        echo "</td>";

        // Student count
        echo "<td>{$student_count} Students</td>";

        // Date
        echo "<td>{$date}</td>";

        // Action buttons
        echo "<td>";
        echo "<div class='btn-group'>";
        echo "<a href='edit_notice.php?random_id={$random_id}' class='btn btn-sm btn-primary'>Edit</a>";
        echo "<a href='allnotice.php?del_id={$random_id}' class='btn btn-sm btn-danger'>Delete</a>";
        echo "</div>";
        echo "</td>";

        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No notices found</td></tr>";
}
?>



                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      
</section>

    <script>
function searchTable() {
    let input = document.getElementById("searchInput").value.toUpperCase();
    let rows = document.querySelectorAll(".notice-row");
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.includes(input) ? "" : "none";
    });
}

function deleteNotice(random_id) {
    if (confirm("Are you sure you want to delete this notice? This will remove all instances sent to students.")) {
        // Redirect to delete page with random_id
        window.location.href = 'delete_notice.php?random_id=' + random_id;
    }
}
</script>
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version</b> 2.4.0
    </div>
    <strong>Copyright &copy; 2025 Sunrise Academy.</strong> All rights
    reserved.
  </footer>

    <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->
<!-- jQuery 3 -->
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
<script src="js/dataTables.bootstrap.min.js"></script>
<!-- Sparkline -->
<script src="js/jquery.sparkline.min.js"></script>
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
<script src="js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
        
        randomNum = '';
        
        randomNum += Math.round (Math.random() * 9999);
        
        window.onload = function () {
            document.getElementById("demo").value = randomNum;
        }
    </script>
	
  
<script>
  $(function () {
    $('#example1').DataTable()
    $('#example2').DataTable({
      'paging'      : true,
      'lengthChange': false,
      'searching'   : false,
      'ordering'    : true,
      'info'        : true,
      'autoWidth'   : false
    })
  })
</script>
<script>
$("#search").keyup(function() {
    var value = this.value;

    $("table").find("tr").each(function(index) {
        if (index === 0) return;

        var if_td_has = false; //boolean value to track if td had the entered key
        $(this).find('td').each(function () {
            if_td_has = if_td_has || $(this).text().indexOf(value) !== -1; //Check if td's text matches key and then use OR to check it for all td's
        });

        $(this).toggle(if_td_has);

    });
});
</script>

</body>
</html>