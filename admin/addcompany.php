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
  <title>Admin Panel</title>
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
Company Information Details        
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        
        <li class="active">Company Details</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Company Details</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
       <form  role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
              <div class="box-body">
			   <!--<div class="form-group">
                  <label for="productid">Code No</label>
                  <input class="form-control" name="code_no"  required  placeholder="Code No of the candidate" type="hidden">
                </div>-->
				
				
           
				
				<!--<div class="form-group">
                  <label for="productid">Select Branch</label>
                  <select class="form-control" name="branch">
				  <option value="">Select</option>
				  
				  <option value="Baguiati/Joramandir">Baguiati/Joramandir</option>
				  <option value="Newtown">Newtown</option>
				  </select>
                </div>-->
				<div class="form-group">
                  <label for="productid">Name of the Company</label>
                  <input class="form-control" name="name"  required  placeholder="Name of the Company" type="text">
                </div>
				<div class="form-group">
                  <label for="name">Logo</label>
                  <input class="form-control" name="image1" type="file" required accept="image/x-png,image/gif,image/jpeg" type="text">
				  
                </div>
				<div class="form-group">
                  <label for="productid">Address</label>
                  <input class="form-control" name="address"  required  placeholder="Enter Address" type="text">
                </div>
				
				<div class="form-group">
                  <label for="productid">Phone No 1</label>
                  <input class="form-control" name="ph1"  required  placeholder="Enter Primary Phone NO." type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Alter Phone No.</label>
                  <input class="form-control" name="ph2"  required  placeholder="Enter Alternative Phone NO." type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Whatsapp No.</label>
                  <input class="form-control" name="wp"  required  placeholder="Enter Whatsapp Phone NO." type="text">
                </div>
				
				
				<div class="form-group">
                  <label for="productid">Email Id</label>
                  <input class="form-control" name="email"  required  placeholder="Enter Email Id" type="email">
                </div>
				<div class="form-group">
                  <label for="productid">Facebook Link</label>
                  <input class="form-control" name="fb"    placeholder="Enter Facebook Link" type="url">
                </div>
				<div class="form-group">
                  <label for="productid">Youtube Link</label>
                  <input class="form-control" name="you"    placeholder="Enter Youtube Link" type="url">
                </div>
				<div class="form-group">
                  <label for="productid">Instagram Link</label>
                  <input class="form-control" name="insta"    placeholder="Enter Instagram Link" type="url">
                </div>
				
			<div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Add Details</button>
              </div>
            </form> 
			</div>
            <!-- /.box-body -->
          </div>
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
        
        randomNum = 'SL-';
        
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
</body>
</html>
 

<?php
if(isset($_POST['submit']))
{

 $name=$_POST['name'];
 
 $address=$_POST['address'];
 $ph1=$_POST['ph1'];
 $ph2=$_POST['ph2'];
 $wp=$_POST['wp'];
 $email=$_POST['email'];
 $fb=$_POST['fb'];
 $you=$_POST['you'];
 $insta=$_POST['insta'];
 $image1=$_FILES['image1']['name'];
 $path1='event/'.rand(1,1000).$image1;
 move_uploaded_file($_FILES['image1']['tmp_name'],$path1);


 $date=date('d-m-Y');	
	
if($con->error)
echo $con->error;
else
{
$sql="INSERT INTO `company`(`id`, `name`, `logo`, `address`, `ph1`, `ph2`, `wp`, `email`,`fb`,`you`,`insta`,`date`) VALUES (null,'$name','$path1','$address','$ph1','$ph2','$wp','$email','$fb','$you','$insta','$date')";

$con->query($sql);

$r=$con->affected_rows;
if($r==1){
echo "<script>alert('successfully added')</script>";  
echo "<script>window.location.href='addcompany.php'</script>";
}
else{
echo"Something Error";
}
}
}
?>
