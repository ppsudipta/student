<?php include('config.php'); ?>
<?php

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from admin";
	$res=$con->query($sql);
	?>
    
    <?php
	while($row=$res->fetch_array())
	{
		?>
<header class="main-header">
    <!-- Logo -->
    <a href="index.php" class="logo" style="background:#cc6600;">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b>AK</b>I</span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>Admin</b></span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top" style="background:#cc6600;">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>

     <div class="navbar-custom-menu">
  <ul class="nav navbar-nav">
    <li class="user-footer" style="padding: 10px 15px;">
      <div class="pull-left">
        <a href="allcompany.php" class="btn btn-default btn-flat">Edit Company Details</a>
      </div>
      <div class="pull-left">
        <a href="editadmin.php" class="btn btn-default btn-flat">Edit Profile</a>
      </div>
      <div class="pull-right">
        <a href="logout.php" class="btn btn-default btn-flat">Sign out</a>
      </div>
    </li>
  </ul>
</div>

    </nav>
  </header>
	<?php } } ?>