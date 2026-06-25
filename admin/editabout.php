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
  <title>Admin</title>
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
        Edit About
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="alltour.php"><i class="fa fa-dashboard"></i> Home</a></li>
        
        <li class="active">Edit About</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Edit About</h3>
            </div>
			<?php

    $id=$_GET['id'];

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from blog where id=$id";
	$res=$con->query($sql);
	$row=$res->fetch_array();
	$img= $row['image'];
	$det=$row['details'];
	?>

            <!-- /.box-header -->
            <div class="box-body">
       <form  role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
              <div class="box-body">
			   
				<div class="form-group">
                  <label for="productid">About Details</label>
                 
                  <textarea name="details" class="form-control" cols="50" rows="10" id="editor1"><?php echo $row['details']; ?></textarea>
                </div>
				
				<!--<div class="form-group">
                  <label for="productid">Category Logo</label>
                  <input class="form-control" name="category"  required  placeholder="Enter Category" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Package Duration</label>
                  <input class="form-control" name="duration"  required  placeholder="Enter Package Duration" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Package Cost</label>
                  <input class="form-control" name="cost"   placeholder="Enter Package Cost" type="text">
                </div>-->
				
				<div class="form-group">
                  <label for="name"> Image</label>
                  <input class="form-control" value="<?php echo $row['image']; ?>" name="image1" type="file"  accept="image/x-png">
                </div>
				
				
			<div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Update About</button>
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
    <strong>Copyright &copy; 2018</strong> All rights
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
<script src="js/adminlte.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="js/dashboard.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="js/demo.js"></script>
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
</body>
</html>

    <?php

if(isset($_POST['submit']))
{
    $course_name=$_POST['c_name'];
	$details1=$_POST['details'];
	if($details1!='')
	{
	    $details=$details1;
	}
	else
	{
	   $details=$det; 
	}
	$category=$_POST['category'];
	$image1=$_FILES['image1']['name'];
	if($image1!='')
	{
	$path1='category/'.$image1;
	move_uploaded_file($_FILES['image1']['tmp_name'],$path1);
	}
	else
	{
		$path1=$img;
	}
	$date=date('d-m-Y');
	
	if($con->error)
	echo $con->error;
	else
	{
	
	$sql= "UPDATE blog SET name='$course_name', image='$path1', details='$details', category='$category' where id='$id'";
     $con->query($sql); 
	echo "<script>alert('Edit successfull')</script>";	
	echo "<script>window.location.href='allabout.php'</script>";	
	}
}

?>
