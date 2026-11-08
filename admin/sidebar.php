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

/**
 * Render one treeview section with identical open/active behavior everywhere.
 *
 * @param string $title
 * @param string $icon
 * @param list<array{0:string,1:string,2?:list<string>}> $items [label, href, optional extra active pages]
 * @param list<string> $sectionPages pages that open this section
 */
$renderTree = static function (string $title, string $icon, array $items, array $sectionPages) use ($currentPage): void {
    $isOpen = in_array($currentPage, $sectionPages, true);
    $liClass = 'treeview' . ($isOpen ? ' active menu-open' : '');
    $menuStyle = $isOpen ? ' style="display:block;"' : '';
    echo '<li class="' . $liClass . '">';
    echo '<a href="#"><i class="' . htmlspecialchars($icon) . '"></i> <span>' . htmlspecialchars($title) . '</span>';
    echo '<span class="pull-right-container"><i class="sidebar-caret pull-right"></i></span></a>';
    echo '<ul class="treeview-menu"' . $menuStyle . '>';
    foreach ($items as $item) {
        $label = $item[0];
        $href = $item[1];
        $activePages = $item[2] ?? [basename(parse_url($href, PHP_URL_PATH) ?: $href)];
        $active = in_array($currentPage, $activePages, true) ? ' active' : '';
        echo '<li class="' . trim($active) . '"><a href="' . htmlspecialchars($href) . '"><i class="fa fa-circle-o"></i> ' . htmlspecialchars($label) . '</a></li>';
    }
    echo '</ul></li>';
};
?>
<!-- Same Font Awesome for every admin page left menu -->
<link rel="stylesheet" href="css/font-awesome.min.css">
<style>
  /* Single consistent left panel look on every admin page */
  .main-sidebar,
  .left-side,
  .main-sidebar .sidebar {
    background-color: #060f71 !important;
  }
  .sidebar-menu > li.header {
    color: #c2c7d8 !important;
    background: transparent !important;
  }
  .sidebar-menu > li > a {
    color: #ffffff !important;
  }
  .sidebar-menu > li:hover > a,
  .sidebar-menu > li.active > a,
  .sidebar-menu > li.menu-open > a {
    color: #ffffff !important;
    background: rgba(0, 0, 0, 0.18) !important;
  }
  .sidebar-menu .treeview-menu {
    background: #050c5c !important;
    display: none;
  }
  .sidebar-menu .treeview.menu-open > .treeview-menu {
    display: block !important;
  }
  .sidebar-menu .treeview-menu > li > a {
    color: #dbe2ff !important;
  }
  .sidebar-menu .treeview-menu > li.active > a,
  .sidebar-menu .treeview-menu > li > a:hover {
    color: #ffffff !important;
    background: rgba(0, 0, 0, 0.15) !important;
    border-left: 3px solid #fff;
  }
  .user-panel > .info,
  .user-panel > .info > p,
  .user-panel > .info > a {
    color: #ffffff !important;
  }
  .main-sidebar .sidebar a {
    color: #ffffff;
  }
  .main-sidebar .user-panel {
    min-height: 60px;
  }
  .main-sidebar .user-panel > .image > img {
    width: 45px;
    height: 45px;
    object-fit: cover;
  }

  /* Open/close caret — CSS only, same on every page (no FA version differences) */
  .sidebar-menu .treeview > a > .pull-right-container {
    position: absolute;
    right: 10px;
    top: 50%;
    margin-top: -7px;
  }
  .sidebar-menu .treeview > a > .pull-right-container > .sidebar-caret,
  .sidebar-menu .treeview > a > .pull-right-container > .fa-angle-left {
    display: inline-block !important;
    width: 0 !important;
    height: 0 !important;
    border-style: solid !important;
    border-width: 5px 0 5px 6px !important;
    border-color: transparent transparent transparent #ffffff !important;
    background: none !important;
    font-size: 0 !important;
    line-height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    transition: transform 0.2s ease;
    transform-origin: 30% 50%;
    -webkit-font-smoothing: antialiased;
  }
  .sidebar-menu .treeview.menu-open > a > .pull-right-container > .sidebar-caret,
  .sidebar-menu .treeview.menu-open > a > .pull-right-container > .fa-angle-left,
  .sidebar-menu .treeview.active.menu-open > a > .pull-right-container > .sidebar-caret {
    transform: rotate(90deg);
  }
  /* Hide any FA glyph text so only the CSS triangle shows */
  .sidebar-menu .treeview > a > .pull-right-container > .sidebar-caret:before,
  .sidebar-menu .treeview > a > .pull-right-container > .fa-angle-left:before {
    content: none !important;
  }
