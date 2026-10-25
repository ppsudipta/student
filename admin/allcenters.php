<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != true) {
    header('location:index.php');
    exit;
}
include('config.php');
include('centers_helper.php');

centers_ensure_table($con);

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delId = (int) $_GET['delete_id'];
    $stmt = $con->prepare('DELETE FROM centers WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $delId);
        $stmt->execute();
        $stmt->close();
    }
    echo "<script>alert('Center location deleted successfully!'); window.location.href='allcenters.php';</script>";
    exit;
}

// Handle Set Primary
if (isset($_GET['make_primary'])) {
    $primId = (int) $_GET['make_primary'];
    $con->query('UPDATE centers SET is_primary = 0');
    $stmt = $con->prepare('UPDATE centers SET is_primary = 1 WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $primId);
        $stmt->execute();
        $stmt->close();
    }
    echo "<script>window.location.href='allcenters.php';</script>";
    exit;
}

$dbError = '';
$centers = centers_all($con);
if ($con->error && empty($centers)) {
    $dbError = $con->error;
}
$totalCenters = count($centers);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Center Locations | Admin</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <style>
    .badge-primary-center {
      background-color: #28a745;
      color: #fff;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      display: inline-block;
      margin-left: 5px;
    }
    .map-btn {
      color: #fff !important;
      background-color: #4285F4;
      border-color: #4285F4;
      padding: 4px 10px;
      font-size: 12px;
      border-radius: 4px;
      display: inline-block;
    }
    .map-btn:hover { background-color: #3367D6; }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>

  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>
        Center Locations &amp; Google Maps
        <small>Manage multiple coaching centers and branches (<?= (int) $totalCenters ?> total)</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Center Locations</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <?php if ($dbError !== ''): ?>
            <div class="alert alert-danger">
              <i class="fa fa-warning"></i> Database error loading centers: <?= htmlspecialchars($dbError) ?>
            </div>
          <?php endif; ?>

          <div class="box box-primary">
            <div class="box-header with-border" style="display:flex; justify-content:space-between; align-items:center;">
              <h3 class="box-title"><i class="fa fa-map-marker text-primary"></i> All Centers</h3>
              <a href="addcenter.php" class="btn btn-success btn-sm pull-right">
                <i class="fa fa-plus"></i> Add New Center
              </a>
            </div>
            <div class="box-body table-responsive">
              <table id="centersTable" class="table table-bordered table-striped table-hover">
                <thead>
                  <tr class="bg-gray-light">
                    <th style="width: 50px;">#</th>
                    <th>Center Name</th>
                    <th>Address</th>
                    <th>Google Maps Location</th>
                    <th>Contact Phone</th>
                    <th>Sort</th>
                    <th style="width: 180px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($totalCenters > 0): ?>
                  <?php $count = 1; foreach ($centers as $row): ?>
                    <?php
                      $mapLink = trim((string) ($row['map_url'] ?? ''));
                      if ($mapLink === '' && !empty($row['address'])) {
                          $mapLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($row['address']);
                      }
                      $centerId = (int) $row['id'];
                    ?>
                  <tr>
                    <td><?= $count++ ?></td>
                    <td>
                      <strong><?= htmlspecialchars((string) $row['name']) ?></strong>
                      <?php if (!empty($row['is_primary'])): ?>
                        <span class="badge-primary-center"><i class="fa fa-check-circle"></i> Primary</span>
                      <?php endif; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars((string) $row['address'])) ?></td>
                    <td>
                      <?php if ($mapLink !== ''): ?>
                        <a href="<?= htmlspecialchars($mapLink) ?>" target="_blank" class="map-btn" rel="noopener noreferrer">
                          <i class="fa fa-map-marker"></i> Open in Maps
                        </a>
                      <?php else: ?>
                        <span class="text-muted"><i class="fa fa-minus"></i> No Map Link</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['phone'] ?: '-')) ?></td>
                    <td><?= (int) $row['sort_order'] ?></td>
                    <td>
                      <a href="editcenter.php?id=<?= $centerId ?>" class="btn btn-warning btn-xs" title="Edit Center">
                        <i class="fa fa-pencil"></i> Edit
                      </a>
                      <a href="allcenters.php?delete_id=<?= $centerId ?>" class="btn btn-danger btn-xs"
                         onclick="return confirm('Are you sure you want to delete this center location?');" title="Delete Center">
                        <i class="fa fa-trash"></i> Delete
                      </a>
                      <?php if (empty($row['is_primary'])): ?>
                        <a href="allcenters.php?make_primary=<?= $centerId ?>" class="btn btn-default btn-xs" title="Set as Main Center">
                          <i class="fa fa-star text-yellow"></i> Make Main
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
              <?php if ($totalCenters === 0 && $dbError === ''): ?>
                <div class="text-center text-muted" style="padding: 30px;">
                  <i class="fa fa-map-marker fa-2x" style="color:#ccc;"></i><br>
                  No centers added yet. Click <strong>"Add New Center"</strong> above to add your first branch.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="pull-right hidden-xs"><b>Version</b> 1.0</div>
    <strong>Copyright &copy; <?= date('Y') ?> Dey Education.</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
  $(function () {
    if ($('#centersTable tbody tr').length > 0) {
      $('#centersTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false
      });
    }
  });
</script>
</body>
</html>
