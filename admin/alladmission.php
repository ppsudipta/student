<?php 
session_start();
if($_SESSION['username'] == true){
  
}else{
  header('location:index.php');
}
?>
<?php include('config.php'); ?>
<?php
if(isset($_GET['id']))
{
$id=$_GET['id'];

	if($con->error)
	echo $con->error;
	else
	{
		$sql="DELETE from admission where id=$id";
		$con->query($sql);
		echo "<script>alert('deleted')</script>";	
		
	}
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
        All
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">All</li>
      </ol>

    </section>
    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">All</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
          
        <div class="box-body table-responsive">

              <table id="example2" class="table table-bordered table-hover">
                <thead>
                   
                <tr>
					
				<th>Candidate Image</th>
				
               
				
                 <th>Name of the candidate</th>
                 <th>Student Code</th>
				 <th>Father's Name</th>
				 <th>Caste</th>
				 <th>Date of birth</th>
                 <th>Sex</th>
                 
                <th>Qualification</th>
				<th>Income</th>
				
				<th>Address</th>				
				<th>Phone Number</th>
				<th>Class</th>
				<th>Teacher</th>
				<th>Date</th>
				<th>Time</th>
				<th>Course</th>
				<th>Branch</th>
				<th>Course Fee</th>
				<th>Duration</th>
				<th>Date Of Joining</th>
				
				
				
				
				<!--<th>Register</th>-->
				<th>Edit</th>
				<th>Delete</th>
				<!--<th>Print</th>-->
                 
                </tr>
                </thead>
 
<?php
// connect to database


// define how many results you want per page
$results_per_page = 10;

// find out the number of results stored in database
$sql='SELECT * FROM admission order by id desc';
$result = mysqli_query($con, $sql);
$number_of_results = mysqli_num_rows($result);

// determine number of total pages available
$number_of_pages = ceil($number_of_results/$results_per_page);

// determine which page number visitor is currently on
if (!isset($_GET['page'])) {
  $page = 1;
} else {
  $page = $_GET['page'];
}

// determine the sql LIMIT starting number for the results on the displaying page
$this_page_first_result = ($page-1)*$results_per_page;

// retrieve selected results from database and display them on page
$sql='SELECT * FROM admission order by id desc LIMIT ' . $this_page_first_result . ',' .  $results_per_page;
$result = mysqli_query($con, $sql);

while($row = mysqli_fetch_array($result)) {
	?> 
                <tbody>
                <tr>
          
          
		  <td><img style="height:70px; width:100px" src="<?php echo $row['image']; ?>"></td>
          <td><?php echo $row['student_name'] ?></td>
		  
          <td><?php echo $row['code_no'] ?></td>
          
          <td><?php echo $row['father_name'] ?></td>
          <td><?php echo $row['caste'] ?></td>
		  
          <td><?php echo $row['dob'] ?></td>
		  <td><?php echo $row['sex'] ?></td>
          <td><?php echo $row['qualification'] ?></td>
				 <td><?php echo $row['income'] ?></td>
				 <td><?php echo $row['address'] ?></td>
				 <td><?php echo $row['phone'] ?></td>
				 <td><?php echo $row['class'] ?></td>
				 <td><?php echo $row['teacher'] ?></td>
				 
				 
				 
				 <td><?php echo $row['date'] ?></td>
				 <td><?php echo $row['time'] ?></td>
				 <td><?php echo $row['course'] ?></td>
				 <td><?php echo $row['branch'] ?></td>
				 <td><?php echo $row['course_fee'] ?></td>
				
				<td><?php echo $row['duration'] ?></td>
				<td><?php echo $row['joining_date'] ?></td>
				
				 
				 
				 <!--<td><a href="adm.php?id=<?php echo $row['id']?>"><button class="btn btn-warning">
          <i class="fa fa-calendar"></i></button></a></td>-->
				 
          <td><a href="edit_addm.php?id=<?php echo $row['id']?>"><button class="btn btn-warning">
          <i class="fa fa-pencil"></i></button></a></td>
                  
                 
            <td><a onclick="return confirm('Are you sure to Delete?');" href="alladmission.php?id=<?php echo $row['id']?>">
           <button class="btn btn-danger"><i class="fa fa-trash"></i></button></a></td>
		   <!-- <td><a href="admprint.php?id=<?php echo $row['id']?>"><button class="btn btn-warning">
          <i class="fa fa-print"></i></button></a></td>-->
                  
                </tr>
                
                </tbody>
         <?php
  }
  

?>     
						    
	 </table>
			  <div class="row">
							<div class="col-md-12 col-sm-12 col-xs-12">
								
								<div class="pagi_ul">
									<ul id="pagination">
										<?php
	for ($page=1;$page<=$number_of_pages;$page++) {
  echo '<a href="alladmission.php?page=' . $page . '">' . $page . '</a> ';
}
	
	
	 ?>
										
								
									</ul>
								</div>
								
							</div>
						</div> 
						


<!--<script>
function myFunction() {
  window.print();
}
</script>-->
                 </div>   
         <!-- <button class="btn" onclick="myFunction()" style="border:2px solid red;">Print this page</button>-->
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
</div>
</div>
</section>

    
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    
    <strong>Copyright &copy; 2019 <a href="http://thememart.in">Thememart Solutions</a>.</strong> All rights
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