</style>
<aside class="main-sidebar">
  <section class="sidebar">
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

      <?php
      $renderTree('Registration', 'fa fa-user-plus', [
          ['All Registration', 'allregister.php', ['allregister.php', 'editregister.php']],
          ['Add Students', 'addstudent.php'],
      ], ['allregister.php', 'addstudent.php', 'editregister.php']);

      $renderTree('Classes', 'fa fa-graduation-cap', [
          ['All Classes', 'allclass.php', ['allclass.php', 'editclass.php']],
          ['Add Classes', 'addclass.php'],
      ], ['allclass.php', 'addclass.php', 'editclass.php']);

      $renderTree('Polls', 'fa fa-bar-chart', [
          ['All Polls', 'allpoll.php', ['allpoll.php', 'view_poll.php']],
          ['Add Poll', 'addpoll.php'],
      ], ['allpoll.php', 'addpoll.php', 'view_poll.php']);

      $renderTree('Notice Management', 'fa fa-bell', [
          ['All Notice', 'allnotice.php', ['allnotice.php', 'edit_notice.php']],
          ['Add Notice', 'addnotice.php'],
      ], ['allnotice.php', 'addnotice.php', 'edit_notice.php']);

      $renderTree('Study Materials', 'fa fa-book', [
          ['All Materials', 'allevent.php', ['allevent.php', 'edit_material.php']],
          ['Add Material', 'addevent.php'],
          ['Video Materials', 'allevent.php?type=video'],
      ], ['allevent.php', 'addevent.php', 'edit_material.php']);

      $renderTree('Promotional Image', 'fa fa-image', [
          ['All Images', 'allgallery.php', ['allgallery.php', 'editgallery.php', 'editgallary.php']],
          ['Add Image', 'addgallery.php'],
      ], ['allgallery.php', 'addgallery.php', 'editgallery.php', 'editgallary.php']);

      $renderTree('About', 'fa fa-info-circle', [
          ['All About', 'allabout.php', ['allabout.php', 'editabout.php']],
          ['Add About', 'addabout.php'],
      ], ['allabout.php', 'addabout.php', 'editabout.php']);

      $renderTree('Center Locations', 'fa fa-map-marker', [
          ['All Centers', 'allcenters.php', ['allcenters.php', 'editcenter.php']],
          ['Add Center', 'addcenter.php'],
      ], ['allcenters.php', 'addcenter.php', 'editcenter.php']);

      $renderTree('Fees Management', 'fa fa-money', [
          ['View Transactions', 'viewtransactions.php', ['viewtransactions.php', 'edit_transaction.php']],
          // ['All Fees', 'allfees.php'],
          // ['View All Fees', 'view_all_fees.php'],
          // ['Add Fees', 'addfees.php'],
      ], ['viewtransactions.php', 'allfees.php', 'view_all_fees.php', 'addfees.php', 'edit_transaction.php']);

      $renderTree('Attendance Report', 'fa fa-calendar-check-o', [
          ['All Reports', 'allprogress.php', ['allprogress.php', 'edit_allprogress.php']],
          ['Add Attendance', 'addprogress.php'],
      ], ['allprogress.php', 'addprogress.php', 'edit_allprogress.php']);

      $renderTree('Chat Management', 'fa fa-comments', [
          ['View Chats', 'allenc.php', ['allenc.php', 'allenquiry.php']],
      ], ['allenc.php', 'allenquiry.php']);

      $renderTree('Course Management', 'fa fa-briefcase', [
          ['All Courses', 'allpack.php', ['allpack.php', 'editpack.php', 'editpackages.php']],
          ['Add Course', 'addpack.php'],
      ], ['allpack.php', 'addpack.php', 'editpack.php', 'editpackages.php']);
      ?>

      <li>
        <a onclick="return confirm('Are you sure you want to logout?');" href="logout.php">
          <i class="fa fa-sign-out"></i> <span>Logout</span>
        </a>
      </li>
    </ul>
  </section>
</aside>
