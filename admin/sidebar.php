<?php
if (!isset($con)) {
    include __DIR__ . '/config.php';
}

$companyName = 'Admin';
$companyLogo = '';
if (isset($con) && !$con->connect_error) {
    $companyRes = $con->query('SELECT name, logo FROM company LIMIT 1');
    if ($companyRes && ($companyRow = $companyRes->fetch_assoc())) {
        $companyName = $companyRow['name'] ?: $companyName;
        $companyLogo = $companyRow['logo'] ?? '';
    }
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
          <?php if ($companyLogo !== ''): ?>
            <img src="<?= htmlspecialchars($companyLogo) ?>" class="img-circle" alt="Logo">
          <?php else: ?>
            <img src="img/user2-160x160.jpg" class="img-circle" alt="Logo" onerror="this.style.display='none'">
          <?php endif; ?>
        </div>
        <div class="pull-left info">
          <p><?= htmlspecialchars($companyName) ?></p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>

      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>

        <li class="treeview <?= in_array($currentPage, ['allregister.php', 'addstudent.php', 'editregister.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-user-plus"></i> <span>Registration</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allregister.php' ? 'active' : '' ?>"><a href="allregister.php"><i class="fa fa-circle-o"></i> All Registration</a></li>
            <li class="<?= $currentPage === 'addstudent.php' ? 'active' : '' ?>"><a href="addstudent.php"><i class="fa fa-circle-o"></i> Add Students</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allclass.php', 'addclass.php', 'editclass.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-graduation-cap"></i> <span>Classes</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allclass.php' ? 'active' : '' ?>"><a href="allclass.php"><i class="fa fa-circle-o"></i> All Classes</a></li>
            <li class="<?= $currentPage === 'addclass.php' ? 'active' : '' ?>"><a href="addclass.php"><i class="fa fa-circle-o"></i> Add Classes</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allpoll.php', 'addpoll.php', 'view_poll.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-bar-chart"></i> <span>Polls</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allpoll.php' ? 'active' : '' ?>"><a href="allpoll.php"><i class="fa fa-circle-o"></i> All Polls</a></li>
            <li class="<?= $currentPage === 'addpoll.php' ? 'active' : '' ?>"><a href="addpoll.php"><i class="fa fa-circle-o"></i> Add Poll</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allnotice.php', 'addnotice.php', 'edit_notice.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-bell"></i> <span>Notice Management</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allnotice.php' ? 'active' : '' ?>"><a href="allnotice.php"><i class="fa fa-circle-o"></i> All Notice</a></li>
            <li class="<?= $currentPage === 'addnotice.php' ? 'active' : '' ?>"><a href="addnotice.php"><i class="fa fa-circle-o"></i> Add Notice</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allevent.php', 'addevent.php', 'edit_material.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-book"></i> <span>Study Materials</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allevent.php' ? 'active' : '' ?>"><a href="allevent.php"><i class="fa fa-circle-o"></i> All Materials</a></li>
            <li class="<?= $currentPage === 'addevent.php' ? 'active' : '' ?>"><a href="addevent.php"><i class="fa fa-circle-o"></i> Add Material</a></li>
            <li><a href="allevent.php?type=video"><i class="fa fa-circle-o"></i> Video Materials</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allgallery.php', 'addgallery.php', 'editgallery.php', 'editgallary.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-image"></i> <span>Promotional Image</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allgallery.php' ? 'active' : '' ?>"><a href="allgallery.php"><i class="fa fa-circle-o"></i> All Images</a></li>
            <li class="<?= $currentPage === 'addgallery.php' ? 'active' : '' ?>"><a href="addgallery.php"><i class="fa fa-circle-o"></i> Add Image</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allabout.php', 'addabout.php', 'editabout.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-info-circle"></i> <span>About</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allabout.php' ? 'active' : '' ?>"><a href="allabout.php"><i class="fa fa-circle-o"></i> All About</a></li>
            <li class="<?= $currentPage === 'addabout.php' ? 'active' : '' ?>"><a href="addabout.php"><i class="fa fa-circle-o"></i> Add About</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['viewtransactions.php', 'allfees.php', 'view_all_fees.php', 'addfees.php', 'edit_transaction.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-money"></i> <span>Fees Management</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'viewtransactions.php' ? 'active' : '' ?>"><a href="viewtransactions.php"><i class="fa fa-circle-o"></i> View Transactions</a></li>
            <li class="<?= $currentPage === 'allfees.php' ? 'active' : '' ?>"><a href="allfees.php"><i class="fa fa-circle-o"></i> All Fees</a></li>
            <li class="<?= $currentPage === 'view_all_fees.php' ? 'active' : '' ?>"><a href="view_all_fees.php"><i class="fa fa-circle-o"></i> View All Fees</a></li>
            <li class="<?= $currentPage === 'addfees.php' ? 'active' : '' ?>"><a href="addfees.php"><i class="fa fa-circle-o"></i> Add Fees</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allprogress.php', 'addprogress.php', 'edit_allprogress.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-calendar-check-o"></i> <span>Attendance Report</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allprogress.php' ? 'active' : '' ?>"><a href="allprogress.php"><i class="fa fa-circle-o"></i> All Reports</a></li>
            <li class="<?= $currentPage === 'addprogress.php' ? 'active' : '' ?>"><a href="addprogress.php"><i class="fa fa-circle-o"></i> Add Attendance</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allenc.php', 'allenquiry.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-comments"></i> <span>Chat Management</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allenc.php' ? 'active' : '' ?>"><a href="allenc.php"><i class="fa fa-circle-o"></i> View Chats</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['allpack.php', 'addpack.php', 'editpack.php', 'editpackages.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-briefcase"></i> <span>Course Management</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'allpack.php' ? 'active' : '' ?>"><a href="allpack.php"><i class="fa fa-circle-o"></i> All Courses</a></li>
            <li class="<?= $currentPage === 'addpack.php' ? 'active' : '' ?>"><a href="addpack.php"><i class="fa fa-circle-o"></i> Add Course</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['add_homework.php', 'view_homework.php', 'edit_homework.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-pencil-square-o"></i> <span>Homework</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'view_homework.php' ? 'active' : '' ?>"><a href="view_homework.php"><i class="fa fa-circle-o"></i> All Homework</a></li>
            <li class="<?= $currentPage === 'add_homework.php' ? 'active' : '' ?>"><a href="add_homework.php"><i class="fa fa-circle-o"></i> Give Homework</a></li>
          </ul>
        </li>

        <li class="treeview <?= in_array($currentPage, ['exam_reminder.php', 'all_reminder_msg.php', 'fees_reminder_msg.php', 'all_fees_reminder.php'], true) ? 'active' : '' ?>">
          <a href="#">
            <i class="fa fa-envelope"></i> <span>Reminders</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?= $currentPage === 'exam_reminder.php' ? 'active' : '' ?>"><a href="exam_reminder.php"><i class="fa fa-circle-o"></i> Exam Reminder</a></li>
            <li class="<?= $currentPage === 'all_reminder_msg.php' ? 'active' : '' ?>"><a href="all_reminder_msg.php"><i class="fa fa-circle-o"></i> All Exam Reminders</a></li>
            <li class="<?= $currentPage === 'fees_reminder_msg.php' ? 'active' : '' ?>"><a href="fees_reminder_msg.php"><i class="fa fa-circle-o"></i> Fees Reminder</a></li>
            <li class="<?= $currentPage === 'all_fees_reminder.php' ? 'active' : '' ?>"><a href="all_fees_reminder.php"><i class="fa fa-circle-o"></i> All Fees Reminders</a></li>
          </ul>
        </li>

        <li>
          <a onclick="return confirm('Are you sure you want to logout?');" href="logout.php">
            <i class="fa fa-sign-out"></i> <span>Logout</span>
          </a>
        </li>
      </ul>
