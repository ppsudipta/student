
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
        Edit Package
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="alltour.php"><i class="fa fa-dashboard"></i> Home</a></li>
        
        <li class="active">Edit Package</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Edit Package</h3>
            </div>
			<?php

    $id=$_GET['id'];

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from packages where id=$id";
	$res=$con->query($sql);
	$row=$res->fetch_array();
	?>

            <!-- /.box-header -->
            <div class="box-body">
       <form  role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
              <div class="box-body">
			   <div class="form-group">
                  <label for="productid">Package Name</label>
                  <input class="form-control" name="package_name" value="<?php echo $row['name'];?>"  required  placeholder="Enter Package Name" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Package Description</label>
                  <input class="form-control" name="description" value="<?php echo $row['description'];?>" required  placeholder="Enter Package Description" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Package Duration</label>
                  <input class="form-control" name="duration" value="<?php echo $row['days']?>" required  placeholder="Enter Package Duration" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Package Cost</label>
                  <input class="form-control" name="cost" value="<?php echo $row['cost']?>"   placeholder="Enter Package Cost" type="text">
                </div>
				<div class="form-group">
                  <label for="name">Package Image</label>
                  <input class="form-control" name="image1" type="file" value="<?php echo $row['image']?>" required accept="image/x-png,image/gif,image/jpeg" type="text">
                </div>
				
				
			<div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Add Packages</button>
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
	$package_name=$_POST['package_name'];
	$description=$_POST['description'];
	$duration=$_POST['duration'];
	$cost=$_POST['cost'];
	$image1=$_FILES['image1']['name'];
	$path1='packages/'.$image1;
	move_uploaded_file($_FILES['image1']['tmp_name'],$path1);
	
	
	$date=date('d-m-Y');
	
	
	if($con->error)
	echo $con->error;
	else
	{
	$sql="update packages set name='$package_name',description='$description',days='$duration',cost='$cost',image='$path1',date='$date'  where id='$id'";
	
	$con->query($sql);
	echo "<script>alert('successfully added')</script>";	
	echo "<script>window.location.href='allpackages.php'</script>";	
	}
}

?>
