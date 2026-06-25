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
        Add Fee
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="profile.php"><i class="fa fa-dashboard"></i> Home</a></li>
        
        <li class="active">Add Fee</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Add Fee</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
			
			
			
	<form  role="form" enctype="multipart/form-data" method="post" accept-charset="utf-8">
    

   
   
   
   
   <div class="form-group">
    <label>Select Course</label>
	<select class="form-control" name="course">
<?php

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from course";
	$res=$con->query($sql);
	while($row=$res->fetch_array())
	{
 ?>
<option value="<?php echo $row['name'] ?>"><?php echo $row['name']?></option>
<?php
	}}
	?>
</select>
   </div>
   <div class="form-group">
	  <label for="productid">Branch </label>
	  <select class="form-control" required name="branch">
			<option value="" disabled >Select Branch</option>
			<option value="Nager Bazar Dumdum">Nager Bazar Dumdum</option>
			<option value="Baguiati Narayantal East">Baguiati Narayantal East</option>
			<option value="Baguiati Jyangra Kalitala">Baguiati Jyangra Kalitala</option>
			<option value="Baguiati Jyangra">Baguiati Jyangra</option>
			<option value="Rajarhat">Rajarhat</option>
	  </select>
	</div>
   <!--<div class="form-group">
	  <label for="productid">Student Code</label>
	  <input class="form-control" name="code"  required  placeholder="Enter Student Code" type="text">
	</div>-->
        
    <div class="box-footer">
 <button type="submit" name="submit" class="btn btn-primary">Show Student</button>
   </div>
 </form> 
	
</div>
</div>	
 <div class="box">
	 <div class="box-body">
	
		   
<table id="example2" class="table table-responsive table-bordered table-hover" style="border: 2px solid #ccc;">
           <center><h3 style="color:blue;"><b>Fees Table</b></h3></center><br>
<?php
$course='';
$branch='';
if(isset($_POST['submit']))
{
  $branch=$_POST['branch'];
  $course=$_POST['course'];
} 
elseif(isset($_SESSION['s_course']))
{
	$course=$_SESSION['s_course'];
	$branch=$_SESSION['s_branch'];
}

  
	  
?>
					<thead style="color:blue;">
                   <tr>
                    <th>Image</th>
                    <th>course</th>
                    <th>Branch</th>
					<th>Student name</th>
					<th>Father Name</th>
					<th>Student Phone</th>
					<th>Add Fees</th>
					<th>View Fees</th>
					
               
                </tr>
                </thead>
			<tbody>
                
				<?php
				if($con->error)
				  echo $con->error;
				  else
				  {
				  
				  $sql="select * from admission where course='$course' and branch='$branch'";
				  
				  $res = $con->query($sql);
				  while($rows = $res->fetch_assoc()){
					  $s_code=$rows['code_no'];
					  $f_course=$rows['course'];
					  $f_branch=$rows['branch'];
					  $_SESSION['s_course']=$f_course;
					  $_SESSION['s_branch']=$f_branch;
				?>
				<tr>
		<td><img style="height:70px; width:100px" src="<?php echo $rows['image']; ?>"></td>
          
          <td><?php echo $rows['course'] ?></td>
		  <td><?php echo $rows['branch'] ?></td>
          <td><?php echo $rows['student_name']?></td>
		  <td><?php echo $rows['father_name'] ?></td>
			<td><?php echo $rows['phone'] ?></td>
			
		  <td><a href="fee.php?id=<?php echo $rows['code_no']?> && course=<?php echo $rows['course']?>"><button class="btn btn-warning">
			<i class="fa fa-money"></i></button></a></td>
			<td><a href="allfees.php?id=<?php echo $rows['code_no']?> && course=<?php echo $rows['course']?>"><button class="btn btn-warning">
			<i class="fa fa-eye"></i></button></a></td>
          </tr>
		  <?php 
				  
				 }
  }
  ?>
          </tbody>
  
  
  <?php

  ?>
 
</table>
	   
          </div>
          
        </div>
        
      </div>
    
      
    </section>
    
  
  </div>
 
  
  <footer class="main-footer">
    
    <strong>Copyright &copy; 2019 <a href="http://thememart.in">Thememart Solutions</a>.</strong> All rights
    reserved.
  </footer>

  
  <div class="control-sidebar-bg"></div>
</div>


<script src="js/jquery.min.js"></script>

<script src="js/jquery-ui.min.js"></script>

<script>
  $.widget.bridge('uibutton', $.ui.button);
</script>

<script src="js/bootstrap.min.js"></script>

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
</body>
</html>
