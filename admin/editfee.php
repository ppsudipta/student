
<?php 
session_start();
if($_SESSION['username'] == true){
}else{
  header('location:index.php');
}
?>
<?php include('config.php'); ?>

 
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>AK INFO</title>
  <!-- Tell the browser to be responsive to screen width -->
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


  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php';?>
  <!-- DataTables -->
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  
  <aside class="main-sidebar">
    <section class="sidebar">
      
      <?php include 'sidebar.php'; ?>
  </section>  
	</aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Edit Fee
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        
        <li class="active">Edit Fee</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Edit Fee</h3>
            </div>
			<?php

    $id=$_GET['id'];

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from fees_table where id=$id";
	$res=$con->query($sql);
	$row=$res->fetch_array();
	?>

            <!-- /.box-header -->
            <div class="box-body">
       <form  role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
              <div class="box-body">
			  <input type="hidden" name="id" value="<?php echo $row['id'];?>">
			   <div class="form-group">
                  <label for="productid">Student name</label>
                  <input class="form-control" name="sname" readonly value="<?php echo $row['student_name']; ?>"   placeholder="Enter Student Name" type="text">
                </div>
			  <div class="form-group">
                  <label for="productid">Course</label>
                  <input class="form-control" name="course" readonly value="<?php echo $row['course']; ?>"   placeholder="Enter Course Name" type="text">
                </div>
              
				
				<div class="form-group">
                  <label for="productid">Fee</label>
                  <input class="form-control" name="fee" value="<?php echo $row['fees']; ?>" required  placeholder="Enter Fee" type="text">
              </div>
				<div class="form-group">
                  <label for="productid">Date</label>
                  <input class="form-control" name="date" value="<?php echo $row['fees_date']; ?>" required  placeholder="Enter Date" type="text">
              </div>
			 <div class="form-group">
                  <label for="productid">Select Month</label>
                  
					<select class="form-control" name="month">
					<option value="<?php echo $row['month']; ?>"><?php echo $row['month'];?></option>
					<option value="January">January</option>
					<option value="February">February</option>
					<option value="March">March</option>
					<option value="April">April</option>
					<option value="May">May</option>
					<option value="June">June</option>
					<option value="July">July</option>
					<option value="August">August</option>
					<option value="September">September</option>
					<option value="October">October</option>
					<option value="November">November</option>
					<option value="December">December</option>
					</select>
			  </div>
			<div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Change</button>
              </div>
            </form> 
			</div>
            <!-- /.box-body -->
          </div>
	<?php } ?>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
	  
      <!-- /.row -->
    </section>
    <!-- /.content -->
	
  </div>
 
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version</b> 1.0
    </div>
    <strong>Copyright &copy; 2019</strong> All rights
    reserved. Made with heart by <a href="http://thememart.in"> Theme mart Solution
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
<script src="bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="bower_components/fastclick/lib/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="dist/js/pages/dashboard.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="dist/js/demo.js"></script>

</body>
</html>

    <?php

if(isset($_POST['submit']))
{
	$id2=$_POST['id'];
	/*$sname=$_POST['sname'];
	$fname=$_POST['fname'];*/
	$fee=$_POST['fee'];
	$date=$_POST['date'];
	$month=$_POST['month'];
	
	
	if($con->error)
	echo $con->error;
	else
	{
	
	$sql="UPDATE fees_table SET fees='$fee',fees_date='$date',month='$month' where id='$id2'";
	$con->query($sql); 
	echo "<script>alert('Update successfull')</script>";	
	echo "<script>window.location.href='addfees.php'</script>";	
	}
}

?>
