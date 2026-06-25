
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
        Edit Admission
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="alltour.php"><i class="fa fa-dashboard"></i> Home</a></li>
        
        <li class="active">Edit Admisssion</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Edit Admission</h3>
            </div>
			<?php

    $id=$_GET['id'];

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from admission where id=$id";
	$res=$con->query($sql);
	$row=$res->fetch_array();
	
	
	$demo=$row['image'];
	
	?>

            <!-- /.box-header -->
            <div class="box-body">
				<form  role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
              <div class="box-body">
			   <!--<div class="form-group">
                  <label for="productid">Code No</label>
                  <input class="form-control" name="code_no"  required  placeholder="Code No of the candidate" type="hidden">
                </div>-->
				<div class="form-group">
                  <label for="name">Candidate Image</label>
                  <input class="form-control" name="image1" type="file"  accept="image/x-png,image/gif,image/jpeg" type="text">
				  
                </div>
				
           
				
				
				<div class="form-group">
                  <label for="productid">Name of the candidate</label>
                  <input class="form-control" name="sname"  required  value="<?php echo $row['student_name'];?>" placeholder="Name of the candidate" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Father's / Guardian's Name</label>
                  <input class="form-control" name="fname"  required  value="<?php echo $row['father_name'];?>" placeholder="Enter Father's Name" type="text">
                </div>
				
				<div class="form-group">
                  <label for="productid">Date of Birth</label>
                  <input class="form-control" name="dob"  value="<?php echo $row['dob'];?>" required  placeholder="dd/mm/yyyy" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Sex</label>
                  <select class="form-control" name="sex">
				  <option value="<?php echo $row['sex'];?>"><?php echo $row['sex'];?></option>
				  
				  <option value="Male">Male</option>
				  <option value="Female">Female</option>
				  <option value="Female">Other</option>
				  </select>
                </div>
				
				<div class="form-group">
                  <label for="productid">Qualification</label>
                  <input class="form-control" name="qualification"   value="<?php echo $row['qualification'];?>"  required  placeholder="Enter Qualification" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Caste</label>
                  <input class="form-control" name="caste"  required   value="<?php echo $row['caste'];?>"  placeholder="Enter Caste" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Annual Income</label>
                  <input class="form-control" name="income"  required   value="<?php echo $row['income'];?>"  placeholder="Enter Annual Income" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Residential Address</label>
                  <input class="form-control" name="address"  required   value="<?php echo $row['address'];?>"  placeholder="Enter Full Address" type="text">
                </div>
				
				
				
				
				
				<div class="form-group">
                  <label for="productid">Phone Number</label>
                  <input class="form-control" name="phone_number"   value="<?php echo $row['phone'];?>"  required  placeholder="Enter Phone No" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Class</label>
                  <input class="form-control" name="class"  required   value="<?php echo $row['class'];?>"  placeholder="Enter Class" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Teacher Name</label>
                  <input class="form-control" name="teacher"  required   value="<?php echo $row['teacher'];?>"  placeholder="Enter Teacher Name" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Class Date</label>
                  <input class="form-control" name="date"  required   value="<?php echo $row['date'];?>"  placeholder="dd/mm/yyyy" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Class Time</label>
                  <input class="form-control" name="time"  required   value="<?php echo $row['time'];?>"  placeholder="Enter Time" type="text">
                </div>
					<div class="form-group">
                  <label>Select Course</label>
					<select class="form-control" name="course">
					<option   value="<?php echo $row['course'];?>" >  <?php echo $row['course'];?> </option>
								 
					</select>
                </div>	
				<div class="form-group">
                  <label for="productid">Course Fee</label>
                  <input class="form-control" name="course_fee"  value="<?php echo $row['course_fee'];?>"   required  placeholder="Enter Course Fee" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Course Duration</label>
                  <input class="form-control" name="duration"  value="<?php echo $row['duration'];?>"  required  placeholder="Enter Course Duration" type="text">
                </div>
				<div class="form-group">
                  <label for="productid">Date of Joining</label>
                  <input class="form-control" name="joining_date" value="<?php echo $row['joining_date'];?>" required  placeholder="dd/mm/yyyy" type="text">
                </div>
			<div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Update Student</button>
              </div>
            </form> 
	<?php }?>			

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
 

 $course=$_POST['course'];
 
 $sname=$_POST['sname'];
 $fname=$_POST['fname'];
 $dob=$_POST['dob'];
 $phone_number=$_POST['phone_number'];
 $sex=$_POST['sex'];
 $qualification=$_POST['qualification'];
 $caste=$_POST['caste'];
 $income=$_POST['income'];
 $address=$_POST['address'];
 $class=$_POST['class'];
 $teacher=$_POST['teacher'];
 $date=$_POST['date'];
 $time=$_POST['time'];
 $course=$_POST['course'];
 $course_fee=$_POST['course_fee'];
 $duration=$_POST['duration'];
 $joining_date=$_POST['joining_date'];
 if($image1!='')
 {
 $image1=$_FILES['image1']['name'];
 $path1='images/'.rand(1,1000).$image1;
 move_uploaded_file($_FILES['image1']['tmp_name'],$path1);
 }
 else{
	 $path1=$demo;
 }

 
	
if($con->error)
echo $con->error;
else
{
$sql="UPDATE `admission` SET `student_name`='$sname',`father_name`='$fname',`dob`='$dob',`sex`='$sex',`qualification`='$qualification',`caste`='$caste',`income`='$income',`address`='$address',`phone`='$phone_number',`class`='$class',`teacher`='$teacher',`date`='$date',`time`='$time',`course`='$course',`course_fee`='$course_fee',`duration`='$duration',`joining_date`='$joining_date',`image`='$path1' WHERE id='$id'";

$con->query($sql);
$r=$con->affected_rows;
if($r==1){
echo "<script>alert('successfully added')</script>";  
echo "<script>window.location.href='alladmission.php'</script>";
}
else{
echo"Something Error";
}
}
}
?>
