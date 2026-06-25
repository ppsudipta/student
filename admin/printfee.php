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
    <title>AK INFOTECH</title>
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
        AK INFOTECH
        
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i>Home</a></li>
        <li class="active">AK INFOTECH</li>
      </ol>

    </section>
    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <!--<div class="box-header">
                  <h3 class="box-title">Fee Receipt</h3>
            </div>-->
            <!-- /.box-header -->
            <div class="box-body">
          
        <div class="box-body table-responsive">

              <table id="example2" class="table table-bordered table-hover">
                <thead>
                <tr>

				<?php 
				 /* $date=date('d-m-Y');
				  echo "<p class='pull-left'>Date: ".$date."</p><br>"*/
				  ?>
					<center>
					<span style="font-size:30px">Jawaharlal Nehru National Youth Centre</span><br>
					<span style="font-size:12px">Council Of Education and Training<br>An ISO 9001:2008 Certified Organisation www.jnnyc-cet.org<br>(Regd. No. 3589/18/1/1968) Recognised by Govt. of India<br></span> 
							<span style="font-size:25px">Study Centre:</span> <span style="font-size:25px"><b>A.K. INFOTECH</b></span><br>
							<?php
							/*$id=$_GET['id'];
							$sql2="select * from fees_table where id='$id'";
							$res2=$con->query($sql2);
							$row2=$res2->fetch_array();*/
							?>
							35, Neel Apartment 1st Floor, Nagerbazar, Dumdum Kolkata-700074
			
			</center>
			
				</tr>	<br> 
                <tr>
                 
                 <td colspan="2"> <center><span  style="font-size:15px;">Fees Receipt</span><br>Student Copy</center></td>
                  
                  
                  
				</tr>
                </thead>
<?php
	$id=$_GET['id'];
	
	$sql="select * from fees_table where id='$id'";
	$res=$con->query($sql);
	?>
    
    <?php
$row=$res->fetch_array();
	
 ?>   
                <tbody>
                <tr>
					<td class="col-sm-6"><b>Student Name</b></td>
					<td class="col-sm-6"><?php echo $row['student_name']?></td>
				</tr>
				 <!--<tr>
					<th>Class</th>
					<td><?php echo $row['course']?></td>
				
				</tr>-->
				<tr>
					<td class="col-sm-6"><b>Fees Amount</b></td>
					<td class="col-sm-6"><?php echo $row['fees']?></td>
			
				</tr>
				<tr>
					<td class="col-sm-6"><b>Fees Month</b></td>
					<td class="col-sm-6"><?php echo $row['month']?></td>
				
				</tr>
				<!--<tr>
					<th>Fees Date</th>
					<td><?php echo $row['fees_date']?></td>
				 </tr>-->
                
                </tbody>
				
         <?php
	
	

?>     
              </table><br><br>
<br>			  <div class="col-xs-6 text-center"><b>Branch: <?php echo $row['branch']?></b></div>
				<div class="col-xs-6 text-center">Authorised Signatory</div><hr>
				<center><b>8282860340 / 8346012704 / 9051525611 / 9830446382</b></center>
                 </div> 
				 
				 <!--Bs Start-->
<!--				 
<?php
	$id=$_GET['id'];
	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from fees_table where id='$id'";
	$res=$con->query($sql);
	?>
    
    <?php
	while($row=$res->fetch_array())
	{
 ?> 
<div class="container-fluid" style="border:2px dashed black;">
<div class="row" align="center">
    <div class="col-sm-12" style="border-bottom:2px dashed black;  padding-bottom:15px;"><h3>Student Name</h3><h2><?php echo $row['student_name']?></h2></div>
    <div class="col-sm-6" style="border-bottom:2px dashed black;padding-bottom:15px;"><h3>Course</h3><h2><?php echo $row['course']?></h2></div>
    <div class="col-sm-6" style="border-bottom:2px dashed black;padding-bottom:15px;"><h3>Fees</h3><h2><?php echo $row['fees']?></h2></div>
</div>
<div class="row" align="center">
    <div class="col-sm-6" style="padding-bottom:15px;"><h3>Months</h3><h2><?php echo $row['month']?></h2></div>
    <div class="col-sm-6" style="padding-bottom:15px;"><h3>Date</h3><h2><?php echo $row['fees_date']?></h2></div>
</div>
</div>

 <?php
	}
	}

?>
-->				 
	<!--BS End-->			 
		 <!-- /.box-body -->
<script>
function myFunction() {
  window.print();
}
</script>		 
	<br>	 
<center><button onclick="myFunction()">Print</button></center>
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
