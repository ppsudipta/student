<?php include('config.php'); ?>
<?php

	if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from company";
	$res=$con->query($sql);
	?>
    
    <?php
	while($row=$res->fetch_array())
	{
		?>
<aside class="main-sidebar" style="background:#060f71">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar" style="background:#060f71">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
          <img src="<?php echo $row['logo']; ?>" class="img-circle" alt="User Image">
          <!--<img src="" alt="User Image" class="user-img">-->

        </div>
        <div class="pull-left info">
          <p><?php echo $row['name']; ?></p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">
        
       
		<!--<li class="treeview">-->
  <!--        <a href="#">-->
  <!--          <i class="fa fa-image"></i>-->
  <!--          <span>Company Details</span>-->
  <!--          <span class="pull-right-container">-->
  <!--            <i class="fa fa-angle-left pull-right"></i>-->
  <!--          </span>-->
  <!--        </a>-->
  <!--        <ul class="treeview-menu">-->
            <!--<li><a href="addcompany.php"><i class="fa fa-circle-o"></i>Add Company Details</a></li>-->
  <!--          <li><a href="allcompany.php"><i class="fa fa-circle-o"></i>Edit details</a></li>-->
  <!--        </ul>-->
  <!--      </li>-->
        	<li class="treeview">
          <a href="#">
            <i class="fa fa-user-plus"></i>
            <span> Registration</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="allregister.php"><i class="fa fa-circle-o"></i>All Registration</a></li>
            <li><a href="addstudent.php"><i class="fa fa-circle-o"></i>Add Students</a></li>
            
          </ul>
        </li>
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Classes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
         <ul class="treeview-menu">
            <li><a href="allclass.php"><i class="fa fa-circle-o"></i>All Classes</a></li>
            <li><a href="addclass.php"><i class="fa fa-circle-o"></i>Add Classes</a></li>
            
          </ul>
        </li>
	<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Notice Management</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addnotice.php"><i class="fa fa-circle-o"></i>Add Notice</a></li>
            <li><a href="allnotice.php"><i class="fa fa-circle-o"></i>All Notice</a></li>
			
          </ul>
        </li>
			<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Study Meterials</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addevent.php"><i class="fa fa-circle-o"></i>Add metarials</a></li>
            <li><a href="allevent.php"><i class="fa fa-circle-o"></i>All metarials</a></li>
			
          </ul>
        </li>
		<!--<li class="treeview">-->
  <!--        <a href="#">-->
  <!--          <i class="fa fa-image"></i>-->
  <!--          <span>Testimonial</span>-->
  <!--          <span class="pull-right-container">-->
  <!--            <i class="fa fa-angle-left pull-right"></i>-->
  <!--          </span>-->
  <!--        </a>-->
  <!--        <ul class="treeview-menu">-->
  <!--          <li><a href="addtestimonial.php"><i class="fa fa-circle-o"></i>Add Testimonial</a></li>-->
  <!--          <li><a href="alltestimonial.php"><i class="fa fa-circle-o"></i>All Testimonial</a></li>-->
			
  <!--        </ul>-->
  <!--      </li>-->
		<!--<li class="treeview">-->
  <!--        <a href="#">-->
  <!--          <i class="fa fa-image"></i>-->
  <!--          <span>HomeWork Management</span>-->
  <!--          <span class="pull-right-container">-->
  <!--            <i class="fa fa-angle-left pull-right"></i>-->
  <!--          </span>-->
  <!--        </a>-->
  <!--        <ul class="treeview-menu">-->
  <!--          <li><a href="add_homework.php"><i class="fa fa-circle-o"></i>Give HomeWork</a></li>-->
  <!--          <li><a href="addimage.php"><i class="fa fa-circle-o"></i>All HomeWork</a></li>-->

  <!--        </ul>-->
  <!--      </li>-->
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Promotional image</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addgallery.php"><i class="fa fa-circle-o"></i>Add image</a></li>
            <li><a href="allgallery.php"><i class="fa fa-circle-o"></i>All image</a></li>

          </ul>
        </li>
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>About</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addabout.php"><i class="fa fa-circle-o"></i>Add About</a></li>
            <li><a href="allabout.php"><i class="fa fa-circle-o"></i>All About</a></li>

          </ul>
        </li>
        <li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Fees Management</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="viewtransactions.php"><i class="fa fa-circle-o"></i>View Transactions</a></li>
            <!--<li><a href="allwhy.php"><i class="fa fa-circle-o"></i>All Services</a></li>-->

          </ul>
        </li>
        <!--<li class="treeview">-->
        <!--  <a href="#">-->
        <!--    <i class="fa fa-image"></i>-->
        <!--    <span>Teacher Management</span>-->
        <!--    <span class="pull-right-container">-->
        <!--      <i class="fa fa-angle-left pull-right"></i>-->
        <!--    </span>-->
        <!--  </a>-->
        <!--  <ul class="treeview-menu">-->
        <!--    <li><a href="addteach.php"><i class="fa fa-circle-o"></i>Add Teacher</a></li>-->
        <!--    <li><a href="allteach.php"><i class="fa fa-circle-o"></i>All Teacher</a></li>-->

        <!--  </ul>-->
        <!--</li>-->
        <li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Attendance Report</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addprogress.php"><i class="fa fa-circle-o"></i>Add Report</a></li>
            <li><a href="allprogress.php"><i class="fa fa-circle-o"></i>All Report</a></li>

          </ul>
        </li>
        <li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Chat Management</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <li><a href="allenc.php"><i class="fa fa-circle-o"></i>View Chats</a></li>

          </ul>
        </li>
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Course Management</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="allpack.php"><i class="fa fa-circle-o"></i>All courses</a></li>
			<li><a href="addpack.php"><i class="fa fa-circle-o"></i> Add course</a></li>

          </ul>
        </li>
		<!--<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Exam Reminder</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="exam_reminder.php"><i class="fa fa-circle-o"></i>Send Reminder Message</a></li>
			
         <li><a href="all_reminder_msg.php"><i class="fa fa-circle-o"></i>All Reminder Message</a></li>

          </ul>
        </li>
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Fees Reminder</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="fees_reminder_msg.php"><i class="fa fa-circle-o"></i>Send Reminder Message</a></li>
			<!--<li><a href="fees_remind_by_month.php"><i class="fa fa-circle-o"></i>Send Reminder By Month</a></li>-->
        <!-- <li><a href="all_fees_reminder.php"><i class="fa fa-circle-o"></i>All Reminder Message</a></li>

          </ul>
        </li>
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Services</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addservice.php"><i class="fa fa-circle-o"></i>Add Service</a></li>
            <li><a href="allservice.php"><i class="fa fa-circle-o"></i>All Service</a></li>

          </ul>
        </li>
		<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Admission</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="addadmission.php"><i class="fa fa-circle-o"></i>Add Student</a></li>
            <li><a href="alladmission.php"><i class="fa fa-circle-o"></i>All Student</a></li>
			
          </ul>
        </li>
		
		<!--<li class="treeview">
          <a href="#">
            <i class="fa fa-image"></i>
            <span>Enquiry</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            
            <li><a href="allenquiry.php"><i class="fa fa-circle-o"></i>All Enquiry</a></li>
          </ul>
        </li>-->
		
		
		
		
		
        <li>
          <a onclick="return confirm('Are you sure to logout?');" href="logout.php">
            <i class="fa fa-sign-out"></i> <span>Logout</span>
            
          </a>
          
        </li>
        
      </ul>
    </section>
    <!-- /.sidebar -->
  </aside>
	<?php } } ?>
	